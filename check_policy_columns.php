<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Schema;

echo "Checking Policy Columns\n";
echo "=======================\n\n";

// Check attendance_employees table
echo "attendance_employees table:\n";
$attendanceColumns = Schema::getColumnListing('attendance_employees');
$requiredAttendance = ['late_mark', 'early_mark', 'less_hours_mark', 'deduction_units'];

foreach ($requiredAttendance as $col) {
    $exists = in_array($col, $attendanceColumns) ? "✓ EXISTS" : "✗ MISSING";
    echo "  $col: $exists\n";
}

echo "\n";

// Check leave_types table
echo "leave_types table:\n";
$leaveColumns = Schema::getColumnListing('leave_types');
$requiredLeave = ['monthly_credit', 'annual_credit', 'approval_requirement'];

foreach ($requiredLeave as $col) {
    $exists = in_array($col, $leaveColumns) ? "✓ EXISTS" : "✗ MISSING";
    echo "  $col: $exists\n";
}

echo "\n";
