<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$emps = App\Models\Employee::whereIn('name',['Vikram Singh','Rohan Mehta','Priya Sharma','Anjali Verma','Mohit Gupta'])->orderBy('id')->get();
foreach($emps as $e){
  $sal = App\Models\EmployeeSalary::where('employee_id',$e->id)->first();
  $minDate = App\Models\AttendanceEmployee::where('employee_id',$e->id)->min('date');
  $maxDate = App\Models\AttendanceEmployee::where('employee_id',$e->id)->max('date');
  $cnt = App\Models\AttendanceEmployee::where('employee_id',$e->id)->count();
  echo $e->id.'|'.$e->name.'|'.$e->present_city.'|branch='.$e->branch_id.'|ctc='.(($sal->ctc ?? 0)).'|attn='.$cnt.'|'.$minDate.'->'.$maxDate.PHP_EOL;
}
