@extends('layouts.admin')
@section('page-title')
    {{ __('Salary Increment') }}
@endsection

@push('css-page')
<style>
    .inc-card { border-radius: 10px; }
    .inc-badge-up { background: #dcfce7; color: #166534; }
    .inc-badge-dn { background: #fee2e2; color: #991b1b; }
    .arrears-tag { background: #fef3c7; color: #92400e; border-radius: 4px; padding: 2px 8px; font-size: .72rem; font-weight: 600; }
    .arrears-paid { background: #dcfce7; color: #166534; }
    .arrears-pending { background: #fee2e2; color: #991b1b; }
</style>
@endpush

@section('content')
    @include('payroll._nav')

    <div class="row">
        {{-- Increment Form --}}
        <div class="col-lg-4 col-md-5">
            <div class="card inc-card">
                <div class="card-header"><h5 class="mb-0"><i class="ti ti-trending-up me-1"></i>{{ __('Apply Salary Increment') }}</h5></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('payroll.salary.increment.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">{{ __('Employee') }} <span class="text-danger">*</span></label>
                            <select name="employee_id" class="form-select" id="inc-employee" required>
                                <option value="">{{ __('Select Employee') }}</option>
                                @foreach($employees as $emp)
                                    @php
                                        $sal = \App\Models\EmployeeSalary::where('employee_id', $emp->id)->first();
                                    @endphp
                                    <option value="{{ $emp->id }}" data-ctc="{{ $sal->ctc ?? 0 }}">
                                        {{ $emp->name }} {{ $sal ? '(CTC: ' . number_format($sal->ctc, 0) . ')' : '(No salary)' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('Current CTC (Annual)') }}</label>
                            <input type="text" class="form-control" id="inc-current-ctc" readonly>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label">{{ __('Increment %') }}</label>
                                <input type="number" step="0.01" min="0" class="form-control" id="inc-pct" placeholder="e.g. 50">
                            </div>
                            <div class="col-6">
                                <label class="form-label">{{ __('New CTC (Annual)') }} <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="1" name="new_ctc" class="form-control" id="inc-new-ctc" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-success" id="inc-diff-label"></label>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('Effective Date') }} <span class="text-danger">*</span></label>
                            <input type="date" name="effective_date" class="form-control" required value="{{ date('Y-m-d') }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('Arrears Payout Month') }}</label>
                            <input type="month" name="arrears_month" class="form-control" id="inc-arrears-month">
                            <small class="text-muted">{{ __('Leave empty if no arrears. Set the month when arrears should be paid.') }}</small>
                        </div>

                        <div class="mb-3" id="inc-arrears-preview" style="display:none;">
                            <div class="alert alert-warning py-2 mb-0">
                                <small>
                                    <i class="ti ti-info-circle me-1"></i>
                                    <strong>{{ __('Arrears Preview') }}:</strong>
                                    <span id="inc-arrears-text"></span>
                                </small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('Remarks') }}</label>
                            <textarea name="remarks" class="form-control" rows="2" placeholder="{{ __('e.g. Annual appraisal, Promotion') }}"></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="ti ti-check me-1"></i>{{ __('Apply Increment') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Increment History --}}
        <div class="col-lg-8 col-md-7">
            <div class="card inc-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="ti ti-history me-1"></i>{{ __('Increment History') }}</h5>
                    <span class="badge bg-primary">{{ $history->count() }} {{ __('records') }}</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>{{ __('Employee') }}</th>
                                    <th>{{ __('Old CTC') }}</th>
                                    <th>{{ __('New CTC') }}</th>
                                    <th class="text-center">{{ __('Increment') }}</th>
                                    <th>{{ __('Effective') }}</th>
                                    <th>{{ __('Arrears') }}</th>
                                    <th>{{ __('Remarks') }}</th>
                                    <th class="text-center">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($history as $row)
                                    <tr>
                                        <td>
                                            <strong>{{ $row->employee->name ?? 'N/A' }}</strong>
                                            <br><small class="text-muted">{{ $row->employee->employee_id ?? '' }}</small>
                                        </td>
                                        <td>{{ \Auth::user()->priceFormat(round($row->old_ctc)) }}</td>
                                        <td><strong>{{ \Auth::user()->priceFormat(round($row->new_ctc)) }}</strong></td>
                                        <td class="text-center">
                                            <span class="badge {{ $row->increment_amount >= 0 ? 'inc-badge-up' : 'inc-badge-dn' }}">
                                                {{ $row->increment_amount >= 0 ? '+' : '' }}{{ number_format($row->increment_percentage, 1) }}%
                                            </span>
                                            <br><small class="text-muted">{{ \Auth::user()->priceFormat(round(abs($row->increment_amount))) }}</small>
                                        </td>
                                        <td>{{ \Carbon\Carbon::parse($row->effective_date)->format('d M Y') }}</td>
                                        <td>
                                            @if($row->arrears_month)
                                                <span class="arrears-tag {{ $row->arrears_paid ? 'arrears-paid' : 'arrears-pending' }}">
                                                    {{ \Carbon\Carbon::parse($row->arrears_month . '-01')->format('M Y') }}
                                                    {{ $row->arrears_paid ? '(Paid)' : '(Pending)' }}
                                                </span>
                                                <br><small class="text-muted">{{ \Auth::user()->priceFormat(round($row->arrears_amount)) }}</small>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td><small>{{ $row->remarks ?? '—' }}</small></td>
                                        <td class="text-center">
                                            @if(!$row->arrears_paid)
                                                <form method="POST" action="{{ route('payroll.salary.increment.delete', $row->id) }}" class="d-inline" onsubmit="return confirm('{{ __('Revert this increment?') }}')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" data-bs-toggle="tooltip" title="{{ __('Revert') }}">
                                                        <i class="ti ti-arrow-back-up"></i>
                                                    </button>
                                                </form>
                                            @else
                                                <span class="badge bg-success"><i class="ti ti-lock me-1"></i>{{ __('Locked') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">
                                            <i class="ti ti-trending-up" style="font-size:2rem;"></i>
                                            <p class="mt-2 mb-0">{{ __('No increment history yet.') }}</p>
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
@endsection

@push('script-page')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const empSelect = document.getElementById('inc-employee');
    const currentCtc = document.getElementById('inc-current-ctc');
    const pctInput = document.getElementById('inc-pct');
    const newCtcInput = document.getElementById('inc-new-ctc');
    const diffLabel = document.getElementById('inc-diff-label');
    const arrearsMonth = document.getElementById('inc-arrears-month');
    const arrearsPreview = document.getElementById('inc-arrears-preview');
    const arrearsText = document.getElementById('inc-arrears-text');

    empSelect.addEventListener('change', function() {
        const opt = this.options[this.selectedIndex];
        const ctc = parseFloat(opt.dataset.ctc || 0);
        currentCtc.value = ctc > 0 ? ctc.toLocaleString('en-IN') : '';
        newCtcInput.value = '';
        pctInput.value = '';
        diffLabel.textContent = '';
        calcArrears();
    });

    pctInput.addEventListener('input', function() {
        const opt = empSelect.options[empSelect.selectedIndex];
        const ctc = parseFloat(opt.dataset.ctc || 0);
        if (ctc > 0 && this.value) {
            const pct = parseFloat(this.value);
            newCtcInput.value = Math.round(ctc * (1 + pct / 100));
            updateDiff(ctc);
        }
        calcArrears();
    });

    newCtcInput.addEventListener('input', function() {
        const opt = empSelect.options[empSelect.selectedIndex];
        const ctc = parseFloat(opt.dataset.ctc || 0);
        if (ctc > 0 && this.value) {
            const newVal = parseFloat(this.value);
            pctInput.value = ((newVal - ctc) / ctc * 100).toFixed(2);
            updateDiff(ctc);
        }
        calcArrears();
    });

    arrearsMonth.addEventListener('change', function() { calcArrears(); });

    function updateDiff(oldCtc) {
        const newVal = parseFloat(newCtcInput.value || 0);
        const diff = newVal - oldCtc;
        const monthly = Math.round(diff / 12);
        if (diff !== 0) {
            diffLabel.textContent = (diff > 0 ? '+' : '') + diff.toLocaleString('en-IN') + '/yr (' + (diff > 0 ? '+' : '') + monthly.toLocaleString('en-IN') + '/mo)';
            diffLabel.className = 'form-label ' + (diff >= 0 ? 'text-success' : 'text-danger');
        } else {
            diffLabel.textContent = '';
        }
    }

    function calcArrears() {
        const opt = empSelect.options[empSelect.selectedIndex];
        const oldCtc = parseFloat(opt.dataset.ctc || 0);
        const newCtc = parseFloat(newCtcInput.value || 0);
        const effDate = document.querySelector('[name="effective_date"]').value;
        const arrMonth = arrearsMonth.value;

        if (oldCtc > 0 && newCtc > 0 && effDate && arrMonth) {
            const oldMonthly = Math.round(oldCtc / 12);
            const newMonthly = Math.round(newCtc / 12);
            const monthlyDiff = newMonthly - oldMonthly;

            const effM = new Date(effDate.substring(0, 7) + '-01');
            const arrM = new Date(arrMonth + '-01');
            let months = (arrM.getFullYear() - effM.getFullYear()) * 12 + (arrM.getMonth() - effM.getMonth());
            if (months < 0) months = 0;

            const arrearsAmt = monthlyDiff * months;
            arrearsText.textContent = monthlyDiff.toLocaleString('en-IN') + '/mo x ' + months + ' months = ' + arrearsAmt.toLocaleString('en-IN') + ' (arrears in ' + arrMonth + ')';
            arrearsPreview.style.display = 'block';
        } else {
            arrearsPreview.style.display = 'none';
        }
    }
});
</script>
@endpush
