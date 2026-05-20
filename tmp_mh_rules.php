<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$stateId = App\Models\State::where('state_name','Maharashtra')->value('id');
$compId = App\Models\StatutoryComponent::where('code','PT')->value('id');
$rules = App\Models\StatutoryRule::where('component_id',$compId)->where('state_id',$stateId)->orderBy('created_by')->orderBy('min_salary')->get(['id','min_salary','max_salary','employee_value','applicable_gender','effective_from','created_by']);
foreach($rules as $r){ echo json_encode($r).PHP_EOL; }
