<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$rows = App\Models\Branch::select('id','name','country','state','city','created_by')->orderBy('created_by')->orderBy('id')->get();
echo "BRANCHES".PHP_EOL;
foreach($rows as $r){ echo $r->id.'|'.$r->name.'|'.$r->country.'|'.$r->state.'|'.$r->city.'|cb='.$r->created_by.PHP_EOL; }

echo PHP_EOL."EMPLOYEES".PHP_EOL;
$emps = App\Models\Employee::with('branch')->select('id','name','present_state','present_city','branch_id','created_by')->orderBy('id')->get();
foreach($emps as $e){
  $bname = optional($e->branch)->name;
  echo $e->id.'|'.$e->name.'|'.$e->present_state.'|'.$e->present_city.'|branch='.$e->branch_id.'('.$bname.')|cb='.$e->created_by.PHP_EOL;
}
