@extends('layouts.admin')
@section('page-title') {{ $cycle->name }} @endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('growth-review.dashboard') }}">{{ __('Growth Review') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('growth-review.cycles') }}">{{ __('Cycles') }}</a></li>
    <li class="breadcrumb-item">{{ $cycle->name }}</li>
@endsection
@section('action-button')
    <a href="{{ route('growth-review.cycles.edit', $cycle->id) }}" class="btn btn-sm btn-info me-1"><i class="ti ti-edit me-1"></i>{{ __('Edit Cycle') }}</a>
    <a href="{{ route('growth-review.cycles') }}" class="btn btn-sm btn-secondary"><i class="ti ti-arrow-left me-1"></i>{{ __('Back') }}</a>
@endsection

@push('css-page')
<style>
    .cy-info{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px;margin-bottom:20px;}
    .cy-info-item{background:#f8fafc;border-radius:10px;padding:12px 16px;}
    .cy-info-item label{display:block;font-size:.7rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.3px;margin-bottom:2px;}
    .cy-info-item span{font-weight:600;color:#1f2a44;font-size:.88rem;}
    .cy-status{display:inline-flex;align-items:center;gap:4px;padding:3px 12px;border-radius:20px;font-size:.75rem;font-weight:600;}
    .cy-draft{background:#f3f4f6;color:#6b7280;}.cy-active{background:#dcfce7;color:#166534;}.cy-review{background:#dbeafe;color:#1e40af;}.cy-calibration{background:#fef3c7;color:#92400e;}.cy-completed{background:#ede9fe;color:#5b21b6;}
    .emp-row{display:flex;align-items:center;gap:10px;padding:10px 14px;border-bottom:1px solid #f1f5f9;}
    .emp-row:last-child{border-bottom:none;}
    .emp-row:hover{background:#f8fafc;}
    .emp-status{font-size:.68rem;padding:2px 8px;border-radius:12px;font-weight:600;}
    .emp-assigned{background:#dbeafe;color:#1e40af;}.emp-goal_pending{background:#fef3c7;color:#92400e;}.emp-goal_submitted{background:#dcfce7;color:#166534;}.emp-review_pending{background:#ede9fe;color:#5b21b6;}.emp-completed{background:#d1fae5;color:#065f46;}
</style>
@endpush

@section('content')
    @include('growth_review._nav')

    @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(session('error'))<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    {{-- Cycle Info --}}
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">{{ $cycle->name }}</h5>
                <span class="cy-status cy-{{ $cycle->status }}">{{ ucfirst($cycle->status) }}</span>
            </div>
            <div class="cy-info">
                <div class="cy-info-item"><label>{{ __('Period') }}</label><span>{{ $cycle->start_date->format('d M Y') }} — {{ $cycle->end_date->format('d M Y') }}</span></div>
                <div class="cy-info-item"><label>{{ __('Goal Deadline') }}</label><span>{{ $cycle->goal_deadline ? $cycle->goal_deadline->format('d M Y') : '—' }}</span></div>
                <div class="cy-info-item"><label>{{ __('Self Review') }}</label><span>{{ $cycle->self_review_start ? $cycle->self_review_start->format('d M') . ' – ' . $cycle->self_review_end?->format('d M') : '—' }}</span></div>
                <div class="cy-info-item"><label>{{ __('Manager Review') }}</label><span>{{ $cycle->manager_review_start ? $cycle->manager_review_start->format('d M') . ' – ' . $cycle->manager_review_end?->format('d M') : '—' }}</span></div>
                <div class="cy-info-item"><label>{{ __('Rating Scale') }}</label><span>{{ $cycle->rating_scale ?? '1-5' }}</span></div>
                <div class="cy-info-item"><label>{{ __('Employees') }}</label><span>{{ count($assigned) }}</span></div>
            </div>
        </div>
    </div>

    {{-- Assign Employees --}}
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="ti ti-users me-1"></i>{{ __('Assigned Employees') }} <span class="badge bg-primary ms-1">{{ count($assigned) }}</span></h6>
            <div class="d-flex gap-2">
                @if($cycle->status === 'draft')
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#assignModal"><i class="ti ti-user-plus me-1"></i>{{ __('Assign Employees') }}</button>
                @endif
                @if($cycle->status === 'draft' && count($assigned) > 0)
                <form method="POST" action="{{ route('growth-review.cycles.activate', $cycle->id) }}" class="d-inline" onsubmit="return confirm('{{ __('Activate this cycle and notify all assigned employees to add their goals?') }}')">@csrf
                    <button type="submit" class="btn btn-sm btn-success"><i class="ti ti-send me-1"></i>{{ __('Activate & Notify') }}</button>
                </form>
                @endif
            </div>
        </div>
        <div class="card-body p-0">
            @if(count($assigned) === 0)
                <div class="text-center text-muted py-4" style="font-size:.85rem;">
                    {{ __('No employees assigned yet.') }}
                    <button type="button" class="btn btn-sm btn-primary ms-2" data-bs-toggle="modal" data-bs-target="#assignModal"><i class="ti ti-user-plus me-1"></i>{{ __('Assign Now') }}</button>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('Employee') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Goal Deadline') }}</th>
                                <th>{{ __('Goals') }}</th>
                                <th>{{ __('Notified') }}</th>
                                <th>{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($assigned as $ce)
                            @php $mc = $missionCounts[$ce->employee_id] ?? null; @endphp
                            <tr>
                                <td>
                                    <strong>{{ $ce->employee->name ?? '—' }}</strong>
                                    <br><small class="text-muted">{{ $ce->employee->employee_id ?? '' }}</small>
                                </td>
                                <td><span class="emp-status emp-{{ $ce->status }}">{{ ucfirst(str_replace('_', ' ', $ce->status)) }}</span></td>
                                <td>{{ $ce->goal_deadline ? $ce->goal_deadline->format('d M Y') : '—' }}</td>
                                <td>
                                    @if($mc)
                                        <span class="badge bg-primary">{{ $mc->total }}</span>
                                        @if($mc->approved > 0)<span class="badge bg-success">{{ $mc->approved }} {{ __('approved') }}</span>@endif
                                    @else
                                        <span class="text-muted">0</span>
                                    @endif
                                </td>
                                <td>
                                    @if($ce->notified_at)
                                        <small class="text-success"><i class="ti ti-check me-1"></i>{{ $ce->notified_at->diffForHumans() }}</small>
                                    @else
                                        <small class="text-muted">{{ __('Not yet') }}</small>
                                    @endif
                                </td>
                                <td>
                                    @if($cycle->status === 'draft')
                                    <form method="POST" action="{{ route('growth-review.cycles.unassign', [$cycle->id, $ce->employee_id]) }}" class="d-inline" onsubmit="return confirm('Remove?')">@csrf @method('DELETE')
                                        <button class="btn btn-sm btn-danger" title="Remove"><i class="ti ti-x"></i></button>
                                    </form>
                                    @endif
                                    <a href="{{ route('growth-review.missions', ['cycle_id' => $cycle->id]) }}" class="btn btn-sm btn-outline-primary" title="View Goals"><i class="ti ti-target"></i></a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- Assign Modal --}}
    <div class="modal fade" id="assignModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content" style="border-radius:14px;border:none;">
        <form method="POST" action="{{ route('growth-review.cycles.assign', $cycle->id) }}">@csrf
            <div class="modal-header" style="background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;border:none;border-radius:14px 14px 0 0;">
                <h5 class="modal-title"><i class="ti ti-user-plus me-2"></i>{{ __('Assign Employees to Cycle') }}</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">{{ __('Filter by Department') }}</label>
                    <select id="deptFilter" class="form-control form-control-sm" style="max-width:250px;">
                        <option value="">{{ __('All Departments') }}</option>
                        @foreach($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach
                    </select>
                </div>
                <div class="mb-2 d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="selectAll">{{ __('Select All') }}</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="deselectAll">{{ __('Deselect All') }}</button>
                </div>
                <div style="max-height:350px;overflow-y:auto;border:1px solid #e5e7eb;border-radius:10px;padding:8px;" id="empList">
                    @foreach($employees as $e)
                    <label class="d-flex align-items-center gap-2 px-2 py-1 emp-check-row" data-dept="{{ $e->department_id }}" style="cursor:pointer;border-radius:6px;{{ in_array($e->id, $assignedIds) ? 'opacity:.5;' : '' }}">
                        <input type="checkbox" name="employee_ids[]" value="{{ $e->id }}" {{ in_array($e->id, $assignedIds) ? 'disabled checked' : '' }} class="emp-check">
                        <strong style="font-size:.85rem;">{{ $e->name }}</strong>
                        <small class="text-muted">({{ $e->employee_id }})</small>
                        @if(in_array($e->id, $assignedIds))<span class="badge bg-success" style="font-size:.6rem;">{{ __('Already assigned') }}</span>@endif
                    </label>
                    @endforeach
                </div>
            </div>
            <div class="modal-footer" style="border:none;">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i>{{ __('Assign Selected') }}</button>
            </div>
        </form>
    </div></div></div>
@endsection

@push('script-page')
<script>
(function(){
    var deptFilter = document.getElementById('deptFilter');
    if (deptFilter) {
        deptFilter.addEventListener('change', function(){
            var val = this.value;
            document.querySelectorAll('.emp-check-row').forEach(function(row){
                row.style.display = (!val || row.dataset.dept === val) ? '' : 'none';
            });
        });
    }
    var selectAll = document.getElementById('selectAll');
    var deselectAll = document.getElementById('deselectAll');
    if (selectAll) selectAll.addEventListener('click', function(){
        document.querySelectorAll('.emp-check:not(:disabled)').forEach(function(cb){ cb.checked = true; });
    });
    if (deselectAll) deselectAll.addEventListener('click', function(){
        document.querySelectorAll('.emp-check:not(:disabled)').forEach(function(cb){ cb.checked = false; });
    });
})();
</script>
@endpush
