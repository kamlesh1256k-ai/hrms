@extends('layouts.admin')
@php
    $emp = $payroll->employee;
    $empName = $emp->name ?? 'N/A';
    $monthLabel = \Carbon\Carbon::parse($payroll->month . '-01')->format('F Y');
    $attn = $payroll->statutory_json['attendance'] ?? [];
    $grossMonthly = (float)$payroll->gross_salary;
    $monthTotalDays = (int)($attn['month_total_days'] ?? date('t', strtotime($payroll->month . '-01')));
    $monthCalendarDays = (int)($attn['month_calendar_days'] ?? date('t', strtotime($payroll->month . '-01')));
    $presentEff = (float)($attn['present_effective'] ?? ($attn['present'] ?? 0));
    $leaveEff = (float)($attn['leave_effective'] ?? ($attn['leave'] ?? 0));
    $weeklyOffs = (int)($attn['weekly_offs'] ?? 0);
    $absentEff = (float)($attn['absent_effective'] ?? ($attn['absent'] ?? 0));
    $hdDeduction = (float)($attn['hd_deduction'] ?? ($attn['half_day'] ?? 0));
    $paidDays = (float)($attn['paid_days'] ?? ($presentEff + $leaveEff + $weeklyOffs));
    $perDay = $attn['per_day_salary'] ?? 0;
    $rf = fn($v) => (int) round((float)$v, 0, PHP_ROUND_HALF_UP);
@endphp

@section('page-title', __('Payroll Breakdown') . ' - ' . $empName . ' - ' . $monthLabel)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('payroll.process') }}">{{ __('Payroll') }}</a></li>
    <li class="breadcrumb-item active">{{ __('Breakdown') }}</li>
@endsection

@push('css-page')
<style>
    .bd-card { border-radius: 10px; border: 1px solid #e2e8f0; }
    .bd-card .card-header { font-weight: 700; font-size: .9rem; }
    .bd-tbl { font-size: .84rem; }
    .bd-tbl td, .bd-tbl th { padding: 6px 10px; }
    .bd-tbl .amt { text-align: right; font-family: monospace; }
    .bd-tbl .tot-row { background: #f0f4ff; font-weight: 700; }
    .bd-tbl .neg { color: #dc2626; }
    .stat-box { padding: 12px; border-radius: 8px; text-align: center; }
    .stat-box .sv { font-size: 1.3rem; font-weight: 800; }
    .stat-box .sl { font-size: .72rem; color: #64748b; margin-top: 2px; }
</style>
@endpush

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
                <h4 class="mb-1"><i class="ti ti-file-analytics me-1"></i>{{ __('Payroll Breakdown') }}</h4>
                <p class="text-muted mb-0">{{ $empName }} &mdash; {{ $monthLabel }}</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('payroll.slip', $payroll->id) }}" class="btn btn-sm btn-primary"><i class="ti ti-file-text me-1"></i>{{ __('Salary Slip') }}</a>
                <a href="{{ route('payroll.process', ['filter_month' => $payroll->month]) }}" class="btn btn-sm btn-outline-secondary"><i class="ti ti-arrow-left me-1"></i>{{ __('Back') }}</a>
            </div>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="col-12 mb-3">
        <div class="row g-2">
            <div class="col-md col-6"><div class="stat-box bg-light"><div class="sv">{{ \Auth::user()->priceFormat($rf($grossMonthly)) }}</div><div class="sl">{{ __('Gross Salary') }}</div></div></div>
            <div class="col-md col-6"><div class="stat-box" style="background:#fee2e2;"><div class="sv neg">-{{ \Auth::user()->priceFormat($rf($payroll->total_deductions)) }}</div><div class="sl">{{ __('Total Deductions') }}</div></div></div>
            <div class="col-md col-6"><div class="stat-box" style="background:#dcfce7;"><div class="sv" style="color:#166534;">{{ \Auth::user()->priceFormat($rf($payroll->net_salary)) }}</div><div class="sl">{{ __('Net Pay') }}</div></div></div>
            <div class="col-md col-6"><div class="stat-box" style="background:#dbeafe;"><div class="sv" style="color:#1e40af;">{{ \Auth::user()->priceFormat($rf($payroll->employer_contribution)) }}</div><div class="sl">{{ __('Employer Cost') }}</div></div></div>
            <div class="col-md col-6"><div class="stat-box bg-light"><div class="sv">{{ \Auth::user()->priceFormat($rf($perDay)) }}</div><div class="sl">{{ __('Per Day Salary') }}</div></div></div>
        </div>
    </div>

    {{-- Attendance Details --}}
    @if(!empty($attn))
    <div class="col-lg-6 col-12">
        <div class="card bd-card mb-3">
            <div class="card-header bg-light"><i class="ti ti-calendar-stats me-1"></i>{{ __('Attendance Summary') }} â€” {{ $monthLabel }}</div>
            <div class="card-body p-0">
                <table class="table bd-tbl mb-0">
                    <tbody>
                        <tr><td class="text-muted">{{ __('Days in Month') }}</td><td class="amt"><strong>{{ $monthTotalDays }}</strong></td></tr>
                        <tr><td><span class="text-success">&#9679;</span> {{ __('Present') }}</td><td class="amt">{{ number_format($presentEff, 1) }}</td></tr>
                        <tr><td><span class="text-secondary">&#9679;</span> {{ __('Leave (Paid)') }}</td><td class="amt">{{ number_format($leaveEff, 1) }}</td></tr>
                        <tr><td><span class="text-muted">&#9679;</span> {{ __('W/Off') }}</td><td class="amt">{{ $weeklyOffs }}</td></tr>
                        <tr><td><span class="text-danger">&#9679;</span> {{ __('Absent') }}</td><td class="amt">{{ number_format($absentEff, 1) }}</td></tr>
                        <tr><td><span class="text-warning">&#9679;</span> {{ __('HD Ded') }}</td><td class="amt">{{ number_format($hdDeduction, 1) }}</td></tr>
                        <tr class="tot-row"><td>{{ __('Paid Days') }}</td><td class="amt">{{ number_format($paidDays, 1) }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Overtime Breakdown --}}
    @if(!empty($attn['overtime_enabled']) && ($attn['overtime_hours'] ?? 0) > 0)
    <div class="col-lg-6 col-12">
        <div class="card bd-card mb-3">
            <div class="card-header" style="background:#dbeafe;color:#1e40af;"><i class="ti ti-clock-plus me-1"></i>{{ __('Overtime Breakdown') }}</div>
            <div class="card-body p-0">
                <table class="table bd-tbl mb-0">
                    <tbody>
                        @php
                            $otHours = (float)($attn['overtime_hours'] ?? 0);
                            $otAmount = (float)($attn['overtime_amount'] ?? 0);
                            $otFormula = $attn['overtime_formula'] ?? 'basic';
                            $otBase = (float)($attn['overtime_base_monthly'] ?? 0);
                            $otPerHour = (float)($attn['overtime_per_hour'] ?? 0);
                        @endphp
                        @php
                            $otRate = round($otPerHour * 1.5, 2);
                            $otCalcAmount = round($otRate * $otHours, 2);
                        @endphp
                        <tr><td class="text-muted">{{ __('OT Allowed') }}</td><td class="amt"><span class="badge bg-success">{{ __('Yes') }}</span></td></tr>
                        <tr><td class="text-muted">{{ __('OT Formula') }}</td><td class="amt">{{ ucfirst($otFormula) }} {{ __('/ 26 days / 8 hrs × 1.5') }}</td></tr>
                        <tr><td class="text-muted">{{ __('OT Base Monthly') }}</td><td class="amt">{{ number_format($otBase, 2) }}</td></tr>
                        <tr><td class="text-muted">{{ __('OT Per Hour Rate') }}</td><td class="amt">{{ number_format($otPerHour, 2) }} × 1.5 = {{ number_format($otRate, 2) }}</td></tr>
                        <tr><td class="text-muted">{{ __('Total OT Hours') }}</td><td class="amt"><strong>{{ number_format($otHours, 2) }}h</strong></td></tr>
                        <tr class="tot-row"><td>{{ __('OT Amount') }}</td><td class="amt" style="color:#1e40af;">{{ \Auth::user()->priceFormat((int)round($otCalcAmount)) }}</td></tr>
                        <tr><td colspan="2" class="text-muted" style="font-size:.72rem;">
                            {{ __('Calculation') }}: {{ number_format($otRate, 2) }} × {{ number_format($otHours, 2) }}h = {{ \Auth::user()->priceFormat((int)round($otCalcAmount)) }}
                        </td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- Salary Arrears Breakdown --}}
    @if(($attn['arrears_total'] ?? 0) > 0)
    <div class="col-lg-6 col-12">
        <div class="card bd-card mb-3">
            <div class="card-header" style="background:#ede9fe;color:#5b21b6;"><i class="ti ti-coin me-1"></i>{{ __('Salary Arrears') }}</div>
            <div class="card-body p-0">
                <table class="table bd-tbl mb-0">
                    <tbody>
                        @foreach(($attn['arrears_details'] ?? []) as $arrDetail)
                        <tr><td colspan="2" style="font-size:.78rem;">
                            <strong>{{ __('Increment') }}:</strong> {{ \Auth::user()->priceFormat((int)round($arrDetail['old_ctc'])) }} &rarr; {{ \Auth::user()->priceFormat((int)round($arrDetail['new_ctc'])) }}
                            <br><span class="text-muted">{{ __('Effective') }}: {{ \Carbon\Carbon::parse($arrDetail['effective_date'])->format('d M Y') }}</span>
                        </td></tr>
                        <tr>
                            <td class="text-muted">{{ __('Monthly Difference') }}</td>
                            <td class="amt">{{ \Auth::user()->priceFormat((int)round($arrDetail['monthly_diff'])) }}/{{ __('mo') }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">{{ __('Arrears Span') }}</td>
                            <td class="amt">{{ $arrDetail['months'] }} {{ __('months') }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">{{ __('Effective Months') }}</td>
                            <td class="amt">{{ $arrDetail['months_multiplier_label'] ?? $arrDetail['months'] }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">{{ __('Calculation') }}</td>
                            <td class="amt" style="font-size:.72rem;">{{ \Auth::user()->priceFormat((int)round($arrDetail['monthly_diff'])) }} × {{ $arrDetail['months_multiplier_label'] ?? $arrDetail['months'] }} = {{ \Auth::user()->priceFormat((int)round($arrDetail['amount'])) }}</td>
                        </tr>
                        @endforeach
                        <tr class="tot-row">
                            <td>{{ __('Total Arrears') }}</td>
                            <td class="amt" style="color:#5b21b6;">{{ \Auth::user()->priceFormat((int)round($attn['arrears_total'])) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
    @endif

    {{-- Attendance Deductions Calculation --}}
    <div class="col-lg-6 col-12">
        <div class="card bd-card mb-3">
            <div class="card-header" style="background:#fef3c7;"><i class="ti ti-calculator me-1"></i>{{ __('Attendance Deduction Calculation') }}</div>
            <div class="card-body p-0">
                <table class="table bd-tbl mb-0">
                    <thead><tr><th>{{ __('Item') }}</th><th>{{ __('Calculation') }}</th><th class="amt">{{ __('Amount') }}</th></tr></thead>
                    <tbody>
                        @php $attTotal = 0; @endphp
                        @if($absentEff > 0)
                            @php $a = round($perDay * $absentEff, 2); $attTotal += $a; @endphp
                            <tr>
                                <td>{{ __('Absent') }} ({{ number_format($absentEff, 1) }} {{ __('days') }})</td>
                                <td class="text-muted">{{ number_format($absentEff, 1) }} × {{ \Auth::user()->priceFormat($rf($perDay)) }}</td>
                                <td class="amt neg">-{{ \Auth::user()->priceFormat($rf($a)) }}</td>
                            </tr>
                        @endif
                        @if($hdDeduction > 0)
                            @php $a = round($perDay * $hdDeduction, 2); $attTotal += $a; @endphp
                            <tr>
                                <td>{{ __('HD Ded') }} ({{ number_format($hdDeduction, 1) }} {{ __('days') }})</td>
                                <td class="text-muted">{{ number_format($hdDeduction, 1) }} × {{ \Auth::user()->priceFormat($rf($perDay)) }}</td>
                                <td class="amt neg">-{{ \Auth::user()->priceFormat($rf($a)) }}</td>
                            </tr>
                        @endif
                        @if($attTotal == 0)
                            <tr><td colspan="3" class="text-center text-muted py-3">{{ __('No attendance deductions this month.') }}</td></tr>
                        @endif
                        <tr class="tot-row"><td colspan="2">{{ __('Total Attendance Deduction') }}</td><td class="amt neg">-{{ \Auth::user()->priceFormat($rf($attTotal)) }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Earnings --}}
    <div class="col-lg-6 col-12">
        <div class="card bd-card mb-3">
            <div class="card-header" style="background:#dcfce7;color:#166534;"><i class="ti ti-plus me-1"></i>{{ __('Earnings (Slip View)') }}</div>
            <div class="card-body p-0">
                <table class="table bd-tbl mb-0">
                    <thead><tr><th>{{ __('Component') }}</th><th class="amt">{{ __('Monthly') }}</th><th class="amt">{{ __('Earnings') }}</th></tr></thead>
                    <tbody>
                        @php $tMonthly = 0; $tEarnings = 0; $epfBasicEarning = 0; $epfBasicMonthly = 0; @endphp
                        @foreach(($payroll->earnings_json ?? []) as $item)
                            @php
                                $ann = round($item['amount'] ?? 0, 2);
                                $isOneTime = (($item['frequency'] ?? 'monthly') === 'one-time');
                                $mon = $isOneTime ? $ann : round($ann / 12, 2);
                                $earn = $isOneTime ? $mon : ($monthCalendarDays > 0 ? round(($mon / $monthCalendarDays) * $paidDays, 2) : $mon);
                                $componentName = strtolower((string)($item['name'] ?? ''));
                                $tMonthly += $mon;
                                $tEarnings += $earn;
                                if (str_contains($componentName, 'basic')) {
                                    $epfBasicMonthly += $mon;
                                    $epfBasicEarning += $earn;
                                }
                            @endphp
                            @if($ann > 0)
                            <tr>
                                <td>{{ $item['name'] ?? '-' }}</td>
                                <td class="amt">{{ \Auth::user()->priceFormat($rf($mon)) }}</td>
                                <td class="amt">{{ \Auth::user()->priceFormat($rf($earn)) }}</td>
                            </tr>
                            @endif
                        @endforeach
                        <tr class="tot-row">
                            <td>{{ __('Gross Salary') }}</td>
                            <td class="amt">{{ \Auth::user()->priceFormat($rf($tMonthly)) }}</td>
                            <td class="amt">{{ \Auth::user()->priceFormat($rf($tEarnings)) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Statutory Deductions --}}
    <div class="col-lg-6 col-12">
        <div class="card bd-card mb-3">
            <div class="card-header" style="background:#fee2e2;color:#991b1b;"><i class="ti ti-minus me-1"></i>{{ __('Statutory Deductions') }}</div>
            <div class="card-body p-0">
                <table class="table bd-tbl mb-0">
                    <thead><tr><th>{{ __('Component') }}</th><th class="amt">{{ __('Amount') }}</th></tr></thead>
                    <tbody>
                        @php $statTotal = 0; @endphp
                        @foreach(($payroll->deductions_json ?? []) as $item)
                            @php
                                $amt = round((float)($item['amount'] ?? 0), 2);
                                if ($amt <= 0) continue;
                                $name = strtolower($item['name'] ?? '');
                                if (str_contains($name, 'absent') || str_contains($name, 'half day') || str_contains($name, 'leave deduction') || str_contains($name, 'late/early') || str_contains($name, 'early ½') || str_contains($name, 'early half')) continue;
                                $statTotal += $amt;
                            @endphp
                            <tr>
                                <td>{{ $item['name'] }}</td>
                                <td class="amt">{{ \Auth::user()->priceFormat($rf($amt)) }}</td>
                            </tr>
                        @endforeach
                        <tr class="tot-row"><td>{{ __('Total Statutory') }}</td><td class="amt">{{ \Auth::user()->priceFormat($rf($statTotal)) }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Employer Contributions --}}
    @php $benefits = collect($payroll->benefits_json ?? [])->filter(fn($i) => ($i['amount'] ?? 0) > 0); @endphp
    @if($benefits->isNotEmpty())
    <div class="col-lg-6 col-12">
        <div class="card bd-card mb-3">
            <div class="card-header" style="background:#dbeafe;color:#1e40af;"><i class="ti ti-building-bank me-1"></i>{{ __('Employer Contributions') }}</div>
            <div class="card-body p-0">
                <table class="table bd-tbl mb-0">
                    <thead><tr><th>{{ __('Component') }}</th><th class="amt">{{ __('Amount') }}</th></tr></thead>
                    <tbody>
                        @php $empTotal = 0; @endphp
                        @foreach($benefits as $item)
                            @php
                                $amt = round((float)($item['amount'] ?? 0), 2);
                                if ($amt <= 0) continue;
                                $empTotal += $amt;
                            @endphp
                            <tr>
                                <td>{{ $item['name'] }}</td>
                                <td class="amt">{{ \Auth::user()->priceFormat($rf($amt)) }}</td>
                            </tr>
                        @endforeach
                        <tr class="tot-row"><td>{{ __('Total Employer') }}</td><td class="amt">{{ \Auth::user()->priceFormat($rf($empTotal)) }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- Final Summary --}}
    <div class="col-12">
        <div class="card bd-card">
            <div class="card-header bg-dark text-white"><i class="ti ti-report-money me-1"></i>{{ __('Final Salary Calculation') }}</div>
            <div class="card-body p-0">
                <table class="table bd-tbl mb-0" style="font-size:.9rem;">
                    <tbody>
                        @php
                            $finalTotalDeductions = (float)($statTotal ?? 0) + (float)($attTotal ?? 0);
                            $finalNetPay = (float)$grossMonthly - $finalTotalDeductions;
                        @endphp
                        <tr><td>{{ __('A. Gross Monthly Salary') }}</td><td class="amt"><strong>{{ \Auth::user()->priceFormat($rf($grossMonthly)) }}</strong></td></tr>
                        <tr><td>{{ __('B. Statutory Deductions (PF + PT + ESIC + LWF)') }}</td><td class="amt neg">-{{ \Auth::user()->priceFormat($rf($statTotal ?? 0)) }}</td></tr>
                        <tr><td>{{ __('C. Attendance Deductions (Absent + HD Ded)') }}</td><td class="amt neg">-{{ \Auth::user()->priceFormat($rf($attTotal ?? 0)) }}</td></tr>
                        <tr><td>{{ __('D. Total Deductions (B + C)') }}</td><td class="amt neg"><strong>-{{ \Auth::user()->priceFormat($rf($finalTotalDeductions)) }}</strong></td></tr>
                        <tr style="background:#dcfce7;font-size:1.1rem;"><td><strong>{{ __('E. Net Pay (A - D)') }}</strong></td><td class="amt"><strong style="color:#166534;">{{ \Auth::user()->priceFormat($rf($finalNetPay)) }}</strong></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

