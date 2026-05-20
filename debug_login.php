<?php
// Force error display
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/php_error.log');

// Force HTTPS off in $_SERVER to break redirect loop
$_SERVER['HTTPS'] = 'on';
$_SERVER['REQUEST_SCHEME'] = 'https';
$_SERVER['SERVER_PORT'] = 443;

echo "<h2>Debug Login - Bypassing redirect</h2>";
echo "<p>Server: " . ($_SERVER['HTTP_HOST'] ?? 'unknown') . "</p>";
echo "<p>HTTPS: " . ($_SERVER['HTTPS'] ?? 'no') . "</p>";

// Simulate /login request
$_SERVER['REQUEST_URI'] = '/login';
$_SERVER['PATH_INFO'] = '/login';

try {
    define('LARAVEL_START', microtime(true));
    require __DIR__ . '/vendor/autoload.php';
    $app = require_once __DIR__ . '/bootstrap/app.php';

    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $request = Illuminate\Http\Request::create('/login', 'GET');
    $request->server->set('HTTPS', 'on');
    $request->server->set('HTTP_HOST', 'jemini.co.in');

    $response = $kernel->handle($request);

    echo "<h3>Status: " . $response->getStatusCode() . "</h3>";
    echo "<h3>Headers:</h3><pre>";
    foreach ($response->headers->all() as $k => $v) {
        echo "$k: " . implode(', ', $v) . "\n";
    }
    echo "</pre>";

    if ($response->getStatusCode() >= 300 && $response->getStatusCode() < 400) {
        echo "<h3 style='color:red'>REDIRECT detected to: " . $response->headers->get('Location') . "</h3>";
    } else {
        echo "<h3>Body preview (first 2000 chars):</h3>";
        echo "<pre>" . htmlspecialchars(substr($response->getContent(), 0, 2000)) . "</pre>";
    }

    $kernel->terminate($request, $response);
} catch (\Throwable $e) {
    echo "<h2 style='color:red'>EXCEPTION:</h2>";
    echo "<p><strong>" . get_class($e) . ":</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>File: " . $e->getFile() . ":" . $e->getLine() . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

// Show storage logs path
echo "<hr><h3>Log file check:</h3>";
$logPath = __DIR__ . '/storage/logs/laravel.log';
echo "<p>Path: $logPath</p>";
echo "<p>Exists: " . (file_exists($logPath) ? 'YES' : 'NO') . "</p>";
echo "<p>Writable: " . (is_writable($logPath) ? 'YES' : 'NO') . "</p>";
echo "<p>Size: " . (file_exists($logPath) ? filesize($logPath) : 'N/A') . " bytes</p>";
echo "<p>Storage dir writable: " . (is_writable(__DIR__ . '/storage') ? 'YES' : 'NO') . "</p>";
echo "<p>Logs dir writable: " . (is_writable(__DIR__ . '/storage/logs') ? 'YES' : 'NO') . "</p>";
