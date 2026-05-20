<?php
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use App\Models\AttendanceEmployee;
use App\Models\AttendanceSetting;

Artisan::command('attendance:reset-late-count', function () {
    $month = now()->month;
    $year = now()->year;
    $employees = AttendanceEmployee::select('employee_id')->distinct()->get();
    foreach ($employees as $employee) {
        AttendanceEmployee::where('employee_id', $employee->employee_id)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->update(['late_mark' => 0]);
    }
    $this->info('Late count reset for all employees for current month.');
});
