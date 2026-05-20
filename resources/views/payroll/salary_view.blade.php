@extends('layouts.admin')
@section('page-title')
    {{ __('Salary Structure') }} - {{ $employee->name }}
@endsection

@section('content')
    @include('payroll._nav')

    @php
        $previewMonth = $previewMonth ?? now()->format('Y-m');
        $totals = $breakdown['totals'] ?? [];
        $earnings = collect($breakdown['earnings'] ?? []);
        $statDeductions = collect($breakdown['statutory']['deductions'] ?? []);
        $manualDeductions = collect($breakdown['deductions'] ?? [])->map(function ($item) {
            $item['frequency'] = $item['frequency'] ?? 'one-time';
            return $item;
        });
        $allDeductions = $statDeductions->concat($manualDeductions)->filter(fn($i) => (float)($i['amount'] ?? 0) > 0)->values();
        $benefits = collect($breakdown['statutory']['benefits'] ?? [])->filter(fn($i) => (float)($i['amount'] ?? 0) > 0)->values();
        $specialAllowanceMonthlyTotal = collect($specialAllowances ?? [])->sum('amount');
        $specialDeductionMonthlyTotal = collect($specialDeductions ?? [])->sum('amount');
    @endphp

    <div class="row mb-3">
        <div class="col-md-6">
            <h5 class="mb-0">{{ $employee->name }} ({{ $employee->employee_id ?? 'EMP-' . $employee->id }})</h5>
        </div>
        <div class="col-md-6">
            <form method="GET" action="{{ route('payroll.employee.salary.view', $employee->id) }}" class="d-flex justify-content-md-end gap-2">
                <input type="month" name="preview_month" class="form-control form-control-sm" value="{{ $previewMonth }}" style="max-width:180px;">
                <button class="btn btn-sm btn-primary" type="submit">{{ __('Preview') }}</button>
                <a href="{{ route('payroll.employee.salary') }}" class="btn btn-sm btn-outline-secondary">{{ __('Back') }}</a>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">{{ __('Earnings') }}</h6>
                    <div class="d-flex gap-2">
                        <span class="badge bg-light text-dark">{{ \Carbon\Carbon::parse($previewMonth . '-01')->format('M Y') }}</span>
                        @if($specialAllowanceMonthlyTotal > 0)
                            <span class="badge bg-warning text-dark">{{ __('Added') }}: {{ \Auth::user()->priceFormat($specialAllowanceMonthlyTotal) }}</span>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('payroll.employee.salary.special.allowance.store', $employee->id) }}" class="row g-2 mb-3">
                        @csrf
                        <input type="hidden" name="month" value="{{ $previewMonth }}">
                        <div class="col-md-3">
                            <label class="form-label">{{ __('Name') }}</label>
                            <input type="text" name="title" class="form-control form-control-sm" placeholder="{{ __('Name') }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('Amount') }}</label>
                            <input type="number" step="0.01" min="0.01" name="amount" class="form-control form-control-sm" placeholder="0.00" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Remarks') }}</label>
                            <input type="text" name="remarks" maxlength="255" class="form-control form-control-sm" placeholder="{{ __('Great performance bonus') }}">
                        </div>
                        <div class="col-md-2 d-grid">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-sm btn-success"><i class="ti ti-device-floppy me-1"></i>{{ __('Save') }}</button>
                        </div>
                    </form>

                    @if(($specialAllowances ?? collect())->isNotEmpty())
                        <div class="mb-2 d-flex flex-wrap gap-2">
                            @foreach($specialAllowances as $allowance)
                                <span class="badge bg-warning text-dark d-inline-flex align-items-center gap-2">
                                    {{ $allowance->title ?: 'Bonus' }}: {{ \Auth::user()->priceFormat($allowance->amount) }}
                                    <form method="POST" action="{{ route('payroll.employee.salary.special.allowance.delete', [$employee->id, $allowance->id]) }}" onsubmit="return confirm('{{ __('Remove this bonus?') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-link p-0 text-danger"><i class="ti ti-x"></i></button>
                                    </form>
                                </span>
                            @endforeach
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>{{ __('Component') }}</th>
                                    <th class="text-end">{{ __('Annual') }}</th>
                                    <th class="text-end">{{ __('Monthly') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($earnings as $item)
                                    @php
                                        $annual = (float)($item['amount'] ?? 0);
                                        $monthly = ($item['frequency'] ?? 'monthly') === 'one-time' ? $annual : round($annual / 12, 2);
                                    @endphp
                                    <tr>
                                        <td>{{ $item['name'] ?? '-' }}</td>
                                        <td class="text-end">{{ \Auth::user()->priceFormat($annual) }}</td>
                                        <td class="text-end">{{ \Auth::user()->priceFormat($monthly) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th>{{ __('Gross Salary') }}</th>
                                    <th class="text-end">{{ \Auth::user()->priceFormat($totals['gross_annual'] ?? 0) }}</th>
                                    <th class="text-end">{{ \Auth::user()->priceFormat($totals['gross_monthly'] ?? 0) }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">{{ __('Deductions') }}</h6>
                    @if($specialDeductionMonthlyTotal > 0)
                        <span class="badge bg-danger">{{ __('Added') }}: {{ \Auth::user()->priceFormat($specialDeductionMonthlyTotal) }}</span>
                    @endif
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('payroll.employee.salary.special.deduction.store', $employee->id) }}" class="row g-2 mb-3">
                        @csrf
                        <input type="hidden" name="month" value="{{ $previewMonth }}">
                        <div class="col-md-3">
                            <label class="form-label">{{ __('Name') }}</label>
                            <input type="text" name="title" class="form-control form-control-sm" placeholder="{{ __('Penalty') }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('Amount') }}</label>
                            <input type="number" step="0.01" min="0.01" name="amount" class="form-control form-control-sm" placeholder="0.00" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Remarks') }}</label>
                            <input type="text" name="remarks" maxlength="255" class="form-control form-control-sm" placeholder="{{ __('Late coming penalty') }}">
                        </div>
                        <div class="col-md-2 d-grid">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-sm btn-danger"><i class="ti ti-device-floppy me-1"></i>{{ __('Save') }}</button>
                        </div>
                    </form>

                    @if(($specialDeductions ?? collect())->isNotEmpty())
                        <div class="mb-2 d-flex flex-wrap gap-2">
                            @foreach($specialDeductions as $deduction)
                                <span class="badge bg-danger text-white d-inline-flex align-items-center gap-2">
                                    {{ $deduction->title ?: 'Penalty' }}: {{ \Auth::user()->priceFormat($deduction->amount) }}
                                    <form method="POST" action="{{ route('payroll.employee.salary.special.deduction.delete', [$employee->id, $deduction->id]) }}" onsubmit="return confirm('{{ __('Remove this deduction?') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-link p-0 text-white"><i class="ti ti-x"></i></button>
                                    </form>
                                </span>
                            @endforeach
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>{{ __('Component') }}</th>
                                    <th class="text-end">{{ __('Annual') }}</th>
                                    <th class="text-end">{{ __('Monthly') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($allDeductions as $item)
                                    @php
                                        $annual = (float)($item['amount'] ?? 0);
                                        $monthly = ($item['frequency'] ?? 'monthly') === 'one-time' ? $annual : round($annual / 12, 2);
                                    @endphp
                                    <tr>
                                        <td>{{ $item['name'] ?? '-' }}</td>
                                        <td class="text-end">-{{ \Auth::user()->priceFormat($annual) }}</td>
                                        <td class="text-end">-{{ \Auth::user()->priceFormat($monthly) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th>{{ __('Total Deductions') }}</th>
                                    <th class="text-end">-{{ \Auth::user()->priceFormat($totals['deductions_annual'] ?? 0) }}</th>
                                    <th class="text-end">-{{ \Auth::user()->priceFormat($totals['deductions_monthly'] ?? 0) }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><h6 class="mb-0">{{ __('Employer Contributions') }}</h6></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>{{ __('Component') }}</th>
                                    <th class="text-end">{{ __('Annual') }}</th>
                                    <th class="text-end">{{ __('Monthly') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($benefits as $item)
                                    <tr>
                                        <td>{{ $item['name'] ?? '-' }}</td>
                                        <td class="text-end">{{ \Auth::user()->priceFormat($item['amount'] ?? 0) }}</td>
                                        <td class="text-end">{{ \Auth::user()->priceFormat(round(($item['amount'] ?? 0) / 12, 2)) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th>{{ __('Total Employer Cost') }}</th>
                                    <th class="text-end">{{ \Auth::user()->priceFormat($totals['benefits_annual'] ?? 0) }}</th>
                                    <th class="text-end">{{ \Auth::user()->priceFormat($totals['benefits_monthly'] ?? 0) }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            @if(!empty($yearlyIncrements) && $yearlyIncrements->isNotEmpty())
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="ti ti-chart-arrows-vertical me-1"></i>{{ __('Increment History (Year-wise)') }}</h6>
                    <div class="d-flex align-items-center gap-3">
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input" type="checkbox" id="incShowMonths">
                            <label class="form-check-label" for="incShowMonths" style="font-size:.82rem;">{{ __('Show month-wise detail') }}</label>
                        </div>
                        <span class="badge bg-info">{{ $yearlyIncrements->count() }} {{ __('year(s)') }}</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle" id="incYearTable">
                            <thead>
                                <tr>
                                    <th width="40"></th>
                                    <th width="100">{{ __('Year') }}</th>
                                    <th class="text-end">{{ __('Total Increment') }}</th>
                                    <th class="text-end">{{ __('%') }}</th>
                                    <th class="text-end">{{ __('New CTC') }}</th>
                                    <th class="text-center">{{ __('Events') }}</th>
                                    <th>{{ __('Remarks') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($yearlyIncrements as $yIdx => $inc)
                                <tr class="inc-year-row inc-clickable" data-target="yearStruct{{ $yIdx }}" style="cursor:pointer;">
                                    <td class="text-center"><i class="ti ti-chevron-right inc-chev"></i></td>
                                    <td><strong>{{ $inc['year'] }}</strong></td>
                                    <td class="text-end text-success">+{{ \Auth::user()->priceFormat($inc['increment_amount']) }}</td>
                                    <td class="text-end">
                                        <span class="badge bg-success">{{ number_format((float) $inc['increment_percentage'], 2) }}%</span>
                                    </td>
                                    <td class="text-end"><strong>{{ \Auth::user()->priceFormat($inc['new_ctc']) }}</strong></td>
                                    <td class="text-center">{{ $inc['count'] }}</td>
                                    <td><small class="text-muted">{{ $inc['remarks'] ?? '—' }}</small></td>
                                </tr>
                                <tr class="inc-struct-row d-none" id="yearStruct{{ $yIdx }}">
                                    <td colspan="7" class="bg-light p-3">
                                        <div class="mb-2"><strong><i class="ti ti-layout-grid me-1"></i>{{ __('Salary Structure') }}</strong> — <span class="text-muted">{{ __('Year-end CTC') }}: {{ \Auth::user()->priceFormat($inc['new_ctc']) }} ({{ __('annual') }})</span></div>
                                        @if(!empty($inc['components']))
                                            <div class="row g-2">
                                                @foreach($inc['components'] as $name => $monthly)
                                                <div class="col-md-4 col-sm-6">
                                                    <div class="d-flex justify-content-between border rounded px-3 py-2 bg-white">
                                                        <span class="text-muted" style="font-size:.82rem;">{{ $name }}</span>
                                                        <strong style="font-size:.85rem;">{{ \Auth::user()->priceFormat($monthly) }}</strong>
                                                    </div>
                                                </div>
                                                @endforeach
                                                <div class="col-md-4 col-sm-6">
                                                    <div class="d-flex justify-content-between border border-success rounded px-3 py-2" style="background:#f0fdf4;">
                                                        <span class="text-success" style="font-size:.82rem;font-weight:600;">{{ __('Total Gross (Monthly)') }}</span>
                                                        <strong class="text-success" style="font-size:.85rem;">{{ \Auth::user()->priceFormat(array_sum($inc['components'])) }}</strong>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                                <tr class="inc-months-row d-none">
                                    <td colspan="7" class="bg-light p-2">
                                        <table class="table table-sm mb-0" style="font-size:.82rem;">
                                            <thead>
                                                <tr class="text-muted">
                                                    <th width="40"></th>
                                                    <th width="140"><i class="ti ti-calendar me-1"></i>{{ __('Month') }}</th>
                                                    <th class="text-end">{{ __('Increment') }}</th>
                                                    <th class="text-end">{{ __('%') }}</th>
                                                    <th class="text-end">{{ __('New CTC') }}</th>
                                                    <th>{{ __('Remarks') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($inc['events'] as $eIdx => $ev)
                                                <tr class="inc-clickable" data-target="evStruct{{ $yIdx }}_{{ $eIdx }}" style="cursor:pointer;">
                                                    <td class="text-center"><i class="ti ti-chevron-right inc-chev"></i></td>
                                                    <td><strong>{{ $ev['month'] }}</strong></td>
                                                    <td class="text-end text-success">+{{ \Auth::user()->priceFormat($ev['increment_amount']) }}</td>
                                                    <td class="text-end"><span class="badge bg-success">{{ number_format((float) $ev['increment_percentage'], 2) }}%</span></td>
                                                    <td class="text-end"><strong>{{ \Auth::user()->priceFormat($ev['new_ctc']) }}</strong></td>
                                                    <td><small class="text-muted">{{ $ev['remarks'] ?? '—' }}</small></td>
                                                </tr>
                                                <tr class="inc-struct-row d-none" id="evStruct{{ $yIdx }}_{{ $eIdx }}">
                                                    <td colspan="6" class="bg-white p-3 border">
                                                        <div class="mb-2"><strong><i class="ti ti-layout-grid me-1"></i>{{ __('Salary Structure') }}</strong> — <span class="text-muted">{{ $ev['month'] }} — CTC {{ \Auth::user()->priceFormat($ev['new_ctc']) }}</span></div>
                                                        @if(!empty($ev['components']))
                                                            <div class="row g-2">
                                                                @foreach($ev['components'] as $name => $monthly)
                                                                <div class="col-md-4 col-sm-6">
                                                                    <div class="d-flex justify-content-between border rounded px-3 py-2">
                                                                        <span class="text-muted" style="font-size:.78rem;">{{ $name }}</span>
                                                                        <strong style="font-size:.82rem;">{{ \Auth::user()->priceFormat($monthly) }}</strong>
                                                                    </div>
                                                                </div>
                                                                @endforeach
                                                                <div class="col-md-4 col-sm-6">
                                                                    <div class="d-flex justify-content-between border border-success rounded px-3 py-2" style="background:#f0fdf4;">
                                                                        <span class="text-success" style="font-size:.78rem;font-weight:600;">{{ __('Gross (Monthly)') }}</span>
                                                                        <strong class="text-success" style="font-size:.82rem;">{{ \Auth::user()->priceFormat(array_sum($ev['components'])) }}</strong>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endif
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @push('script-page')
            <script>
            (function(){
                // Month-wise detail toggle
                var cb = document.getElementById('incShowMonths');
                if (cb) {
                    cb.addEventListener('change', function(){
                        document.querySelectorAll('.inc-months-row').forEach(function(r){
                            r.classList.toggle('d-none', !cb.checked);
                        });
                    });
                }
                // Clickable rows to expand salary structure
                document.querySelectorAll('#incYearTable .inc-clickable').forEach(function(row){
                    row.addEventListener('click', function(ev){
                        // Ignore clicks that happen on interactive children
                        if (ev.target.closest('button,a,input,label')) return;
                        var targetId = row.getAttribute('data-target');
                        var target = document.getElementById(targetId);
                        if (!target) return;
                        target.classList.toggle('d-none');
                        var chev = row.querySelector('.inc-chev');
                        if (chev) {
                            chev.classList.toggle('ti-chevron-right');
                            chev.classList.toggle('ti-chevron-down');
                        }
                    });
                });
            })();
            </script>
            @endpush
            @endif

            <div class="card">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div><strong>{{ __('Net Pay (Monthly)') }}</strong></div>
                    <h4 class="mb-0 text-success">{{ \Auth::user()->priceFormat($totals['net_monthly'] ?? 0) }}</h4>
                </div>
            </div>
        </div>
    </div>
@endsection
