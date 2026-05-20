<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$calc = app(App\Services\StatutoryCalculator::class);
$emps = App\Models\Employee::select('id','name','gender','present_state')->orderBy('id')->get();
foreach ($emps as $e) {
    $gender = strtolower((string)($e->gender ?: 'male'));
    $ptMar = $calc->calculatePT(20000, null, $gender, '2026-03', (int)$e->id);
    $ptFeb = $calc->calculatePT(20000, null, $gender, '2026-02', (int)$e->id);
    echo $e->name.'|'.$e->present_state.'|PT-Mar='.$ptMar['employee'].'|PT-Feb='.$ptFeb['employee'].PHP_EOL;
}
