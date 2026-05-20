<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

$base = __DIR__;

try {
    define('LARAVEL_START', microtime(true));
    require $base . '/vendor/autoload.php';

    // Load .env manually
    $dotenv = Dotenv\Dotenv::createImmutable($base);
    $dotenv->load();

    $app = require_once $base . '/bootstrap/app.php';

    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $response = $kernel->handle(
        $request = Illuminate\Http\Request::capture()
    );
    $response->send();
    $kernel->terminate($request, $response);

} catch (\Throwable $e) {
    echo "<h2 style='color:red'>ERROR:</h2>";
    echo "<p><strong>" . get_class($e) . ":</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre style='background:#f1f1f1;padding:1rem;font-size:12px'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
