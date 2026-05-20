<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "<h2>URL Diagnostic</h2>";
echo "<p><b>env('APP_URL'):</b> " . env('APP_URL') . "</p>";
echo "<p><b>config('app.url'):</b> " . config('app.url') . "</p>";
echo "<p><b>config('filesystems.disks.local.url'):</b> " . config('filesystems.disks.local.url') . "</p>";
echo "<p><b>Storage::url('uploads/logo/logo-dark.png'):</b> " . Illuminate\Support\Facades\Storage::url('uploads/logo/logo-dark.png') . "</p>";
echo "<hr><b>Raw .env contents:</b><pre>" . htmlspecialchars(file_get_contents(__DIR__ . '/.env')) . "</pre>";
