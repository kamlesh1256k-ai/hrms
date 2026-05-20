<?php
require 'vendor/autoload.php';
$app = require_once('bootstrap/app.php');
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Schema;

echo "Checking database schema...\n";
echo "compensatory_leaves table: " . (Schema::hasTable('compensatory_leaves') ? "✓ EXISTS\n" : "✗ MISSING\n");
echo "leaves.is_compensatory column: " . (Schema::hasColumn('leaves', 'is_compensatory') ? "✓ EXISTS\n" : "✗ MISSING\n");
echo "leaves.compensatory_leave_id column: " . (Schema::hasColumn('leaves', 'compensatory_leave_id') ? "✓ EXISTS\n" : "✗ MISSING\n");
echo "leaves.medical_certificate column: " . (Schema::hasColumn('leaves', 'medical_certificate') ? "✓ EXISTS\n" : "✗ MISSING\n");
?>
