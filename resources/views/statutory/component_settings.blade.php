@extends('layouts.admin')
@section('page-title')
    {{ __('Statutory Settings') }} - {{ $component->code }}
@endsection

@section('content')
    @include('statutory._nav')

    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">{{ $component->name }} ({{ $component->code }})</h5></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('statutory.component.save', ['code' => $component->code]) }}">
                        @csrf
                        <input type="hidden" name="component_status" value="1">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="status" id="status" value="1" {{ $component->status ? 'checked' : '' }}>
                            <label class="form-check-label" for="status">{{ __('Enable Component') }}</label>
                        </div>
                        <button class="btn btn-primary">{{ __('Save') }}</button>
                    </form>
                    <hr>
                    <form method="POST" action="{{ route('statutory.component.save', ['code' => $component->code]) }}">
                        @csrf
                        <div class="row">
                            <div class="col-md-6 mb-2"><label class="form-label">{{ __('State') }}</label>
                                <select name="state_id" class="form-control">
                                    <option value="">{{ __('National') }}</option>
                                    @foreach($states as $state)
                                        <option value="{{ $state->id }}">{{ $state->state_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-2"><label class="form-label">{{ __('Frequency') }}</label>
                                <select name="frequency" class="form-control">
                                    <option value="monthly">{{ __('Monthly') }}</option>
                                    <option value="yearly">{{ __('Yearly') }}</option>
                                    <option value="half-yearly">{{ __('Half-Yearly') }}</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-2"><label class="form-label">{{ __('Min Salary') }}</label><input type="number" step="0.01" name="min_salary" class="form-control"></div>
                            <div class="col-md-6 mb-2"><label class="form-label">{{ __('Max Salary') }}</label><input type="number" step="0.01" name="max_salary" class="form-control"></div>
                            <div class="col-md-6 mb-2"><label class="form-label">{{ __('Employee Type') }}</label>
                                <select name="employee_contribution_type" class="form-control"><option value="percentage">%</option><option value="fixed">Fixed</option></select>
                            </div>
                            <div class="col-md-6 mb-2"><label class="form-label">{{ __('Employee Value') }}</label><input type="number" step="0.0001" name="employee_value" class="form-control" required></div>
                            <div class="col-md-6 mb-2"><label class="form-label">{{ __('Employer Type') }}</label>
                                <select name="employer_contribution_type" class="form-control"><option value="percentage">%</option><option value="fixed">Fixed</option></select>
                            </div>
                            <div class="col-md-6 mb-2"><label class="form-label">{{ __('Employer Value') }}</label><input type="number" step="0.0001" name="employer_value" class="form-control" required></div>
                            <div class="col-md-6 mb-2"><label class="form-label">{{ __('Max Limit') }}</label><input type="number" step="0.01" name="max_limit" class="form-control"></div>
                            <div class="col-md-6 mb-2"><label class="form-label">{{ __('Applicable Gender') }}</label>
                                <select name="applicable_gender" class="form-control">
                                    <option value="">{{ __('All') }}</option>
                                    <option value="male">{{ __('Male') }}</option>
                                    <option value="female">{{ __('Female') }}</option>
                                    <option value="other">{{ __('Other') }}</option>
                                </select>
                            </div>
                            <div class="col-md-8 mb-2"><label class="form-label">{{ __('Effective From') }}</label><input type="date" name="effective_from" value="{{ now()->toDateString() }}" class="form-control" required></div>
                            <div class="col-md-4 mb-2 d-flex align-items-end">
                                <div class="form-check"><input class="form-check-input" type="checkbox" name="status" id="rule_status" value="1" checked><label class="form-check-label" for="rule_status">{{ __('Active') }}</label></div>
                            </div>
                        </div>
                        <button class="btn btn-primary w-100 mt-2">{{ __('Add Rule') }}</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">{{ __('Rule History') }}</h5></div>
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                            <tr>
                                <th>{{ __('Effective') }}</th><th>{{ __('State') }}</th><th>{{ __('Range') }}</th><th>{{ __('Employee') }}</th><th>{{ __('Employer') }}</th><th>{{ __('Frequency') }}</th><th>{{ __('Status') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($rules as $r)
                                <tr>
                                    <td>{{ $r->effective_from ? $r->effective_from->format('Y-m-d') : '-' }}</td>
                                    <td>{{ optional($states->firstWhere('id', $r->state_id))->state_name ?? __('National') }}</td>
                                    <td>{{ $r->min_salary ?? 0 }} - {{ $r->max_salary ?? __('No Max') }}</td>
                                    <td>{{ $r->employee_contribution_type }} {{ $r->employee_value }}</td>
                                    <td>{{ $r->employer_contribution_type }} {{ $r->employer_value }}</td>
                                    <td>{{ ucfirst($r->frequency) }}</td>
                                    <td>{{ $r->status ? __('Active') : __('Inactive') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7">{{ __('No rules found.') }}</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

