@extends('layouts.admin')
@section('page-title')
    {{ __('Payroll - Employee Salary') }}
@endsection

@section('content')
    @include('payroll._nav')

    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5 class="mb-0">{{ __('Employee Salary Configuration') }}</h5>
            <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
                <form method="GET" action="{{ route('payroll.employee.salary') }}" class="d-flex align-items-center gap-2">
                    <input type="month" name="export_month" class="form-control form-control-sm" value="{{ $exportMonth }}" style="max-width:180px;">
                    <button type="submit" class="btn btn-sm btn-outline-primary">{{ __('Set Month') }}</button>
                </form>
                <a href="{{ route('payroll.process.export', ['filter_month' => $exportMonth]) }}"
                    class="btn btn-sm btn-success"
                    data-bs-toggle="tooltip"
                    title="{{ __('Download month-wise salary statement in Excel') }}">
                    <i class="ti ti-file-export"></i> {{ __('Excel') }}
                </a>
                <span class="badge bg-primary">{{ $employees->count() }} {{ __('Employees') }}</span>
            </div>
        </div>
        <div class="card-body table-border-style">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th style="width:50px;">#</th>
                            <th>{{ __('Employee') }}</th>
                            <th>{{ __('CTC (Annual)') }}</th>
                            <th>{{ __('Basic %') }}</th>
                            <th>{{ __('Structure') }}</th>
                            <th class="text-center">{{ __('PF') }}</th>
                            <th class="text-center">{{ __('ESIC') }}</th>
                            <th class="text-center">{{ __('OT') }}</th>
                            <th>{{ __('OT Formula') }}</th>
                            <th class="text-center" style="width:180px;">{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employees as $emp)
                            @php($row = $salaries->get($emp->id))
                            @php($formId = 'salary-form-'.$emp->id)
                            <tr>
                                <td>
                                    <form id="{{ $formId }}" method="POST" action="{{ route('payroll.employee.salary.save') }}">
                                        @csrf
                                        <input type="hidden" name="employee_id" value="{{ $emp->id }}">
                                    </form>
                                    <div style="width:32px;height:32px;border-radius:8px;background:linear-gradient(135deg,#3b82f6,#8b5cf6);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:.7rem;">
                                        {{ strtoupper(substr($emp->name, 0, 2)) }}
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight:600;">{{ $emp->name }}</div>
                                    <small class="text-muted">{{ $emp->employee_id ?? 'EMP-'.$emp->id }}</small>
                                </td>
                                <td><input type="number" step="0.01" name="ctc" form="{{ $formId }}" class="form-control form-control-sm" value="{{ $row->ctc ?? '' }}" placeholder="e.g. 1200000" required style="min-width:130px;"></td>
                                <td><input type="number" step="0.01" min="1" max="100" name="basic_percentage" form="{{ $formId }}" class="form-control form-control-sm" value="{{ $row->basic_percentage ?? 50 }}" required style="width:80px;"></td>
                                <td>
                                    <select name="structure_id" form="{{ $formId }}" class="form-control form-control-sm" required style="min-width:140px;">
                                        @foreach($structures as $structure)
                                            <option value="{{ $structure->id }}" {{ (int)($row->structure_id ?? 0) === (int)$structure->id ? 'selected' : '' }}>{{ $structure->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="text-center"><input type="checkbox" name="is_pf_enabled" value="1" form="{{ $formId }}" class="form-check-input" {{ !empty($row) && $row->is_pf_enabled ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="is_esic_enabled" value="1" form="{{ $formId }}" class="form-check-input" {{ !empty($row) && $row->is_esic_enabled ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="overtime_enabled" value="1" form="{{ $formId }}" class="form-check-input" {{ !empty($row) && $row->overtime_enabled ? 'checked' : '' }}></td>
                                <td>
                                    <select name="overtime_formula" form="{{ $formId }}" class="form-control form-control-sm" style="min-width:190px;">
                                        @php($otFormula = $row->overtime_formula ?? 'basic')
                                        <option value="basic" {{ $otFormula === 'basic' ? 'selected' : '' }}>{{ __('Basic/26/8 × OT Hours × 1.5') }}</option>
                                        <option value="gross" {{ $otFormula === 'gross' ? 'selected' : '' }}>{{ __('Gross/26/8 × OT Hours × 1.5') }}</option>
                                    </select>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex gap-1 justify-content-center">
                                        <button type="submit" form="{{ $formId }}" class="btn btn-sm btn-primary" title="{{ __('Save') }}">
                                            <i class="ti ti-device-floppy"></i> {{ __('Save') }}
                                        </button>
                                        @if(!empty($row) && $row->ctc > 0)
                                        <a href="{{ route('payroll.employee.salary.view', $emp->id) }}" class="btn btn-sm btn-info" title="{{ __('View Salary Structure') }}">
                                            <i class="ti ti-eye"></i> {{ __('View') }}
                                        </a>
                                        @else
                                        <button type="button" class="btn btn-sm btn-outline-secondary" disabled title="{{ __('Save CTC first to view structure') }}">
                                            <i class="ti ti-eye-off"></i> {{ __('View') }}
                                        </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="text-center text-muted py-4">{{ __('No employees found. Add employees first.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
