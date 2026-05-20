<?php

namespace App\Exports;

use App\Models\Payroll;
use App\Models\EmployeeSalary;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class PayrollDetailedExport implements FromArray, WithHeadings, WithTitle, WithStyles, ShouldAutoSize, WithEvents
{
    protected int $creatorId;
    protected ?string $month;
    protected ?int $employeeId;
    protected ?string $status;
    protected ?string $year;

    // Discovered component names
    protected array $earningNames = [];
    protected array $statutoryDeductionNames = [];
    protected array $employerContribNames = [];
    protected int $rowCount = 0;
    protected bool $discovered = false;

    // Column index tracking for styling
    protected int $earningStartCol = 0;
    protected int $earningEndCol = 0;
    protected int $grossCol = 0;
    protected int $dedStartCol = 0;
    protected int $dedEndCol = 0;
    protected int $totalDedCol = 0;
    protected int $netPayCol = 0;
    protected int $empContribStartCol = 0;
    protected int $empContribEndCol = 0;
    protected int $totalEmpCostCol = 0;

    public function __construct(int $creatorId, ?string $year = null, ?string $month = null, ?int $employeeId = null, ?string $status = null)
    {
        $this->creatorId = $creatorId;
        $this->year = $year ?: null;
        $this->month = $month ?: null;
        $this->employeeId = $employeeId ?: null;
        $this->status = $status ?: null;
    }

    protected ?\Illuminate\Support\Collection $cachedPayrolls = null;

    /**
     * Fetch payrolls and discover all component names.
     * Called by both array() and headings() to ensure headers are populated.
     */
    protected function discoverComponents(): \Illuminate\Support\Collection
    {
        if ($this->discovered) {
            return $this->cachedPayrolls ?? collect();
        }

        $query = Payroll::query()
            ->where('created_by', $this->creatorId)
            ->with(['employee.branch', 'employee.department', 'employee.designation']);

        if ($this->year && $this->month) {
            $query->where('month', $this->year . '-' . $this->month);
        } elseif ($this->year) {
            $query->where('month', 'like', $this->year . '-%');
        } elseif ($this->month) {
            $query->where('month', 'like', '%-' . $this->month);
        }

        if ($this->employeeId) {
            $query->where('employee_id', $this->employeeId);
        }
        if ($this->status === 'processed') {
            $query->where('is_locked', 1);
        } elseif ($this->status === 'draft') {
            $query->where('is_locked', 0);
        }

        $payrolls = $query->orderBy('month')->orderBy('employee_id')->get();
        $this->cachedPayrolls = $payrolls;

        // Pull ALL salary components from master table
        $masterComponents = \App\Models\SalaryComponent::where('created_by', $this->creatorId)
            ->orderBy('type')->orderBy('name')->get();

        $masterEarnings = [];
        $masterDeductions = [];
        $masterEmployer = [];
        foreach ($masterComponents as $comp) {
            $name = trim((string) $comp->name);
            $type = strtolower(trim((string) ($comp->type ?? '')));

            if (strtolower($name) === 'gross') continue;

            if ($type === 'earning') {
                $normalized = $this->normalizeEarningName($name);
                if (!in_array($normalized, $masterEarnings)) $masterEarnings[] = $normalized;
            } elseif ($type === 'deduction') {
                $normalized = $this->normalizeDeductionName($name);
                if ($normalized !== '__ATTENDANCE__' && !in_array($normalized, $masterDeductions)) $masterDeductions[] = $normalized;
            } elseif ($type === 'employer') {
                $normalized = $this->normalizeEmployerName($name);
                if (!in_array($normalized, $masterEmployer)) $masterEmployer[] = $normalized;
            }
        }

        // Also discover from actual payroll JSON (catches runtime-computed items)
        foreach ($payrolls as $row) {
            foreach (($row->earnings_json ?? []) as $item) {
                $name = $this->normalizeEarningName(trim((string) ($item['name'] ?? '')));
                if ($name !== '' && !in_array($name, $masterEarnings)) {
                    $masterEarnings[] = $name;
                }
            }

            foreach (($row->deductions_json ?? []) as $item) {
                $name = $this->normalizeDeductionName(trim((string) ($item['name'] ?? '')));
                if ($name === '' || $name === '__ATTENDANCE__' || stripos($name, 'employer') !== false) continue;
                if (!in_array($name, $masterDeductions)) {
                    $masterDeductions[] = $name;
                }
            }

            foreach (($row->benefits_json ?? []) as $item) {
                $name = $this->normalizeEmployerName(trim((string) ($item['name'] ?? '')));
                if ($name !== '' && !in_array($name, $masterEmployer)) {
                    $masterEmployer[] = $name;
                }
            }

            $gratuity = (float) ($row->statutory_json['gratuity'] ?? 0);
            if ($gratuity > 0 && !in_array('Gratuity', $masterEmployer)) {
                $masterEmployer[] = 'Gratuity';
            }
        }

        $this->earningNames = $this->sortEarnings($masterEarnings);
        $this->statutoryDeductionNames = $this->sortDeductions($masterDeductions);
        $this->employerContribNames = $this->sortEmployerContribs($masterEmployer);
        $this->discovered = true;

        return $payrolls;
    }

    public function array(): array
    {
        $payrolls = $this->discoverComponents();

        if ($payrolls->isEmpty()) {
            return [];
        }

        // ── Build rows ──
        $rows = [];
        foreach ($payrolls as $row) {
            $attn = $row->statutory_json['attendance'] ?? [];
            $monthCalendarDays = (int) ($attn['month_calendar_days'] ?? date('t', strtotime(($row->month ?? '2026-01') . '-01')));
            $paidDays = (float) ($attn['paid_days'] ?? $monthCalendarDays);

            $r = [];

            // ── S.No ──
            $r[] = count($rows) + 1;

            // ── Employee Info ──
            $emp = $row->employee;
            $r[] = $emp->employee_id ?? $row->employee_id;
            $r[] = $emp->name ?? '';
            $r[] = !empty($emp->company_doj) ? date('d-m-Y', strtotime($emp->company_doj)) : '';
            $r[] = trim(($emp->present_city ?? '') . ($emp->present_city && $emp->present_state ? ', ' : '') . ($emp->present_state ?? '')) ?: '-';
            $r[] = $emp->department->name ?? '';
            $r[] = $emp->designation->name ?? '';
            $r[] = date('F Y', strtotime($row->month . '-01'));

            // ── Attendance Summary ──
            $r[] = (int) ($attn['month_total_days'] ?? 0);
            $r[] = round($paidDays, 1);
            $r[] = round((float) ($attn['present_effective'] ?? ($attn['present'] ?? 0)), 1);
            $r[] = round((float) ($attn['leave_effective'] ?? ($attn['leave'] ?? 0)), 1);
            $r[] = round((float) ($attn['absent_effective'] ?? ($attn['absent'] ?? 0)), 1);
            $r[] = round((float) ($attn['hd_deduction'] ?? 0), 1);
            $r[] = (int) ($attn['weekly_offs'] ?? 0);
            $r[] = round((float) ($attn['overtime_hours'] ?? 0), 2);

            // ── CTC ──
            $empSalary = EmployeeSalary::where('employee_id', $row->employee_id)->first();
            $r[] = $empSalary ? round((float) $empSalary->ctc, 2) : 0;

            // ── EARNINGS (each component → Monthly + Earned/Paid) ──
            // Build map: name => ['monthly' => x, 'earned' => y]
            $earningsMap = [];
            foreach (($row->earnings_json ?? []) as $item) {
                $name = $this->normalizeEarningName(trim((string) ($item['name'] ?? '')));
                if ($name === '') continue;
                $annual = round((float) ($item['amount'] ?? 0), 2);
                $isOneTime = (($item['frequency'] ?? 'monthly') === 'one-time');
                $monthly = $isOneTime ? $annual : round($annual / 12, 2);
                $paid = $isOneTime ? $monthly : ($monthCalendarDays > 0 ? round(($monthly / $monthCalendarDays) * $paidDays, 2) : $monthly);
                if (!isset($earningsMap[$name])) {
                    $earningsMap[$name] = ['monthly' => 0, 'earned' => 0];
                }
                $earningsMap[$name]['monthly'] += $monthly;
                $earningsMap[$name]['earned'] += $paid;
            }

            $totalMonthly = 0;
            $totalEarned = 0;
            foreach ($this->earningNames as $ename) {
                $m = round($earningsMap[$ename]['monthly'] ?? 0, 2);
                $e = round($earningsMap[$ename]['earned'] ?? 0, 2);
                $r[] = $m;
                $r[] = $e;
                $totalMonthly += $m;
                $totalEarned += $e;
            }
            // Total Gross Earnings (Monthly + Earned)
            $r[] = round($totalMonthly, 2);
            $r[] = round($totalEarned, 2);

            // ── DEDUCTIONS (statutory only — attendance deductions already in Earned via pro-rata) ──
            $deductionsMap = [];
            foreach (($row->deductions_json ?? []) as $item) {
                $name = $this->normalizeDeductionName(trim((string) ($item['name'] ?? '')));
                if ($name === '' || $name === '__ATTENDANCE__' || stripos($name, 'employer') !== false) continue;
                $deductionsMap[$name] = ($deductionsMap[$name] ?? 0) + round((float) ($item['amount'] ?? 0), 2);
            }

            $totalDed = 0;
            foreach ($this->statutoryDeductionNames as $dname) {
                $amt = round($deductionsMap[$dname] ?? 0, 2);
                $r[] = $amt;
                $totalDed += $amt;
            }
            // Total Deductions
            $r[] = round($totalDed, 2);

            // ── NET PAY = Total Gross (Earned) − Statutory Deductions ──
            $r[] = round($totalEarned - $totalDed, 2);

            // ── EMPLOYER CONTRIBUTIONS ──
            $benefitsMap = [];
            foreach (($row->benefits_json ?? []) as $item) {
                $name = $this->normalizeEmployerName(trim((string) ($item['name'] ?? '')));
                if ($name !== '') {
                    $benefitsMap[$name] = ($benefitsMap[$name] ?? 0) + round((float) ($item['amount'] ?? 0), 2);
                }
            }
            // Gratuity from statutory_json (may not be in benefits_json)
            $statJson = $row->statutory_json ?? [];
            if (empty($benefitsMap['Gratuity'])) {
                $gratuityVal = (float) ($statJson['gratuity'] ?? 0);
                if ($gratuityVal > 0) {
                    $benefitsMap['Gratuity'] = round($gratuityVal, 2);
                }
            }
            // EPF Employer fallback from statutory
            if (empty($benefitsMap['EPF Employer']) && !empty($statJson['epf_employer'])) {
                $benefitsMap['EPF Employer'] = round((float) $statJson['epf_employer'], 2);
            }

            $totalBen = 0;
            foreach ($this->employerContribNames as $bname) {
                $amt = round($benefitsMap[$bname] ?? 0, 2);
                $r[] = $amt;
                $totalBen += $amt;
            }
            // Total Employer Cost
            $r[] = round($totalBen, 2);

            // ── CTC (monthly) ──
            $r[] = round((float) $row->gross_salary + $totalBen, 2);

            $rows[] = $r;
        }

        $this->rowCount = count($rows);

        // Calculate column indices for styling
        // Earnings: 2 cols per component + 2 total cols (Monthly + Earned)
        $infoCount = 17; // S.No + emp info (6+DOJ+Location) + attendance (8) + CTC
        $earningColsCount = count($this->earningNames) * 2; // Monthly + Earned per component
        $this->earningStartCol = $infoCount + 1;
        $this->earningEndCol = $this->earningStartCol + $earningColsCount - 1;
        $this->grossCol = $this->earningEndCol + 1; // TOTAL GROSS (Monthly)
        // grossCol + 1 = TOTAL GROSS (Earned)
        $this->dedStartCol = $this->grossCol + 2;
        $this->dedEndCol = $this->dedStartCol + count($this->statutoryDeductionNames) - 1;
        $this->totalDedCol = $this->dedEndCol + 1;
        $this->netPayCol = $this->totalDedCol + 1;
        $this->empContribStartCol = $this->netPayCol + 1;
        $this->empContribEndCol = $this->empContribStartCol + count($this->employerContribNames) - 1;
        $this->totalEmpCostCol = $this->empContribEndCol + 1;

        return $rows;
    }

    public function headings(): array
    {
        // Ensure component names are discovered before building headers
        $this->discoverComponents();

        $h = [
            'S.No',
            'Emp Code', 'Employee Name', 'Date of Joining', 'Location', 'Department', 'Designation', 'Month',
            // Attendance
            'Days in Month', 'Paid Days', 'Present', 'Leave', 'Absent', 'HD Ded', 'W/Off', 'OT Hrs',
            // CTC
            'Annual CTC',
        ];

        // ── Earnings (2 cols each: Monthly + Earned) ──
        foreach ($this->earningNames as $name) {
            $h[] = $name . ' (Monthly)';
            $h[] = $name . ' (Earned)';
        }
        $h[] = 'TOTAL GROSS (Monthly)';
        $h[] = 'TOTAL GROSS (Earned)';

        // ── Deductions ──
        foreach ($this->statutoryDeductionNames as $name) {
            $h[] = $name;
        }
        $h[] = 'TOTAL DEDUCTIONS';

        // ── Net Pay ──
        $h[] = 'NET PAY';

        // ── Employer Contributions ──
        foreach ($this->employerContribNames as $name) {
            $h[] = $name;
        }
        $h[] = 'TOTAL EMPLOYER COST';

        // CTC Monthly
        $h[] = 'CTC (Monthly)';

        return $h;
    }

    public function title(): string
    {
        $label = 'Salary Statement';
        if ($this->year && $this->month) {
            $label .= ' ' . date('M Y', strtotime($this->year . '-' . $this->month . '-01'));
        } elseif ($this->year) {
            $label .= ' ' . $this->year;
        }
        return substr($label, 0, 31);
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = $this->rowCount + 1;
        $lastColLetter = $sheet->getHighestColumn();

        return [
            // Header row: dark background, white bold text
            1 => [
                'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1E293B'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = $this->rowCount + 1;
                $lastCol = $sheet->getHighestColumn();

                // Freeze header row
                $sheet->freezePane('A2');

                // Number format for currency columns (earnings, deductions, net pay, employer)
                $currencyStartCol = $this->colLetter($this->earningStartCol);
                $currencyEndCol = $lastCol;
                if ($lastRow > 1) {
                    $sheet->getStyle("{$currencyStartCol}2:{$currencyEndCol}{$lastRow}")
                        ->getNumberFormat()
                        ->setFormatCode('#,##0.00');
                }

                // CTC column (last info col before earnings)
                $ctcCol = $this->colLetter($this->earningStartCol - 1);
                if ($lastRow > 1) {
                    $sheet->getStyle("{$ctcCol}2:{$ctcCol}{$lastRow}")
                        ->getNumberFormat()
                        ->setFormatCode('#,##0.00');
                }

                // ── Section coloring on header ──

                // Earnings section: green header (includes both TOTAL GROSS cols)
                if (count($this->earningNames) > 0) {
                    $eStart = $this->colLetter($this->earningStartCol);
                    $eEnd = $this->colLetter($this->grossCol + 1); // +1 for TOTAL GROSS (Earned)
                    $sheet->getStyle("{$eStart}1:{$eEnd}1")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '166534']],
                    ]);
                }

                // Deductions section: red header
                if (count($this->statutoryDeductionNames) > 0) {
                    $dStart = $this->colLetter($this->dedStartCol);
                    $dEnd = $this->colLetter($this->totalDedCol);
                    $sheet->getStyle("{$dStart}1:{$dEnd}1")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '991B1B']],
                    ]);
                }

                // Net Pay: dark blue header
                $npCol = $this->colLetter($this->netPayCol);
                $sheet->getStyle("{$npCol}1")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E40AF']],
                    'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
                ]);

                // Net Pay data: bold
                if ($lastRow > 1) {
                    $sheet->getStyle("{$npCol}2:{$npCol}{$lastRow}")->getFont()->setBold(true);
                }

                // Employer Contributions: blue header
                if (count($this->employerContribNames) > 0) {
                    $bStart = $this->colLetter($this->empContribStartCol);
                    $bEnd = $this->colLetter($this->totalEmpCostCol);
                    $sheet->getStyle("{$bStart}1:{$bEnd}1")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A5F']],
                    ]);
                }

                // Total columns: bold in data rows
                $totalCols = [$this->grossCol, $this->grossCol + 1, $this->totalDedCol, $this->totalEmpCostCol];
                foreach ($totalCols as $tc) {
                    $tcl = $this->colLetter($tc);
                    if ($lastRow > 1) {
                        $sheet->getStyle("{$tcl}2:{$tcl}{$lastRow}")->getFont()->setBold(true);
                    }
                }

                // Borders for all data
                $sheet->getStyle("A1:{$lastCol}{$lastRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'D1D5DB'],
                        ],
                    ],
                ]);
            },
        ];
    }

    // ── Helpers ──

    private function normalizeEarningName(string $name): string
    {
        if ($name === '') return '';
        $lower = strtolower($name);

        // Normalize duplicates: master may have "House Rent Allowance" but payroll stores "HRA"
        if ($lower === 'house rent allowance') return 'HRA';
        if ($lower === 'conveyance') return 'Conveyance Allowance';
        if ($lower === 'medical') return 'Medical Allowance';
        if ($lower === 'overtime allowance') return 'Overtime';

        return $name;
    }

    private function isAttendanceDeduction(string $name): bool
    {
        $lower = strtolower($name);
        return str_contains($lower, 'absent') || str_contains($lower, 'half day')
            || str_contains($lower, 'late/early') || str_contains($lower, 'early ½')
            || str_contains($lower, 'early half') || str_contains($lower, 'leave deduction');
    }

    private function normalizeDeductionName(string $name): string
    {
        if ($name === '') return '';
        $lower = strtolower($name);

        // Attendance deductions are excluded (already in Earned via pro-rata)
        // Return a marker so they can be filtered out
        if ($this->isAttendanceDeduction($name)) {
            return '__ATTENDANCE__';
        }

        // EPF normalization
        if (str_contains($lower, 'epf') && str_contains($lower, 'employee') || str_contains($lower, 'epf contribution') || str_contains($lower, 'pf employee')) {
            return 'EPF Employee';
        }
        if (str_contains($lower, 'esic') && str_contains($lower, 'employee')) {
            return 'ESIC Employee';
        }
        if (str_contains($lower, 'professional tax')) {
            return 'Professional Tax';
        }
        if (str_contains($lower, 'lwf') && str_contains($lower, 'employee')) {
            return 'LWF Employee';
        }
        if (str_contains($lower, 'tds on additional') || str_contains($lower, 'tds on arrears')) {
            return 'TDS on Additional Earnings';
        }
        if (str_contains($lower, 'tds') || str_contains($lower, 'income tax')) {
            return 'TDS / Income Tax';
        }

        return $name;
    }

    private function sortEarnings(array $names): array
    {
        $order = [
            'Basic', 'House Rent Allowance', 'HRA',
            'Dearness Allowance', 'Conveyance', 'Conveyance Allowance',
            'Transport Allowance', 'Travelling Allowance',
            'Medical', 'Medical Allowance',
            'Children Education Allowance', 'City Compensatory Allowance',
            'Special Allowance', 'Fixed Allowance',
            'Other Allowance', 'Leave Encashment',
            'Overtime Allowance', 'Overtime',
            'Commission', 'Bonus',
            'Salary Arrears',
        ];
        $sorted = [];
        foreach ($order as $o) {
            if (in_array($o, $names)) {
                $sorted[] = $o;
            }
        }
        foreach ($names as $n) {
            if (!in_array($n, $sorted)) {
                $sorted[] = $n;
            }
        }
        return $sorted;
    }

    private function sortDeductions(array $names): array
    {
        $order = [
            'Professional Tax', 'EPF Employee', 'PF Employee',
            'ESIC Employee', 'LWF Employee', 'TDS / Income Tax', 'TDS on Additional Earnings', 'Notice Pay',
        ];
        $sorted = [];
        foreach ($order as $o) {
            if (in_array($o, $names)) $sorted[] = $o;
        }
        foreach ($names as $n) {
            if (!in_array($n, $sorted)) $sorted[] = $n;
        }
        return $sorted;
    }

    private function normalizeEmployerName(string $name): string
    {
        if ($name === '') return '';
        $lower = strtolower($name);

        // Normalize PF Employer / EPF Employer → EPF Employer
        if ((str_contains($lower, 'pf') || str_contains($lower, 'epf')) && str_contains($lower, 'employer')) {
            return 'EPF Employer';
        }
        if (str_contains($lower, 'esic') && str_contains($lower, 'employer')) {
            return 'ESIC Employer';
        }
        if (str_contains($lower, 'lwf') && str_contains($lower, 'employer')) {
            return 'LWF Employer';
        }
        if ($lower === 'gratuity' || str_contains($lower, 'gratuity')) {
            return 'Gratuity';
        }

        return $name;
    }

    private function sortEmployerContribs(array $names): array
    {
        $order = ['EPF Employer', 'PF Employer', 'ESIC Employer', 'Gratuity', 'LWF Employer'];
        $sorted = [];
        foreach ($order as $o) {
            if (in_array($o, $names)) $sorted[] = $o;
        }
        foreach ($names as $n) {
            if (!in_array($n, $sorted)) $sorted[] = $n;
        }
        return $sorted;
    }

    private function colLetter(int $colNum): string
    {
        $letter = '';
        while ($colNum > 0) {
            $mod = ($colNum - 1) % 26;
            $letter = chr(65 + $mod) . $letter;
            $colNum = (int) (($colNum - $mod) / 26);
        }
        return $letter;
    }
}
