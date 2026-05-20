<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$rows = App\Models\Branch::select('id','name','country','state','city','created_by')->orderBy('id')->get();
foreach($rows as $r){ echo $r->id.'|'.$r->name.'|'.$r->country.'|'.$r->state.'|'.$r->city.'|'.$r->created_by.PHP_EOL; }
