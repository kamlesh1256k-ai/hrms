<?php
/**
 * Miraix Activity Tracker - Live Deployment Script
 * 
 * This script will generate all necessary files and commands
 * to deploy the activity tracker to the live Miraix.in server
 */

echo "<h2>🚀 Miraix Activity Tracker - Live Deployment</h2>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;}.success{color:green;}.error{color:red;}.warning{color:orange;}.code{background:#f4f4f4;padding:15px;border-radius:5px;margin:10px 0;}.btn{background:#007bff;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block;margin:10px 5px;}</style>";

// Generate deployment files
$deploymentCommands = [
    'database' => [
        'title' => 'Database Migrations',
        'commands' => [
            'php artisan migrate --path=database/migrations/2026_05_02_000001_create_at_devices_table.php',
            'php artisan migrate --path=database/migrations/2026_05_02_000002_create_at_activity_logs_table.php',
            'php artisan migrate --path=database/migrations/2026_05_02_000003_create_at_screenshots_table.php',
            'php artisan migrate --path=database/migrations/2026_05_02_000004_create_at_app_usage_logs_table.php',
            'php artisan migrate --path=database/migrations/2026_05_02_000005_create_at_daily_summaries_table.php',
            'php artisan db:seed --class=ActivityTrackerPermissionsSeeder',
            'php artisan storage:link'
        ]
    ],
    'files' => [
        'title' => 'File Deployment',
        'commands' => [
            'cp Modules/activity-tracker/laravel/app/Models/AtDevice.php app/Models/',
            'cp Modules/activity-tracker/laravel/app/Models/AtActivityLog.php app/Models/',
            'cp Modules/activity-tracker/laravel/app/Models/AtScreenshot.php app/Models/',
            'cp Modules/activity-tracker/laravel/app/Models/AtAppUsageLog.php app/Models/',
            'cp Modules/activity-tracker/laravel/app/Models/AtDailySummary.php app/Models/',
            'cp Modules/activity-tracker/laravel/app/Http/Controllers/ActivityTrackerController.php app/Http/Controllers/',
            'cp Modules/activity-tracker/laravel/app/Http/Controllers/Api/ActivityTrackerApiController.php app/Http/Controllers/Api/',
            'cp -r Modules/activity-tracker/laravel/resources/views/activity_tracker resources/views/',
            'cp Modules/activity-tracker/laravel/database/seeders/ActivityTrackerPermissionsSeeder.php database/seeders/'
        ]
    ],
    'routes' => [
        'title' => 'Route Updates',
        'web_routes' => "// Add to routes/web.php
Route::middleware(['auth', 'XSS'])->prefix('activity-tracker')->name('activity-tracker.')->group(function () {
    Route::get('/', [ActivityTrackerController::class, 'index'])->name('index');
    Route::get('/user-activity', [ActivityTrackerController::class, 'userActivity'])->name('user-activity');
    Route::get('/timeline', [ActivityTrackerController::class, 'timeline'])->name('timeline');
    Route::get('/app-usage', [ActivityTrackerController::class, 'appUsage'])->name('app-usage');
    Route::get('/daily-report', [ActivityTrackerController::class, 'dailyReport'])->name('daily-report');
    Route::get('/token', [ActivityTrackerController::class, 'token'])->name('token');
});",
        'api_routes' => "// Add to routes/api.php
Route::middleware(['auth:sanctum'])->prefix('api/activity-tracker')->name('api.activity-tracker.')->group(function () {
    Route::post('/device/register', [ActivityTrackerApiController::class, 'registerDevice']);
    Route::post('/device/heartbeat', [ActivityTrackerApiController::class, 'heartbeat']);
    Route::post('/activity/store', [ActivityTrackerApiController::class, 'storeActivity']);
    Route::post('/screenshot/upload', [ActivityTrackerApiController::class, 'uploadScreenshot']);
    Route::post('/app-usage/store', [ActivityTrackerApiController::class, 'storeAppUsage']);
    Route::get('/dashboard/summary', [ActivityTrackerApiController::class, 'dashboardSummary']);
});"
    ],
    'menu' => [
        'title' => 'Navigation Menu Update',
        'menu_code' => "// Add to resources/views/partial/Admin/menu.blade.php
@can('manage-activity-tracker')
    <li class=\"dash-item {{ request()->routeIs('activity-tracker.*') ? 'active' : '' }}\">
        <a class=\"dash-link\" href=\"{{ route('activity-tracker.index') }}\">
            <span class=\"dash-micon\">
                <i class=\"ti ti-activity\"></i>
            </span>
            <span class=\"dash-mtext\">{{ __('Activity Tracker') }}</span>
        </a>
    </li>
@endcan"
    ],
    'cache' => [
        'title' => 'Cache Clear',
        'commands' => [
            'php artisan cache:clear',
            'php artisan config:clear',
            'php artisan view:clear',
            'php artisan route:clear',
            'php artisan optimize'
        ]
    ]
];

echo "<div class='warning'>";
echo "<h3>⚠️ PROBLEM IDENTIFIED</h3>";
echo "<p><strong>Issue:</strong> Activity Tracker working locally but NOT showing on live Miraix.in dashboard</p>";
echo "<p><strong>Root Cause:</strong> Activity Tracker module not deployed on live server</p>";
echo "</div>";

echo "<div class='success'>";
echo "<h3>🎯 SOLUTION</h3>";
echo "<p>Deploy the Activity Tracker module to the live Miraix.in server using the commands below:</p>";
echo "</div>";

foreach ($deploymentCommands as $section => $data) {
    echo "<div style='margin:20px 0;'>";
    echo "<h4>📋 {$data['title']}</h4>";
    
    if (isset($data['commands'])) {
        echo "<div class='code'>";
        foreach ($data['commands'] as $cmd) {
            echo htmlspecialchars($cmd) . "<br>";
        }
        echo "</div>";
    }
    
    if (isset($data['web_routes'])) {
        echo "<div class='code'>";
        echo "<strong>routes/web.php:</strong><br>";
        echo htmlspecialchars($data['web_routes']);
        echo "</div>";
    }
    
    if (isset($data['api_routes'])) {
        echo "<div class='code'>";
        echo "<strong>routes/api.php:</strong><br>";
        echo htmlspecialchars($data['api_routes']);
        echo "</div>";
    }
    
    if (isset($data['menu_code'])) {
        echo "<div class='code'>";
        echo "<strong>resources/views/partial/Admin/menu.blade.php:</strong><br>";
        echo htmlspecialchars($data['menu_code']);
        echo "</div>";
    }
    
    echo "</div>";
}

echo "<div style='margin:30px 0;'>";
echo "<h3>🚀 DEPLOYMENT STEPS</h3>";
echo "<ol>";
echo "<li><strong>SSH into live server:</strong> ssh user@miraix.in</li>";
echo "<li><strong>Navigate to project:</strong> cd /var/www/miraix.in</li>";
echo "<li><strong>Run database migrations</strong> (see commands above)</li>";
echo "<li><strong>Copy files from local to live server</strong></li>";
echo "<li><strong>Update routes in web.php and api.php</strong></li>";
echo "<li><strong>Add menu item to navigation</strong></li>";
echo "<li><strong>Clear all caches</strong></li>";
echo "<li><strong>Test dashboard access</strong></li>";
echo "</ol>";
echo "</div>";

echo "<div style='margin:30px 0;'>";
echo "<h3>📋 VERIFICATION</h3>";
echo "<p>After deployment, test these URLs:</p>";
echo "<ul>";
echo "<li><a href='https://miraix.in/activity-tracker' target='_blank'>https://miraix.in/activity-tracker</a> - Dashboard</li>";
echo "<li><a href='https://miraix.in/api/activity-tracker/dashboard/summary' target='_blank'>https://miraix.in/api/activity-tracker/dashboard/summary</a> - API</li>";
echo "<li><a href='https://miraix.in/activity-tracker/timeline' target='_blank'>https://miraix.in/activity-tracker/timeline</a> - Timeline</li>";
echo "</ul>";
echo "</div>";

echo "<div class='warning'>";
echo "<h3>⚠️ IMPORTANT</h3>";
echo "<p>You need <strong>SSH access</strong> to the live Miraix.in server to deploy these changes.</p>";
echo "<p>If you don't have access, contact the Miraix development team.</p>";
echo "</div>";

echo "<div style='margin:30px 0;'>";
echo "<h3>📞 SUPPORT</h3>";
echo "<p>If you need help with deployment:</p>";
echo "<ul>";
echo "<li>📧 Email: dev@miraix.in</li>";
echo "<li>🚀 Technical support: support@miraix.in</li>";
echo "<li>📱 Emergency: admin@miraix.in</li>";
echo "</ul>";
echo "</div>";

?>
