<?php

namespace App\Http\Controllers;

use App\Models\BackgroundScreenshot;
use App\Models\User;
use App\Models\UserPageVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BackgroundScreenshotController extends Controller
{
    /**
     * POST /bg-screenshot/capture
     * Employee ke browser se page screenshot receive karo
     */
    public function capture(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $dataUrl = $request->input('screenshot');
        if (!$dataUrl || !str_starts_with($dataUrl, 'data:image/')) {
            return response()->json(['error' => 'Invalid screenshot data'], 422);
        }

        $parts   = explode(',', $dataUrl, 2);
        $imgData = base64_decode($parts[1] ?? '');
        if (!$imgData) {
            return response()->json(['error' => 'Decode failed'], 422);
        }

        $relDir = "uploads/bg-screenshots/{$user->id}";
        $absDir = public_path($relDir);
        if (!is_dir($absDir)) {
            mkdir($absDir, 0755, true);
        }

        $relPath = $relDir . '/' . now()->format('Y-m-d_H-i-s') . '_' . uniqid() . '.jpg';
        file_put_contents(public_path($relPath), $imgData);

        BackgroundScreenshot::create([
            'user_id'         => $user->id,
            'screenshot_path' => $relPath,
            'page_url'        => $request->input('page_url'),
            'ip_address'      => $request->ip(),
            'captured_at'     => now(),
        ]);

        // 7 din se purane screenshots delete karo
        $this->pruneOld($user->id);

        return response()->json(['ok' => true, 'time' => now()->format('H:i:s')]);
    }

    /**
     * GET /bg-screenshot
     * Admin — saare employees ka overview
     */
    public function index(Request $request)
    {
        $authUser = Auth::user();

        if ($authUser->type === 'employee' || $authUser->type === 'super admin') {
            return redirect()->route('dashboard');
        }

        $creatorId = $authUser->creatorId();

        $employees = User::where('created_by', $creatorId)
            ->where('type', '!=', 'super admin')
            ->where('type', '!=', 'company')
            ->with(['backgroundScreenshots' => function ($q) {
                $q->orderBy('captured_at', 'desc')->limit(1);
            }])
            ->get();

        $search = $request->input('search');
        if ($search) {
            $employees = $employees->filter(fn($e) => stripos($e->name, $search) !== false);
        }

        $todayCounts = BackgroundScreenshot::whereIn('user_id', $employees->pluck('id'))
            ->whereDate('captured_at', today())
            ->selectRaw('user_id, count(*) as cnt')
            ->groupBy('user_id')
            ->pluck('cnt', 'user_id');

        return view('bg-screenshot.index', compact('employees', 'todayCounts', 'search'));
    }

    /**
     * GET /bg-screenshot/{userId}
     * Admin — ek employee ki screenshots date-wise
     */
    public function show(Request $request, int $userId)
    {
        $authUser = Auth::user();

        if ($authUser->type === 'employee' || $authUser->type === 'super admin') {
            return redirect()->route('dashboard');
        }

        $creatorId = $authUser->creatorId();
        $employee  = User::where('created_by', $creatorId)->findOrFail($userId);

        $date = $request->input('date', today()->toDateString());

        $screenshots = BackgroundScreenshot::where('user_id', $userId)
            ->whereDate('captured_at', $date)
            ->orderBy('captured_at', 'desc')
            ->paginate(30)
            ->withQueryString();

        // Available dates jisme screenshots hain
        $availableDates = BackgroundScreenshot::where('user_id', $userId)
            ->selectRaw('DATE(captured_at) as dt, count(*) as cnt')
            ->groupByRaw('DATE(captured_at)')
            ->orderByDesc('dt')
            ->limit(30)
            ->pluck('cnt', 'dt');

        // ── Page-visit data for the same date ────────────────────────
        $this->closeStaleVisits($userId);

        $visits = UserPageVisit::where('user_id', $userId)
            ->whereDate('started_at', $date)
            ->orderBy('started_at', 'desc')
            ->limit(200)
            ->get();

        // Top pages by total focused time (or duration if focus tracking lagged).
        $topPages = UserPageVisit::where('user_id', $userId)
            ->whereDate('started_at', $date)
            ->selectRaw("
                COALESCE(NULLIF(page_title, ''), url) AS label,
                url,
                COUNT(*) AS visits,
                SUM(GREATEST(focus_seconds, 0)) AS focus_total,
                SUM(GREATEST(duration_seconds, 0)) AS dur_total
            ")
            ->groupBy('label', 'url')
            ->orderByDesc('focus_total')
            ->limit(10)
            ->get();

        // Daily totals
        $totals = [
            'visits'   => $visits->count(),
            'duration' => (int) $visits->sum('duration_seconds'),
            'focus'    => (int) $visits->sum('focus_seconds'),
            'unique'   => $visits->pluck('url')->unique()->count(),
            'tabs'     => $visits->pluck('tab_id')->filter()->unique()->count(),
        ];

        return view('bg-screenshot.show', compact(
            'employee', 'screenshots', 'date', 'availableDates',
            'visits', 'topPages', 'totals'
        ));
    }

    /**
     * DELETE /bg-screenshot/{id}
     */
    public function destroy(int $id)
    {
        $authUser = Auth::user();

        if ($authUser->type === 'employee' || $authUser->type === 'super admin') {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $creatorId = $authUser->creatorId();

        $shot = BackgroundScreenshot::whereHas('user', function ($q) use ($creatorId) {
            $q->where('created_by', $creatorId);
        })->findOrFail($id);

        @unlink(public_path($shot->screenshot_path));
        $shot->delete();

        return response()->json(['ok' => true]);
    }

    /**
     * POST /bg-screenshot/interval
     * Admin — screenshot interval update karo
     */
    public function updateInterval(Request $request)
    {
        $authUser = Auth::user();
        if ($authUser->type === 'employee' || $authUser->type === 'super admin') {
            return back()->with('error', 'Forbidden');
        }

        $minutes = (int) $request->input('interval', 5);
        if ($minutes < 1) $minutes = 1;
        if ($minutes > 60) $minutes = 60;

        $createdBy = $authUser->creatorId();

        DB::table('settings')->updateOrInsert(
            ['name' => 'screenshot_interval', 'created_by' => $createdBy],
            ['value' => $minutes]
        );

        return back()->with('success', "Screenshot interval updated to {$minutes} minutes.");
    }

    /**
     * 7 din se purane screenshots delete karo
     */
    private function pruneOld(int $userId): void
    {
        $old = BackgroundScreenshot::where('user_id', $userId)
            ->where('captured_at', '<', now()->subDays(7))
            ->get();

        foreach ($old as $shot) {
            @unlink(public_path($shot->screenshot_path));
            $shot->delete();
        }
    }

    // ══════════════════════════════════════════════════════════════
    // PAGE-VISIT TRACKING — kaunse user ne kaunsa page kab tak khola
    // ══════════════════════════════════════════════════════════════

    /**
     * POST /bg-screenshot/visit/start
     * Body: { tab_id, url, page_title }
     * Creates a new visit row when the user lands on a page. Returns
     * `visit_id` which the frontend echoes back on heartbeats and end.
     */
    public function visitStart(Request $request)
    {
        $user = Auth::user();
        if (!$user) return response()->json(['error' => 'Unauthenticated'], 401);

        $data = $request->validate([
            'tab_id'     => 'nullable|string|max:64',
            'url'        => 'required|string|max:500',
            'page_title' => 'nullable|string|max:300',
        ]);

        // Auto-close any stale visits from this tab (browser may have crashed
        // before sending the end beacon — finalise them so durations are sane).
        if (!empty($data['tab_id'])) {
            UserPageVisit::where('user_id', $user->id)
                ->where('tab_id', $data['tab_id'])
                ->where('is_active', true)
                ->update(['is_active' => false]);
        }

        $visit = UserPageVisit::create([
            'user_id'          => $user->id,
            'tab_id'           => $data['tab_id'] ?? null,
            'url'              => $data['url'],
            'page_title'       => $data['page_title'] ?? null,
            'started_at'       => now(),
            'last_seen_at'     => now(),
            'duration_seconds' => 0,
            'focus_seconds'    => 0,
            'is_active'        => true,
            'ip_address'       => $request->ip(),
        ]);

        return response()->json(['ok' => true, 'visit_id' => $visit->id]);
    }

    /**
     * POST /bg-screenshot/visit/heartbeat
     * Body: { visit_id, focus_delta }
     * Frontend sends this every ~30s while the page is open. We update
     * last_seen_at and accumulate focus/total seconds atomically.
     */
    public function visitHeartbeat(Request $request)
    {
        $user = Auth::user();
        if (!$user) return response()->json(['error' => 'Unauthenticated'], 401);

        $data = $request->validate([
            'visit_id'    => 'required|integer',
            'focus_delta' => 'nullable|integer|min:0|max:600',
        ]);

        $visit = UserPageVisit::where('id', $data['visit_id'])
            ->where('user_id', $user->id)
            ->first();
        if (!$visit) return response()->json(['error' => 'Not found'], 404);

        $now      = now();
        $duration = max(0, (int) $visit->started_at->diffInSeconds($now));
        $focus    = (int) $visit->focus_seconds + (int) ($data['focus_delta'] ?? 0);

        $visit->update([
            'last_seen_at'     => $now,
            'duration_seconds' => $duration,
            'focus_seconds'    => min($focus, $duration),
        ]);

        return response()->json(['ok' => true]);
    }

    /**
     * POST /bg-screenshot/visit/end
     * Body: { visit_id, focus_delta }
     * Called via `navigator.sendBeacon` on page unload. Marks the visit
     * inactive and finalises duration. Idempotent — safe if called twice.
     */
    public function visitEnd(Request $request)
    {
        $user = Auth::user();
        if (!$user) return response()->json(['error' => 'Unauthenticated'], 401);

        $data = $request->validate([
            'visit_id'    => 'required|integer',
            'focus_delta' => 'nullable|integer|min:0|max:600',
        ]);

        $visit = UserPageVisit::where('id', $data['visit_id'])
            ->where('user_id', $user->id)
            ->first();
        if (!$visit) return response()->json(['ok' => true]); // idempotent

        $now      = now();
        $duration = max(0, (int) $visit->started_at->diffInSeconds($now));
        $focus    = (int) $visit->focus_seconds + (int) ($data['focus_delta'] ?? 0);

        $visit->update([
            'last_seen_at'     => $now,
            'duration_seconds' => $duration,
            'focus_seconds'    => min($focus, $duration),
            'is_active'        => false,
        ]);

        return response()->json(['ok' => true]);
    }

    /**
     * Auto-finalise visits that have not received a heartbeat in 5+ minutes.
     * Called from the show() admin page so stale "is_active" rows don't show
     * inflated durations. Cheap UPDATE — runs in ms.
     */
    private function closeStaleVisits(int $userId): void
    {
        UserPageVisit::where('user_id', $userId)
            ->where('is_active', true)
            ->where('last_seen_at', '<', now()->subMinutes(5))
            ->update(['is_active' => false]);
    }
}
