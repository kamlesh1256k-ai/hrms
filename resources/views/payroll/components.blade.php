@extends('layouts.admin')
@section('page-title')
    {{ __('Payroll - Salary Components') }}
@endsection

@section('content')
    @include('payroll._nav')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="mb-1">{{ __('Salary Components') }}</h5>
                <div class="d-flex flex-wrap gap-3">
                    @foreach(['earning' => 'Earnings', 'deduction' => 'Deductions', 'benefit' => 'Benefits', 'reimbursement' => 'Reimbursements'] as $k => $v)
                        <a href="{{ route('payroll.components', ['category' => $k]) }}"
                           class="small {{ $category === $k ? 'text-primary fw-bold' : 'text-muted' }}">
                            {{ __($v) }}
                        </a>
                    @endforeach
                </div>
            </div>
            <div class="d-flex gap-2">
                <form method="POST" action="{{ route('payroll.components.seed') }}">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-primary">{{ __('Add Default Components') }}</button>
                </form>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addComponentModal" data-toggle="modal" data-target="#addComponentModal">
                    {{ __('Add Component') }}
                </button>
            </div>
        </div>

        {{-- Bulk Action Toolbar (hidden by default, shown when checkboxes selected) --}}
        <div class="card-body py-2 border-bottom" id="bulkBar" style="display:none;background:#f0f4ff;">
            <form method="POST" action="{{ route('payroll.components.bulk') }}" id="bulkForm">
                @csrf
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <span class="fw-semibold text-primary" id="selectedCount">0 selected</span>
                    <input type="hidden" name="action" id="bulkAction" value="">
                    <div id="bulkIds"></div>
                    <button type="button" class="btn btn-sm btn-success" onclick="submitBulk('activate')">
                        <i class="ti ti-check me-1"></i>{{ __('Activate') }}
                    </button>
                    <button type="button" class="btn btn-sm btn-warning" onclick="submitBulk('deactivate')">
                        <i class="ti ti-x me-1"></i>{{ __('Deactivate') }}
                    </button>
                    <button type="button" class="btn btn-sm btn-danger" onclick="submitBulk('delete')">
                        <i class="ti ti-trash me-1"></i>{{ __('Delete') }}
                    </button>
                    <button type="button" class="btn btn-sm btn-light" onclick="clearSelection()">
                        {{ __('Cancel') }}
                    </button>
                </div>
            </form>
        </div>

        <div class="card-body table-border-style">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th style="width:40px;">
                                <input class="form-check-input" type="checkbox" id="selectAll" title="{{ __('Select All') }}">
                            </th>
                            <th>{{ __('Component Name') }}</th>
                            <th>{{ __('Calculation') }}</th>
                            <th>{{ __('PF Applicable') }}</th>
                            <th>{{ __('ESIC Applicable') }}</th>
                            <th>{{ __('Taxable') }}</th>
                            <th>{{ __('Frequency') }}</th>
                            <th>{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($components as $item)
                        <tr>
                            <td>
                                <input class="form-check-input row-check" type="checkbox" name="component_ids[]" value="{{ $item->id }}">
                            </td>
                            <td class="text-primary fw-semibold">{{ $item->name }}</td>
                            <td>
                                <span class="badge bg-light text-dark">{{ ucfirst($item->calculation_type) }}</span>
                                @if($item->calculation_type === 'percentage' && $item->value)
                                    <small class="text-muted">{{ $item->value }}%</small>
                                @elseif($item->calculation_type === 'fixed' && $item->value > 0)
                                    <small class="text-muted">₹{{ number_format($item->value) }}</small>
                                @elseif($item->calculation_type === 'formula' && $item->formula)
                                    <small class="text-muted" title="{{ $item->formula }}">{{ Str::limit($item->formula, 25) }}</small>
                                @endif
                            </td>
                            <td>
                                @if($item->is_pf_applicable)
                                    <span class="badge bg-success-subtle text-success">{{ __('Yes') }}</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary">{{ __('No') }}</span>
                                @endif
                            </td>
                            <td>
                                @if($item->is_esic_applicable)
                                    <span class="badge bg-success-subtle text-success">{{ __('Yes') }}</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary">{{ __('No') }}</span>
                                @endif
                            </td>
                            <td>
                                @if($item->is_taxable)
                                    <span class="badge bg-warning-subtle text-warning">{{ __('Yes') }}</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary">{{ __('No') }}</span>
                                @endif
                            </td>
                            <td><span class="text-muted small">{{ ucfirst($item->frequency ?? 'monthly') }}</span></td>
                            <td>
                                @if($item->status)
                                    <span class="badge bg-success">{{ __('Active') }}</span>
                                @else
                                    <span class="badge bg-secondary">{{ __('Inactive') }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">{{ __('No components found.') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Add Component Modal --}}
    <div class="modal fade" id="addComponentModal" tabindex="-1" aria-labelledby="addComponentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addComponentModalLabel">{{ __('Add Salary Component') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="{{ route('payroll.components.store') }}">
                        @csrf
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label class="form-label">{{ __('Name') }}</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label">{{ __('Category') }}</label>
                                <select name="category" class="form-control" required>
                                    <option value="earning">{{ __('Earning') }}</option>
                                    <option value="deduction">{{ __('Deduction') }}</option>
                                    <option value="benefit">{{ __('Benefit') }}</option>
                                    <option value="reimbursement">{{ __('Reimbursement') }}</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label">{{ __('Calculation Type') }}</label>
                                <select name="calculation_type" class="form-control" required>
                                    <option value="fixed">{{ __('Fixed') }}</option>
                                    <option value="percentage">{{ __('Percentage') }}</option>
                                    <option value="variable">{{ __('Variable') }}</option>
                                    <option value="formula">{{ __('Formula') }}</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label">{{ __('Value') }}</label>
                                <input type="number" step="0.01" name="value" class="form-control">
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label">{{ __('Frequency') }}</label>
                                <select name="frequency" class="form-control">
                                    <option value="monthly">{{ __('Monthly') }}</option>
                                    <option value="yearly">{{ __('Yearly') }}</option>
                                    <option value="one-time">{{ __('One-time') }}</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label">{{ __('Structure') }}</label>
                                <select name="structure_id" class="form-control" required>
                                    @foreach($structures as $structure)
                                        <option value="{{ $structure->id }}" {{ (int)$structure->id === (int)$defaultStructure->id ? 'selected' : '' }}>{{ $structure->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-12 mb-2">
                                <label class="form-label">{{ __('Formula') }}</label>
                                <textarea name="formula" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label">{{ __('Max Limit') }}</label>
                                <input type="number" step="0.01" name="max_limit" class="form-control">
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label">{{ __('Priority') }}</label>
                                <input type="number" name="priority" value="999" class="form-control">
                            </div>
                            <div class="col-md-4 form-check mb-1 ms-2">
                                <input class="form-check-input" type="checkbox" name="is_taxable" id="is_taxable" value="1" checked>
                                <label class="form-check-label" for="is_taxable">{{ __('Taxable') }}</label>
                            </div>
                            <div class="col-md-4 form-check mb-1">
                                <input class="form-check-input" type="checkbox" name="is_pf_applicable" id="is_pf_applicable" value="1">
                                <label class="form-check-label" for="is_pf_applicable">{{ __('PF Applicable') }}</label>
                            </div>
                            <div class="col-md-4 form-check mb-1">
                                <input class="form-check-input" type="checkbox" name="is_esic_applicable" id="is_esic_applicable" value="1">
                                <label class="form-check-label" for="is_esic_applicable">{{ __('ESIC Applicable') }}</label>
                            </div>
                            <div class="col-12 form-check mb-3 ms-2">
                                <input class="form-check-input" type="checkbox" name="status" id="status" value="1" checked>
                                <label class="form-check-label" for="status">{{ __('Active') }}</label>
                            </div>
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                            <button class="btn btn-primary">{{ __('Save Component') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('custom-scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var selectAll  = document.getElementById('selectAll');
    var rowChecks  = document.querySelectorAll('.row-check');
    var bulkBar    = document.getElementById('bulkBar');
    var countLabel = document.getElementById('selectedCount');

    function updateBulkBar() {
        var checked = document.querySelectorAll('.row-check:checked');
        if (checked.length > 0) {
            bulkBar.style.display = 'block';
            countLabel.textContent = checked.length + ' selected';
        } else {
            bulkBar.style.display = 'none';
        }
        // Update "select all" state
        selectAll.checked = rowChecks.length > 0 && checked.length === rowChecks.length;
        selectAll.indeterminate = checked.length > 0 && checked.length < rowChecks.length;
    }

    selectAll.addEventListener('change', function() {
        rowChecks.forEach(function(cb) { cb.checked = selectAll.checked; });
        updateBulkBar();
    });

    rowChecks.forEach(function(cb) {
        cb.addEventListener('change', updateBulkBar);
    });
});

function submitBulk(action) {
    var checked = document.querySelectorAll('.row-check:checked');
    if (checked.length === 0) return;

    var label = action === 'delete' ? '{{ __("delete") }}' : (action === 'activate' ? '{{ __("activate") }}' : '{{ __("deactivate") }}');
    if (!confirm('{{ __("Are you sure you want to") }} ' + label + ' ' + checked.length + ' {{ __("component(s)") }}?')) return;

    document.getElementById('bulkAction').value = action;

    // Add hidden inputs for selected IDs
    var container = document.getElementById('bulkIds');
    container.innerHTML = '';
    checked.forEach(function(cb) {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'ids[]';
        input.value = cb.value;
        container.appendChild(input);
    });

    document.getElementById('bulkForm').submit();
}

function clearSelection() {
    document.querySelectorAll('.row-check').forEach(function(cb) { cb.checked = false; });
    document.getElementById('selectAll').checked = false;
    document.getElementById('selectAll').indeterminate = false;
    document.getElementById('bulkBar').style.display = 'none';
}
</script>
@endpush
