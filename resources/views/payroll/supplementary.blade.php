@extends('layouts.admin')
@section('page-title')
    {{ __('Payroll - Supplementary') }}
@endsection

@section('content')
    @include('payroll._nav')

    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('Add Supplementary Adjustment') }}</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('payroll.supplementary.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">{{ __('Employee') }}</label>
                            <select name="employee_id" class="form-control select" required>
                                <option value="">{{ __('Select Employee') }}</option>
                                @foreach($employees as $employee)
                                    <option value="{{ $employee->id }}" {{ old('employee_id') == $employee->id ? 'selected' : '' }}>
                                        {{ $employee->name }}{{ !empty($employee->employee_id) ? ' - '.$employee->employee_id : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('Deducted Month') }}</label>
                                <input type="month" name="source_month" class="form-control" value="{{ old('source_month', now()->subMonth()->format('Y-m')) }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('Pay In Month') }}</label>
                                <input type="month" name="payout_month" class="form-control" value="{{ old('payout_month', now()->format('Y-m')) }}" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('Type') }}</label>
                            <select name="adjustment_type" class="form-control" required>
                                <option value="credit" {{ old('adjustment_type', 'credit') === 'credit' ? 'selected' : '' }}>{{ __('Credit / Pay Extra') }}</option>
                                <option value="debit" {{ old('adjustment_type') === 'debit' ? 'selected' : '' }}>{{ __('Debit / Recover') }}</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('Title') }}</label>
                            <input type="text" name="title" class="form-control" value="{{ old('title', 'Supplementary Leave Correction') }}" required>
                        </div>

                        <div class="row">
                            <div class="col-md-5 mb-3">
                                <label class="form-label">{{ __('Days') }}</label>
                                <input type="number" name="days" class="form-control" step="0.5" min="0" max="31" value="{{ old('days', 2) }}">
                            </div>
                            <div class="col-md-7 mb-3">
                                <label class="form-label">{{ __('Amount') }}</label>
                                <input type="number" name="amount" class="form-control" step="0.01" min="0.01" value="{{ old('amount') }}" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('Remarks') }}</label>
                            <textarea name="remarks" class="form-control" rows="3" placeholder="{{ __('Example: Leave was deducted in previous payroll but manager approval was pending.') }}">{{ old('remarks') }}</textarea>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="ti ti-plus me-1"></i>{{ __('Add Supplementary') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">{{ __('Supplementary Adjustments') }}</h5>
                    <span class="badge bg-primary">{{ $adjustments->count() }} {{ __('records') }}</span>
                </div>
                <div class="card-body table-border-style p-0">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>{{ __('Employee') }}</th>
                                    <th>{{ __('Reason') }}</th>
                                    <th>{{ __('Deducted Month') }}</th>
                                    <th>{{ __('Pay In') }}</th>
                                    <th class="text-end">{{ __('Days') }}</th>
                                    <th class="text-end">{{ __('Amount') }}</th>
                                    <th class="text-center">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($adjustments as $adjustment)
                                    <tr>
                                        <td>
                                            <strong>{{ $adjustment->employee->name ?? 'N/A' }}</strong>
                                            <br><small class="text-muted">{{ $adjustment->employee->employee_id ?? '' }}</small>
                                        </td>
                                        <td>
                                            <span class="badge {{ $adjustment->adjustment_type === 'credit' ? 'bg-success' : 'bg-danger' }}">
                                                {{ $adjustment->adjustment_type === 'credit' ? __('Credit') : __('Debit') }}
                                            </span>
                                            <div class="mt-1">{{ $adjustment->title }}</div>
                                            @if(!empty($adjustment->remarks))
                                                <small class="text-muted">{{ $adjustment->remarks }}</small>
                                            @endif
                                        </td>
                                        <td>{{ \Carbon\Carbon::parse($adjustment->source_month . '-01')->format('M Y') }}</td>
                                        <td>{{ \Carbon\Carbon::parse($adjustment->payout_month . '-01')->format('M Y') }}</td>
                                        <td class="text-end">{{ rtrim(rtrim(number_format((float)$adjustment->days, 2), '0'), '.') }}</td>
                                        <td class="text-end">
                                            <strong class="{{ $adjustment->adjustment_type === 'credit' ? 'text-success' : 'text-danger' }}">
                                                {{ $adjustment->adjustment_type === 'credit' ? '+' : '-' }}{{ \Auth::user()->priceFormat($adjustment->amount) }}
                                            </strong>
                                        </td>
                                        <td class="text-center">
                                            <form method="POST" action="{{ route('payroll.supplementary.delete', $adjustment->id) }}" class="d-inline"
                                                  onsubmit="return confirm('{{ __('Delete this supplementary adjustment? Re-run payroll if this month was already generated.') }}');">
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
                                            {{ __('No supplementary adjustments added yet.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="alert alert-info mt-3 mb-0">
                <i class="ti ti-info-circle me-1"></i>
                {{ __('After adding an adjustment, run or re-run payroll for the Pay In month. It will appear as a one-time line item on the salary slip.') }}
            </div>
        </div>
    </div>
@endsection
