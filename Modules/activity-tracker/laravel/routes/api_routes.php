<?php
/**
 * Activity Tracker — API routes (Sanctum bearer auth).
 * Mounted from routes/api.php.
 */

use App\Http\Controllers\Api\ActivityTrackerApiController;
use Illuminate\Support\Facades\Route;

// Public — agent uses this with email+password to obtain a token
Route::post('activity-tracker/login', [ActivityTrackerApiController::class, 'login']);

Route::prefix('activity-tracker')->middleware('auth:sanctum')->group(function () {
    // Device lifecycle
    Route::post('device/register',  [ActivityTrackerApiController::class, 'registerDevice']);
    Route::post('device/heartbeat', [ActivityTrackerApiController::class, 'heartbeat']);

    // Sample ingestion
    Route::post('activity/store',     [ActivityTrackerApiController::class, 'storeActivity']);
    Route::post('screenshot/upload',  [ActivityTrackerApiController::class, 'uploadScreenshot']);
    Route::post('app-usage/store',    [ActivityTrackerApiController::class, 'storeAppUsage']);

    // Read-side helpers (also usable from dashboard JS)
    Route::get ('dashboard/summary',        [ActivityTrackerApiController::class, 'dashboardSummary']);

    // Stop-tracking request flow
    Route::post('stop-request',             [ActivityTrackerApiController::class, 'requestStop']);
    Route::get ('stop-request/status',      [ActivityTrackerApiController::class, 'stopRequestStatus']);
    Route::get ('stop-requests',            [ActivityTrackerApiController::class, 'listStopRequests']);
    Route::post('stop-request/{id}/review', [ActivityTrackerApiController::class, 'reviewStopRequest']);
});
