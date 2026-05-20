<?php

namespace App\Exports;

use App\Models\Payroll;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PayrollProcessExport implements FromCollection, WithHeadings
{
    protected int $creatorId;
    protected ?string $month;
    protected ?int $employeeId;
    protected ?string $status;

    public function __construct(int $creatorId, ?string $month = null, ?int $employeeId = null, ?string $status = null)
    {
        $this->creatorId = $creatorId;
        $this->month = $month ?: null;
        $this->employeeId = $employeeId ?: null;
        $this->status = $status ?: null;
    }

    public function collection()
    {
        $payrollRows = Payroll::query()
            ->where('created_by', $this->creatorId)
            ->with(['employee.branch', 'employee.department', 'employee.designation'])
            ->when($this->month, function ($query) {
                $query->where('month', $this->month);
            })
            ->when($this->employeeId, function ($query) {
                $query->where('employee_id', $this->employeeId);
            })
            ->when($this->status === 'processed', function ($query) {
                $query->where('is_locked', 1);
            })
            ->when($this->status === 'draft', function ($query) {
                $query->where('is_locked', 0);
            })
            ->orderByDesc('month')
            ->orderBy('employee_id')
            ->get();

        return $payrollRows->map(function (Payroll $row) {
            return [
                'employee_code' => $row->employee->employee_id ?? '',
                'employee_name' => $row->employee->name ?? '',
                'branch' => $row->employee->branch->name ?? '',
                'department' => $row->employee->department->name ?? '',
                'designation' => $row->employee->designation->name ?? '',
                'month' => date('F Y', strtotime($row->month . '-01')),
                'gross_salary' => (float) $row->gross_salary,
                'total_deductions' => (float) $row->total_deductions,
                'employer_contribution' => (float) $row->employer_contribution,
                'net_salary' => (float) $row->net_salary,
                'status' => $row->is_locked ? 'Processed' : 'Draft',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Employee Code',
            'Employee Name',
            'Branch',
            'Department',
            'Designation',
            'Month',
            'Gross Salary',
            'Total Deductions',
            'Employer Contribution',
            'Net Salary',
            'Status',
        ];
    }
}
