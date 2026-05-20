@extends('layouts.admin')
@section('page-title')
    {{ __('Dynamic Salary Structure') }}
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h5 class="mb-0">{{ __('Salary Components') }}</h5>
                        <div class="mt-2 d-flex flex-wrap gap-3">
                            <a href="javascript:void(0)" class="salary-type-tab text-primary fw-bold" data-type="all">{{ __('Earnings') }}</a>
                            <a href="javascript:void(0)" class="salary-type-tab text-muted" data-type="deduction">{{ __('Deductions') }}</a>
                            <a href="javascript:void(0)" class="salary-type-tab text-muted" data-type="employer">{{ __('Benefits') }}</a>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('salary.structure.calculate') }}" class="btn btn-sm btn-outline-primary">{{ __('Open Calculator') }}</a>
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addComponentModal" data-toggle="modal" data-target="#addComponentModal">
                            {{ __('Add Component') }}
                        </button>
                    </div>
                </div>
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>{{ __('Name') }}</th>
                                    <th>{{ __('Type') }}</th>
                                    <th>{{ __('Calculation') }}</th>
                                    <th>{{ __('Value') }}</th>
                                    <th>{{ __('Condition') }}</th>
                                    <th>{{ __('Status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($components as $component)
                                    <tr class="salary-component-row" data-type="{{ $component->type }}">
                                        <td>{{ $component->name }}</td>
                                        <td>{{ ucfirst($component->type) }}</td>
                                        <td>
                                            <div>{{ ucfirst($component->calculation_type) }}</div>
                                            @if(!empty($component->formula))
                                                <small class="text-muted">{{ Str::limit($component->formula, 40) }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $component->value }}</td>
                                        <td><small>{{ Str::limit($component->condition_rule, 45) }}</small></td>
                                        <td>
                                            <span class="badge {{ $component->status ? 'bg-success' : 'bg-secondary' }}">
                                                {{ $component->status ? __('Active') : __('Inactive') }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6">{{ __('No components found.') }}</td>
                                    </tr>
                                @endforelse
                                <tr id="empty-filter-row" style="display:none;">
                                    <td colspan="6">{{ __('No components found for selected type.') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addComponentModal" tabindex="-1" aria-labelledby="addComponentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addComponentModalLabel">{{ __('Add Salary Component') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                </div>
                <div class="modal-body">
                    {{ Form::open(['route' => 'salary.structure.component.store', 'method' => 'post']) }}
                    <div class="row">
                        <div class="col-md-6 form-group mb-3">
                            {{ Form::label('name', __('Name')) }}
                            {{ Form::text('name', null, ['class' => 'form-control', 'required']) }}
                        </div>
                        <div class="col-md-6 form-group mb-3">
                            {{ Form::label('structure_id', __('Structure')) }}
                            <select name="structure_id" class="form-control" required>
                                @foreach($structures as $structure)
                                    <option value="{{ $structure->id }}">{{ $structure->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 form-group mb-3">
                            {{ Form::label('type', __('Type')) }}
                            <select name="type" class="form-control" required>
                                <option value="earning">{{ __('Earning') }}</option>
                                <option value="deduction">{{ __('Deduction') }}</option>
                                <option value="employer">{{ __('Employer / Benefit') }}</option>
                            </select>
                        </div>
                        <div class="col-md-6 form-group mb-3">
                            {{ Form::label('calculation_type', __('Calculation Type')) }}
                            <select name="calculation_type" class="form-control" required>
                                <option value="fixed">{{ __('Fixed') }}</option>
                                <option value="percentage">{{ __('Percentage') }}</option>
                                <option value="formula">{{ __('Formula') }}</option>
                            </select>
                        </div>
                        <div class="col-md-6 form-group mb-3">
                            {{ Form::label('value', __('Value')) }}
                            {{ Form::number('value', null, ['class' => 'form-control', 'step' => '0.01']) }}
                        </div>
                        <div class="col-md-6 form-group mb-3">
                            {{ Form::label('priority', __('Priority')) }}
                            {{ Form::number('priority', 999, ['class' => 'form-control']) }}
                        </div>
                        <div class="col-md-12 form-group mb-3">
                            {{ Form::label('formula', __('Formula')) }}
                            {{ Form::textarea('formula', null, ['class' => 'form-control', 'rows' => 2]) }}
                        </div>
                        <div class="col-md-12 form-group mb-3">
                            {{ Form::label('condition_rule', __('Condition Rule')) }}
                            {{ Form::textarea('condition_rule', null, ['class' => 'form-control', 'rows' => 2]) }}
                        </div>
                        <div class="col-12 form-check mb-2 ms-1">
                            <input class="form-check-input" type="checkbox" value="1" name="status" id="status" checked>
                            <label class="form-check-label" for="status">{{ __('Active') }}</label>
                        </div>
                    </div>
                    <div class="text-end mt-2">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button class="btn btn-primary" type="submit">{{ __('Save Component') }}</button>
                    </div>
                    {{ Form::close() }}
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script-page')
<script>
    (function () {
        const tabs = document.querySelectorAll('.salary-type-tab');
        const rows = document.querySelectorAll('.salary-component-row');
        const emptyRow = document.getElementById('empty-filter-row');

        function setActiveTab(activeType) {
            tabs.forEach(function (tab) {
                const isActive = tab.getAttribute('data-type') === activeType;
                tab.classList.toggle('text-primary', isActive);
                tab.classList.toggle('fw-bold', isActive);
                tab.classList.toggle('text-muted', !isActive);
            });
        }

        function filterRows(type) {
            let visibleCount = 0;
            rows.forEach(function (row) {
                const rowType = row.getAttribute('data-type');
                const show = type === 'all' ? rowType === 'earning' : rowType === type;
                row.style.display = show ? '' : 'none';
                if (show) {
                    visibleCount++;
                }
            });
            if (emptyRow) {
                emptyRow.style.display = visibleCount === 0 ? '' : 'none';
            }
        }

        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                const type = tab.getAttribute('data-type') || 'all';
                setActiveTab(type);
                filterRows(type);
            });
        });

        setActiveTab('all');
        filterRows('all');
    })();
</script>
@endpush

