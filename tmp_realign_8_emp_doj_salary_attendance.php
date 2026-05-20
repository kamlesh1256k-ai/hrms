<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Employee;
use App\Models\EmployeeSalary;
use App\Models\AttendanceEmployee;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

DB::beginTransaction();
try {
    $creatorId = 6;
    $all = Employee::where('created_by', $creatorId)->orderBy('id')->get();
    if ($all->count() < 8) {
        throw new Exception('At least 8 employees are required.');
    }

    $selected = $all->take(8)->values();
    $extra = $all->slice(8);

    // Keep exactly 8 active employees
    if ($extra->isNotEmpty()) {
        Employee::whereIn('id', $extra->pluck('id')->all())->update(['is_active' => 0]);
    }
    Employee::whereIn('id', $selected->pluck('id')->all())->update(['is_active' => 1]);

    // Unique DOJ months/days; only one employee at 2025-04-01
    $dojMap = [
        '2025-04-01',
        '2024-05-12',
        '2024-06-18',
        '2024-07-09',
        '2024-08-23',
        '2024-09-14',
        '2024-10-07',
        '2024-11-19',
    ];

    $salaryPool = [1600000, 2000000, 3500000, 1600000, 2000000, 3500000];
    $salaryIdx = 0;

    foreach ($selected as $i => $emp) {
        $newDoj = $dojMap[$i];
        Employee::where('id', $emp->id)->update(['company_doj' => $newDoj]);

        $name = strtolower(trim((string)$emp->name));
        $isProtected = in_array($name, ['sapna', 'soniyaaa'], true);

        $sal = EmployeeSalary::firstOrCreate(
            ['employee_id' => $emp->id],
            [
                'ctc' => 1200000,
                'basic_percentage' => 50,
                'is_pf_enabled' => 1,
                'is_esic_enabled' => 1,
                'structure_id' => 1,
            ]
        );

        $updateData = [
            'overtime_enabled' => 1,
            'overtime_formula' => ($i % 2 === 0) ? 'basic' : 'gross',
        ];

        if (!$isProtected) {
            $updateData['ctc'] = $salaryPool[$salaryIdx % count($salaryPool)];
            $salaryIdx++;
        }

        EmployeeSalary::where('employee_id', $emp->id)->update($updateData);
    }

    // Attendance upload range: Apr 2025 to Mar 2026
    $from = Carbon::parse('2025-04-01');
    $to = Carbon::parse('2026-03-31');

    foreach ($selected as $emp) {
        $rows = 0;
        for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
            if ((int)$d->dayOfWeek === 0) {
                continue; // Sunday skip
            }

            $status = 'Present';
            $clockIn = '09:05:00';
            $clockOut = '18:05:00';
            $late = '00:05:00';
            $early = '00:00:00';
            $ot = '00:00:00';
            $lateMark = 0;
            $earlyMark = 0;
            $lessHours = 0;
            $du = 0.0;

            if ($d->day % 19 === 0) {
                $status = 'Absent';
                $clockIn = '00:00:00';
                $clockOut = '00:00:00';
                $late = '00:00:00';
                $early = '00:00:00';
                $du = 1.0;
            } elseif ($d->day % 13 === 0) {
                $status = 'Half Day';
                $clockIn = '09:20:00';
                $clockOut = '13:45:00';
                $late = '00:20:00';
                $early = '04:15:00';
                $lateMark = 1;
                $earlyMark = 1;
                $lessHours = 1;
                $du = 0.5;
                $ot = '00:30:00';
            } else {
                if ($d->day % 5 === 0) {
                    $ot = '02:00:00';
                } elseif ($d->day % 3 === 0) {
                    $ot = '01:15:00';
                }
                if ($d->day % 7 === 0) {
                    $late = '00:25:00';
                    $lateMark = 1;
                    $clockIn = '09:25:00';
                }
            }

            $daysDiff = Carbon::parse($emp->company_doj ?: '2024-01-01')->diffInDays($d);
            $monthsAt = (int) floor($daysDiff / 30);
            $yearsAt = (int) floor($monthsAt / 12);

            AttendanceEmployee::updateOrCreate(
                ['employee_id' => $emp->id, 'date' => $d->toDateString()],
                [
                    'status' => $status,
                    'clock_in' => $clockIn,
                    'clock_out' => $clockOut,
                    'late' => $late,
                    'late_mark' => $lateMark,
                    'early_leaving' => $early,
                    'early_mark' => $earlyMark,
                    'overtime' => $ot,
                    'less_hours_mark' => $lessHours,
                    'total_rest' => '01:00:00',
                    'deduction_units' => $du,
                    'created_by' => $creatorId,
                    'device_type' => 'web',
                    'latitude' => '28.6139',
                    'longitude' => '77.2090',
                    'address' => $emp->present_city . ', ' . $emp->present_state,
                    'photo_verified' => 1,
                    'device_type_out' => 'web',
                    'latitude_out' => '28.6139',
                    'longitude_out' => '77.2090',
                    'address_out' => $emp->present_city . ', ' . $emp->present_state,
                    'photo_out_verified' => 1,
                    'professional_days_at_attendance' => $daysDiff,
                    'professional_months_at_attendance' => $monthsAt,
                    'professional_years_at_attendance' => $yearsAt,
                    'in_probation_at_attendance' => 0,
                ]
            );
            $rows++;
        }
    }

    DB::commit();

    echo "DONE\n";
    foreach ($selected as $emp) {
        $sal = EmployeeSalary::where('employee_id', $emp->id)->first();
        $cnt = AttendanceEmployee::where('employee_id', $emp->id)->whereBetween('date', ['2025-04-01','2026-03-31'])->count();
        echo $emp->id.'|'.$emp->name.'|doj='.$emp->fresh()->company_doj.'|ctc='.(float)($sal->ctc ?? 0).'|ot='.(int)($sal->overtime_enabled ?? 0).'|formula='.($sal->overtime_formula ?? 'na').'|att='.$cnt."\n";
    }
} catch (\Throwable $e) {
    DB::rollBack();
    echo 'ERROR: '.$e->getMessage()."\n";
}
