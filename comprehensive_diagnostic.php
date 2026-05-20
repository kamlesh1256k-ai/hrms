<?php
// Comprehensive Diagnostic Tool for Grievance Module
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 Comprehensive Diagnostic Tool</h2>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;}.success{color:green;}.error{color:red;}.warning{color:orange;}.info{color:blue;}.card{background:#f8f9fa;padding:20px;border-radius:10px;margin:10px 0;}.code{background:#f5f5f5;padding:10px;border-radius:5px;font-family:monospace;}</style>";

// Step 1: Check Laravel Environment
echo "<div class='card'>";
echo "<h3>📋 Laravel Environment Check</h3>";

try {
    // Check if Laravel is properly installed
    if (!file_exists(__DIR__ . '/bootstrap/app.php')) {
        echo "<p class='error'>❌ Laravel bootstrap file missing</p>";
    } else {
        echo "<p class='success'>✅ Laravel bootstrap file exists</p>";
    }
    
    // Check vendor
    if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
        echo "<p class='error'>❌ Vendor dependencies missing - Run: composer install</p>";
    } else {
        echo "<p class='success'>✅ Vendor dependencies exist</p>";
    }
    
    // Check .env
    if (!file_exists(__DIR__ . '/.env')) {
        echo "<p class='error'>❌ .env file missing</p>";
    } else {
        echo "<p class='success'>✅ .env file exists</p>";
        $envContent = file_get_contents(__DIR__ . '/.env');
        if (strpos($envContent, 'DB_DATABASE') !== false) {
            echo "<p class='success'>✅ Database configuration found in .env</p>";
        } else {
            echo "<p class='error'>❌ Database configuration missing in .env</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Environment check failed: " . $e->getMessage() . "</p>";
}

echo "</div>";

// Step 2: Database Deep Check
echo "<div class='card'>";
echo "<h3>🗄️ Database Deep Check</h3>";

try {
    $pdo = new PDO("mysql:host=localhost;dbname=hrms", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p class='success'>✅ Database connection successful</p>";
    
    // Check tables structure
    $tables = ['grievances', 'grievance_responses', 'users'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<p class='success'>✅ Table '$table' exists</p>";
            
            // Check table structure
            $stmt = $pdo->query("DESCRIBE $table");
            $columns = $stmt->fetchAll();
            echo "<div class='code'>";
            echo "<strong>$table structure:</strong><br>";
            foreach ($columns as $col) {
                echo "- {$col['Field']} ({$col['Type']})<br>";
            }
            echo "</div>";
        } else {
            echo "<p class='error'>❌ Table '$table' missing</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Database error: " . $e->getMessage() . "</p>";
}

echo "</div>";

// Step 3: Laravel Files Check
echo "<div class='card'>";
echo "<h3>📁 Laravel Files Check</h3>";

$requiredFiles = [
    'app/Models/Grievance.php' => 'Grievance Model',
    'app/Models/GrievanceResponse.php' => 'GrievanceResponse Model',
    'app/Http/Controllers/GrievanceController.php' => 'Grievance Controller',
    'resources/views/grievances/index.blade.php' => 'Index View',
    'resources/views/grievances/create.blade.php' => 'Create View',
    'resources/views/grievances/show.blade.php' => 'Show View',
    'routes/web.php' => 'Web Routes'
];

foreach ($requiredFiles as $file => $name) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "<p class='success'>✅ $name exists</p>";
        
        // Check file syntax
        if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
            $content = file_get_contents(__DIR__ . '/' . $file);
            if (strpos($content, '<?php') !== false) {
                echo "<p class='success'>✅ $name has valid PHP syntax</p>";
            } else {
                echo "<p class='warning'>⚠️ $name may have syntax issues</p>";
            }
        }
    } else {
        echo "<p class='error'>❌ $name missing</p>";
    }
}

echo "</div>";

// Step 4: Laravel Bootstrap Test
echo "<div class='card'>";
echo "<h3>🚀 Laravel Bootstrap Test</h3>";

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
    
    // Test model instantiation
    try {
        $grievance = new \App\Models\Grievance();
        echo "<p class='success'>✅ Grievance model loads successfully</p>";
    } catch (Exception $e) {
        echo "<p class='error'>❌ Grievance model failed: " . $e->getMessage() . "</p>";
    }
    
    // Test controller
    try {
        $controller = new \App\Http\Controllers\GrievanceController();
        echo "<p class='success'>✅ GrievanceController loads successfully</p>";
    } catch (Exception $e) {
        echo "<p class='error'>❌ GrievanceController failed: " . $e->getMessage() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Laravel bootstrap failed: " . $e->getMessage() . "</p>";
    echo "<p class='info'>This is likely the root cause of 500 errors</p>";
}

echo "</div>";

// Step 5: Route Check
echo "<div class='card'>";
echo "<h3>🛣️ Route Check</h3>";

if (file_exists(__DIR__ . '/routes/web.php')) {
    $webRoutes = file_get_contents(__DIR__ . '/routes/web.php');
    if (strpos($webRoutes, 'grievances') !== false) {
        echo "<p class='success'>✅ grievance routes found in web.php</p>";
        
        // Extract grievance routes
        preg_match_all('/Route::[^(]*\([^)]*grievances[^)]*\)/', $webRoutes, $matches);
        if (!empty($matches[0])) {
            echo "<div class='code'>";
            echo "<strong>Found grievance routes:</strong><br>";
            foreach ($matches[0] as $route) {
                echo "- " . htmlspecialchars($route) . "<br>";
            }
            echo "</div>";
        }
    } else {
        echo "<p class='error'>❌ grievance routes missing in web.php</p>";
    }
} else {
    echo "<p class='error'>❌ web.php not found</p>";
}

echo "</div>";

// Step 6: Error Log Check
echo "<div class='card'>";
echo "<h3>📋 Error Log Analysis</h3>";

$logFile = __DIR__ . '/storage/logs/laravel.log';
if (file_exists($logFile)) {
    $logs = file_get_contents($logFile);
    $recentLogs = substr($logs, -3000); // Last 3000 characters
    
    // Look for grievance-related errors
    if (strpos($recentLogs, 'grievances') !== false) {
        echo "<p class='error'>❌ Found grievance-related errors in log</p>";
        echo "<div class='code'>";
        echo "<strong>Recent grievance errors:</strong><br>";
        $lines = explode("\n", $recentLogs);
        foreach ($lines as $line) {
            if (strpos($line, 'grievances') !== false) {
                echo htmlspecialchars($line) . "<br>";
            }
        }
        echo "</div>";
    } else {
        echo "<p class='success'>✅ No grievance-related errors in recent logs</p>";
    }
    
    // Show recent errors
    echo "<div class='code'>";
    echo "<strong>Recent log entries (last 1000 chars):</strong><br>";
    echo htmlspecialchars(substr($recentLogs, -1000));
    echo "</div>";
} else {
    echo "<p class='info'>ℹ️ No Laravel log file found</p>";
}

echo "</div>";

// Step 7: Quick Fixes
echo "<div class='card'>";
echo "<h3>🔧 Quick Fixes</h3>";

echo "<div class='code'>";
echo "<strong>If you see specific errors above, try these fixes:</strong><br><br>";

echo "<strong>1. Model/Controller Issues:</strong><br>";
echo "php artisan optimize<br>";
echo "php artisan clear-compiled<br>";
echo "composer dump-autoload<br><br>";

echo "<strong>2. Database Issues:</strong><br>";
echo "php artisan migrate:fresh<br>";
echo "php artisan db:seed<br><br>";

echo "<strong>3. Cache Issues:</strong><br>";
echo "php artisan cache:clear<br>";
echo "php artisan config:clear<br>";
echo "php artisan view:clear<br>";
echo "php artisan route:clear<br><br>";

echo "<strong>4. Permission Issues:</strong><br>";
echo "chmod -R 755 storage/<br>";
echo "chmod -R 755 bootstrap/cache/<br><br>";

echo "<strong>5. Complete Reset:</strong><br>";
echo "php artisan migrate:reset<br>";
echo "php artisan migrate<br>";
echo "</div>";

echo "</div>";

// Step 8: Alternative Solution
echo "<div class='card'>";
echo "<h3>🚀 Alternative Solution</h3>";
echo "<p>If Laravel continues to fail, try this standalone grievance system:</p>";
echo "<a href='/hrms/standalone_grievances.php' style='background:#28a745;color:white;padding:15px;text-decoration:none;border-radius:5px;display:inline-block;'>Try Standalone Grievance System</a>";
echo "</div>";

?>
