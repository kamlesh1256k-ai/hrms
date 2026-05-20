@extends('layouts.admin')
@section('page-title', __('Tax Computation') . ' - ' . $employee->name . ' - FY ' . $fy)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('payroll.process') }}">{{ __('Payroll') }}</a></li>
    <li class="breadcrumb-item active">{{ __('Tax Computation') }}</li>
@endsection

@php $f = fn($v) => \Auth::user()->priceFormat((int)round((float)$v)); @endphp

@push('css-page')
<style>
    .tc-card { border-radius: 10px; border: 1px solid #e2e8f0; margin-bottom: 16px; }
    .tc-card .card-header { font-weight: 700; font-size: .9rem; padding: 12px 16px; }
    .tc-tbl { font-size: .84rem; width: 100%; }
    .tc-tbl td, .tc-tbl th { padding: 7px 12px; border-bottom: 1px solid #f1f5f9; }
    .tc-tbl .amt { text-align: right; font-family: monospace; font-weight: 500; }
    .tc-tbl .tot { background: #f0f4ff; font-weight: 700; border-top: 2px solid #4361ee; }
    .tc-tbl .neg { color: #dc2626; }
    .tc-tbl .pos { color: #059669; }
    .slab-row td { font-size: .8rem; }
    .slab-row .rate-badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: .7rem; font-weight: 700; }
    .regime-box { border-radius: 10px; padding: 16px; }
    .regime-active { border: 2px solid #4361ee; }
    .regime-inactive { border: 1px solid #e2e8f0; opacity: .7; }
    .tds-timeline { position: relative; }
    .tds-month { display: flex; align-items: center; gap: 10px; padding: 6px 0; border-bottom: 1px solid #f1f5f9; font-size: .82rem; }
    .tds-month .m-label { min-width: 80px; font-weight: 600; color: #475569; }
    .tds-month .m-bar { flex: 1; height: 20px; background: #e2e8f0; border-radius: 4px; overflow: hidden; position: relative; }
    .tds-month .m-bar-fill { height: 100%; border-radius: 4px; }
    .tds-month .m-amt { min-width: 90px; text-align: right; font-family: monospace; font-weight: 600; }
    @media print {
        .no-print { display: none !important; }
        .dash-sidebar, .dash-header, .dash-footer { display: none !important; }
        .dash-container, .dash-content { margin: 0 !important; padding: 0 !important; }
    }
</style>
@endpush

@section('content')
<div class="row">
    {{-- Header --}}
    <div class="col-12 mb-3 no-print">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h4 class="mb-1"><i class="ti ti-receipt-tax me-2"></i>{{ __('Income Tax Computation Sheet') }}</h4>
                <span class="text-muted">{{ $employee->name }} ({{ $employee->employee_id }}) &mdash; FY {{ $fy }}</span>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <form method="GET" action="{{ route('payroll.tax-computation', $employee->id) }}" class="d-flex gap-2 align-items-center">
                    <label class="form-label mb-0 fw-bold" style="font-size:.82rem;">FY:</label>
                    <select name="fy" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
                        @php
                            $curYear = (int)date('Y');
                            for ($y = $curYear + 1; $y >= $curYear - 3; $y--) {
                                $fyOpt = ($y - 1) . '-' . $y;
                                echo '<option value="' . $fyOpt . '"' . ($fy === $fyOpt ? ' selected' : '') . '>' . $fyOpt . '</option>';
                            }
                        @endphp
                    </select>
                </form>
                <button onclick="window.print()" class="btn btn-sm btn-primary"><i class="ti ti-printer me-1"></i>{{ __('Print') }}</button>
                <a href="{{ url()->previous() }}" class="btn btn-sm btn-outline-secondary"><i class="ti ti-arrow-left me-1"></i>{{ __('Back') }}</a>
            </div>
        </div>
    </div>

    {{-- Income Summary --}}
    <div class="col-md-6">
        <div class="tc-card">
            <div class="card-header bg-dark text-white"><i class="ti ti-coin me-2"></i>{{ __('Income Summary — FY') }} {{ $fy }}</div>
            <div class="card-body p-0">
                <table class="tc-tbl">
                    <tbody>
                        <tr><td>{{ __('Current Annual CTC') }}</td><td class="amt">{{ $f($ctc) }}</td></tr>
                        <tr><td>{{ __('Current Gross (CTC − Employer PF)') }}</td><td class="amt">{{ $f($grossAnnualFromCTC) }}</td></tr>
                        @if(isset($salaryChanged) && $salaryChanged)
                            <tr style="background:#fef3c7;">
                                <td><i class="ti ti-arrow-up-right text-warning me-1"></i>{{ __('Salary changed during FY') }}</td>
                                <td class="amt">
                                    @foreach($increments as $inc)
                                        <div style="font-size:.75rem;">{{ \Carbon\Carbon::parse($inc->effective_date)->format('d M Y') }}: {{ $f($inc->old_ctc) }} → {{ $f($inc->new_ctc) }}</div>
                                    @endforeach
                                </td>
                            </tr>
                        @endif
                        <tr>
                            <td>
                                <i class="ti ti-check text-success me-1"></i>{{ __('Processed payroll (Earned)') }}
                                @if($monthsProcessed > 0) <span class="badge bg-success ms-1" style="font-size:.65rem;">{{ $monthsProcessed }} {{ __('mo') }}</span>@endif
                            </td>
                            <td class="amt">{{ $f($actualGrossFromPayroll) }}</td>
                        </tr>
                        @if(($monthsUnprocessed ?? 0) > 0)
                        <tr style="background:#eff6ff;">
                            <td>
                                <i class="ti ti-clock text-primary me-1"></i>{{ __('Assumed (unprocessed)') }}
                                <span class="badge bg-primary ms-1" style="font-size:.65rem;">{{ $monthsUnprocessed }} {{ __('mo') }}</span>
                                <div class="text-muted" style="font-size:.7rem;margin-top:2px;">{{ __('@ :m / month', ['m' => $f($assumedMonthlyGross)]) }}</div>
                            </td>
                            <td class="amt">{{ $f($assumedGrossRemaining) }}</td>
                        </tr>
                        @endif
                        <tr class="tot"><td>{{ __('Projected Gross Annual Income') }}</td><td class="amt">{{ $f($grossAnnual) }}</td></tr>
                        <tr><td>{{ __('Tax Regime Chosen') }}</td><td class="amt"><span class="badge bg-{{ $regime === 'new' ? 'primary' : 'warning' }}">{{ strtoupper($regime) }}</span></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- TDS This FY --}}
    <div class="col-md-6">
        <div class="tc-card">
            <div class="card-header bg-dark text-white"><i class="ti ti-calendar-stats me-2"></i>{{ __('TDS Summary — FY') }} {{ $fy }}</div>
            <div class="card-body p-0">
                <table class="tc-tbl">
                    <tbody>
                        <tr><td>{{ __('Annual Tax Liability') }} ({{ strtoupper($regime) }})</td><td class="amt" style="font-size:1.1rem;font-weight:800;">{{ $f($chosenTax) }}</td></tr>
                        <tr><td>{{ __('TDS Deducted So Far') }} ({{ count($monthlyTdsHistory) }} {{ __('months') }})</td><td class="amt pos">{{ $f($tdsPaidSoFar) }}</td></tr>
                        <tr><td>{{ __('Remaining Tax') }}</td><td class="amt neg">{{ $f($remainingTax) }}</td></tr>
                        <tr class="tot"><td>{{ __('Projected Monthly TDS') }} ({{ $monthsRemaining }} {{ __('months left') }})</td><td class="amt">{{ $f($projectedMonthlyTds) }}/{{ __('month') }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- New Regime --}}
    <div class="col-md-6">
        <div class="tc-card regime-box {{ $regime === 'new' ? 'regime-active' : 'regime-inactive' }}">
            <div class="card-header bg-success text-white" style="margin:-16px -16px 12px;border-radius:8px 8px 0 0;padding:12px 16px;">
                <i class="ti ti-sparkles me-1"></i>{{ __('New Tax Regime (FY 25-26)') }}
                @if($regime === 'new') <span class="badge bg-warning text-dark ms-2">{{ __('ACTIVE') }}</span> @endif
            </div>

            <table class="tc-tbl">
                <tr><td>{{ __('Gross Annual Income') }}</td><td class="amt">{{ $f($grossAnnual) }}</td></tr>
                <tr><td>{{ __('Less: Standard Deduction') }}</td><td class="amt neg">-{{ $f($stdDeductionNew) }}</td></tr>
                <tr class="tot"><td>{{ __('Taxable Income') }}</td><td class="amt">{{ $f($taxableNew) }}</td></tr>
            </table>

            <table class="tc-tbl mt-2">
                <thead><tr><th>{{ __('Slab') }}</th><th class="amt">{{ __('Income') }}</th><th class="amt">{{ __('Rate') }}</th><th class="amt">{{ __('Tax') }}</th></tr></thead>
                <tbody>
                    @foreach($newSlabCalc['breakdown'] as $s)
                    <tr class="slab-row">
                        <td>{{ number_format($s['from']) }} — {{ $s['to'] ? number_format($s['to']) : __('Above') }}</td>
                        <td class="amt">{{ $f($s['taxable']) }}</td>
                        <td class="amt"><span class="rate-badge" style="background:{{ $s['rate'] > 0 ? '#fee2e2' : '#dcfce7' }};color:{{ $s['rate'] > 0 ? '#991b1b' : '#166534' }};">{{ $s['rate'] }}%</span></td>
                        <td class="amt">{{ $f($s['tax']) }}</td>
                    </tr>
                    @endforeach
                    <tr><td colspan="3"><strong>{{ __('Tax Before Cess') }}</strong></td><td class="amt"><strong>{{ $f($newTaxBeforeCess) }}</strong></td></tr>
                    @if($newRebate > 0)
                    <tr><td colspan="3" class="pos">{{ __('Less: Rebate u/s 87A (Income ≤ ₹7L)') }}</td><td class="amt pos">-{{ $f($newRebate) }}</td></tr>
                    @endif
                    <tr><td colspan="3">{{ __('Health & Education Cess (4%)') }}</td><td class="amt">{{ $f($newCess) }}</td></tr>
                    <tr class="tot"><td colspan="3"><strong>{{ __('Total Tax (New Regime)') }}</strong></td><td class="amt" style="font-size:1.05rem;">{{ $f($newTotalTax) }}</td></tr>
                    <tr><td colspan="3">{{ __('Monthly TDS') }}</td><td class="amt"><strong>{{ $f(round($newTotalTax / 12)) }}</strong></td></tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Old Regime --}}
    <div class="col-md-6">
        <div class="tc-card regime-box {{ $regime === 'old' ? 'regime-active' : 'regime-inactive' }}">
            <div class="card-header bg-warning text-dark" style="margin:-16px -16px 12px;border-radius:8px 8px 0 0;padding:12px 16px;">
                <i class="ti ti-building-bank me-1"></i>{{ __('Old Tax Regime') }}
                @if($regime === 'old') <span class="badge bg-primary ms-2">{{ __('ACTIVE') }}</span> @endif
            </div>

            <table class="tc-tbl">
                <tr><td>{{ __('Gross Annual Income') }}</td><td class="amt">{{ $f($grossAnnual) }}</td></tr>
                @foreach($oldDeductionDetails as $od)
                <tr><td>{{ __('Less:') }} {{ $od['name'] }}</td><td class="amt neg">-{{ $f($od['amount']) }}</td></tr>
                @endforeach
                <tr class="tot"><td>{{ __('Taxable Income') }}</td><td class="amt">{{ $f($taxableOld) }}</td></tr>
            </table>

            <table class="tc-tbl mt-2">
                <thead><tr><th>{{ __('Slab') }}</th><th class="amt">{{ __('Income') }}</th><th class="amt">{{ __('Rate') }}</th><th class="amt">{{ __('Tax') }}</th></tr></thead>
                <tbody>
                    @foreach($oldSlabCalc['breakdown'] as $s)
                    <tr class="slab-row">
                        <td>{{ number_format($s['from']) }} — {{ $s['to'] ? number_format($s['to']) : __('Above') }}</td>
                        <td class="amt">{{ $f($s['taxable']) }}</td>
                        <td class="amt"><span class="rate-badge" style="background:{{ $s['rate'] > 0 ? '#fee2e2' : '#dcfce7' }};color:{{ $s['rate'] > 0 ? '#991b1b' : '#166534' }};">{{ $s['rate'] }}%</span></td>
                        <td class="amt">{{ $f($s['tax']) }}</td>
                    </tr>
                    @endforeach
                    <tr><td colspan="3"><strong>{{ __('Tax Before Cess') }}</strong></td><td class="amt"><strong>{{ $f($oldTaxBeforeCess) }}</strong></td></tr>
                    @if($oldRebate > 0)
                    <tr><td colspan="3" class="pos">{{ __('Less: Rebate u/s 87A (Income ≤ ₹5L)') }}</td><td class="amt pos">-{{ $f($oldRebate) }}</td></tr>
                    @endif
                    <tr><td colspan="3">{{ __('Health & Education Cess (4%)') }}</td><td class="amt">{{ $f($oldCess) }}</td></tr>
                    <tr class="tot"><td colspan="3"><strong>{{ __('Total Tax (Old Regime)') }}</strong></td><td class="amt" style="font-size:1.05rem;">{{ $f($oldTotalTax) }}</td></tr>
                    <tr><td colspan="3">{{ __('Monthly TDS') }}</td><td class="amt"><strong>{{ $f(round($oldTotalTax / 12)) }}</strong></td></tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Savings --}}
    <div class="col-12">
        <div class="tc-card">
            <div class="card-body text-center py-3">
                @php $saving = abs($newTotalTax - $oldTotalTax); $better = $newTotalTax <= $oldTotalTax ? 'NEW' : 'OLD'; @endphp
                <span class="badge bg-{{ $better === 'NEW' ? 'success' : 'warning' }} fs-6 px-3 py-2">
                    <i class="ti ti-trophy me-1"></i>{{ $better }} {{ __('REGIME saves') }} {{ $f($saving) }}/{{ __('year') }} ({{ $f(round($saving / 12)) }}/{{ __('month') }})
                </span>
            </div>
        </div>
    </div>

    {{-- Monthly TDS History --}}
    @if(count($monthlyTdsHistory) > 0)
    <div class="col-12">
        <div class="tc-card">
            <div class="card-header bg-dark text-white"><i class="ti ti-chart-bar me-2"></i>{{ __('Monthly TDS Deducted — FY') }} {{ $fy }}</div>
            <div class="card-body">
                @php $maxTds = max(array_column($monthlyTdsHistory, 'total_tds')); @endphp
                <div class="tds-timeline">
                    @foreach($monthlyTdsHistory as $mh)
                    <div class="tds-month">
                        <div class="m-label">{{ \Carbon\Carbon::parse($mh['month'] . '-01')->format('M Y') }}</div>
                        <div class="m-bar">
                            @php $pct = $maxTds > 0 ? ($mh['total_tds'] / $maxTds) * 100 : 0; @endphp
                            <div class="m-bar-fill" style="width:{{ $pct }}%;background:{{ $mh['additional_tds'] > 0 ? 'linear-gradient(90deg, #4361ee ' . (($mh['base_tds'] / max($mh['total_tds'],1)) * 100) . '%, #f59e0b 0%)' : '#4361ee' }};"></div>
                        </div>
                        <div class="m-amt">{{ $f($mh['total_tds']) }}</div>
                    </div>
                    @endforeach
                </div>
                <div class="d-flex gap-3 mt-2" style="font-size:.72rem;color:#94a3b8;">
                    <span><span style="display:inline-block;width:12px;height:12px;background:#4361ee;border-radius:2px;"></span> {{ __('Regular TDS') }}</span>
                    <span><span style="display:inline-block;width:12px;height:12px;background:#f59e0b;border-radius:2px;"></span> {{ __('Additional (OT/Arrears/Bonus)') }}</span>
                </div>
                <div class="mt-2 text-end" style="font-size:.85rem;">
                    <strong>{{ __('Total TDS Paid:') }}</strong> {{ $f($tdsPaidSoFar) }} / {{ $f($chosenTax) }}
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
