<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BiometricAttendanceController;
use App\Http\Controllers\Api\MobileAppController;
use App\Http\Controllers\Api\MobileExtendedController;
use App\Http\Controllers\Api\DesktopAgentController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Facial Recognition API
Route::post('/facial-recognition/verify', [BiometricAttendanceController::class, 'verifyFacialRecognition'])->middleware('auth:sanctum');
Route::post('/attendance/clock-in-facial', [BiometricAttendanceController::class, 'clockInWithFacialRecognition'])->middleware('auth:sanctum');

// ══════════════════════════════════════════════════════════════
// MOBILE ATTENDANCE APP APIs
// ══════════════════════════════════════════════════════════════

// Public (no auth) - protected by app key
Route::prefix('mobile')->middleware('mobile.app.key')->group(function () {
    Route::post('/login', [MobileAppController::class, 'login']);
    Route::post('/forgot-password', [MobileAppController::class, 'forgotPassword']);
});

// Protected (token required)
Route::prefix('mobile')->middleware(['mobile.app.key', 'auth:sanctum'])->group(function () {
    // Auth
    Route::post('/logout', [MobileAppController::class, 'logout']);
    Route::post('/change-password', [MobileAppController::class, 'changePassword']);
    Route::post('/verify-face', [MobileAppController::class, 'verifyFace']);

    // Dashboard
    Route::get('/dashboard', [MobileAppController::class, 'dashboard']);

    // Attendance
    Route::post('/clock-in', [MobileAppController::class, 'clockIn']);
    Route::post('/clock-out', [MobileAppController::class, 'clockOut']);
    Route::get('/attendance-history', [MobileAppController::class, 'attendanceHistory']);

    // Leave
    Route::get('/leave-types', [MobileAppController::class, 'leaveTypes']);
    Route::get('/leaves', [MobileAppController::class, 'leaves']);
    Route::post('/leave/apply', [MobileAppController::class, 'applyLeave']);

    // Swipe Request
    Route::post('/swipe-request', [MobileAppController::class, 'submitSwipeRequest']);
    Route::get('/swipe-requests', [MobileAppController::class, 'swipeRequests']);

    // Profile
    Route::get('/profile', [MobileAppController::class, 'profile']);

    // ── Edit Profile ──────────────────────────────────────────
    Route::get('/profile/edit',  [MobileExtendedController::class, 'getProfile']);
    Route::put('/profile/edit',  [MobileExtendedController::class, 'updateProfile']);

    // ── My Account ───────────────────────────────────────────
    Route::get('/account',                   [MobileExtendedController::class, 'myAccount']);
    Route::post('/account/change-password',  [MobileExtendedController::class, 'changePassword']);
    Route::delete('/account/sessions',       [MobileExtendedController::class, 'logoutAllDevices']);

    // ── Notifications ─────────────────────────────────────────
    Route::get('/notifications',             [MobileExtendedController::class, 'notifications']);
    Route::get('/notifications/unread-count',[MobileExtendedController::class, 'unreadCount']);

    // ── Live Chat ─────────────────────────────────────────────
    Route::get('/chat/contacts',             [MobileExtendedController::class, 'chatContacts']);
    Route::get('/chat/messages/{userId}',    [MobileExtendedController::class, 'chatMessages']);
    Route::post('/chat/send',                [MobileExtendedController::class, 'chatSend']);

    // ── Support (Tickets) ─────────────────────────────────────
    Route::get('/support/tickets',           [MobileExtendedController::class, 'myTickets']);
    Route::post('/support/tickets',          [MobileExtendedController::class, 'createTicket']);
    Route::get('/support/tickets/{id}',      [MobileExtendedController::class, 'ticketDetail']);
    Route::post('/support/tickets/{id}/reply', [MobileExtendedController::class, 'replyTicket']);

    // ── Contact ───────────────────────────────────────────────
    Route::get('/contact',                   [MobileExtendedController::class, 'contact']);

    // ── Help ──────────────────────────────────────────────────
    Route::get('/help',                      [MobileExtendedController::class, 'help']);

    // ── Connect (Team Directory) ──────────────────────────────
    Route::get('/connect',                   [MobileExtendedController::class, 'connect']);

    // ── Meetings ──────────────────────────────────────────────
    Route::get('/meetings',                  [MobileExtendedController::class, 'meetings']);

    // Fingerprint Biometric
    Route::prefix('fingerprint')->group(function () {
        Route::post('/enroll',   [MobileAppController::class, 'enrollFingerprint']);
        Route::post('/verify',   [MobileAppController::class, 'verifyFingerprint']);
        Route::post('/clock-in', [MobileAppController::class, 'clockInFingerprint']);
        Route::get('/status',    [MobileAppController::class, 'fingerprintStatus']);
        Route::delete('/remove', [MobileAppController::class, 'removeFingerprint']);
    });
});

// ══════════════════════════════════════════════════════════════
// SURVEY MODULE APIs (Sanctum bearer)
// ══════════════════════════════════════════════════════════════
//
// Permissions enforced inside the controller (manage-surveys / submit-surveys
// / view-survey-analytics / view-survey-alerts / export-surveys).
// All endpoints scoped to authenticated user's company via creatorId().
//
// Auth header required: Authorization: Bearer <sanctum-token>

Route::prefix('surveys')->middleware('auth:sanctum')->group(function () {
    $sa = \App\Http\Controllers\Api\SurveyApiController::class;

    // Survey CRUD
    Route::get('/',                     [$sa, 'index']);
    Route::post('/',                    [$sa, 'store']);
    Route::get('/{id}',                 [$sa, 'show']);
    Route::put('/{id}',                 [$sa, 'update']);
    Route::patch('/{id}',               [$sa, 'update']);
    Route::delete('/{id}',              [$sa, 'destroy']);

    // Status transitions
    Route::post('/{id}/activate',       [$sa, 'activate']);
    Route::post('/{id}/close',          [$sa, 'close']);

    // Question management
    Route::get('/{id}/questions',       [$sa, 'questions']);
    Route::post('/{id}/questions',      [$sa, 'questionStore']);

    // Per-survey analytics + export
    Route::get('/{id}/analytics',       [$sa, 'analytics']);
    Route::get('/{id}/export',          [$sa, 'export']);
});

// Employee — active surveys + submit
Route::prefix('my-surveys')->middleware('auth:sanctum')->group(function () {
    $sa = \App\Http\Controllers\Api\SurveyApiController::class;
    Route::get('/',                     [$sa, 'myActive']);
    Route::post('/{id}/submit',         [$sa, 'submit']);
});

// HR analytics + alerts (cross-survey)
Route::prefix('reports')->middleware('auth:sanctum')->group(function () {
    $sa = \App\Http\Controllers\Api\SurveyApiController::class;
    Route::get('/enps',                 [$sa, 'enps']);
    Route::get('/sentiment',            [$sa, 'sentiment']);
    Route::get('/pulse',                [$sa, 'pulse']);
});

Route::middleware('auth:sanctum')->get('/survey-alerts', [\App\Http\Controllers\Api\SurveyApiController::class, 'alerts']);

// ══════════════════════════════════════════════════════════════
// DESKTOP MONITORING AGENT APIs (Windows agent — Sanctum bearer)
// ══════════════════════════════════════════════════════════════
Route::prefix('agent')->group(function () {
    Route::post('/login', [DesktopAgentController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout',     [DesktopAgentController::class, 'logout']);
        Route::get('/config',      [DesktopAgentController::class, 'config']);
        Route::post('/screenshot', [DesktopAgentController::class, 'screenshot']);
        Route::post('/activity',   [DesktopAgentController::class, 'activity']);
    });
});

// ══════════════════════════════════════════════════════════════
// Activity Tracker module (Laptop/Desktop monitoring — Electron agent)
// Module folder: modules/activity-tracker/
// ══════════════════════════════════════════════════════════════
$activityTrackerRoutes = base_path('Modules/activity-tracker/laravel/routes/api_routes.php');
if (!file_exists($activityTrackerRoutes)) {
    // Backward compat for older deployments on case-insensitive filesystems.
    $activityTrackerRoutes = base_path('modules/activity-tracker/laravel/routes/api_routes.php');
}
if (file_exists($activityTrackerRoutes)) {
    require $activityTrackerRoutes;
}
