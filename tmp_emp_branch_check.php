<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$rows = App\Models\Employee::select('id','name','present_state','present_city','branch_id','created_by')->orderBy('id')->get();
foreach($rows as $r){ echo $r->id.'|'.$r->name.'|'.$r->present_state.'|'.$r->present_city.'|branch='.$r->branch_id.'|cb='.$r->created_by.PHP_EOL; }
