<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\AttendanceEmployee;
use Carbon\Carbon;

class AttendanceRealisticSeeder extends Seeder
{
    public function run()
    {
        $createdBy = 6;
        $endDate = Carbon::parse('2026-03-31');

        $employees = Employee::all();

        // Each employee gets a unique personality for attendance patterns
        $profiles = [
            1 => ['late_pct' => 12, 'leave_pct' => 5,  'half_pct' => 3, 'absent_pct' => 2, 'early_pct' => 4,  'ot_pct' => 4,  'in_base' => '09:00', 'out_base' => '18:00'],
            2 => ['late_pct' => 18, 'leave_pct' => 6,  'half_pct' => 4, 'absent_pct' => 3, 'early_pct' => 5,  'ot_pct' => 4,  'in_base' => '09:15', 'out_base' => '18:00'],
            3 => ['late_pct' => 8,  'leave_pct' => 4,  'half_pct' => 2, 'absent_pct' => 1, 'early_pct' => 3,  'ot_pct' => 7,  'in_base' => '08:50', 'out_base' => '18:10'],
            4 => ['late_pct' => 20, 'leave_pct' => 7,  'half_pct' => 5, 'absent_pct' => 4, 'early_pct' => 6,  'ot_pct' => 3,  'in_base' => '09:30', 'out_base' => '17:50'],
            5 => ['late_pct' => 6,  'leave_pct' => 4,  'half_pct' => 2, 'absent_pct' => 1, 'early_pct' => 2,  'ot_pct' => 7,  'in_base' => '08:55', 'out_base' => '18:15'],
            6 => ['late_pct' => 15, 'leave_pct' => 5,  'half_pct' => 3, 'absent_pct' => 2, 'early_pct' => 5,  'ot_pct' => 5,  'in_base' => '09:10', 'out_base' => '18:05'],
            7 => ['late_pct' => 10, 'leave_pct' => 5,  'half_pct' => 3, 'absent_pct' => 2, 'early_pct' => 3,  'ot_pct' => 5,  'in_base' => '09:05', 'out_base' => '18:00'],
            8 => ['late_pct' => 5,  'leave_pct' => 3,  'half_pct' => 2, 'absent_pct' => 1, 'early_pct' => 2,  'ot_pct' => 7,  'in_base' => '08:45', 'out_base' => '18:20'],
            9 => ['late_pct' => 17, 'leave_pct' => 8,  'half_pct' => 5, 'absent_pct' => 3, 'early_pct' => 5,  'ot_pct' => 4,  'in_base' => '09:20', 'out_base' => '17:55'],
            10=> ['late_pct' => 13, 'leave_pct' => 5,  'half_pct' => 3, 'absent_pct' => 2, 'early_pct' => 4,  'ot_pct' => 5,  'in_base' => '09:00', 'out_base' => '18:00'],
        ];

        $totalInserted = 0;
        $bulkData = [];

        foreach ($employees as $emp) {
            $p = $profiles[$emp->id] ?? $profiles[1];
            $doj = Carbon::parse($emp->company_doj);
            $current = $doj->copy();

            // Cumulative thresholds out of 100
            $t1 = $p['late_pct'];
            $t2 = $t1 + $p['leave_pct'];
            $t3 = $t2 + $p['half_pct'];
            $t4 = $t3 + $p['absent_pct'];
            $t5 = $t4 + $p['early_pct'];
            $t6 = $t5 + $p['ot_pct'];

            while ($current->lte($endDate)) {
                // Skip Sundays
                if ($current->dayOfWeek === Carbon::SUNDAY) {
                    $current->addDay();
                    continue;
                }

                $roll = mt_rand(1, 100);

                $status = 'Present';
                $clockIn = $p['in_base'] . ':00';
                $clockOut = $p['out_base'] . ':00';
                $late = '00:00:00';
                $lateMark = 0;
                $earlyLeaving = '00:00:00';
                $earlyMark = 0;
                $overtime = '00:00:00';

                if ($roll <= $t1) {
                    // Late arrival
                    $lateMin = mt_rand(5, 45);
                    $clockIn = Carbon::parse($p['in_base'])->addMinutes($lateMin)->format('H:i:s');
                    $late = gmdate('H:i:s', $lateMin * 60);
                    $lateMark = 1;
                } elseif ($roll <= $t2) {
                    // Leave
                    $status = 'Leave';
                    $clockIn = '00:00:00';
                    $clockOut = '00:00:00';
                } elseif ($roll <= $t3) {
                    // Half Day
                    $status = 'Half Day';
                    $clockOut = '13:' . str_pad(mt_rand(0, 30), 2, '0', STR_PAD_LEFT) . ':00';
                    $earlyLeaving = '04:30:00';
                    $earlyMark = 1;
                } elseif ($roll <= $t4) {
                    // Absent
                    $status = 'Absent';
                    $clockIn = '00:00:00';
                    $clockOut = '00:00:00';
                } elseif ($roll <= $t5) {
                    // Early leaving
                    $earlyMin = mt_rand(15, 60);
                    $clockOut = Carbon::parse($p['out_base'])->subMinutes($earlyMin)->format('H:i:s');
                    $earlyLeaving = gmdate('H:i:s', $earlyMin * 60);
                    $earlyMark = 1;
                } elseif ($roll <= $t6) {
                    // Overtime
                    $otMin = mt_rand(30, 120);
                    $clockOut = Carbon::parse($p['out_base'])->addMinutes($otMin)->format('H:i:s');
                    $overtime = gmdate('H:i:s', $otMin * 60);
                } else {
                    // Normal present with slight time variation
                    $varIn = mt_rand(-5, 5);
                    $varOut = mt_rand(-5, 5);
                    $clockIn = Carbon::parse($p['in_base'])->addMinutes($varIn)->format('H:i:s');
                    $clockOut = Carbon::parse($p['out_base'])->addMinutes($varOut)->format('H:i:s');
                }

                // Professional period calculation
                $profDays = $doj->diffInDays($current);
                $profMonths = $doj->diffInMonths($current) % 12;
                $profYears = (int) $doj->diffInYears($current);
                $inProbation = $profDays <= 180 ? 1 : 0;

                $bulkData[] = [
                    'employee_id' => $emp->id,
                    'date' => $current->format('Y-m-d'),
                    'status' => $status,
                    'clock_in' => $clockIn,
                    'clock_out' => $clockOut,
                    'late' => $late,
                    'late_mark' => $lateMark,
                    'early_leaving' => $earlyLeaving,
                    'early_mark' => $earlyMark,
                    'overtime' => $overtime,
                    'total_rest' => '00:00:00',
                    'deduction_units' => 0,
                    'created_by' => $createdBy,
                    'professional_days_at_attendance' => $profDays,
                    'professional_months_at_attendance' => $profMonths,
                    'professional_years_at_attendance' => $profYears,
                    'in_probation_at_attendance' => $inProbation,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if (count($bulkData) >= 500) {
                    AttendanceEmployee::insert($bulkData);
                    $totalInserted += count($bulkData);
                    $bulkData = [];
                }

                $current->addDay();
            }
        }

        if (!empty($bulkData)) {
            AttendanceEmployee::insert($bulkData);
            $totalInserted += count($bulkData);
        }

        echo "Total attendance records inserted: {$totalInserted}\n";

        // Summary per employee
        foreach ($employees as $emp) {
            $counts = AttendanceEmployee::where('employee_id', $emp->id)
                ->selectRaw('status, count(*) as cnt')
                ->groupBy('status')
                ->pluck('cnt', 'status')
                ->toArray();
            echo "{$emp->name} (DOJ: {$emp->company_doj}): " . json_encode($counts) . "\n";
        }
    }
}
