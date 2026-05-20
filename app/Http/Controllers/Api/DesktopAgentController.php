<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AgentActivity;
use App\Models\BackgroundScreenshot;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DesktopAgentController extends Controller
{
    /**
     * The web XSS middleware sets app.timezone from the company's settings,
     * but it only runs on web auth — Sanctum-authed API calls skip it. We
     * mirror that behaviour here so writes line up with the timezone the
     * admin index page reads in. Without this, captured_at lands in UTC and
     * displays as ~5.5 hours off when the company runs on Asia/Kolkata.
     */
    private function applyCompanyTimezone($user): void
    {
        $tz = DB::table('settings')
            ->where('name', 'timezone')
            ->where('created_by', $user->creatorId())
            ->value('value');
        if ($tz) {
            Config::set('app.timezone', $tz);
            date_default_timezone_set($tz);
        }
    }

    /**
     * POST /api/agent/login
     * Issues a Sanctum bearer token bound to the supplied device name.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'       => 'required|email',
            'password'    => 'required|string',
            'device_name' => 'required|string|max:120',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['success' => false, 'message' => 'Invalid credentials.'], 401);
        }

        if ((int) ($user->is_login_enable ?? 1) !== 1) {
            return response()->json(['success' => false, 'message' => 'Account is disabled.'], 403);
        }

        $deviceName = 'desktop:' . substr($request->device_name, 0, 100);
        $user->tokens()->where('name', $deviceName)->delete();
        $token = $user->createToken($deviceName, ['agent:write'])->plainTextToken;

        $intervalMinutes = (int) (DB::table('settings')
            ->where('name', 'screenshot_interval')
            ->where('created_by', $user->creatorId())
            ->value('value') ?: 5);

        return response()->json([
            'success' => true,
            'data' => [
                'token'            => $token,
                'user_id'          => $user->id,
                'name'             => $user->name,
                'email'            => $user->email,
                'interval_seconds' => $intervalMinutes * 60,
            ],
        ]);
    }

    /**
     * POST /api/agent/logout
     * Revokes the current bearer token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['success' => true]);
    }

    /**
     * GET /api/agent/config
     * Returns the latest interval/setting so the agent can re-poll without reinstalling.
     */
    public function config(Request $request): JsonResponse
    {
        $user = $request->user();
        $minutes = (int) (DB::table('settings')
            ->where('name', 'screenshot_interval')
            ->where('created_by', $user->creatorId())
            ->value('value') ?: 5);

        return response()->json([
            'success' => true,
            'data'    => [
                'interval_seconds' => $minutes * 60,
                'idle_threshold'   => 60,
            ],
        ]);
    }

    /**
     * POST /api/agent/screenshot
     * multipart/form-data: screenshot (file), active_window (string), captured_at (iso8601)
     */
    public function screenshot(Request $request): JsonResponse
    {
        $request->validate([
            'screenshot'    => 'required|file|mimes:jpg,jpeg,png,webp|max:8192',
            'active_window' => 'nullable|string|max:500',
            'active_url'    => 'nullable|string|max:500',
            'captured_at'   => 'nullable|date',
        ]);

        $user = $request->user();
        $this->applyCompanyTimezone($user);

        $relDir = "uploads/bg-screenshots/{$user->id}";
        $absDir = public_path($relDir);
        if (!is_dir($absDir)) {
            mkdir($absDir, 0755, true);
        }

        $captured = $request->filled('captured_at')
            ? Carbon::parse($request->input('captured_at'))
            : now();

        $file = $request->file('screenshot');
        $filename = $captured->format('Y-m-d_H-i-s') . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $file->move($absDir, $filename);
        $relPath = $relDir . '/' . $filename;

        BackgroundScreenshot::create([
            'user_id'         => $user->id,
            'screenshot_path' => $relPath,
            'page_url'        => $request->input('active_window') ?? $request->input('active_url'),
            'ip_address'      => $request->ip(),
            'captured_at'     => $captured,
        ]);

        $this->pruneOld($user->id);

        return response()->json([
            'success' => true,
            'data'    => ['path' => $relPath, 'url' => asset($relPath)],
        ]);
    }

    /**
     * POST /api/agent/activity
     * JSON body. Accepts a single tick or a batched array under `entries`.
     */
    public function activity(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->applyCompanyTimezone($user);

        $payload = $request->input('entries');
        if (!is_array($payload) || empty($payload)) {
            $payload = [$request->all()];
        }

        $rows = [];
        $now = now();
        foreach ($payload as $entry) {
            $rows[] = [
                'user_id'            => $user->id,
                'active_seconds'     => (int) ($entry['active_seconds']  ?? $entry['active_time'] ?? 0),
                'idle_seconds'       => (int) ($entry['idle_seconds']    ?? $entry['idle_time']   ?? 0),
                'keystrokes'         => (int) ($entry['keystrokes']      ?? 0),
                'mouse_events'       => (int) ($entry['mouse_events']    ?? 0),
                'active_window'      => isset($entry['active_window']) ? mb_substr((string) $entry['active_window'], 0, 500) : null,
                'active_app'         => isset($entry['active_app'])    ? mb_substr((string) $entry['active_app'],    0, 200) : null,
                'active_url'         => isset($entry['active_url'])    ? mb_substr((string) $entry['active_url'],    0, 500) : null,
                'productivity_score' => isset($entry['productivity_score']) ? max(0, min(100, (int) $entry['productivity_score'])) : null,
                'hostname'           => isset($entry['hostname']) ? mb_substr((string) $entry['hostname'], 0, 200) : null,
                'ip_address'         => $request->ip(),
                'captured_at'        => isset($entry['captured_at']) ? Carbon::parse($entry['captured_at']) : $now,
                'created_at'         => $now,
                'updated_at'         => $now,
            ];
        }

        AgentActivity::insert($rows);

        return response()->json([
            'success' => true,
            'data'    => ['accepted' => count($rows)],
        ]);
    }

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
}
