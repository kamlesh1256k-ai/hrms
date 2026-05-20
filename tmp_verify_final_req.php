<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$emps = App\Models\Employee::where('created_by',6)->orderBy('id')->get(['id','name','company_doj','is_active']);
$active = $emps->where('is_active',1);
echo "TOTAL=".$emps->count()." ACTIVE=".$active->count()."\n";
foreach($emps as $e){ echo $e->id.'|'.$e->name.'|doj='.$e->company_doj.'|active='.$e->is_active."\n"; }
