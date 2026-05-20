<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\ScreenMonitor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ScreenMonitorController extends Controller
{
    /* ──────────────────────────────────────────────────────────
     |  POST /screen-monitor/capture
     |  Employee ke browser se screenshot receive karke save karo
     ─────────────────────────────────────────────────────────── */
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

        // Base64 decode
        $parts   = explode(',', $dataUrl, 2);
        $imgData = base64_decode($parts[1] ?? '');
        if (!$imgData) {
            return response()->json(['error' => 'Decode failed'], 422);
        }

        // Save directly to public/uploads/screen-monitors/{user_id}/
        $relDir   = "uploads/screen-monitors/{$user->id}";
        $absDir   = public_path($relDir);
        if (!is_dir($absDir)) {
            mkdir($absDir, 0755, true);
        }
        $relPath = $relDir . '/' . now()->format('Y-m-d_H-i-s') . '_' . uniqid() . '.jpg';
        file_put_contents(public_path($relPath), $imgData);

        ScreenMonitor::create([
            'user_id'         => $user->id,
            'screenshot_path' => $relPath,
            'ip_address'      => $request->ip(),
            'captured_at'     => now(),
        ]);

        // Old screenshots rakho sirf last 48 ghante ke (disk save karo)
        $this->pruneOldScreenshots($user->id);

        return response()->json(['ok' => true, 'time' => now()->format('H:i:s')]);
    }

    /* ──────────────────────────────────────────────────────────
     |  GET /screen-monitor
     |  HR / Company Admin – saare employees ka overview
     ─────────────────────────────────────────────────────────── */
    public function index(Request $request)
    {
        $authUser  = Auth::user();

        // Employees aur Super Admin ko access nahi
        if ($authUser->type === 'employee' || $authUser->type === 'super admin') {
            return redirect()->route('dashboard');
        }

        $creatorId = $authUser->creatorId();

        // Employees + unka latest screenshot
        $employees = User::where('created_by', $creatorId)
            ->where('type', '!=', 'super admin')
            ->where('type', '!=', 'company')
            ->with(['screenMonitors' => function ($q) {
                $q->orderBy('captured_at', 'desc')->limit(1);
            }])
            ->get();

        // Search filter
        $search = $request->input('search');
        if ($search) {
            $employees = $employees->filter(fn($e) => stripos($e->name, $search) !== false);
        }

        // Badge counts per employee (today)
        $todayCounts = ScreenMonitor::whereIn('user_id', $employees->pluck('id'))
            ->whereDate('captured_at', today())
            ->selectRaw('user_id, count(*) as cnt')
            ->groupBy('user_id')
            ->pluck('cnt', 'user_id');

        return view('screen-monitor.index', compact('employees', 'todayCounts', 'search'));
    }

    /* ──────────────────────────────────────────────────────────
     |  GET /screen-monitor/{userId}
     |  HR – ek specific employee ki saari screenshots
     ─────────────────────────────────────────────────────────── */
    public function show(Request $request, int $userId)
    {
        $authUser  = Auth::user();

        if ($authUser->type === 'employee' || $authUser->type === 'super admin') {
            return redirect()->route('dashboard');
        }

        $creatorId = $authUser->creatorId();

        $employee = User::where('created_by', $creatorId)->findOrFail($userId);

        $date = $request->input('date', today()->toDateString());

        $screenshots = ScreenMonitor::where('user_id', $userId)
            ->whereDate('captured_at', $date)
            ->orderBy('captured_at', 'desc')
            ->paginate(30)
            ->withQueryString();

        return view('screen-monitor.show', compact('employee', 'screenshots', 'date'));
    }

    /* ──────────────────────────────────────────────────────────
     |  DELETE /screen-monitor/{id}
     |  Single screenshot delete karo
     ─────────────────────────────────────────────────────────── */
    public function destroy(int $id)
    {
        $authUser  = Auth::user();

        if ($authUser->type === 'employee' || $authUser->type === 'super admin') {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $creatorId = $authUser->creatorId();

        $shot = ScreenMonitor::whereHas('user', function ($q) use ($creatorId) {
            $q->where('created_by', $creatorId);
        })->findOrFail($id);

        @unlink(public_path($shot->screenshot_path));
        $shot->delete();

        return response()->json(['ok' => true]);
    }

    /* ──────────────────────────────────────────────────────────
     |  Private: 48-hour se purane screenshots delete karo
     ─────────────────────────────────────────────────────────── */
    private function pruneOldScreenshots(int $userId): void
    {
        $old = ScreenMonitor::where('user_id', $userId)
            ->where('captured_at', '<', now()->subHours(48))
            ->get();

        foreach ($old as $shot) {
            @unlink(public_path($shot->screenshot_path));
            $shot->delete();
        }
    }
}
