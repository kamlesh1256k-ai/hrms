<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Employee;
use App\Models\EmployeeSalary;
use App\Models\AttendanceEmployee;
use App\Models\SalaryStructure;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Designation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

DB::beginTransaction();
try {
    $creatorId = 6;
    $departmentId = (int)(Department::where('created_by', $creatorId)->value('id') ?? 2);
    $designationId = (int)(Designation::where('created_by', $creatorId)->value('id') ?? 1);
    $structureId = (int)(SalaryStructure::where('created_by', $creatorId)->value('id') ?? 1);

    $branchMap = [
        'Kolkata' => (int)(Branch::where('created_by', $creatorId)->where('name', 'Kolkata Branch')->value('id') ?? 8),
        'Mumbai' => (int)(Branch::where('created_by', $creatorId)->where('name', 'Mumbai Branch')->value('id') ?? 8),
        'Bangalore' => (int)(Branch::where('created_by', $creatorId)->where('name', 'Bangalore Branch')->value('id') ?? 8),
        'Delhi' => (int)(Branch::where('created_by', $creatorId)->where('name', 'Delhi Branch')->value('id') ?? 8),
    ];

    $employeesData = [
        [
            'name' => 'Vikram Singh',
            'email' => 'dummy.vikram.'.time().'@example.com',
            'gender' => 'male',
            'phone' => '9000001001',
            'city' => 'Bangalore', 'state' => 'Karnataka', 'country' => 'India',
            'doj' => '2025-01-03',
            'monthly_salary' => 30000,
        ],
        [
            'name' => 'Rohan Mehta',
            'email' => 'dummy.rohan.'.time().'@example.com',
            'gender' => 'male',
            'phone' => '9000001002',
            'city' => 'Kolkata', 'state' => 'West Bengal', 'country' => 'India',
            'doj' => '2025-01-05',
            'monthly_salary' => 25000,
        ],
        [
            'name' => 'Priya Sharma',
            'email' => 'dummy.priya.'.time().'@example.com',
            'gender' => 'female',
            'phone' => '9000001003',
            'city' => 'Mumbai', 'state' => 'Maharashtra', 'country' => 'India',
            'doj' => '2025-01-10',
            'monthly_salary' => 35000,
        ],
        [
            'name' => 'Anjali Verma',
            'email' => 'dummy.anjali.'.time().'@example.com',
            'gender' => 'female',
            'phone' => '9000001004',
            'city' => 'Delhi', 'state' => 'Delhi', 'country' => 'India',
            'doj' => '2025-02-15',
            'monthly_salary' => 25000,
        ],
        [
            'name' => 'Mohit Gupta',
            'email' => 'dummy.mohit.'.time().'@example.com',
            'gender' => 'male',
            'phone' => '9000001005',
            'city' => 'Delhi', 'state' => 'Delhi', 'country' => 'India',
            'doj' => '2025-02-20',
            'monthly_salary' => 30000,
        ],
    ];

    $fromDate = Carbon::parse('2025-03-01')->startOfDay();
    $toDate = Carbon::now()->startOfDay();

    $created = [];

    foreach ($employeesData as $idx => $row) {
        $user = User::create([
            'name' => $row['name'],
            'email' => $row['email'],
            'password' => Hash::make('12345678'),
            'type' => 'employee',
            'lang' => 'en',
            'created_by' => $creatorId,
            'email_verified_at' => now(),
        ]);

        try { $user->assignRole('Employee'); } catch (\Throwable $e) { /* ignore role errors */ }

        $empCode = 'EMPD' . str_pad((string)($user->id), 6, '0', STR_PAD_LEFT);

        $employee = Employee::create([
            'user_id' => $user->id,
            'name' => $row['name'],
            'dob' => '1998-01-01',
            'gender' => $row['gender'],
            'phone' => $row['phone'],
            'address' => $row['city'] . ', ' . $row['state'],
            'present_address' => $row['city'] . ', ' . $row['state'],
            'permanent_address' => $row['city'] . ', ' . $row['state'],
            'present_country' => $row['country'],
            'present_state' => $row['state'],
            'present_city' => $row['city'],
            'permanent_country' => $row['country'],
            'permanent_state' => $row['state'],
            'permanent_city' => $row['city'],
            'email' => $row['email'],
            'password' => '12345678',
            'employee_id' => $empCode,
            'branch_id' => (int)($branchMap[$row['city']] ?? $branchMap['Delhi']),
            'department_id' => $departmentId,
            'designation_id' => $designationId,
            'company_doj' => $row['doj'],
            'salary' => (float)$row['monthly_salary'],
            'created_by' => $creatorId,
        ]);

        EmployeeSalary::updateOrCreate(
            ['employee_id' => $employee->id],
            [
                'ctc' => (float)$row['monthly_salary'] * 12,
                'basic_percentage' => 50,
                'is_pf_enabled' => 1,
                'is_esic_enabled' => 1,
                'structure_id' => $structureId,
            ]
        );

        $joinDate = Carbon::parse($row['doj'])->startOfDay();
        $start = $fromDate->copy()->greaterThan($joinDate) ? $fromDate->copy() : $joinDate;

        $attendanceCount = 0;
        for ($date = $start->copy(); $date->lte($toDate); $date->addDay()) {
            // Sundays as weekly-off: skip row
            if ((int)$date->dayOfWeek === 0) {
                continue;
            }

            $status = 'Present';
            $clockIn = '09:05:00';
            $clockOut = '18:00:00';
            $late = '00:05:00';
            $early = '00:00:00';
            $overtime = '00:00:00';
            $lessHours = false;
            $lateMark = false;
            $earlyMark = false;
            $deductionUnits = 0.0;

            if ($date->day % 17 === 0) {
                $status = 'Absent';
                $clockIn = '00:00:00';
                $clockOut = '00:00:00';
                $late = '00:00:00';
                $early = '00:00:00';
                $deductionUnits = 1.0;
            } elseif ($date->day % 11 === 0) {
                $status = 'Half Day';
                $clockIn = '09:10:00';
                $clockOut = '13:30:00';
                $late = '00:10:00';
                $early = '04:30:00';
                $lateMark = true;
                $earlyMark = true;
                $lessHours = true;
                $deductionUnits = 0.5;
            } elseif ($date->day % 7 === 0) {
                $clockIn = '09:25:00';
                $clockOut = '18:05:00';
                $late = '00:25:00';
                $lateMark = true;
            }

            $daysDiff = Carbon::parse($row['doj'])->diffInDays($date);
            $monthsAt = (int) floor($daysDiff / 30);
            $yearsAt = (int) floor($monthsAt / 12);

            AttendanceEmployee::updateOrCreate(
                ['employee_id' => $employee->id, 'date' => $date->toDateString()],
                [
                    'status' => $status,
                    'clock_in' => $clockIn,
                    'clock_out' => $clockOut,
                    'late' => $late,
                    'late_mark' => $lateMark,
                    'early_leaving' => $early,
                    'early_mark' => $earlyMark,
                    'overtime' => $overtime,
                    'less_hours_mark' => $lessHours,
                    'total_rest' => '01:00:00',
                    'deduction_units' => $deductionUnits,
                    'created_by' => $creatorId,
                    'device_type' => 'web',
                    'latitude' => '26.9124',
                    'longitude' => '75.7873',
                    'address' => $row['city'] . ', ' . $row['state'],
                    'photo_verified' => 1,
                    'device_type_out' => 'web',
                    'latitude_out' => '26.9124',
                    'longitude_out' => '75.7873',
                    'address_out' => $row['city'] . ', ' . $row['state'],
                    'photo_out_verified' => 1,
                    'professional_days_at_attendance' => $daysDiff,
                    'professional_months_at_attendance' => $monthsAt,
                    'professional_years_at_attendance' => $yearsAt,
                    'in_probation_at_attendance' => 0,
                ]
            );
            $attendanceCount++;
        }

        $created[] = [
            'employee_id' => $employee->id,
            'name' => $employee->name,
            'email' => $row['email'],
            'doj' => $row['doj'],
            'monthly_salary' => $row['monthly_salary'],
            'attendance_rows' => $attendanceCount,
        ];
    }

    DB::commit();

    echo "CREATED_OK\n";
    foreach ($created as $c) {
        echo $c['employee_id'].'|'.$c['name'].'|'.$c['email'].'|doj='.$c['doj'].'|salary='.$c['monthly_salary'].'|attn='.$c['attendance_rows']."\n";
    }
} catch (\Throwable $e) {
    DB::rollBack();
    echo "ERROR: ".$e->getMessage()."\n";
}
