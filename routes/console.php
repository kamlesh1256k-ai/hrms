<?php

use App\Models\AttendanceEmployee;
use App\Models\Utility;
use Carbon\Carbon;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('attendance:recalculate-latest {--scope=latest} {--month=}', function () {
    $settings = Utility::settings();

    $graceLateMinutes = (int) ($settings['attendance_grace_late_minutes'] ?? 0);
    $graceEarlyMinutes = (int) ($settings['attendance_grace_early_minutes'] ?? 0);
    $companyStart = (string) ($settings['company_start_time'] ?? '09:00:00');
    $companyEnd = (string) ($settings['company_end_time'] ?? '18:00:00');
    $requiredWorkHours = 10;
    $halfDayDelayMinutes = 60;
    $scope = strtolower((string) $this->option('scope'));

    if (!in_array($scope, ['latest', 'month'])) {
        $this->error('Invalid --scope. Use latest or month.');
        return;
    }

    $monthInput = (string) ($this->option('month') ?: now()->format('Y-m'));
    try {
        $monthStart = Carbon::createFromFormat('Y-m', $monthInput)->startOfMonth()->toDateString();
        $monthEnd = Carbon::createFromFormat('Y-m', $monthInput)->endOfMonth()->toDateString();
    } catch (\Throwable $th) {
        $this->error('Invalid --month format. Use YYYY-MM (example: 2026-02).');
        return;
    }

    $baseQuery = AttendanceEmployee::query()
        ->whereNotNull('clock_in')
        ->whereNotNull('clock_out')
        ->where('clock_in', '!=', '00:00:00')
        ->where('clock_out', '!=', '00:00:00');

    if ($scope === 'month') {
        $baseQuery->whereBetween('date', [$monthStart, $monthEnd]);
    }

    $employeeIds = (clone $baseQuery)
        ->distinct()
        ->pluck('employee_id');

    $totalUsers = $employeeIds->count();
    $updatedRows = 0;
    $halfDayByDelayCount = 0;
    $tenPlusHoursCount = 0;
    $earlyAttendanceCount = 0;
    $onTimeCount = 0;
    $totalDelayMinutes = 0;

    if ($scope === 'month') {
        $records = (clone $baseQuery)
            ->orderBy('employee_id')
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        foreach ($records as $record) {
            try {
                $clockInTime = Carbon::parse($record->date . ' ' . $record->clock_in);
                $clockOutTime = Carbon::parse($record->date . ' ' . $record->clock_out);
                $startTime = Carbon::parse($record->date . ' ' . $companyStart);
                $endTime = Carbon::parse($record->date . ' ' . $companyEnd);

                if ($endTime->lessThanOrEqualTo($startTime)) {
                    $endTime->addDay();
                    if ($clockOutTime->lessThanOrEqualTo($clockInTime)) {
                        $clockOutTime->addDay();
                    }
                }

                $lateSeconds = max(0, $clockInTime->diffInSeconds($startTime, false) * -1);
                $earlySeconds = max(0, $endTime->diffInSeconds($clockOutTime, false) * -1);
                $workSeconds = max(0, $clockOutTime->diffInSeconds($clockInTime));
                $overtimeSeconds = $clockOutTime->gt($endTime) ? $clockOutTime->diffInSeconds($endTime) : 0;

                $delayMinutes = (int) floor($lateSeconds / 60);
                $totalDelayMinutes += $delayMinutes;

                $isEarlyAttendance = $clockInTime->lessThanOrEqualTo($startTime);
                $isTenPlusHours = $workSeconds >= ($requiredWorkHours * 3600);
                $isHalfDayByDelay = $delayMinutes >= $halfDayDelayMinutes;

                if ($isEarlyAttendance) {
                    $earlyAttendanceCount++;
                }
                if ($delayMinutes === 0) {
                    $onTimeCount++;
                }
                if ($isTenPlusHours) {
                    $tenPlusHoursCount++;
                }
                if ($isHalfDayByDelay) {
                    $halfDayByDelayCount++;
                }

                $lateMark = $delayMinutes > $graceLateMinutes;
                $earlyMark = ((int) floor($earlySeconds / 60)) > $graceEarlyMinutes;
                $lessHoursMark = !$isTenPlusHours;
                $status = $isHalfDayByDelay ? 'Half Day' : 'Present';

                $record->status = $status;
                $record->late = gmdate('H:i:s', $lateSeconds);
                $record->early_leaving = gmdate('H:i:s', $earlySeconds);
                $record->overtime = gmdate('H:i:s', $overtimeSeconds);
                $record->late_mark = $lateMark;
                $record->early_mark = $earlyMark;
                $record->less_hours_mark = $lessHoursMark;
                $record->deduction_units = $isHalfDayByDelay ? 0.5 : 0.0;
                $record->save();

                $updatedRows++;
            } catch (\Throwable $th) {
                continue;
            }
        }
    } else {
        foreach ($employeeIds as $employeeId) {
            $latest = AttendanceEmployee::query()
                ->where('employee_id', $employeeId)
                ->whereNotNull('clock_in')
                ->whereNotNull('clock_out')
                ->where('clock_in', '!=', '00:00:00')
                ->where('clock_out', '!=', '00:00:00')
                ->orderByDesc('date')
                ->orderByDesc('id')
                ->first();

            if (empty($latest)) {
                continue;
            }

            try {
                $clockInTime = Carbon::parse($latest->date . ' ' . $latest->clock_in);
                $clockOutTime = Carbon::parse($latest->date . ' ' . $latest->clock_out);
            $startTime = Carbon::parse($latest->date . ' ' . $companyStart);
            $endTime = Carbon::parse($latest->date . ' ' . $companyEnd);

            if ($endTime->lessThanOrEqualTo($startTime)) {
                $endTime->addDay();
                if ($clockOutTime->lessThanOrEqualTo($clockInTime)) {
                    $clockOutTime->addDay();
                }
            }

            $lateSeconds = max(0, $clockInTime->diffInSeconds($startTime, false) * -1);
            $earlySeconds = max(0, $endTime->diffInSeconds($clockOutTime, false) * -1);
            $workSeconds = max(0, $clockOutTime->diffInSeconds($clockInTime));
            $overtimeSeconds = $clockOutTime->gt($endTime) ? $clockOutTime->diffInSeconds($endTime) : 0;

            $delayMinutes = (int) floor($lateSeconds / 60);
            $totalDelayMinutes += $delayMinutes;

            $isEarlyAttendance = $clockInTime->lessThanOrEqualTo($startTime);
            $isTenPlusHours = $workSeconds >= ($requiredWorkHours * 3600);
            $isHalfDayByDelay = $delayMinutes >= $halfDayDelayMinutes;

            if ($isEarlyAttendance) {
                $earlyAttendanceCount++;
            }
            if ($delayMinutes === 0) {
                $onTimeCount++;
            }
            if ($isTenPlusHours) {
                $tenPlusHoursCount++;
            }
            if ($isHalfDayByDelay) {
                $halfDayByDelayCount++;
            }

            $lateMark = $delayMinutes > $graceLateMinutes;
            $earlyMark = ((int) floor($earlySeconds / 60)) > $graceEarlyMinutes;
            $lessHoursMark = !$isTenPlusHours;
            $status = $isHalfDayByDelay ? 'Half Day' : 'Present';

            $latest->status = $status;
            $latest->late = gmdate('H:i:s', $lateSeconds);
            $latest->early_leaving = gmdate('H:i:s', $earlySeconds);
            $latest->overtime = gmdate('H:i:s', $overtimeSeconds);
            $latest->late_mark = $lateMark;
            $latest->early_mark = $earlyMark;
            $latest->less_hours_mark = $lessHoursMark;
            $latest->deduction_units = $isHalfDayByDelay ? 0.5 : 0.0;
            $latest->save();

                $updatedRows++;
            } catch (\Throwable $th) {
                continue;
            }
        }
    }

    $averageDelayMinutes = $updatedRows > 0 ? round($totalDelayMinutes / $updatedRows, 2) : 0;

    $analytics = [
        'scope' => $scope,
        'month' => $scope === 'month' ? $monthInput : null,
        'processed_users' => $totalUsers,
        'updated_latest_records' => $updatedRows,
        'ten_plus_working_hours' => $tenPlusHoursCount,
        'half_day_due_to_1h_delay' => $halfDayByDelayCount,
        'early_attendance_count' => $earlyAttendanceCount,
        'on_time_login_count' => $onTimeCount,
        'average_login_delay_minutes' => $averageDelayMinutes,
        'rules' => [
            'required_work_hours_for_full' => 10,
            'half_day_delay_minutes' => 60,
            'late_grace_minutes' => $graceLateMinutes,
            'early_exit_grace_minutes' => $graceEarlyMinutes,
        ],
    ];

    $this->info($scope === 'month'
        ? 'Attendance month records updated successfully.'
        : 'Attendance latest records updated successfully.');
    $this->line(json_encode($analytics, JSON_PRETTY_PRINT));
})->purpose('Recalculate attendance records using 10+ hours and 1-hour delay rules and print analytics.');
