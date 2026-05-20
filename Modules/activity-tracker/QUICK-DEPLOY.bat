@echo off
echo ========================================
echo Miraix Activity Tracker - Quick Deploy
echo ========================================
echo.

echo This script will prepare all files for deployment to live server.
echo.

REM Create deployment package
echo [1] Creating deployment package...
if not exist "deploy-package" mkdir deploy-package

REM Copy necessary files to deploy package
echo [2] Copying files for deployment...

REM Models
xcopy "laravel\app\Models\At*.php" "deploy-package\app\Models\" /Y /I

REM Controllers
xcopy "laravel\app\Http\Controllers\ActivityTracker*.php" "deploy-package\app\Http\Controllers\" /Y /I
if not exist "deploy-package\app\Http\Controllers\Api" mkdir deploy-package\app\Http\Controllers\Api
xcopy "laravel\app\Http\Controllers\Api\ActivityTrackerApiController.php" "deploy-package\app\Http\Controllers\Api\" /Y

REM Views
xcopy "laravel\resources\views\activity_tracker\*" "deploy-package\resources\views\activity_tracker\" /Y /E /I

REM Database
xcopy "laravel\database\migrations\2026_05_02_*.php" "deploy-package\database\migrations\" /Y
xcopy "laravel\database\seeders\ActivityTrackerPermissionsSeeder.php" "deploy-package\database\seeders\" /Y

REM Create route files
echo [3] Creating route update files...

echo // Add these routes to routes/web.php > deploy-package\web-routes.txt
echo Route::middleware(['auth', 'XSS'])->prefix('activity-tracker')->name('activity-tracker.')->group(function () { >> deploy-package\web-routes.txt
echo     Route::get('/', [ActivityTrackerController::class, 'index'])->name('index'); >> deploy-package\web-routes.txt
echo     Route::get('/user-activity', [ActivityTrackerController::class, 'userActivity'])->name('user-activity'); >> deploy-package\web-routes.txt
echo     Route::get('/timeline', [ActivityTrackerController::class, 'timeline'])->name('timeline'); >> deploy-package\web-routes.txt
echo     Route::get('/app-usage', [ActivityTrackerController::class, 'appUsage'])->name('app-usage'); >> deploy-package\web-routes.txt
echo     Route::get('/daily-report', [ActivityTrackerController::class, 'dailyReport'])->name('daily-report'); >> deploy-package\web-routes.txt
echo     Route::get('/token', [ActivityTrackerController::class, 'token'])->name('token'); >> deploy-package\web-routes.txt
echo }); >> deploy-package\web-routes.txt

echo // Add these routes to routes/api.php > deploy-package\api-routes.txt
echo Route::middleware(['auth:sanctum'])->prefix('api/activity-tracker')->name('api.activity-tracker.')->group(function () { >> deploy-package\api-routes.txt
echo     Route::post('/device/register', [ActivityTrackerApiController::class, 'registerDevice']); >> deploy-package\api-routes.txt
echo     Route::post('/device/heartbeat', [ActivityTrackerApiController::class, 'heartbeat']); >> deploy-package\api-routes.txt
echo     Route::post('/activity/store', [ActivityTrackerApiController::class, 'storeActivity']); >> deploy-package\api-routes.txt
echo     Route::post('/screenshot/upload', [ActivityTrackerApiController::class, 'uploadScreenshot']); >> deploy-package\api-routes.txt
echo     Route::post('/app-usage/store', [ActivityTrackerApiController::class, 'storeAppUsage']); >> deploy-package\api-routes.txt
echo     Route::get('/dashboard/summary', [ActivityTrackerApiController::class, 'dashboardSummary']); >> deploy-package\api-routes.txt
echo }); >> deploy-package\api-routes.txt

echo // Add this menu item to resources/views/partial/Admin/menu.blade.php > deploy-package\menu-update.txt
echo @can('manage-activity-tracker') >> deploy-package\menu-update.txt
echo     ^<li class="dash-item {{ request()->routeIs('activity-tracker.*') ? 'active' : '' }}">^ >> deploy-package\menu-update.txt
echo         ^<a class="dash-link" href="{{ route('activity-tracker.index') }}">^ >> deploy-package\menu-update.txt
echo             ^<span class="dash-micon"^>^ >> deploy-package\menu-update.txt
echo                 ^<i class="ti ti-activity"^>^</i^>^ >> deploy-package\menu-update.txt
echo             ^</span^>^ >> deploy-package\menu-update.txt
echo             ^<span class="dash-mtext"^>{{ __('Activity Tracker') }}^</span^>^ >> deploy-package\menu-update.txt
echo         ^</a^>^ >> deploy-package\menu-update.txt
echo     ^</li^>^ >> deploy-package\menu-update.txt
echo @endcan >> deploy-package\menu-update.txt

REM Create deployment commands file
echo [4] Creating deployment commands...
echo # Miraix Activity Tracker - Live Deployment Commands > deploy-package\deploy-commands.sh
echo echo "🚀 Deploying to Miraix.in Live Server" >> deploy-package\deploy-commands.sh
echo. >> deploy-package\deploy-commands.sh
echo echo "Step 1: Database Migrations" >> deploy-package\deploy-commands.sh
echo php artisan migrate --path=database/migrations/2026_05_02_000001_create_at_devices_table.php >> deploy-package\deploy-commands.sh
echo php artisan migrate --path=database/migrations/2026_05_02_000002_create_at_activity_logs_table.php >> deploy-package\deploy-commands.sh
echo php artisan migrate --path=database/migrations/2026_05_02_000003_create_at_screenshots_table.php >> deploy-package\deploy-commands.sh
echo php artisan migrate --path=database/migrations/2026_05_02_000004_create_at_app_usage_logs_table.php >> deploy-package\deploy-commands.sh
echo php artisan migrate --path=database/migrations/2026_05_02_000005_create_at_daily_summaries_table.php >> deploy-package\deploy-commands.sh
echo. >> deploy-package\deploy-commands.sh
echo echo "Step 2: Seed Permissions" >> deploy-package\deploy-commands.sh
echo php artisan db:seed --class=ActivityTrackerPermissionsSeeder >> deploy-package\deploy-commands.sh
echo. >> deploy-package\deploy-commands.sh
echo echo "Step 3: Clear Caches" >> deploy-package\deploy-commands.sh
echo php artisan cache:clear >> deploy-package\deploy-commands.sh
echo php artisan config:clear >> deploy-package\deploy-commands.sh
echo php artisan view:clear >> deploy-package\deploy-commands.sh
echo php artisan route:clear >> deploy-package\deploy-commands.sh
echo. >> deploy-package\deploy-commands.sh
echo echo "Step 4: Create Storage Link" >> deploy-package\deploy-commands.sh
echo php artisan storage:link >> deploy-package\deploy-commands.sh

echo.
echo ========================================
echo ✅ DEPLOYMENT PACKAGE CREATED!
echo ========================================
echo.
echo Package location: %CD%\deploy-package
echo.
echo Contents:
echo - app\Models\*.php (Activity Tracker Models)
echo - app\Http\Controllers\*.php (Controllers)
echo - resources\views\activity_tracker\ (Dashboard Views)
echo - database\migrations\*.php (Database Migrations)
echo - database\seeders\*.php (Permissions Seeder)
echo - web-routes.txt (Web Routes to Add)
echo - api-routes.txt (API Routes to Add)
echo - menu-update.txt (Menu Item to Add)
echo - deploy-commands.sh (Commands to Run)
echo.
echo ========================================
echo 📋 NEXT STEPS
echo ========================================
echo.
echo 1. Copy deploy-package folder to live Miraix.in server
echo 2. Run deploy-commands.sh on live server
echo 3. Add routes from web-routes.txt to routes/web.php
echo 4. Add routes from api-routes.txt to routes/api.php
echo 5. Add menu item from menu-update.txt to navigation
echo 6. Test: https://miraix.in/activity-tracker
echo.
echo After deployment, Activity Tracker menu will appear in Miraix dashboard!
echo.
pause
