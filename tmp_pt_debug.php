<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$calc = app(App\Services\StatutoryCalculator::class);
$stateId = App\Models\State::where('state_name','West Bengal')->value('id');
$pt1 = $calc->calculatePT(20000, $stateId, 'male', '2026-03', 1);
$pt2 = $calc->calculatePT(20000, null, 'male', '2026-03', 1);
var_dump($stateId,$pt1,$pt2);
