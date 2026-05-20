<?php
// Debug script for grievance module
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Grievance Module Debug</h2>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;}.error{color:red;}.success{color:green;}.info{color:blue;}</style>";

// Check if tables exist
try {
    $pdo = new PDO("mysql:host=localhost;dbname=hrms", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check grievances table
    $stmt = $pdo->query("SHOW TABLES LIKE 'grievances'");
    if ($stmt->rowCount() > 0) {
        echo "<p class='success'>✅ grievances table exists</p>";
        
        // Check table structure
        $stmt = $pdo->query("DESCRIBE grievances");
        echo "<h3>grievances table structure:</h3>";
        echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
        while ($row = $stmt->fetch()) {
            echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='error'>❌ grievances table does not exist</p>";
    }
    
    // Check grievance_responses table
    $stmt = $pdo->query("SHOW TABLES LIKE 'grievance_responses'");
    if ($stmt->rowCount() > 0) {
        echo "<p class='success'>✅ grievance_responses table exists</p>";
    } else {
        echo "<p class='error'>❌ grievance_responses table does not exist</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>Database Error: " . $e->getMessage() . "</p>";
}

// Check Laravel files
echo "<h3>Laravel File Check:</h3>";

$files = [
    'app/Models/Grievance.php' => 'Grievance Model',
    'app/Models/GrievanceResponse.php' => 'GrievanceResponse Model',
    'app/Http/Controllers/GrievanceController.php' => 'Grievance Controller',
    'resources/views/grievances/index.blade.php' => 'Index View',
    'resources/views/grievances/create.blade.php' => 'Create View',
    'resources/views/grievances/show.blade.php' => 'Show View'
];

foreach ($files as $file => $name) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "<p class='success'>✅ $name exists</p>";
    } else {
        echo "<p class='error'>❌ $name missing</p>";
    }
}

// Check Laravel routes
echo "<h3>Route Check:</h3>";
if (file_exists(__DIR__ . '/routes/web.php')) {
    $webRoutes = file_get_contents(__DIR__ . '/routes/web.php');
    if (strpos($webRoutes, 'grievances') !== false) {
        echo "<p class='success'>✅ grievance routes found in web.php</p>";
    } else {
        echo "<p class='error'>❌ grievance routes missing in web.php</p>";
    }
} else {
    echo "<p class='error'>❌ web.php not found</p>";
}

// Check Laravel error log
echo "<h3>Laravel Error Log:</h3>";
$logFile = __DIR__ . '/storage/logs/laravel.log';
if (file_exists($logFile)) {
    $logs = file_get_contents($logFile);
    $recentLogs = substr($logs, -2000); // Last 2000 characters
    echo "<div style='background:#f5f5f5;padding:10px;border-radius:5px;height:300px;overflow-y:auto;'>";
    echo "<pre>" . htmlspecialchars($recentLogs) . "</pre>";
    echo "</div>";
} else {
    echo "<p class='info'>No Laravel log file found</p>";
}

// Test basic Laravel functionality
echo "<h3>Laravel Bootstrap Test:</h3>";
try {
    require __DIR__ . '/vendor/autoload.php';
    $app = require_once __DIR__ . '/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    echo "<p class='success'>✅ Laravel bootstrap successful</p>";
    
    // Test database connection through Laravel
    try {
        \DB::connection()->getPdo();
        echo "<p class='success'>✅ Laravel database connection successful</p>";
    } catch (Exception $e) {
        echo "<p class='error'>❌ Laravel database connection failed: " . $e->getMessage() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Laravel bootstrap failed: " . $e->getMessage() . "</p>";
}

// Quick fix suggestions
echo "<h3>Quick Fix Suggestions:</h3>";
echo "<div style='background:#e7f3ff;padding:15px;border-radius:5px;'>";
echo "<ol>";
echo "<li>Clear Laravel cache: <code>php artisan cache:clear</code></li>";
echo "<li>Clear config cache: <code>php artisan config:clear</code></li>";
echo "<li>Clear view cache: <code>php artisan view:clear</code></li>";
echo "<li>Restart Apache server</li>";
echo "<li>Check .env file for correct database settings</li>";
echo "<li>Run: <code>php artisan migrate</code> (if tables missing)</li>";
echo "</ol>";
echo "</div>";

// Test direct grievance route
echo "<h3>Test Direct Route:</h3>";
echo "<p><a href='/hrms/test_simple_grievance' style='background:#007bff;color:white;padding:10px;text-decoration:none;border-radius:5px;'>Test Simple Grievance Route</a></p>";

?>
