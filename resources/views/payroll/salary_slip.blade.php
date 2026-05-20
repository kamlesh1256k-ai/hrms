@extends('layouts.admin')
@php
    $settings = \App\Models\Utility::settings();
    $companyName = $settings['company_name'] ?? config('app.name');
    $companyAddress = $settings['company_address'] ?? '';
    $companyCity = $settings['company_city'] ?? '';
    $companyState = $settings['company_state'] ?? '';
    $companyZip = $settings['company_zipcode'] ?? '';
    $companyPhone = $settings['company_telephone'] ?? '';
    $companyEmail = $settings['company_email'] ?? '';
    $logoPath = \App\Models\Utility::get_file('uploads/logo/');
    $companyLogo = \App\Models\Utility::get_company_logo();
    $emp = $payroll->employee;
    $empName = $emp->name ?? 'N/A';
    $monthLabel = \Carbon\Carbon::parse($payroll->month . '-01')->format('F Y');
    $attn = $payroll->statutory_json['attendance'] ?? null;
    $grossMonthly = (float)$payroll->gross_salary;
    $monthTotalDays = (int)($attn['month_total_days'] ?? date('t', strtotime($payroll->month . '-01')));
    $monthCalendarDays = (int)($attn['month_calendar_days'] ?? date('t', strtotime($payroll->month . '-01')));
    $paidDays = (float)($attn['paid_days'] ?? $monthTotalDays);
    $perDay = $attn['per_day_salary'] ?? ($monthCalendarDays > 0 ? $grossMonthly / $monthCalendarDays : 0);
    $weeklyOffs = (int)($attn['weekly_offs'] ?? 0);
    $publicHolidays = (int)($attn['public_holidays'] ?? 0);
    $presentEff = (float)($attn['present_effective'] ?? ($attn['present'] ?? 0));
    $leaveEff = (float)($attn['leave_effective'] ?? ($attn['leave'] ?? 0));
    $absentEff = (float)($attn['absent_effective'] ?? ($attn['absent'] ?? 0));
    $hdDeduction = (float)($attn['hd_deduction'] ?? ($attn['half_day'] ?? 0));
    $unpaidDays = (float)($attn['unpaid_days'] ?? ($absentEff + $hdDeduction));

    // Slip badge label varies by employee type:
    // Intern → "STIPEND SLIP", Consultant → "CONSULTANT AMOUNT", else → "SALARY SLIP"
    $stat = is_array($payroll->statutory_json) ? $payroll->statutory_json : [];
    $typeCode = $stat['employee_type']['code'] ?? optional($emp->employeeType ?? null)->code;
    $slipBadge = match ($typeCode) {
        'intern'     => __('STIPEND SLIP'),
        'consultant' => __('CONSULTANT AMOUNT'),
        default      => __('SALARY SLIP'),
    };
    $pageTitle = match ($typeCode) {
        'intern'     => __('Stipend Slip'),
        'consultant' => __('Consultant Amount'),
        default      => __('Salary Slip'),
    };
@endphp

@section('page-title', $pageTitle . ' - ' . $empName . ' - ' . $monthLabel)

@push('css-page')
<style>
    .slip-wrap { max-width: 960px; margin: 0 auto; }
    .slip { border: 2px solid #cbd5e1; border-radius: 10px; background: #fff; overflow: hidden; }
    .slip-hdr { background: linear-gradient(135deg, #1e293b 0%, #334155 100%); color: #fff; padding: 24px 28px; }
    .slip-hdr-logo { max-height: 44px; margin-right: 14px; border-radius: 6px; }
    .slip-hdr h3 { margin: 0; font-weight: 800; font-size: 1.2rem; }
    .slip-hdr .co-det { font-size: .75rem; opacity: .8; margin-top: 3px; }
    .slip-hdr .slip-badge { font-size: .78rem; background: rgba(255,255,255,.15); padding: 3px 12px; border-radius: 16px; }
    .slip-body { padding: 24px 28px; }
    .emp-tbl td { padding: 2px 8px; font-size: .82rem; }
    .emp-tbl .lbl { color: #64748b; width: 130px; }
    .attn-bar { background: #f1f5f9; border-radius: 8px; padding: 12px 16px; margin-bottom: 20px; border: 1px solid #e2e8f0; }
    .attn-bar .ab-item { font-size: .8rem; }
    .attn-bar .ab-val { font-weight: 700; }
    .s-tbl { width: 100%; border-collapse: collapse; font-size: .82rem; }
    .s-tbl th { background: #f8fafc; padding: 7px 10px; font-weight: 600; text-align: left; border-bottom: 2px solid #e2e8f0; }
    .s-tbl td { padding: 6px 10px; border-bottom: 1px solid #f1f5f9; }
    .s-tbl .amt { text-align: right; font-family: monospace; font-weight: 500; }
    .s-tbl .tot { background: #f0f4ff; font-weight: 700; border-top: 2px solid #4361ee; }
    .s-tbl .earn-h th { background: #dcfce7; color: #166534; }
    .s-tbl .ded-h th { background: #fee2e2; color: #991b1b; }
    .s-tbl .attn-h th { background: #fef3c7; color: #92400e; }
    .s-tbl .ben-h th { background: #dbeafe; color: #1e40af; }
    .slip-ft { background: #1e293b; color: #fff; padding: 16px 28px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
    .slip-ft .net-amt { font-size: 1.5rem; font-weight: 800; }
    .slip-meta { font-size: .7rem; color: #94a3b8; padding: 8px 28px; text-align: center; border-top: 1px solid #f1f5f9; }
    @media print {
        .no-print { display: none !important; }
        .dash-sidebar, .dash-header, .dash-footer, .page-header, .breadcrumb { display: none !important; }
        .dash-container, .dash-content { margin: 0 !important; padding: 0 !important; }
        .slip-wrap { max-width: 100%; }
        body { background: #fff !important; }
    }
</style>
@endpush

@section('content')
<div class="slip-wrap">
    <div class="mb-3 no-print d-flex gap-2 flex-wrap">
        <button onclick="window.print()" class="btn btn-sm btn-primary"><i class="ti ti-printer me-1"></i>{{ __('Print') }}</button>
        <button onclick="downloadSlip()" class="btn btn-sm btn-success"><i class="ti ti-download me-1"></i>{{ __('Download') }}</button>
        <a href="{{ route('payroll.breakdown', $payroll->id) }}" class="btn btn-sm btn-info"><i class="ti ti-list-details me-1"></i>{{ __('Detailed Breakdown') }}</a>
        <a href="{{ route('payroll.tax-computation', $payroll->employee_id) }}" class="btn btn-sm btn-warning"><i class="ti ti-receipt-tax me-1"></i>{{ __('Tax Computation') }}</a>
        <a href="{{ url()->previous() }}" class="btn btn-sm btn-outline-secondary"><i class="ti ti-arrow-left me-1"></i>{{ __('Back') }}</a>
    </div>

    <div class="slip" id="salary-slip">
        {{-- Header --}}
        <div class="slip-hdr">
            <div class="d-flex justify-content-between align-items-start">
                <div class="d-flex align-items-center">
                    @if(!empty($companyLogo))
                        <img src="{{ $logoPath . $companyLogo }}" alt="Logo" class="slip-hdr-logo" onerror="this.style.display='none'">
                    @endif
                    <div>
                        <h3>{{ $companyName }}</h3>
                        @if($companyAddress || $companyCity)
                            <div class="co-det">{{ $companyAddress }}{{ $companyCity ? ', ' . $companyCity : '' }}{{ $companyState ? ', ' . $companyState : '' }} {{ $companyZip }}</div>
                        @endif
                        @if($companyPhone || $companyEmail)
                            <div class="co-det">
                                @if($companyPhone)<i class="ti ti-phone"></i> {{ $companyPhone }} @endif
                                @if($companyPhone && $companyEmail) | @endif
                                @if($companyEmail)<i class="ti ti-mail"></i> {{ $companyEmail }} @endif
                            </div>
                        @endif
                    </div>
                </div>
                <div class="text-end">
                    <span class="slip-badge">{{ $slipBadge }}</span>
                    <div class="co-det mt-2">#{{ str_pad($payroll->id, 5, '0', STR_PAD_LEFT) }}</div>
                    <div class="co-det">{{ $monthLabel }}</div>
                </div>
            </div>
        </div>

        <div class="slip-body">
            {{-- Employee Info --}}
            <div class="row mb-3">
                <div class="col-6">
                    <table class="emp-tbl">
                        <tr><td class="lbl">{{ __('Name') }}</td><td><strong>{{ $empName }}</strong></td></tr>
                        <tr><td class="lbl">{{ __('Employee ID') }}</td><td>{{ $emp->employee_id ?? $payroll->employee_id }}</td></tr>
                        <tr><td class="lbl">{{ __('Department') }}</td><td>{{ $emp->department->name ?? '-' }}</td></tr>
                    </table>
                </div>
                <div class="col-6">
                    <table class="emp-tbl">
                        <tr><td class="lbl">{{ __('Designation') }}</td><td>{{ $emp->designation->name ?? '-' }}</td></tr>
                        <tr><td class="lbl">{{ __('Bank A/C') }}</td><td>{{ $emp->account_number ?? '-' }}</td></tr>
                        <tr><td class="lbl">{{ __('Pay Date') }}</td><td>{{ \Auth::user()->dateFormat($payroll->updated_at) }}</td></tr>
                    </table>
                </div>
            </div>

            {{-- Attendance & Formula Summary --}}
            @if($attn)
            <div class="attn-bar">
                <div class="d-flex flex-wrap gap-3 mb-2">
                    <div class="ab-item"><span class="text-muted">{{ __('Days in Month') }}:</span> <span class="ab-val">{{ $monthTotalDays }}</span></div>
                    <div class="ab-item"><span class="text-success">{{ __('Present') }}:</span> <span class="ab-val">{{ number_format($presentEff, 1) }}</span></div>
                    <div class="ab-item"><span class="text-secondary">{{ __('Leave (Paid)') }}:</span> <span class="ab-val">{{ number_format($leaveEff, 1) }}</span></div>
                    <div class="ab-item"><span class="text-muted">{{ __('W/Off') }}:</span> <span class="ab-val">{{ $weeklyOffs }}</span></div>
                    @if($publicHolidays > 0)
                    <div class="ab-item"><span class="text-primary">{{ __('Holidays') }}:</span> <span class="ab-val">{{ $publicHolidays }}</span></div>
                    @endif
                    <div class="ab-item"><span class="text-danger">{{ __('Absent') }}:</span> <span class="ab-val">{{ number_format($absentEff, 1) }}</span></div>
                    <div class="ab-item"><span class="text-warning">{{ __('HD Ded') }}:</span> <span class="ab-val">{{ number_format($hdDeduction, 1) }}</span></div>
                    @if(!empty($attn['overtime_enabled']) && ($attn['overtime_hours'] ?? 0) > 0)
                    <div class="ab-item"><span class="text-primary">{{ __('OT') }}:</span> <span class="ab-val">{{ number_format($attn['overtime_hours'], 1) }}h</span></div>
                    @endif
                    @if(($attn['arrears_total'] ?? 0) > 0)
                    <div class="ab-item"><span style="color:#7c3aed;">{{ __('Arrears') }}:</span> <span class="ab-val">{{ \Auth::user()->priceFormat((int)round($attn['arrears_total'])) }}</span></div>
                    @endif
                </div>
            </div>
            @endif

            @php
                $arrearsDetails = $attn['arrears_details'] ?? [];
                $arrearsComponents = $attn['arrears_components'] ?? [];
                $arrearsTotalVal = (float)($attn['arrears_total'] ?? 0);
            @endphp

            {{-- Earnings Component-wise --}}
            <table class="s-tbl mb-3">
                <thead class="earn-h">
                    <tr>
                        <th>{{ __('Component') }}</th>
                        <th class="amt">{{ __('Monthly') }}</th>
                        <th class="amt">{{ __('Earnings') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @php $totalMonthly = 0; $totalPaid = 0; $epfBasicMonthly = 0; $epfBasicEarning = 0; @endphp
                    @foreach(($payroll->earnings_json ?? []) as $item)
                        @php
                            // Skip Salary Arrears here — shown in dedicated section below
                            if (strtolower((string)($item['name'] ?? '')) === 'salary arrears') { continue; }
                            $annual = round($item['amount'] ?? 0, 2);
                            $isOneTime = (($item['frequency'] ?? 'monthly') === 'one-time');
                            $monthly = $isOneTime ? $annual : round($annual / 12, 2);
                            $paid = $isOneTime ? $monthly : ($monthCalendarDays > 0 ? round(($monthly / $monthCalendarDays) * $paidDays, 2) : $monthly);
                            $componentName = strtolower((string)($item['name'] ?? ''));
                            $totalMonthly += $monthly;
                            $totalPaid += $paid;
                            if (str_contains($componentName, 'basic')) {
                                $epfBasicMonthly += $monthly;
                                $epfBasicEarning += $paid;
                            }
                            // Round to rupee with 50-paise rule (>= 0.50 rounds up).
                            $monthlyRounded = (int) round($monthly, 0, PHP_ROUND_HALF_UP);
                            $paidRounded = (int) round($paid, 0, PHP_ROUND_HALF_UP);
                        @endphp
                        @if($monthly > 0)
                        <tr>
                            <td>{{ $item['name'] ?? '-' }}</td>
                            <td class="amt">{{ \Auth::user()->priceFormat($monthlyRounded) }}</td>
                            <td class="amt fw-bold">{{ \Auth::user()->priceFormat($paidRounded) }}</td>
                        </tr>
                        @endif
                    @endforeach
                    @php
                        $totalMonthlyRounded = (int) round($totalMonthly, 0, PHP_ROUND_HALF_UP);
                        $totalPaidRounded = (int) round($totalPaid, 0, PHP_ROUND_HALF_UP);
                    @endphp
                    <tr class="tot">
                        <td>{{ __('Total Earnings') }}</td>
                        <td class="amt">{{ \Auth::user()->priceFormat($totalMonthlyRounded) }}</td>
                        <td class="amt">{{ \Auth::user()->priceFormat($totalPaidRounded) }}</td>
                    </tr>
                </tbody>
            </table>

            {{-- ── SALARY ARREARS (detailed component-wise breakup) ── --}}
            @if($arrearsTotalVal > 0)
            <table class="s-tbl mb-3">
                <thead style="background:#ede9fe;">
                    <tr>
                        <th style="color:#5b21b6;">{{ __('Salary Arrears — Component-wise Breakup') }}</th>
                        <th class="amt" style="color:#5b21b6;">{{ __('Old Monthly') }}</th>
                        <th class="amt" style="color:#5b21b6;">{{ __('New Monthly') }}</th>
                        <th class="amt" style="color:#5b21b6;">{{ __('Diff/Month') }}</th>
                        <th class="amt" style="color:#5b21b6;">{{ __('Effective Months') }}</th>
                        <th class="amt" style="color:#5b21b6;">{{ __('Arrear Amount') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($arrearsDetails as $ar)
                        <tr style="background:#faf5ff;">
                            <td colspan="6" style="font-size:.75rem;color:#6d28d9;">
                                <i class="ti ti-arrow-up-right"></i>
                                {{ __('Increment from') }}
                                <strong>{{ \Auth::user()->priceFormat((int)round($ar['old_ctc'])) }}</strong>
                                {{ __('to') }}
                                <strong>{{ \Auth::user()->priceFormat((int)round($ar['new_ctc'])) }}</strong>
                                {{ __('(CTC)') }} &nbsp;•&nbsp;
                                {{ __('Effective:') }} {{ \Carbon\Carbon::parse($ar['effective_date'])->format('d M Y') }} &nbsp;•&nbsp;
                                {{ $ar['months'] }} {{ __('month(s) arrears') }}
                            </td>
                        </tr>
                        @foreach(($ar['breakup'] ?? []) as $bk)
                            @php
                                $bkAmt = (float)($bk['amount'] ?? 0);
                                $isNegative = $bkAmt < 0;
                                $amtColor = $isNegative ? '#dc2626' : '#7c3aed';
                                $diffColor = $isNegative ? '#dc2626' : '#059669';
                                $diffSign = ($bk['diff_monthly'] ?? 0) >= 0 ? '+' : '';
                            @endphp
                        <tr>
                            <td style="padding-left:22px;">{{ $bk['name'] }}</td>
                            <td class="amt">{{ \Auth::user()->priceFormat((int)round($bk['old_monthly'])) }}</td>
                            <td class="amt">{{ \Auth::user()->priceFormat((int)round($bk['new_monthly'])) }}</td>
                            <td class="amt" style="color:{{ $diffColor }};">{{ $diffSign }}{{ \Auth::user()->priceFormat((int)round($bk['diff_monthly'])) }}</td>
                            <td class="amt">×{{ $bk['months'] }}</td>
                            <td class="amt fw-bold" style="color:{{ $amtColor }};">
                                {{ $bkAmt < 0 ? '−' : '' }}{{ \Auth::user()->priceFormat((int)round(abs($bkAmt))) }}
                            </td>
                        </tr>
                        @endforeach
                        @if(!empty($ar['per_month']))
                            <tr style="background:#fefce8;">
                                <td colspan="6" style="font-size:.7rem;color:#854d0e;padding-left:22px;">
                                    <i class="ti ti-info-circle"></i>
                                    {{ __('Per-month OT / Attendance detail:') }}
                                    @foreach($ar['per_month'] as $pm)
                                        <span class="ms-2" style="display:inline-block;">
                                            <strong>{{ \Carbon\Carbon::parse($pm['month'].'-01')->format('M Y') }}</strong>:
                                            @if(($pm['ot_hours'] ?? 0) > 0)
                                                OT {{ $pm['ot_hours'] }}h (Δ +{{ \Auth::user()->priceFormat((int)round($pm['ot_arrear'])) }})
                                            @endif
                                            @if(($pm['unpaid_days'] ?? 0) > 0)
                                                • Unpaid {{ $pm['unpaid_days'] }}d (adj {{ $pm['attn_adjust'] < 0 ? '−' : '+' }}{{ \Auth::user()->priceFormat((int)round(abs($pm['attn_adjust']))) }})
                                            @endif
                                        </span>
                                    @endforeach
                                </td>
                            </tr>
                        @endif
                    @endforeach
                    <tr class="tot" style="background:#ede9fe;border-top:2px solid #7c3aed;">
                        <td colspan="5">{{ __('Total Arrears') }}</td>
                        <td class="amt" style="color:#5b21b6;">{{ \Auth::user()->priceFormat((int)round($arrearsTotalVal)) }}</td>
                    </tr>
                </tbody>
            </table>
            @endif

            @php
                // Split deductions into statutory vs attendance buckets.
                // Only items with amount > 0 are kept — zero-value rows hide automatically.
                $statutoryDed = [];
                $attendanceDed = [];
                foreach(($payroll->deductions_json ?? []) as $item) {
                    $amt = round($item['amount'] ?? 0, 2);
                    if ($amt <= 0) continue;
                    $name = strtolower($item['name'] ?? '');
                    if (str_contains($name, 'employer')) continue; // employer-side only — not employee deduction
                    if (str_contains($name, 'absent') || str_contains($name, 'half day') || str_contains($name, 'leave deduction') || str_contains($name, 'late/early') || str_contains($name, 'early ½') || str_contains($name, 'early half')) {
                        $attendanceDed[] = $item;
                    } else {
                        $statutoryDed[] = $item;
                    }
                }
                $totalStatDed = array_sum(array_column($statutoryDed, 'amount'));
                $totalAttnDed = array_sum(array_column($attendanceDed, 'amount'));
            @endphp

            @if(!empty($statutoryDed) || !empty($attendanceDed))
            <div class="row g-3">
                {{-- Statutory Deductions — only render if applicable --}}
                @if(!empty($statutoryDed))
                <div class="col-md-6">
                    <table class="s-tbl">
                        <thead class="ded-h"><tr><th>{{ __('Statutory Deductions') }}</th><th class="amt">{{ __('Monthly') }}</th></tr></thead>
                        <tbody>
                            @foreach($statutoryDed as $item)
                                @php
                                    $itemName = (string)($item['name'] ?? '');
                                    $amt = round((float)($item['amount'] ?? 0), 2);
                                @endphp
                                <tr>
                                    <td>{{ $itemName === 'EPF Employee' ? 'EPF Contribution' : ($itemName ?: '-') }}</td>
                                    <td class="amt">{{ \Auth::user()->priceFormat($amt) }}</td>
                                </tr>
                            @endforeach
                            <tr class="tot"><td>{{ __('Total Statutory') }}</td><td class="amt">{{ \Auth::user()->priceFormat($totalStatDed) }}</td></tr>
                        </tbody>
                    </table>
                </div>
                @endif

                {{-- Attendance Deductions — only render if applicable --}}
                @if(!empty($attendanceDed))
                <div class="col-md-6">
                    <table class="s-tbl">
                        <thead class="ded-h"><tr><th>{{ __('Attendance Deductions') }}</th><th class="amt">{{ __('Amount') }}</th></tr></thead>
                        <tbody>
                            @foreach($attendanceDed as $item)
                                <tr>
                                    <td>{{ $item['name'] ?? '-' }}</td>
                                    <td class="amt">{{ \Auth::user()->priceFormat(round((float)($item['amount'] ?? 0), 2)) }}</td>
                                </tr>
                            @endforeach
                            <tr class="tot"><td>{{ __('Total Attendance') }}</td><td class="amt">{{ \Auth::user()->priceFormat($totalAttnDed) }}</td></tr>
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
            @endif

        </div>

        {{-- Net Pay Footer --}}
        <div class="slip-ft">
            @php
                // Use backend-stored values for reliability; the controller has the
                // authoritative numbers (handles CTC, Intern, Consultant — all paths).
                $grossRounded = (int) round((float)$payroll->gross_salary, 0, PHP_ROUND_HALF_UP);
                $arrearsRounded = (int) round($arrearsTotalVal, 0, PHP_ROUND_HALF_UP);
                $totalDedRounded = (int) round((float)$payroll->total_deductions, 0, PHP_ROUND_HALF_UP);
                $displayNetPay = (int) round((float)$payroll->net_salary, 0, PHP_ROUND_HALF_UP);
                // Aliases kept so the existing markup below doesn't change semantics.
                $grossPayRounded = $grossRounded;
                $statutoryRounded = (int) round((float)($totalStatDed ?? 0), 0, PHP_ROUND_HALF_UP);
            @endphp
            <div style="font-size:.82rem;opacity:.8;">
                {{ __('Gross') }}: {{ \Auth::user()->priceFormat($grossPayRounded) }}
                @if($arrearsRounded > 0)
                    &nbsp;|&nbsp; <span style="color:#c4b5fd;">{{ __('Arrears') }}: +{{ \Auth::user()->priceFormat($arrearsRounded) }}</span>
                @endif
                &nbsp;|&nbsp; {{ __('Deductions') }}: {{ \Auth::user()->priceFormat($totalDedRounded) }}
            </div>
            <div class="text-end">
                <div style="font-size:.75rem;opacity:.6;">{{ __('NET PAY') }}</div>
                <div class="net-amt">{{ \Auth::user()->priceFormat($displayNetPay) }}</div>
            </div>
        </div>

        <div class="slip-meta">
            {{ __('System-generated salary slip — no signature required.') }} &bull; {{ $companyName }} &bull; {{ \Auth::user()->dateFormat(now()) }}
        </div>
    </div>
</div>
@endsection

@push('script-page')
<script src="{{ asset('js/html2canvas.min.js') }}"></script>
<script>
function downloadSlip() {
    html2canvas(document.getElementById('salary-slip'), { scale: 2, useCORS: true, backgroundColor: '#fff' }).then(function(c) {
        var a = document.createElement('a');
        a.download = 'Salary_Slip_{{ str_replace(" ", "_", $empName) }}_{{ $payroll->month }}.png';
        a.href = c.toDataURL('image/png');
        a.click();
    });
}
@if(request('download')) document.addEventListener('DOMContentLoaded', function(){ setTimeout(downloadSlip, 500); }); @endif
</script>
@endpush
