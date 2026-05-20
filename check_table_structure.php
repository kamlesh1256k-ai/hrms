<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Table Structure Check\n";
echo "=====================\n\n";

echo "attendance_employees columns:\n";
$columns = DB::select("SHOW COLUMNS FROM attendance_employees");
$columnNames = array_map(function($col) {
    return $col->Field;
}, $columns);

$requiredColumns = ['late_mark', 'early_mark', 'less_hours_mark', 'deduction_units'];

echo "Looking for policy columns:\n";
foreach ($requiredColumns as $req) {
    $found = in_array($req, $columnNames) ? "✓ FOUND" : "✗ MISSING";
    echo "  $req: $found\n";
}

echo "\nAll columns in table:\n";
foreach ($columnNames as $name) {
    echo "  - $name\n";
}
