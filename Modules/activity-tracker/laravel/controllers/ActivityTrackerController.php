<?php

namespace App\Http\Controllers;

use App\Models\AtActivityLog;
use App\Models\AtAppUsageLog;
use App\Models\AtDevice;
use App\Models\AtScreenshot;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Activity Tracker — admin dashboard (web routes).
 *
 * 5 pages:
 *  - index            : overview KPIs + recent activity
 *  - userActivity     : per-user filterable view
 *  - timeline         : screenshot grid timeline
 *  - appUsage         : aggregated app-usage report
 *  - dailyReport      : day-level rollup with CSV export
 *
 * Plus: token() — dashboard "Generate Token" UI used to provision the agent.
 */
class ActivityTrackerController extends Controller
{
    /* ──────────────────────────────────────────────────────────────
     * Overview
     * ──────────────────────────────────────────────────────────── */
    public function index()
    {
        $this->ensureManage();

        $user = Auth::user();
        $cid  = $user->creatorId();
        $today = now()->toDateString();

        $deviceIds = AtDevice::where('created_by', $cid)->pluck('id');

        $totals = [
            'active_users'   => AtActivityLog::whereIn('device_id', $deviceIds)
                ->whereDate('captured_at', $today)->distinct('user_id')->count('user_id'),
            'active_devices' => AtDevice::whereIn('id', $deviceIds)
                ->where('last_seen_at', '>', now()->subMinutes(5))->count(),
            'total_devices'  => AtDevice::whereIn('id', $deviceIds)->count(),
            'shots_today'    => AtScreenshot::whereIn('device_id', $deviceIds)
                ->whereDate('captured_at', $today)->count(),
        ];

        // Approx active vs idle today across all users
        $agg = AtActivityLog::whereIn('device_id', $deviceIds)
            ->whereDate('captured_at', $today)
            ->selectRaw('SUM(CASE WHEN idle_seconds < 30 THEN 30 ELSE 0 END) as active_s, SUM(idle_seconds) as idle_s')
            ->first();
        $totals['avg_active_seconds'] = $totals['active_users'] > 0 ? (int) (($agg->active_s ?? 0) / $totals['active_users']) : 0;
        $totals['avg_idle_seconds']   = $totals['active_users'] > 0 ? (int) (($agg->idle_s ?? 0) / $totals['active_users'])   : 0;

        // Top apps today
        $topApps = AtAppUsageLog::whereIn('device_id', $deviceIds)
            ->whereDate('started_at', $today)
            ->selectRaw('app_name, SUM(duration_seconds) as total')
            ->groupBy('app_name')->orderByDesc('total')->limit(8)->get();

        // Recent screenshots
        $recentShots = AtScreenshot::with('user:id,name')
            ->whereIn('device_id', $deviceIds)
            ->orderByDesc('captured_at')->limit(12)->get();

        // Online devices list
        $devices = AtDevice::with('user:id,name,email')
            ->where('created_by', $cid)
            ->orderByDesc('last_seen_at')->limit(20)->get();

        return view('activity_tracker.index', compact('totals', 'topApps', 'recentShots', 'devices'));
    }

    /* ──────────────────────────────────────────────────────────────
     * User-activity page (filter by user + date range)
     * ──────────────────────────────────────────────────────────── */
    public function userActivity(Request $request)
    {
        $this->ensureManage();
        $cid = Auth::user()->creatorId();

        $userId   = $request->input('user_id');
        $fromDate = $request->input('from', now()->toDateString());
        $toDate   = $request->input('to',   now()->toDateString());

        $users = User::whereIn('id', AtDevice::where('created_by', $cid)->distinct()->pluck('user_id'))
            ->orderBy('name')->get();

        $deviceIds = AtDevice::where('created_by', $cid)
            ->when($userId, fn($q) => $q->where('user_id', $userId))->pluck('id');

        $activity = AtActivityLog::with('device:id,device_name')
            ->whereIn('device_id', $deviceIds)
            ->whereBetween('captured_at', [Carbon::parse($fromDate)->startOfDay(), Carbon::parse($toDate)->endOfDay()])
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->orderByDesc('captured_at')->paginate(50)->withQueryString();

        // Aggregates for the filter window
        $agg = AtActivityLog::whereIn('device_id', $deviceIds)
            ->whereBetween('captured_at', [Carbon::parse($fromDate)->startOfDay(), Carbon::parse($toDate)->endOfDay()])
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->selectRaw('SUM(CASE WHEN idle_seconds < 30 THEN 30 ELSE 0 END) as active_s, SUM(idle_seconds) as idle_s, SUM(keyboard_count) as kb, SUM(mouse_count) as mouse')
            ->first();

        $appUsage = AtAppUsageLog::whereIn('device_id', $deviceIds)
            ->whereBetween('started_at', [Carbon::parse($fromDate)->startOfDay(), Carbon::parse($toDate)->endOfDay()])
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->selectRaw('app_name, SUM(duration_seconds) as total')
            ->groupBy('app_name')->orderByDesc('total')->limit(15)->get();

        return view('activity_tracker.user_activity', compact(
            'users', 'activity', 'agg', 'appUsage', 'userId', 'fromDate', 'toDate'
        ));
    }

    /* ──────────────────────────────────────────────────────────────
     * Screenshot timeline (grid)
     * ──────────────────────────────────────────────────────────── */
    public function timeline(Request $request)
    {
        $this->ensureManage();
        $cid = Auth::user()->creatorId();

        $userId = $request->input('user_id');
        $date   = $request->input('date', now()->toDateString());

        $users = User::whereIn('id', AtDevice::where('created_by', $cid)->distinct()->pluck('user_id'))
            ->orderBy('name')->get();

        $deviceIds = AtDevice::where('created_by', $cid)
            ->when($userId, fn($q) => $q->where('user_id', $userId))->pluck('id');

        $shots = AtScreenshot::with('user:id,name', 'device:id,device_name')
            ->whereIn('device_id', $deviceIds)
            ->whereDate('captured_at', $date)
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->orderByDesc('captured_at')->paginate(48)->withQueryString();

        return view('activity_tracker.timeline', compact('users', 'shots', 'userId', 'date'));
    }

    /* ──────────────────────────────────────────────────────────────
     * App usage report
     * ──────────────────────────────────────────────────────────── */
    public function appUsage(Request $request)
    {
        $this->ensureManage();
        $cid = Auth::user()->creatorId();

        $userId   = $request->input('user_id');
        $fromDate = $request->input('from', now()->subDays(7)->toDateString());
        $toDate   = $request->input('to',   now()->toDateString());

        $users = User::whereIn('id', AtDevice::where('created_by', $cid)->distinct()->pluck('user_id'))
            ->orderBy('name')->get();

        $deviceIds = AtDevice::where('created_by', $cid)
            ->when($userId, fn($q) => $q->where('user_id', $userId))->pluck('id');

        $rows = AtAppUsageLog::whereIn('device_id', $deviceIds)
            ->whereBetween('started_at', [Carbon::parse($fromDate)->startOfDay(), Carbon::parse($toDate)->endOfDay()])
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->selectRaw('app_name, SUM(duration_seconds) as total, COUNT(*) as sessions')
            ->groupBy('app_name')
            ->orderByDesc('total')
            ->paginate(40)
            ->withQueryString();

        return view('activity_tracker.app_usage', compact('users', 'rows', 'userId', 'fromDate', 'toDate'));
    }

    /* ──────────────────────────────────────────────────────────────
     * Daily report (per user/device per day)
     * ──────────────────────────────────────────────────────────── */
    public function dailyReport(Request $request)
    {
        $this->ensureManage();
        $cid = Auth::user()->creatorId();

        $fromDate = $request->input('from', now()->subDays(7)->toDateString());
        $toDate   = $request->input('to',   now()->toDateString());

        $deviceIds = AtDevice::where('created_by', $cid)->pluck('id');

        $rows = $this->computeDailyRows($deviceIds, $fromDate, $toDate);

        return view('activity_tracker.daily_report', compact('rows', 'fromDate', 'toDate'));
    }

    /** CSV export of the same query as dailyReport(). */
    public function dailyReportCsv(Request $request): StreamedResponse
    {
        $this->ensureManage();
        $cid = Auth::user()->creatorId();
        $fromDate = $request->input('from', now()->subDays(7)->toDateString());
        $toDate   = $request->input('to',   now()->toDateString());

        $deviceIds = AtDevice::where('created_by', $cid)->pluck('id');
        $rows      = $this->computeDailyRows($deviceIds, $fromDate, $toDate);

        $filename = 'activity_daily_' . $fromDate . '_to_' . $toDate . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'User', 'Device', 'Active (h:m)', 'Idle (h:m)', 'Screenshots', 'Most Used App']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->work_date,
                    $r->user_name,
                    $r->device_name,
                    $this->fmtHm($r->active_s),
                    $this->fmtHm($r->idle_s),
                    $r->shots,
                    $r->most_used_app ?: '—',
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /* ──────────────────────────────────────────────────────────────
     * Token UI — issue a Sanctum personal-access-token for the agent.
     * ──────────────────────────────────────────────────────────── */
    public function tokenIndex()
    {
        $user = Auth::user();
        return view('activity_tracker.token', [
            'tokens' => $user->tokens()->where('name', 'like', 'activity-tracker%')->latest()->get(),
        ]);
    }

    public function tokenCreate(Request $request)
    {
        $request->validate(['name' => 'required|string|max:80']);
        $user = Auth::user();
        $token = $user->createToken('activity-tracker:' . $request->name);
        return back()->with('plain_token', $token->plainTextToken)
                     ->with('success', __('Token created — copy it now, you will not see it again.'));
    }

    public function tokenRevoke(Request $request, $id)
    {
        Auth::user()->tokens()->where('id', $id)->delete();
        return back()->with('success', __('Token revoked.'));
    }

    /* ──────────────────────────────────────────────────────────────
     * Helpers
     * ──────────────────────────────────────────────────────────── */
    protected function ensureManage(): void
    {
        $u = Auth::user();
        if (!$u || !$u->can('manage-activity-tracker')) {
            abort(403, __('You do not have permission to view activity tracking.'));
        }
    }

    /** Compute per-day-per-user-per-device rollup on the fly. */
    protected function computeDailyRows($deviceIds, $from, $to)
    {
        return DB::table('at_activity_logs as al')
            ->join('at_devices as d', 'd.id', '=', 'al.device_id')
            ->join('users as u',     'u.id', '=', 'al.user_id')
            ->whereIn('al.device_id', $deviceIds)
            ->whereBetween('al.captured_at', [Carbon::parse($from)->startOfDay(), Carbon::parse($to)->endOfDay()])
            ->selectRaw('
                DATE(al.captured_at) as work_date,
                u.name as user_name,
                d.device_name,
                u.id  as user_id,
                d.id  as device_id,
                SUM(CASE WHEN al.idle_seconds < 30 THEN 30 ELSE 0 END) as active_s,
                SUM(al.idle_seconds) as idle_s,
                (SELECT COUNT(*) FROM at_screenshots s
                   WHERE s.device_id = d.id AND DATE(s.captured_at) = DATE(al.captured_at)) as shots,
                (SELECT app_name FROM at_app_usage_logs au
                   WHERE au.device_id = d.id AND DATE(au.started_at) = DATE(al.captured_at)
                   GROUP BY app_name ORDER BY SUM(duration_seconds) DESC LIMIT 1) as most_used_app
            ')
            ->groupBy('work_date', 'u.name', 'd.device_name', 'u.id', 'd.id')
            ->orderByDesc('work_date')
            ->get();
    }

    public function fmtHm($seconds): string
    {
        $seconds = max(0, (int) $seconds);
        $h = intdiv($seconds, 3600);
        $m = intdiv($seconds % 3600, 60);
        return $h . 'h ' . str_pad((string) $m, 2, '0', STR_PAD_LEFT) . 'm';
    }

    public function pollStopRequests()
    {
        if (!\Schema::hasTable('at_stop_requests')) return response()->json(['items' => []]);
        try {
            $cid = \Auth::user()->creatorId();
            $deviceIds = \DB::table('at_devices')->where('created_by', $cid)->pluck('id');
            $items = \DB::table('at_stop_requests')
                ->whereIn('device_id', $deviceIds)
                ->where('status', 'pending')
                ->orderByDesc('created_at')
                ->limit(20)
                ->get();
            return response()->json(['items' => $items]);
        } catch (\Throwable $e) {
            return response()->json(['items' => [], 'error' => $e->getMessage()]);
        }
    }
}
