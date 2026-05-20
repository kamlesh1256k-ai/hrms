<?php

namespace Database\Seeders;

use App\Models\AttendanceEmployee;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AttendanceMonthSeeder extends Seeder
{
    public function run(): void
    {
        $start = Carbon::now()->startOfMonth();
        $end = Carbon::now()->endOfMonth();

        $employees = Employee::query()->select('id', 'created_by')->get();

        $created = 0;
        $skipped = 0;

        foreach ($employees as $employee) {
            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                $dateString = $date->toDateString();

                $exists = AttendanceEmployee::query()
                    ->where('employee_id', $employee->id)
                    ->where('date', $dateString)
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                $seed = abs(crc32($employee->id . '-' . $dateString));
                $mod = $seed % 10;

                $status = 'Present';
                $clockIn = '09:10:00';
                $clockOut = '18:00:00';
                $late = '00:00:00';
                $lateMark = 0;
                $earlyLeaving = '00:00:00';
                $earlyMark = 0;
                $overtime = '00:00:00';
                $lessHoursMark = 0;
                $deductionUnits = 0.0;
                $totalRest = '01:00:00';

                if ($mod === 0) {
                    $status = 'Half Day';
                    $clockIn = '09:35:00';
                    $clockOut = '13:30:00';
                    $lessHoursMark = 1;
                    $deductionUnits = 0.5;
                    $totalRest = '00:30:00';
                } elseif ($mod === 1 || $mod === 2) {
                    $status = 'Present';
                    $clockIn = '10:20:00';
                    $clockOut = '18:30:00';
                    $late = '01:20:00';
                    $lateMark = 1;
                }

                AttendanceEmployee::query()->create([
                    'employee_id' => (int) $employee->id,
                    'date' => $dateString,
                    'status' => $status,
                    'clock_in' => $clockIn,
                    'clock_out' => $clockOut,
                    'late' => $late,
                    'late_mark' => $lateMark,
                    'early_leaving' => $earlyLeaving,
                    'early_mark' => $earlyMark,
                    'overtime' => $overtime,
                    'less_hours_mark' => $lessHoursMark,
                    'total_rest' => $totalRest,
                    'deduction_units' => $deductionUnits,
                    'created_by' => (int) ($employee->created_by ?? 1),
                ]);

                $created++;
            }
        }

        $this->command?->info('Attendance month seeding completed. Employees=' . $employees->count() . ', Created=' . $created . ', SkippedExisting=' . $skipped);
    }
}
