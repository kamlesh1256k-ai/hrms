<?php

namespace App\Exports;

use App\Models\AttendanceEmployee;
use App\Models\Employee;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MonthlyAttendanceExport implements FromArray, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    protected int $creatorId;
    protected string $month;
    protected ?int $branchId;
    protected ?int $departmentId;

    public function __construct(int $creatorId, string $month, ?int $branchId = null, ?int $departmentId = null)
    {
        $this->creatorId = $creatorId;
        $this->month = $month;
        $this->branchId = $branchId;
        $this->departmentId = $departmentId;
    }

    public function array(): array
    {
        $monthStart = $this->month . '-01';
        $monthEnd = date('Y-m-t', strtotime($monthStart));
        $numDays = (int) date('t', strtotime($monthStart));

        $query = Employee::where('created_by', $this->creatorId)->orderBy('name');
        if ($this->branchId) {
            $query->where('branch_id', $this->branchId);
        }
        if ($this->departmentId) {
            $query->where('department_id', $this->departmentId);
        }
        $employees = $query->get();

        // Fetch all attendance records for the month in one query
        $empIds = $employees->pluck('id')->toArray();
        $allAttendance = AttendanceEmployee::whereIn('employee_id', $empIds)
            ->whereBetween('date', [$monthStart, $monthEnd])
            ->get()
            ->groupBy('employee_id');

        $rows = [];

        foreach ($employees as $emp) {
            $empAttendance = $allAttendance->get($emp->id, collect());
            $attendanceByDate = $empAttendance->keyBy('date');

            $row = [
                'employee_id' => $emp->employee_id ?? $emp->id,
                'name' => $emp->name,
                'department' => $emp->department->name ?? '-',
                'designation' => $emp->designation->name ?? '-',
            ];

            $presentCount = 0;
            $absentCount = 0;
            $leaveCount = 0;
            $halfDayCount = 0;
            $totalOtMinutes = 0;
            $lateCount = 0;
            $earlyCount = 0;

            for ($d = 1; $d <= $numDays; $d++) {
                $dateStr = $this->month . '-' . str_pad($d, 2, '0', STR_PAD_LEFT);
                $rec = $attendanceByDate->get($dateStr);

                if (!$rec) {
                    $row['day_' . $d] = '-';
                    continue;
                }

                $status = $rec->status;
                $clockIn = $rec->clock_in ?? '00:00:00';
                $clockOut = $rec->clock_out ?? '00:00:00';
                $statusCode = $this->statusCode($status);

                // Build cell: status + times
                if ($statusCode === 'P' || $statusCode === 'HD') {
                    $inTime = ($clockIn !== '00:00:00') ? substr($clockIn, 0, 5) : '';
                    $outTime = ($clockOut !== '00:00:00') ? substr($clockOut, 0, 5) : '';
                    $row['day_' . $d] = $statusCode . ($inTime ? " ({$inTime}-{$outTime})" : '');
                } else {
                    $row['day_' . $d] = $statusCode;
                }

                // Counters
                switch ($statusCode) {
                    case 'P': $presentCount++; break;
                    case 'A': $absentCount++; break;
                    case 'L': $leaveCount++; break;
                    case 'HD': $halfDayCount++; break;
                }

                $lateCount += (int) ($rec->late_mark ?? 0);
                $earlyCount += (int) ($rec->early_mark ?? 0);

                // OT
                $ot = (string) ($rec->overtime ?? '00:00:00');
                if ($ot !== '00:00:00' && $ot !== '') {
                    $parts = explode(':', $ot);
                    $totalOtMinutes += ((int) ($parts[0] ?? 0)) * 60 + (int) ($parts[1] ?? 0);
                }
            }

            // Summary columns
            $otHours = floor($totalOtMinutes / 60);
            $otMins = $totalOtMinutes % 60;
            $row['total_present'] = $presentCount;
            $row['total_absent'] = $absentCount;
            $row['total_leave'] = $leaveCount;
            $row['total_half_day'] = $halfDayCount;
            $row['late_marks'] = $lateCount;
            $row['early_marks'] = $earlyCount;
            $row['total_ot'] = ($otHours > 0 || $otMins > 0) ? "{$otHours}h {$otMins}m" : '0';

            $rows[] = $row;
        }

        return $rows;
    }

    public function headings(): array
    {
        $numDays = (int) date('t', strtotime($this->month . '-01'));

        $heads = ['Emp ID', 'Name', 'Department', 'Designation'];
        for ($d = 1; $d <= $numDays; $d++) {
            $heads[] = $d;
        }
        $heads = array_merge($heads, ['Present', 'Absent', 'Leave', 'Half Day', 'Late', 'Early', 'OT']);

        return $heads;
    }

    public function title(): string
    {
        return date('M Y', strtotime($this->month . '-01'));
    }

    public function styles(Worksheet $sheet): array
    {
        $numDays = (int) date('t', strtotime($this->month . '-01'));
        $lastCol = $numDays + 4 + 7; // 4 info cols + days + 7 summary cols

        return [
            1 => [
                'font' => ['bold' => true, 'size' => 11],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E2E8F0'],
                ],
            ],
        ];
    }

    private function statusCode(string $status): string
    {
        $s = strtolower($status);
        if ($s === 'present') return 'P';
        if ($s === 'absent') return 'A';
        if ($s === 'leave') return 'L';
        if ($s === 'half day') return 'HD';
        return substr($status, 0, 2);
    }
}
