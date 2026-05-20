<?php

require_once 'vendor/autoload.php';
require_once 'bootstrap/app.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Check if columns exist
$columns = Schema::getColumnListing('attendance_employees');

$searchColumns = [
    'device_type_out',
    'latitude_out',
    'longitude_out',
    'address_out',
    'photo_out'
];

echo "Checking attendance_employees table columns:\n";
echo "===========================================\n";

foreach ($searchColumns as $col) {
    $exists = in_array($col, $columns) ? "✓ EXISTS" : "✗ MISSING";
    echo "$col: $exists\n";
}

echo "\n\nAll columns: " . implode(", ", $columns) . "\n";
