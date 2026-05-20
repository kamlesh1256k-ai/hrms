@extends('layouts.admin')

@section('page-title') {{ __('Requisition') }} #{{ $req->id }} @endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('recruitment.dashboard') }}">{{ __('Recruitment') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('recruitment.requisitions.index') }}">{{ __('Requisitions') }}</a></li>
    <li class="breadcrumb-item">#{{ $req->id }}</li>
@endsection

@push('css-page')
<style>
    .rq-section-title { font-size:.78rem; text-transform:uppercase; letter-spacing:.5px; color:#6c757d; font-weight:600; margin-bottom:6px; }
    .rq-skill-pill { display:inline-block; background:#eef2ff; color:#4338ca; padding:3px 10px; border-radius:20px; font-size:.78rem; margin:2px 4px 2px 0; }
    .rq-jd { background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:18px 20px; min-height:260px; white-space:pre-wrap; line-height:1.65; font-size:.92rem; }
    .rq-jd:empty::before { content:"{{ __('No JD generated yet. Click \\"Generate JD\\" to create one from the requisition skills.') }}"; color:#9ca3af; font-style:italic; }
    .rq-event { border-left:2px solid #e2e8f0; padding:10px 14px; margin-bottom:10px; }
    .rq-event.approved  { border-color:#10b981; background:#ecfdf5; }
    .rq-event.rejected  { border-color:#ef4444; background:#fef2f2; }
    .rq-event .who { font-weight:600; }
    .rq-event .when { color:#6b7280; font-size:.78rem; }
</style>
@endpush

@section('content')
<div class="container-fluid">
    @include('recruitment._nav')

    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if(session('error'))   <div class="alert alert-danger">{{ session('error') }}</div>   @endif
    @if(session('info'))    <div class="alert alert-info">{{ session('info') }}</div>      @endif

    <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h4 class="mb-1">{{ $req->title }}</h4>
            <div class="text-muted small">
                {{ __('Raised by') }} <strong>{{ $req->raisedBy->name ?? '—' }}</strong>
                · {{ $req->created_at->format('d M Y, H:i') }}
                · <span class="badge bg-{{ $req->status_badge }}">{{ \App\Models\ManpowerRequisition::$statuses[$req->status] ?? $req->status }}</span>
            </div>
        </div>
        <div class="d-flex gap-2">
            @if($canApprove)
                <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#approveModal">
                    <i class="ti ti-check me-1"></i>{{ __('Approve as') }} <span class="text-uppercase ms-1">{{ $req->next_approver_role }}</span>
                </button>
                <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#rejectModal">
                    <i class="ti ti-x me-1"></i>{{ __('Reject') }}
                </button>
            @elseif($req->status === 'pending' && $req->next_approver_role)
                <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-2">
                    <i class="ti ti-clock me-1"></i>{{ __('Awaiting') }} <strong class="text-uppercase">{{ $req->next_approver_role }}</strong> {{ __('approval') }}
                </span>
            @endif
            @if($req->status !== 'fulfilled' && (int) $req->raised_by_user_id === \Auth::id() || $isApprover)
                <form method="POST" action="{{ route('recruitment.requisitions.destroy', $req->id) }}" class="d-inline" onsubmit="return confirm('{{ __('Delete this requisition?') }}');">
                    @csrf @method('DELETE')
                    <button class="btn btn-outline-danger btn-sm"><i class="ti ti-trash"></i></button>
                </form>
            @endif
        </div>
    </div>

    <div class="row g-3">
        {{-- LEFT — details + JD --}}
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header"><h6 class="mb-0">{{ __('Requisition Details') }}</h6></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="rq-section-title">{{ __('Department') }}</div>
                            <div>{{ $req->department->name ?? '—' }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="rq-section-title">{{ __('Designation') }}</div>
                            <div>{{ $req->designation->name ?? '—' }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="rq-section-title">{{ __('Branch') }}</div>
                            <div>{{ $req->branch->name ?? '—' }}</div>
                        </div>
                        <div class="col-md-3">
                            <div class="rq-section-title">{{ __('Positions') }}</div>
                            <div>{{ $req->positions }}</div>
                        </div>
                        <div class="col-md-3">
                            <div class="rq-section-title">{{ __('Priority') }}</div>
                            <div class="text-capitalize">{{ $req->priority }}</div>
                        </div>
                        <div class="col-md-3">
                            <div class="rq-section-title">{{ __('Reason') }}</div>
                            <div class="text-capitalize">{{ str_replace('_',' ', $req->reason) }}</div>
                        </div>
                        <div class="col-md-3">
                            <div class="rq-section-title">{{ __('Experience') }}</div>
                            <div>{{ $req->experience ?: '—' }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="rq-section-title">{{ __('Salary Range') }}</div>
                            <div>{{ $req->salary_range ?: '—' }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="rq-section-title">{{ __('Location') }}</div>
                            <div>{{ $req->location ?: '—' }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="rq-section-title">{{ __('Needed By') }}</div>
                            <div>{{ $req->needed_by ? $req->needed_by->format('d M Y') : '—' }}</div>
                        </div>
                        @if($req->replacement_for)
                            <div class="col-md-12">
                                <div class="rq-section-title">{{ __('Replacement For') }}</div>
                                <div>{{ $req->replacement_for }}</div>
                            </div>
                        @endif
                        <div class="col-md-12">
                            <div class="rq-section-title">{{ __('Required Skills') }}</div>
                            <div>
                                @foreach($req->skills_array as $skill)
                                    <span class="rq-skill-pill">{{ $skill }}</span>
                                @endforeach
                            </div>
                        </div>
                        @if($req->description)
                            <div class="col-md-12">
                                <div class="rq-section-title">{{ __('Description / Justification') }}</div>
                                <div style="white-space:pre-wrap;">{{ $req->description }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- AI JD --}}
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <h6 class="mb-0"><i class="ti ti-sparkles me-1 text-primary"></i>{{ __('AI Job Description') }}</h6>
                    <div class="d-flex gap-2">
                        @if($canEditJd)
                            <button id="genJdBtn" class="btn btn-primary btn-sm">
                                <i class="ti ti-wand me-1"></i>{{ $req->generated_jd ? __('Re-generate') : __('Generate JD') }}
                            </button>
                        @endif
                        @if($req->generated_jd && $req->status === 'approved' && $isApprover && !$req->job_id)
                            <form method="POST" action="{{ route('recruitment.requisitions.create-job', $req->id) }}" class="d-inline" onsubmit="return confirm('{{ __('Create a job opening from this requisition? HR can edit & post it.') }}');">
                                @csrf
                                <button class="btn btn-success btn-sm">
                                    <i class="ti ti-briefcase me-1"></i>{{ __('Create Job & Post') }}
                                </button>
                            </form>
                        @elseif($req->job_id)
                            <a href="{{ route('job.edit', $req->job_id) }}" class="btn btn-outline-success btn-sm">
                                <i class="ti ti-link me-1"></i>{{ __('Open Linked Job') }}
                            </a>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    @if($canEditJd)
                        <form method="POST" action="{{ route('recruitment.requisitions.update-jd', $req->id) }}">
                            @csrf @method('PUT')
                            <textarea id="jdField" name="generated_jd" rows="18" class="form-control" placeholder="{{ __('Click Generate JD or write one manually…') }}">{{ $req->generated_jd }}</textarea>
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <small class="text-muted">
                                    <i class="ti ti-info-circle me-1"></i>{{ __('Both manager and HR can edit this JD until the job is created.') }}
                                </small>
                                <button class="btn btn-outline-primary btn-sm">
                                    <i class="ti ti-device-floppy me-1"></i>{{ __('Save JD') }}
                                </button>
                            </div>
                        </form>
                    @else
                        <div class="rq-jd">{{ $req->generated_jd }}</div>
                        @if($req->status !== 'approved')
                            <div class="text-muted small mt-2">
                                <i class="ti ti-info-circle me-1"></i>{{ __('JD becomes editable after the requisition is approved.') }}
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>

        {{-- RIGHT — approval chain + log --}}
        <div class="col-lg-4">
            {{-- Approval chain visualizer --}}
            <div class="card mb-3">
                <div class="card-header"><h6 class="mb-0"><i class="ti ti-route me-1"></i>{{ __('Approval Chain') }}</h6></div>
                <div class="card-body">
                    @php
                        $chain = $req->approval_chain_array;
                        $cur   = (int) $req->current_approval_step;
                        $isRejected = $req->status === 'rejected';
                    @endphp
                    <ol class="list-unstyled mb-0">
                        <li class="d-flex align-items-start gap-2 mb-2">
                            <span class="badge bg-secondary rounded-circle" style="width:22px;height:22px;line-height:14px;font-size:.65rem;">M</span>
                            <div>
                                <div class="fw-semibold small">{{ __('Manager') }}</div>
                                <div class="small text-muted">{{ $req->raisedBy->name ?? '—' }} · {{ __('raised') }}</div>
                            </div>
                            <i class="ti ti-check text-success ms-auto"></i>
                        </li>
                        @foreach($chain as $idx => $role)
                            @php
                                $done   = !$isRejected && $cur > $idx;
                                $active = !$isRejected && $cur === $idx && $req->status === 'pending';
                                $approval = $req->approvals->where('action','approved')->where('actor_role',$role)->last();
                            @endphp
                            <li class="d-flex align-items-start gap-2 mb-2">
                                <span class="badge rounded-circle text-white {{ $done ? 'bg-success' : ($active ? 'bg-warning' : 'bg-light text-dark') }}"
                                      style="width:22px;height:22px;line-height:14px;font-size:.65rem;">{{ $idx + 2 }}</span>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold small text-uppercase">{{ $role }}</div>
                                    <div class="small text-muted">
                                        @if($done && $approval)
                                            {{ $approval->actor->name ?? '—' }} · {{ $approval->created_at->diffForHumans() }}
                                        @elseif($active)
                                            <em class="text-warning">{{ __('Awaiting approval…') }}</em>
                                        @elseif($isRejected)
                                            <em class="text-muted">{{ __('Skipped (rejected)') }}</em>
                                        @else
                                            <em class="text-muted">{{ __('Pending earlier step') }}</em>
                                        @endif
                                    </div>
                                </div>
                                @if($done) <i class="ti ti-check text-success"></i>
                                @elseif($active) <i class="ti ti-clock text-warning"></i>
                                @elseif($isRejected) <i class="ti ti-x text-danger"></i>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h6 class="mb-0">{{ __('Approval Log') }}</h6></div>
                <div class="card-body">
                    @if($req->approvals->isEmpty())
                        <div class="text-muted small">
                            <i class="ti ti-clock me-1"></i>
                            @if($req->status === 'pending')
                                {{ __('Waiting for HR / Management to review.') }}
                            @else
                                {{ __('No approval activity recorded.') }}
                            @endif
                        </div>
                    @else
                        @foreach($req->approvals as $a)
                            <div class="rq-event {{ $a->action }}">
                                <div class="d-flex justify-content-between">
                                    <span class="who">{{ $a->actor->name ?? __('Unknown') }}</span>
                                    <span class="when">{{ $a->created_at->diffForHumans() }}</span>
                                </div>
                                <div class="small text-capitalize text-{{ $a->action === 'approved' ? 'success' : 'danger' }}">
                                    <i class="ti ti-{{ $a->action === 'approved' ? 'check' : 'x' }}"></i> {{ $a->action }}
                                    <span class="text-muted">· {{ $a->actor_role }}</span>
                                </div>
                                @if($a->comments)
                                    <div class="small mt-1" style="white-space:pre-wrap;">{{ $a->comments }}</div>
                                @endif
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── APPROVE MODAL ── --}}
@if($canApprove)
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('recruitment.requisitions.approve', $req->id) }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Approve Requisition') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted">{{ __('Once approved, HR will be able to generate the JD and create a job opening.') }}</p>
                <label class="form-label">{{ __('Comments (optional)') }}</label>
                <textarea name="comments" rows="3" class="form-control" maxlength="1000"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button class="btn btn-success"><i class="ti ti-check me-1"></i>{{ __('Approve') }}</button>
            </div>
        </form>
    </div>
</div>

{{-- ── REJECT MODAL ── --}}
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('recruitment.requisitions.reject', $req->id) }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Reject Requisition') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label">{{ __('Reason for rejection') }} <span class="text-danger">*</span></label>
                <textarea name="comments" rows="3" class="form-control" required maxlength="1000"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button class="btn btn-danger"><i class="ti ti-x me-1"></i>{{ __('Reject') }}</button>
            </div>
        </form>
    </div>
</div>
@endif

@if($canGenerate)
<script>
(function () {
    var btn = document.getElementById('genJdBtn');
    var fld = document.getElementById('jdField');
    if (!btn) return;
    var URL = "{{ route('recruitment.requisitions.generate-jd', $req->id) }}";
    var TOKEN = "{{ csrf_token() }}";

    btn.addEventListener('click', function (e) {
        e.preventDefault();
        if (fld && fld.value.trim() &&
            !confirm("{{ __('Replace the existing JD with a freshly generated one?') }}")) return;

        var origLabel = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="ti ti-loader me-1"></i>{{ __("Generating…") }}';

        fetch(URL, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': TOKEN, 'Accept': 'application/json' },
        })
        .then(function (r) { return r.json(); })
        .then(function (d) {
            if (d.ok && d.jd && fld) {
                fld.value = d.jd;
                fld.focus();
            } else {
                alert(d.error || "{{ __('JD generation failed.') }}");
            }
        })
        .catch(function () { alert("{{ __('Network error while generating JD.') }}"); })
        .finally(function () {
            btn.disabled = false;
            btn.innerHTML = origLabel;
        });
    });
})();
</script>
@endif

@endsection
