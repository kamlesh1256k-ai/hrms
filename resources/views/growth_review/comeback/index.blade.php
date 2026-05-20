@extends('layouts.admin')
@section('page-title') {{ __('Comeback Plans') }} @endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('growth-review.dashboard') }}">{{ __('Growth Review') }}</a></li>
    <li class="breadcrumb-item">{{ __('Comeback Plans') }}</li>
@endsection
@push('css-page')
<style>
    .pip-card{border:1px solid var(--bs-border-color);border-radius:12px;padding:18px;margin-bottom:14px;background:var(--bs-body-bg);}
    .pip-status{font-size:.7rem;padding:3px 10px;border-radius:20px;font-weight:600;}
    .pip-active{background:#dbeafe;color:#1e40af;}.pip-on_track{background:#dcfce7;color:#166534;}.pip-at_risk{background:#fef3c7;color:#92400e;}.pip-completed{background:#e0e7ff;color:#3730a3;}.pip-failed{background:#fee2e2;color:#991b1b;}
</style>
@endpush
@section('content')
    @include('growth_review._nav')
    @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(session('error'))<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    @php $viewerEmpId = $emp?->id; @endphp

    @if($canAssign)
    <div class="card mb-3">
        <div class="card-header"><h5 class="mb-0"><i class="ti ti-arrow-back-up me-2"></i>{{ __('Initiate Comeback Plan') }}</h5></div>
        <div class="card-body">
            <form method="POST" action="{{ route('growth-review.comeback.store') }}">@csrf
                <div class="row g-3">
                    <div class="col-md-3"><label class="form-label">{{ __('Employee') }} <span class="text-danger">*</span></label><select name="employee_id" class="form-control" required><option value="">{{ __('Select...') }}</option>@foreach($assignableEmployees as $e)<option value="{{ $e->id }}">{{ $e->name }}</option>@endforeach</select></div>
                    <div class="col-md-3"><label class="form-label">{{ __('Title') }} <span class="text-danger">*</span></label><input type="text" name="title" class="form-control" required placeholder="e.g. 90 Day Improvement Plan"></div>
                    <div class="col-md-3"><label class="form-label">{{ __('Start Date') }} <span class="text-danger">*</span></label><input type="date" name="start_date" class="form-control" required value="{{ date('Y-m-d') }}"></div>
                    <div class="col-md-3"><label class="form-label">{{ __('End Date') }} <span class="text-danger">*</span></label><input type="date" name="end_date" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label">{{ __('Issues Identified') }}</label><textarea name="issues" class="form-control" rows="2" placeholder="Describe performance issues..."></textarea></div>
                    <div class="col-md-6"><label class="form-label">{{ __('Action Steps / Goals') }} <small class="text-muted">(one per line)</small></label><textarea name="action_steps" class="form-control" rows="2" placeholder="Goal 1
Goal 2"></textarea></div>
                </div>
                <div class="mt-3"><button class="btn btn-primary"><i class="ti ti-check me-1"></i>{{ __('Assign Plan') }}</button></div>
            </form>
        </div>
    </div>
    @endif

    @forelse($plans as $p)
    @php $canEdit = $isAdmin || ($viewerEmpId && (int)$p->assigned_by === (int)$viewerEmpId); @endphp
    <div class="pip-card" style="border-left:4px solid {{ $p->status==='completed'?'#059669':($p->status==='at_risk'?'#f59e0b':($p->status==='failed'?'#ef4444':'#4361ee')) }};">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <h6 class="mb-1">{{ $p->title }}</h6>
                <small class="text-muted">{{ $p->employee->name ?? '—' }} · Initiated by {{ $p->assignedBy->name ?? '—' }} @if($p->auto_initiated)<span class="badge bg-info ms-1">{{ __('Auto') }}</span>@endif</small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="pip-status pip-{{ $p->status }}">{{ ucfirst(str_replace('_',' ',$p->status)) }}</span>
                <small class="text-muted">{{ $p->start_date->format('d M') }} — {{ $p->end_date->format('d M Y') }}</small>
            </div>
        </div>

        @if($p->issues)<p class="text-muted mt-2 mb-1" style="font-size:.85rem;"><strong>{{ __('Issues') }}:</strong> {{ $p->issues }}</p>@endif
        @if($p->action_steps)
        <div class="mt-2">
            <small class="text-muted fw-bold">{{ __('Action Steps / Goals') }}:</small>
            <ul class="mb-0 ps-3" style="font-size:.85rem;">
                @foreach($p->action_steps as $step)
                    @php
                        $stepText = is_array($step) ? ($step['step'] ?? ($step['title'] ?? null)) : $step;
                    @endphp
                    <li>{{ $stepText ?? json_encode($step) }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        @if($p->final_outcome && $p->final_outcome !== 'pending')
            <div class="mt-2">
                <small class="text-muted fw-bold">{{ __('Final Outcome') }}:</small>
                <span class="badge bg-secondary">{{ ucfirst($p->final_outcome) }}</span>
                @if($p->outcome_decided_at)<small class="text-muted ms-2">{{ $p->outcome_decided_at->format('d M Y') }}</small>@endif
                @if($p->final_remarks)<div class="text-muted" style="font-size:.85rem;"><strong>{{ __('Remarks') }}:</strong> {{ $p->final_remarks }}</div>@endif
            </div>
        @endif

        @if($p->reviews && $p->reviews->count())
            <div class="mt-3">
                <small class="text-muted fw-bold">{{ __('Reviews') }}:</small>
                <div class="mt-2 d-grid gap-2">
                    @foreach($p->reviews as $r)
                        <div class="border rounded p-2" style="font-size:.85rem;">
                            <div class="d-flex justify-content-between flex-wrap gap-2">
                                <div><strong>{{ $r->review_date->format('d M Y') }}</strong> · {{ $r->reviewer->name ?? '—' }}</div>
                                <div>
                                    <span class="badge bg-{{ $r->progress==='on_track'?'success':($r->progress==='at_risk'?'warning':'danger') }}">{{ ucfirst(str_replace('_',' ',$r->progress)) }}</span>
                                    @if($r->rating)<span class="badge bg-light text-dark ms-1">{{ __('Rating') }}: {{ $r->rating }}/5</span>@endif
                                </div>
                            </div>
                            @if($r->strengths)<div class="text-muted mt-1"><strong>{{ __('Strengths') }}:</strong> {{ $r->strengths }}</div>@endif
                            @if($r->improvements)<div class="text-muted mt-1"><strong>{{ __('Improvements') }}:</strong> {{ $r->improvements }}</div>@endif
                            @if($r->comments)<div class="text-muted mt-1"><strong>{{ __('Comments') }}:</strong> {{ $r->comments }}</div>@endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if($canEdit)
            <div class="mt-3">
                <details>
                    <summary class="text-primary" style="cursor:pointer;">{{ __('Update / Add Review') }}</summary>

                    <div class="mt-3">
                        <form method="POST" action="{{ route('growth-review.comeback.update', $p->id) }}">@csrf @method('PUT')
                            <div class="row g-2 align-items-end">
                                <div class="col-md-3">
                                    <label class="form-label">{{ __('Status') }}</label>
                                    <select name="status" class="form-control form-control-sm">
                                        @foreach(['active','on_track','at_risk','completed','failed'] as $st)
                                            <option value="{{ $st }}" {{ $p->status==$st?'selected':'' }}>{{ ucfirst(str_replace('_',' ',$st)) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">{{ __('Final Outcome') }}</label>
                                    <select name="final_outcome" class="form-control form-control-sm">
                                        @foreach(['pending','success','failed','extended'] as $out)
                                            <option value="{{ $out }}" {{ ($p->final_outcome ?? 'pending')==$out?'selected':'' }}>{{ ucfirst($out) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('Final Remarks') }}</label>
                                    <input type="text" name="final_remarks" class="form-control form-control-sm" value="{{ $p->final_remarks }}" placeholder="Short summary / decision notes">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">{{ __('Action Steps / Goals') }} <small class="text-muted">(one per line)</small></label>
                                    @php
                                        $actionLines = [];
                                        if (is_array($p->action_steps)) {
                                            foreach ($p->action_steps as $step) {
                                                $actionLines[] = is_array($step) ? ($step['step'] ?? ($step['title'] ?? json_encode($step))) : $step;
                                            }
                                        }
                                    @endphp
                                    <textarea name="action_steps" class="form-control form-control-sm" rows="2">{{ implode("\n", array_filter($actionLines)) }}</textarea>
                                </div>
                            </div>
                            <div class="mt-2">
                                <button class="btn btn-sm btn-primary">{{ __('Save Plan') }}</button>
                            </div>
                        </form>
                    </div>

                    <div class="mt-4">
                        <form method="POST" action="{{ route('growth-review.comeback.reviews.store', $p->id) }}">@csrf
                            <div class="row g-2 align-items-end">
                                <div class="col-md-3">
                                    <label class="form-label">{{ __('Review Date') }} <span class="text-danger">*</span></label>
                                    <input type="date" name="review_date" class="form-control form-control-sm" required value="{{ date('Y-m-d') }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">{{ __('Progress') }} <span class="text-danger">*</span></label>
                                    <select name="progress" class="form-control form-control-sm" required>
                                        <option value="on_track">{{ __('On Track') }}</option>
                                        <option value="at_risk">{{ __('At Risk') }}</option>
                                        <option value="off_track">{{ __('Off Track') }}</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">{{ __('Rating') }}</label>
                                    <input type="number" name="rating" min="1" max="5" class="form-control form-control-sm" placeholder="1-5">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">{{ __('Comments') }}</label>
                                    <input type="text" name="comments" class="form-control form-control-sm" placeholder="Quick notes">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('Strengths') }}</label>
                                    <textarea name="strengths" class="form-control form-control-sm" rows="2"></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('Improvements') }}</label>
                                    <textarea name="improvements" class="form-control form-control-sm" rows="2"></textarea>
                                </div>
                            </div>
                            <div class="mt-2">
                                <button class="btn btn-sm btn-outline-primary">{{ __('Add Review') }}</button>
                            </div>
                        </form>
                    </div>
                </details>
            </div>
        @endif

        @if($isAdmin)
        <div class="mt-3 d-flex gap-2 flex-wrap">
            <form method="POST" action="{{ route('growth-review.comeback.delete', $p->id) }}" class="d-inline" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')
                <button class="btn btn-sm btn-danger"><i class="ti ti-trash"></i></button>
            </form>
        </div>
        @endif
    </div>
    @empty
    <div class="card"><div class="card-body text-center text-muted py-5"><i class="ti ti-arrow-back-up" style="font-size:3rem;opacity:.3;"></i><p class="mt-2">{{ __('No comeback plans assigned.') }}</p></div></div>
    @endforelse
@endsection
