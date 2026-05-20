@extends('layouts.admin')
@section('page-title')
    {{ __('Payroll - Process') }}
@endsection

@push('css-page')
<style>
    .payroll-summary { background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%); border-radius: 12px; color: #fff; padding: 20px; }
    .payroll-summary .ps-item { text-align: center; }
    .payroll-summary .ps-value { font-size: 1.5rem; font-weight: 700; }
    .payroll-summary .ps-label { font-size: .75rem; opacity: .8; }

    .run-payroll-card { border: 2px solid #4361ee; border-radius: 12px; overflow: hidden; }
    .run-payroll-card .rp-header { background: linear-gradient(135deg, #1e293b 0%, #334155 100%); color: #fff; padding: 16px 20px; }
    .run-payroll-card .rp-header h5 { margin: 0; font-size: 1rem; font-weight: 700; }
    .run-payroll-card .rp-header .rp-sub { font-size: .75rem; opacity: .7; margin-top: 2px; }
    .run-payroll-card .rp-body { padding: 20px; }
    .month-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px; }
    .month-btn { padding: 8px 4px; border: 1px solid #e2e8f0; border-radius: 8px; text-align: center; cursor: pointer; font-size: .78rem; font-weight: 500; transition: all .15s; background: #fff; color: #334155; }
    .month-btn:hover { border-color: #4361ee; background: #eff6ff; color: #4361ee; }
    .month-btn.active { border-color: #4361ee; background: #4361ee; color: #fff; font-weight: 700; }
    .month-btn.current { border-color: #059669; }
    .month-btn.current::after { content: ''; display: block; width: 4px; height: 4px; background: #059669; border-radius: 50%; margin: 2px auto 0; }
    .year-selector { display: flex; align-items: center; gap: 8px; justify-content: center; margin-bottom: 12px; }
    .year-selector .year-nav { width: 32px; height: 32px; border-radius: 50%; border: 1px solid #e2e8f0; background: #fff; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: .9rem; color: #475569; transition: all .15s; }
    .year-selector .year-nav:hover { background: #4361ee; color: #fff; border-color: #4361ee; }
    .year-selector .year-display { font-size: 1.2rem; font-weight: 700; color: #1e293b; min-width: 60px; text-align: center; }
    .run-btn { background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%); border: none; color: #fff; font-weight: 700; font-size: .95rem; padding: 12px; border-radius: 10px; width: 100%; cursor: pointer; transition: all .2s; }
    .run-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(67,97,238,.4); }
    .run-btn:disabled { opacity: .5; cursor: not-allowed; transform: none; box-shadow: none; }
    .selected-month-label { text-align: center; font-size: .82rem; color: #4361ee; font-weight: 600; margin: 10px 0; min-height: 20px; }
</style>
@endpush

@section('content')
    @include('payroll._nav')

    {{-- Success / Error Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center shadow-sm mb-3" role="alert" style="border-left:4px solid #059669; border-radius:10px;">
            <div style="width:40px;height:40px;border-radius:50%;background:#059669;display:flex;align-items:center;justify-content:center;margin-right:12px;flex-shrink:0;">
                <i class="ti ti-circle-check text-white" style="font-size:1.3rem;"></i>
            </div>
            <div>
                <strong style="font-size:.95rem;">{{ __('Payroll Generated Successfully!') }}</strong>
                <div style="font-size:.82rem;color:#475569;margin-top:2px;">{{ session('success') }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center shadow-sm mb-3" role="alert" style="border-left:4px solid #dc2626; border-radius:10px;">
            <div style="width:40px;height:40px;border-radius:50%;background:#dc2626;display:flex;align-items:center;justify-content:center;margin-right:12px;flex-shrink:0;">
                <i class="ti ti-alert-circle text-white" style="font-size:1.3rem;"></i>
            </div>
            <div>
                <strong style="font-size:.95rem;">{{ __('Payroll Failed!') }}</strong>
                <div style="font-size:.82rem;color:#475569;margin-top:2px;">{{ session('error') }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        {{-- Run Payroll Card --}}
        <div class="col-lg-4 col-md-5">
            <div class="run-payroll-card">
                <div class="rp-header">
                    <h5><i class="ti ti-player-play me-2"></i>{{ __('Run Payroll') }}</h5>
                    <div class="rp-sub">{{ __('Select month and year, then click Run') }}</div>
                </div>
                <div class="rp-body">
                    <form method="POST" action="{{ route('payroll.process.run') }}" id="runPayrollForm">
                        @csrf
                        <input type="hidden" name="month" id="selectedMonth" value="{{ now()->format('Y-m') }}">

                        {{-- Year Selector --}}
                        <div class="year-selector">
                            <button type="button" class="year-nav" onclick="changeYear(-1)"><i class="ti ti-chevron-left"></i></button>
                            <div class="year-display" id="yearDisplay">{{ now()->year }}</div>
                            <button type="button" class="year-nav" onclick="changeYear(1)"><i class="ti ti-chevron-right"></i></button>
                        </div>

                        {{-- Month Grid --}}
                        <div class="month-grid" id="monthGrid">
                            @php $currentMonth = now()->format('Y-m'); @endphp
                            @for($m = 1; $m <= 12; $m++)
                                @php
                                    $mPad = str_pad($m, 2, '0', STR_PAD_LEFT);
                                    $mKey = now()->year . '-' . $mPad;
                                    $isActive = $mKey === $currentMonth;
                                    $isCurrent = $mKey === $currentMonth;
                                    $mName = \Carbon\Carbon::create()->month($m)->format('M');
                                @endphp
                                <div class="month-btn {{ $isActive ? 'active' : '' }} {{ $isCurrent ? 'current' : '' }}"
                                     data-month="{{ $mPad }}"
                                     onclick="selectMonth(this)">
                                    {{ $mName }}
                                </div>
                            @endfor
                        </div>

                        {{-- Selected Label --}}
                        <div class="selected-month-label" id="selectedLabel">
                            <i class="ti ti-calendar-event me-1"></i>{{ now()->format('F Y') }}
                        </div>

                        @if(empty($schedule))
                            <div class="alert alert-warning py-2 mb-2" style="font-size:.8rem;">
                                <i class="ti ti-alert-triangle me-1"></i>{{ __('Create pay schedule first.') }}
                            </div>
                        @endif

                        <button type="submit" class="run-btn" id="runPayrollBtn" {{ empty($schedule) ? 'disabled' : '' }}
                                onclick="return confirm('Run payroll for ' + document.getElementById('selectedLabel').textContent.trim() + '?')">
                            <i class="ti ti-player-play me-1"></i>{{ __('Run Payroll') }}
                        </button>
                    </form>

                    <div class="mt-2" style="font-size:.72rem;color:#94a3b8;">
                        <i class="ti ti-info-circle me-1"></i>{{ __('Re-running replaces existing payroll for the selected month.') }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Summary Card --}}
        <div class="col-lg-8 col-md-7">
            @if($recent->isNotEmpty())
                @php
                    $summaryGross = $recent->sum('gross_salary');
                    $summaryDeductions = $recent->sum('total_deductions');
                    $summaryNet = $recent->sum('net_salary');
                    $summaryEmployer = $recent->sum('employer_contribution');
                    $summaryCount = $recent->count();
                @endphp
                <div class="payroll-summary mb-3">
                    <div class="row g-3">
                        <div class="col ps-item">
                            <div class="ps-value">{{ $summaryCount }}</div>
                            <div class="ps-label">{{ __('Employees') }}</div>
                        </div>
                        <div class="col ps-item">
                            <div class="ps-value">{{ \Auth::user()->priceFormat($summaryGross) }}</div>
                            <div class="ps-label">{{ __('Total Gross') }}</div>
                        </div>
                        <div class="col ps-item">
                            <div class="ps-value">{{ \Auth::user()->priceFormat($summaryDeductions) }}</div>
                            <div class="ps-label">{{ __('Total Deductions') }}</div>
                        </div>
                        <div class="col ps-item">
                            <div class="ps-value">{{ \Auth::user()->priceFormat($summaryNet) }}</div>
                            <div class="ps-label">{{ __('Total Net Pay') }}</div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Filters --}}
            <div class="card mb-3">
                <div class="card-body py-3">
                    <form method="GET" action="{{ route('payroll.process') }}" id="payroll-filter-form">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-2">
                                <label class="form-label">{{ __('Year') }}</label>
                                <select name="filter_year" class="form-select form-select-sm">
                                    <option value="">{{ __('All Years') }}</option>
                                    @foreach($availableYears ?? [] as $y)
                                        <option value="{{ $y }}" {{ ($filterYear ?? '') == $y ? 'selected' : '' }}>{{ $y }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('Month') }}</label>
                                <select name="filter_month" class="form-select form-select-sm">
                                    <option value="">{{ __('All Months') }}</option>
                                    @for($i = 1; $i <= 12; $i++)
                                        <option value="{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}" {{ ($filterMonth ?? '') == str_pad($i, 2, '0', STR_PAD_LEFT) ? 'selected' : '' }}>
                                            {{ \Carbon\Carbon::create()->month($i)->format('F') }}
                                        </option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('Employee') }}</label>
                                <select name="filter_employee" class="form-select form-select-sm">
                                    <option value="">{{ __('All Employees') }}</option>
                                    @foreach($employees ?? [] as $emp)
                                        <option value="{{ $emp->id }}" {{ ($filterEmployee ?? '') == $emp->id ? 'selected' : '' }}>
                                            {{ $emp->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('Status') }}</label>
                                <select name="filter_status" class="form-select form-select-sm">
                                    <option value="">{{ __('All') }}</option>
                                    <option value="processed" {{ ($filterStatus ?? '') == 'processed' ? 'selected' : '' }}>{{ __('Processed') }}</option>
                                    <option value="draft" {{ ($filterStatus ?? '') == 'draft' ? 'selected' : '' }}>{{ __('Draft') }}</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <div class="d-flex gap-1 flex-wrap">
                                    <button type="submit" class="btn btn-sm btn-primary"><i class="ti ti-search"></i> {{ __('Filter') }}</button>
                                    <a href="{{ route('payroll.process.export', ['filter_year' => $filterYear, 'filter_month' => $filterMonth, 'filter_employee' => $filterEmployee, 'filter_status' => $filterStatus]) }}"
                                        class="btn btn-sm btn-success"
                                        data-bs-toggle="tooltip"
                                        title="{{ __('Download month-wise salary statement in Excel') }}">
                                        <i class="ti ti-file-export"></i> {{ __('Excel') }}
                                    </a>
                                    <a href="{{ route('payroll.process.pdf', ['filter_year' => $filterYear, 'filter_month' => $filterMonth, 'filter_employee' => $filterEmployee, 'filter_status' => $filterStatus]) }}"
                                        class="btn btn-sm btn-danger"
                                        data-bs-toggle="tooltip"
                                        title="{{ __('View & download salary statement as PDF') }}">
                                        <i class="ti ti-file-type-pdf"></i> {{ __('PDF') }}
                                    </a>
                                    @if(!empty($filterYear) && !empty($filterMonth) && in_array(\Auth::user()->type, ['company','hr']))
                                        <button type="button"
                                                class="btn btn-sm btn-outline-danger"
                                                data-bs-toggle="tooltip"
                                                title="{{ __('Delete filtered payroll records') }}"
                                                onclick="document.getElementById('payrollFilteredDeleteForm').submit();">
                                            <i class="ti ti-trash"></i> {{ __('Delete') }}
                                        </button>
                                    @endif
                                    <a href="{{ route('payroll.process') }}" class="btn btn-sm btn-outline-secondary"><i class="ti ti-refresh"></i></a>
                                </div>
                            </div>
                        </div>
                    </form>
                    @if(!empty($filterYear) && !empty($filterMonth) && in_array(\Auth::user()->type, ['company','hr']))
                        @php
                            $_delMonthLabel = \Carbon\Carbon::createFromFormat('Y-m', $filterYear.'-'.str_pad($filterMonth,2,'0',STR_PAD_LEFT))->format('F Y');
                        @endphp
                        <form id="payrollFilteredDeleteForm" method="POST" action="{{ route('payroll.process.delete.filtered') }}" class="d-none"
                              onsubmit="return confirm('{{ __('Delete all matching payroll records for') }} {{ $_delMonthLabel }}? {{ __('This cannot be undone.') }}');">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="filter_year"     value="{{ $filterYear }}">
                            <input type="hidden" name="filter_month"    value="{{ $filterMonth }}">
                            <input type="hidden" name="filter_employee" value="{{ $filterEmployee }}">
                            <input type="hidden" name="filter_status"   value="{{ $filterStatus }}">
                        </form>
                    @endif
                </div>
            </div>

            {{-- Payroll Table --}}
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="ti ti-file-invoice me-1"></i>{{ __('Payroll Records') }}</h5>
                    <span class="badge bg-primary">{{ $recent->count() }} {{ __('records') }}</span>
                </div>
                <div class="card-body table-border-style p-0">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>{{ __('Employee') }}</th>
                                    <th>{{ __('Month') }}</th>
                                    <th class="text-end">{{ __('Gross') }}</th>
                                    <th class="text-end">{{ __('Deductions') }}</th>
                                    <th class="text-end">{{ __('Net Pay') }}</th>
                                    <th class="text-center">{{ __('Status') }}</th>
                                    <th class="text-center">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recent as $row)
                                    <tr>
                                        <td>
                                            <strong>{{ $row->employee->name ?? 'N/A' }}</strong>
                                            <br><small class="text-muted">{{ $row->employee->employee_id ?? '' }}</small>
                                        </td>
                                        <td>{{ \Carbon\Carbon::parse($row->month . '-01')->format('M Y') }}</td>
                                        <td class="text-end">{{ \Auth::user()->priceFormat($row->gross_salary) }}</td>
                                        <td class="text-end text-danger">{{ \Auth::user()->priceFormat($row->total_deductions) }}</td>
                                        <td class="text-end"><strong>{{ \Auth::user()->priceFormat($row->net_salary) }}</strong></td>
                                        <td class="text-center">
                                            @if($row->is_locked)
                                                <span class="badge bg-success">{{ __('Processed') }}</span>
                                            @else
                                                <span class="badge bg-warning text-dark">{{ __('Draft') }}</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('payroll.slip', $row->id) }}" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="{{ __('View Salary Slip') }}">
                                                <i class="ti ti-file-text"></i>
                                            </a>
                                            @if(!empty($row->employee_id))
                                                <a href="{{ route('payroll.employee.salary.view', $row->employee_id) }}" class="btn btn-sm btn-outline-warning" data-bs-toggle="tooltip" title="{{ __('Edit Salary') }}">
                                                    <i class="ti ti-edit"></i>
                                                </a>
                                            @endif
                                            <a href="{{ route('payroll.slip', $row->id) }}?download=1" class="btn btn-sm btn-outline-success" data-bs-toggle="tooltip" title="{{ __('Download') }}">
                                                <i class="ti ti-download"></i>
                                            </a>
                                            <form method="POST" action="{{ route('payroll.process.delete', $row->id) }}" class="d-inline"
                                                  onsubmit="return confirm('{{ __('Delete this payroll record? This cannot be undone.') }}');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" data-bs-toggle="tooltip" title="{{ __('Delete') }}">
                                                    <i class="ti ti-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            <i class="ti ti-file-off" style="font-size:2rem;"></i>
                                            <p class="mt-2 mb-0">{{ __('No payroll records found. Run payroll to generate.') }}</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

@push('script-page')
<script>
(function() {
    var currentYear = {{ now()->year }};
    var currentMonthNum = {{ now()->month }};
    var selectedYear = currentYear;
    var selectedMonth = String(currentMonthNum).padStart(2, '0');
    var monthNames = ['', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

    window.changeYear = function(delta) {
        selectedYear += delta;
        document.getElementById('yearDisplay').textContent = selectedYear;
        updateMonthGrid();
        updateHidden();
    };

    window.selectMonth = function(el) {
        document.querySelectorAll('.month-btn').forEach(function(b) { b.classList.remove('active'); });
        el.classList.add('active');
        selectedMonth = el.getAttribute('data-month');
        updateHidden();
    };

    function updateMonthGrid() {
        var btns = document.querySelectorAll('.month-btn');
        btns.forEach(function(b) {
            b.classList.remove('current');
            var m = parseInt(b.getAttribute('data-month'));
            if (selectedYear === currentYear && m === currentMonthNum) {
                b.classList.add('current');
            }
        });
    }

    function updateHidden() {
        var val = selectedYear + '-' + selectedMonth;
        document.getElementById('selectedMonth').value = val;
        var mNum = parseInt(selectedMonth);
        document.getElementById('selectedLabel').innerHTML = '<i class="ti ti-calendar-event me-1"></i>' + monthNames[mNum] + ' ' + selectedYear;
    }

    // Safety net: always re-sync the hidden input right before the form is
    // submitted, so the POST always carries the month the user last picked,
    // even if earlier state updates were missed due to event ordering.
    var form = document.getElementById('runPayrollForm');
    if (form) {
        form.addEventListener('submit', function() {
            updateHidden();
        });
    }

    // On initial load, clear the "active" class that PHP set for the current
    // calendar month, then mark whichever month matches the hidden input so
    // the UI accurately reflects what will be submitted.
    document.querySelectorAll('.month-btn').forEach(function(b) { b.classList.remove('active'); });
    var initialBtn = document.querySelector('.month-btn[data-month="' + selectedMonth + '"]');
    if (initialBtn) initialBtn.classList.add('active');
})();
</script>
@endpush
@endsection
