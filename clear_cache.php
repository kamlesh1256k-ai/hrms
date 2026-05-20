<?php
chdir(__DIR__);

echo "<h2>Environment Check</h2>";

// Read .env directly
$env = file_get_contents(__DIR__ . '/.env');
preg_match('/DB_DATABASE=(.+)/', $env, $m);
echo "<p>.env DB_DATABASE: " . trim($m[1] ?? 'NOT FOUND') . "</p>";
preg_match('/DB_USERNAME=(.+)/', $env, $m);
echo "<p>.env DB_USERNAME: " . trim($m[1] ?? 'NOT FOUND') . "</p>";

echo "<h2>Running Commands...</h2>";

// Reset OPcache
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "<p>OPcache reset ✓</p>";
}

$out = shell_exec('php artisan config:clear 2>&1');
echo "<p>Config clear: $out</p>";

$out = shell_exec('php artisan view:clear 2>&1');
echo "<p>View clear: $out</p>";

// Force delete compiled view files
$viewDir = __DIR__ . '/storage/framework/views';
if (is_dir($viewDir)) {
    $files = glob($viewDir . '/*.php');
    $deleted = 0;
    foreach ($files as $f) {
        if (unlink($f)) $deleted++;
    }
    echo "<p>Deleted $deleted compiled view files from $viewDir</p>";
}

$out = shell_exec('php artisan storage:link 2>&1');
echo "<p>Storage link: $out</p>";

$out = shell_exec('php artisan migrate --force 2>&1');
echo "<pre>Migration: $out</pre>";

$out = shell_exec('php artisan db:seed --force 2>&1');
echo "<pre>Seed: $out</pre>";

echo "<p style='color:green'>Done!</p>";
