<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Migration Status\n";
echo "================\n\n";

// Check which migrations have been run
$migrations = DB::table('migrations')->orderBy('batch', 'desc')->orderBy('id', 'desc')->limit(10)->get();

echo "Last 10 migrations:\n";
foreach ($migrations as $migration) {
    echo "  [{$migration->batch}] {$migration->migration}\n";
}

echo "\n";

// Check if our migrations exist
$ourMigrations = [
    '2026_02_13_000001_add_leave_policy_columns_to_leave_types_table',
    '2026_02_13_000002_add_policy_columns_to_attendance_employees_table'
];

echo "Our policy migrations:\n";
foreach ($ourMigrations as $name) {
    $exists = DB::table('migrations')->where('migration', $name)->exists();
    $status = $exists ? "✓ RUN" : "✗ PENDING";
    echo "  $status: $name\n";
}
