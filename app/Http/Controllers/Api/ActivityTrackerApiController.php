<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AtActivityLog;
use App\Models\AtAppUsageLog;
use App\Models\AtDailySummary;
use App\Models\AtDevice;
use App\Models\AtScreenshot;
use App\Models\AtStopRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Activity Tracker — REST API consumed by the Node.js Electron agent.
 *
 * Auth: Sanctum bearer token (issued from the dashboard "Generate Token" UI).
 * All endpoints return JSON. Failures are logged with context for the failed
 * sample so the agent can retry the same record without dropping data.
 */
class ActivityTrackerApiController extends Controller
{
    /* ──────────────────────────────────────────────────────────────
     * POST /api/activity-tracker/device/register
     * Idempotent — same device_uuid returns the existing row.
     * ──────────────────────────────────────────────────────────── */
    /* ──────────────────────────────────────────────────────────────
     * POST /api/activity-tracker/login   (UNAUTHENTICATED — public)
     * Agent calls this with employee email+password; receives a Sanctum
     * personal-access-token. Token is then stored locally by the agent and
     * used as Bearer auth for all subsequent calls.
     * ──────────────────────────────────────────────────────────── */
    public function login(Request $request)
    {
        $v = Validator::make($request->all(), [
            'email'       => 'required|email',
            'password'    => 'required|string',
            'device_uuid' => 'nullable|string|max:80',
            'device_name' => 'nullable|string|max:200',
        ]);
        if ($v->fails()) {
            return response()->json(['ok' => false, 'errors' => $v->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'ok'      => false,
                'message' => 'Invalid email or password',
            ], 401);
        }

        // Reuse existing token for this device (avoids piling up tokens on every restart)
        $tokenName = 'activity-tracker-agent:' . ($request->device_uuid ?: 'default');
        $user->tokens()->where('name', $tokenName)->delete();
        $token = $user->createToken($tokenName);

        return response()->json([
            'ok'    => true,
            'token' => $token->plainTextToken,
            'user'  => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    public function registerDevice(Request $request)
    {
        $user = $request->user();
        if (!$user) return response()->json(['ok' => false, 'message' => 'Unauthenticated'], 401);

        $v = Validator::make($request->all(), [
            'device_uuid' => 'required|string|max:80',
            'device_name' => 'required|string|max:200',
            'os'          => 'nullable|string|max:80',
        ]);
        if ($v->fails()) {
            return response()->json(['ok' => false, 'errors' => $v->errors()], 422);
        }

        $device = AtDevice::firstOrNew(['device_uuid' => $request->device_uuid]);
        $device->user_id      = $user->id;
        $device->created_by   = method_exists($user, 'creatorId') ? $user->creatorId() : $user->id;
        $device->device_name  = $request->device_name;
        $device->os           = $request->os;
        $device->ip_address   = $request->ip();
        $device->status       = 'active';
        $device->last_seen_at = now();
        $device->save();

        return response()->json([
            'ok'        => true,
            'device_id' => $device->id,
            'device'    => $device,
        ]);
    }

    /* ──────────────────────────────────────────────────────────────
     * POST /api/activity-tracker/device/heartbeat
     * Updates last_seen_at; sent every minute by the agent.
     * ──────────────────────────────────────────────────────────── */
    public function heartbeat(Request $request)
    {
        $device = $this->resolveDevice($request);
        if (!$device) {
            return response()->json(['ok' => false, 'message' => 'Device not found'], 404);
        }

        // Detect tracker wapas online aaya — pehle 10+ min se offline tha
        $wasOffline = $device->last_seen_at && $device->last_seen_at->lt(now()->subMinutes(5));

        $device->update([
            'last_seen_at' => now(),
            'status'       => 'active',
            'ip_address'   => $request->ip(),
        ]);

        // Admin ko notify karo jab tracker wapas start ho
        if ($wasOffline) {
            try {
                $user  = $request->user();
                $admin = User::find($user->created_by);
                if ($admin && $admin->email) {
                    config([
                        'mail.default'                 => 'smtp',
                        'mail.mailers.smtp.host'       => $_ENV['MAIL_HOST']        ?? 'smtp.gmail.com',
                        'mail.mailers.smtp.port'       => $_ENV['MAIL_PORT']        ?? 587,
                        'mail.mailers.smtp.username'   => $_ENV['MAIL_USERNAME']    ?? '',
                        'mail.mailers.smtp.password'   => $_ENV['MAIL_PASSWORD']    ?? '',
                        'mail.mailers.smtp.encryption' => $_ENV['MAIL_ENCRYPTION']  ?? 'tls',
                        'mail.from.address'            => $_ENV['MAIL_FROM_ADDRESS'] ?? '',
                        'mail.from.name'               => $_ENV['MAIL_FROM_NAME']    ?? 'HRMS Tracker',
                    ]);

                    $subject = '[Tracker Online] ' . $user->name . ' ka tracker wapas start ho gaya';
                    $body    = "Employee: {$user->name}\n"
                             . "Device:   {$device->device_name}\n"
                             . "Time:     " . now()->format('d M Y, h:i A') . "\n\n"
                             . "Yeh employee ka tracker wapas online aa gaya hai.";

                    Mail::raw($body, function ($msg) use ($admin, $subject) {
                        $msg->to($admin->email, $admin->name)->subject($subject);
                    });
                }
            } catch (\Throwable $e) {
                Log::warning('Tracker online notify failed', [
                    'device_id' => $device->id,
                    'error'     => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'ok'           => true,
            'server_time'  => now()->toIso8601String(),
        ]);
    }

    /* ──────────────────────────────────────────────────────────────
     * POST /api/activity-tracker/activity/store
     * Bulk-friendly: accepts a single sample OR an array under "samples".
     * ──────────────────────────────────────────────────────────── */
    public function storeActivity(Request $request)
    {
        $user   = $request->user();
        $device = $this->resolveDevice($request);
        if (!$device) {
            return response()->json(['ok' => false, 'message' => 'Device not found'], 404);
        }

        // Accept either a single payload or {samples: [...]}
        $samples = $request->has('samples') ? $request->input('samples') : [$request->all()];
        if (!is_array($samples) || empty($samples)) {
            return response()->json(['ok' => false, 'message' => 'No samples provided'], 422);
        }

        $accepted = 0;
        $rejected = [];
        $now = now();

        foreach ($samples as $i => $s) {
            $v = Validator::make((array) $s, [
                'active_app'          => 'nullable|string|max:200',
                'active_window_title' => 'nullable|string|max:500',
                'idle_seconds'        => 'nullable|integer|min:0|max:86400',
                'keyboard_count'      => 'nullable|integer|min:0',
                'mouse_count'         => 'nullable|integer|min:0',
                'captured_at'         => 'nullable|date',
            ]);
            if ($v->fails()) {
                $rejected[] = ['index' => $i, 'errors' => $v->errors()];
                continue;
            }

            try {
                AtActivityLog::create([
                    'user_id'              => $user->id,
                    'device_id'            => $device->id,
                    'active_app'           => $s['active_app']          ?? null,
                    'active_window_title'  => $s['active_window_title'] ?? null,
                    'idle_seconds'         => $s['idle_seconds']        ?? 0,
                    'keyboard_count'       => $s['keyboard_count']      ?? 0,
                    'mouse_count'          => $s['mouse_count']         ?? 0,
                    'captured_at'          => isset($s['captured_at']) ? Carbon::parse($s['captured_at']) : $now,
                ]);
                $accepted++;
            } catch (\Throwable $e) {
                Log::warning('AT activity insert failed', ['err' => $e->getMessage(), 'sample' => $s]);
                $rejected[] = ['index' => $i, 'errors' => ['system' => $e->getMessage()]];
            }
        }

        // Touch heartbeat so agents that only ship activity stay marked online
        $device->update(['last_seen_at' => $now]);

        return response()->json([
            'ok'       => true,
            'accepted' => $accepted,
            'rejected' => $rejected,
        ]);
    }

    /* ──────────────────────────────────────────────────────────────
     * POST /api/activity-tracker/screenshot/upload
     * Multipart: image (jpg/png up to 5 MB) + optional metadata.
     * ──────────────────────────────────────────────────────────── */
    public function uploadScreenshot(Request $request)
    {
        $user   = $request->user();
        $device = $this->resolveDevice($request);
        if (!$device) {
            return response()->json(['ok' => false, 'message' => 'Device not found'], 404);
        }

        $v = Validator::make($request->all(), [
            'image'               => 'required|file|mimes:jpg,jpeg,png,webp|max:5120',  // 5 MB
            'active_app'          => 'nullable|string|max:200',
            'active_window_title' => 'nullable|string|max:500',
            'captured_at'         => 'nullable|date',
        ]);
        if ($v->fails()) {
            return response()->json(['ok' => false, 'errors' => $v->errors()], 422);
        }

        try {
            $file = $request->file('image');
            $ext  = $file->getClientOriginalExtension() ?: 'jpg';
            $name = 'at_' . $user->id . '_' . $device->id . '_' . time() . '_' . Str::random(6) . '.' . $ext;

            // Bucket by date so storage stays browseable.
            $relativeDir = 'screenshots/' . now()->format('Y/m/d');
            $path = $file->storeAs($relativeDir, $name, 'public');

            $shot = AtScreenshot::create([
                'user_id'              => $user->id,
                'device_id'            => $device->id,
                'image_path'           => $path,
                'active_app'           => $request->input('active_app'),
                'active_window_title'  => $request->input('active_window_title'),
                'size_bytes'           => $file->getSize(),
                'captured_at'          => $request->input('captured_at') ? Carbon::parse($request->input('captured_at')) : now(),
            ]);

            $device->update(['last_seen_at' => now()]);

            return response()->json([
                'ok'           => true,
                'screenshot_id'=> $shot->id,
                'url'          => Storage::disk('public')->url($path),
            ]);
        } catch (\Throwable $e) {
            Log::error('AT screenshot upload failed', ['err' => $e->getMessage()]);
            return response()->json(['ok' => false, 'message' => 'Upload failed'], 500);
        }
    }

    /* ──────────────────────────────────────────────────────────────
     * POST /api/activity-tracker/app-usage/store
     * Accepts {usages: [...]} array of completed app spans.
     * ──────────────────────────────────────────────────────────── */
    public function storeAppUsage(Request $request)
    {
        $user   = $request->user();
        $device = $this->resolveDevice($request);
        if (!$device) {
            return response()->json(['ok' => false, 'message' => 'Device not found'], 404);
        }

        $rows = $request->has('usages') ? $request->input('usages') : [$request->all()];
        if (!is_array($rows) || empty($rows)) {
            return response()->json(['ok' => false, 'message' => 'No usages provided'], 422);
        }

        $accepted = 0;
        $rejected = [];

        foreach ($rows as $i => $r) {
            $v = Validator::make((array) $r, [
                'app_name'         => 'required|string|max:200',
                'window_title'     => 'nullable|string|max:500',
                'duration_seconds' => 'required|integer|min:1|max:86400',
                'started_at'       => 'required|date',
                'ended_at'         => 'nullable|date|after_or_equal:started_at',
            ]);
            if ($v->fails()) {
                $rejected[] = ['index' => $i, 'errors' => $v->errors()];
                continue;
            }
            try {
                AtAppUsageLog::create([
                    'user_id'           => $user->id,
                    'device_id'         => $device->id,
                    'app_name'          => $r['app_name'],
                    'window_title'      => $r['window_title']  ?? null,
                    'duration_seconds'  => $r['duration_seconds'],
                    'started_at'        => Carbon::parse($r['started_at']),
                    'ended_at'          => isset($r['ended_at']) ? Carbon::parse($r['ended_at']) : null,
                ]);
                $accepted++;
            } catch (\Throwable $e) {
                Log::warning('AT app-usage insert failed', ['err' => $e->getMessage()]);
                $rejected[] = ['index' => $i, 'errors' => ['system' => $e->getMessage()]];
            }
        }

        $device->update(['last_seen_at' => now()]);

        return response()->json(['ok' => true, 'accepted' => $accepted, 'rejected' => $rejected]);
    }

    /* ──────────────────────────────────────────────────────────────
     * GET endpoints — used by the dashboard's JS for AJAX/SPA-ish UX.
     * Auth still required (Sanctum) so they can be called from a logged-in
     * admin's session-cookie too.
     * ──────────────────────────────────────────────────────────── */
    public function dashboardSummary(Request $request)
    {
        $user      = $request->user();
        $creatorId = method_exists($user, 'creatorId') ? $user->creatorId() : $user->id;
        $today     = now()->toDateString();

        $deviceIds = AtDevice::where('created_by', $creatorId)->pluck('id');

        $activeUsers   = AtActivityLog::whereIn('device_id', $deviceIds)
            ->whereDate('captured_at', $today)->distinct('user_id')->count('user_id');
        $activeDevices = AtDevice::whereIn('id', $deviceIds)
            ->where('last_seen_at', '>', now()->subMinutes(5))->count();
        $shotsToday    = AtScreenshot::whereIn('device_id', $deviceIds)
            ->whereDate('captured_at', $today)->count();

        // Approx active vs idle from activity samples (each sample ~30s window)
        $aggToday = AtActivityLog::whereIn('device_id', $deviceIds)
            ->whereDate('captured_at', $today)
            ->selectRaw('SUM(CASE WHEN idle_seconds < 30 THEN 30 ELSE 0 END) as active_s, SUM(idle_seconds) as idle_s')
            ->first();

        return response()->json([
            'ok'   => true,
            'data' => [
                'active_users_today'   => (int) $activeUsers,
                'active_devices'       => (int) $activeDevices,
                'screenshots_today'    => (int) $shotsToday,
                'avg_active_seconds'   => $activeUsers > 0 ? (int) (($aggToday->active_s ?? 0) / $activeUsers) : 0,
                'avg_idle_seconds'     => $activeUsers > 0 ? (int) (($aggToday->idle_s ?? 0)   / $activeUsers) : 0,
            ],
        ]);
    }

    /* ──────────────────────────────────────────────────────────────
     * POST /api/activity-tracker/stop-request
     * Employee requests to stop tracking; admin must approve.
     * ──────────────────────────────────────────────────────────── */
    public function requestStop(Request $request)
    {
        $user   = $request->user();
        $device = $this->resolveDevice($request);
        if (!$device) {
            return response()->json(['ok' => false, 'message' => 'Device not found'], 404);
        }

        // Check if there's already a pending request
        $existing = AtStopRequest::where('user_id', $user->id)
            ->where('device_id', $device->id)
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            return response()->json([
                'ok'     => true,
                'status' => 'pending',
                'message' => 'Stop request already pending admin approval',
            ]);
        }

        AtStopRequest::create([
            'user_id'   => $user->id,
            'device_id' => $device->id,
            'status'    => 'pending',
            'reason'    => $request->input('reason'),
        ]);

        // Admin ko notify karo jab employee tracker band karne ki request kare
        try {
            config([
                'mail.default'                 => 'smtp',
                'mail.mailers.smtp.host'       => env('MAIL_HOST', 'smtp.gmail.com'),
                'mail.mailers.smtp.port'       => env('MAIL_PORT', 587),
                'mail.mailers.smtp.username'   => env('MAIL_USERNAME'),
                'mail.mailers.smtp.password'   => env('MAIL_PASSWORD'),
                'mail.mailers.smtp.encryption' => env('MAIL_ENCRYPTION', 'tls'),
                'mail.from.address'            => env('MAIL_FROM_ADDRESS'),
                'mail.from.name'               => env('MAIL_FROM_NAME', 'HRMS Tracker'),
            ]);
            $admin = User::find($user->created_by);
            if ($admin && $admin->email) {
                $reason  = $request->input('reason') ?: 'No reason provided';
                $subject = '[Tracker] ' . $user->name . ' ne tracker band karne ki request ki';
                $body    = "Employee: {$user->name}\n"
                         . "Device:   {$device->device_name}\n"
                         . "Reason:   {$reason}\n"
                         . "Time:     " . now()->format('d M Y, h:i A') . "\n\n"
                         . "Admin panel mein jaake approve ya reject karein.";

                Mail::raw($body, function ($msg) use ($admin, $subject) {
                    $msg->to($admin->email, $admin->name)->subject($subject);
                });
            }
        } catch (\Throwable $e) {
            Log::warning('Tracker stop-request admin notify failed', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
        }

        return response()->json([
            'ok'     => true,
            'status' => 'pending',
            'message' => 'Stop request sent to admin. Tracking will continue until approved.',
        ]);
    }

    /* ──────────────────────────────────────────────────────────────
     * GET /api/activity-tracker/stop-request/status
     * Tracker polls this to know if admin approved stop.
     * ──────────────────────────────────────────────────────────── */
    public function stopRequestStatus(Request $request)
    {
        $user   = $request->user();
        $device = $this->resolveDevice($request);
        if (!$device) {
            return response()->json(['ok' => false, 'message' => 'Device not found'], 404);
        }

        $req = AtStopRequest::where('user_id', $user->id)
            ->where('device_id', $device->id)
            ->orderByDesc('created_at')
            ->first();

        if (!$req) {
            return response()->json(['ok' => true, 'status' => 'none']);
        }

        return response()->json([
            'ok'     => true,
            'status' => $req->status,  // pending | approved | rejected
        ]);
    }

    /* ──────────────────────────────────────────────────────────────
     * POST /api/activity-tracker/stop-request/{id}/review  (admin)
     * Admin approves or rejects a stop request.
     * ──────────────────────────────────────────────────────────── */
    public function reviewStopRequest(Request $request, $id)
    {
        $admin = $request->user();
        $v = Validator::make($request->all(), [
            'action' => 'required|in:approved,rejected',
        ]);
        if ($v->fails()) {
            return response()->json(['ok' => false, 'errors' => $v->errors()], 422);
        }

        $stopReq = AtStopRequest::find($id);
        if (!$stopReq) {
            return response()->json(['ok' => false, 'message' => 'Request not found'], 404);
        }

        $stopReq->update([
            'status'      => $request->input('action'),
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ]);

        return response()->json(['ok' => true, 'status' => $stopReq->status]);
    }

    /* ──────────────────────────────────────────────────────────────
     * GET /api/activity-tracker/stop-requests  (admin)
     * List all pending stop requests for this tenant.
     * ──────────────────────────────────────────────────────────── */
    public function listStopRequests(Request $request)
    {
        $user = $request->user();
        $cid  = method_exists($user, 'creatorId') ? $user->creatorId() : $user->id;

        $deviceIds = AtDevice::where('created_by', $cid)->pluck('id');

        $requests = AtStopRequest::with(['user:id,name,email', 'device:id,device_name'])
            ->whereIn('device_id', $deviceIds)
            ->where('status', 'pending')
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['ok' => true, 'data' => $requests]);
    }

    /* ──────────────────────────────────────────────────────────────
     * Helpers
     * ──────────────────────────────────────────────────────────── */

    /**
     * Resolve the device referenced by the request — accepts either
     * device_uuid or device_id; both are scoped to the authenticated user.
     */
    protected function resolveDevice(Request $request): ?AtDevice
    {
        $user = $request->user();
        if (!$user) return null;

        if ($request->filled('device_uuid')) {
            return AtDevice::where('device_uuid', $request->device_uuid)
                ->where('user_id', $user->id)->first();
        }
        if ($request->filled('device_id')) {
            return AtDevice::where('id', $request->device_id)
                ->where('user_id', $user->id)->first();
        }
        return null;
    }
}
