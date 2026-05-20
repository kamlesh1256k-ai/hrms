<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$emps = App\Models\Employee::select('id','name','present_state','present_city')->orderBy('id')->get();
foreach ($emps as $e) {
    echo $e->id.'|'.$e->name.'|'.$e->present_state.'|'.$e->present_city.PHP_EOL;
}

$calc = app(App\Services\StatutoryCalculator::class);
foreach ($emps as $e) {
    $pt = $calc->calculatePT(20000, null, strtolower((string)($e->gender ?? 'male')), '2026-03', (int)$e->id);
    echo 'PT@20000 '.$e->name.'='.$pt['employee'].PHP_EOL;
}
