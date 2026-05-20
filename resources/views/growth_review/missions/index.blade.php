@extends('layouts.admin')
@section('page-title') {{ __('Missions (Goals)') }} @endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('growth-review.dashboard') }}">{{ __('Growth Review') }}</a></li>
    <li class="breadcrumb-item">{{ __('Missions') }}</li>
@endsection
@push('css-page')
<style>
    .mission-card{border:1px solid var(--bs-border-color);border-radius:10px;padding:16px;margin-bottom:12px;background:var(--bs-body-bg);transition:border-color .15s;}
    .mission-card:hover{border-color:var(--bs-primary);}
    .m-status{font-size:.7rem;padding:2px 10px;border-radius:20px;font-weight:600;}
    .m-pending{background:#fef3c7;color:#92400e;} .m-in_progress{background:#dbeafe;color:#1e40af;} .m-completed{background:#dcfce7;color:#166534;} .m-cancelled{background:#fee2e2;color:#991b1b;}
    .m-appr{font-size:.68rem;padding:2px 8px;border-radius:20px;font-weight:600;}
    .m-appr-pending{background:#fef3c7;color:#92400e;} .m-appr-approved{background:#dcfce7;color:#166534;} .m-appr-rejected{background:#fee2e2;color:#991b1b;}
    .progress-thin{height:6px;border-radius:3px;}

    /* Assigned KRA/KPI section */
    .asn-section-head{display:flex;align-items:center;gap:10px;margin:18px 0 10px;padding-bottom:6px;border-bottom:2px solid #e5e7eb;cursor:pointer;}
    a.asn-section-head:hover{border-bottom-color:#6366f1;}
    a.asn-section-head:hover h6{color:#4f46e5;}
    a.asn-section-head:hover .ti-external-link{color:#6366f1!important;}
    .asn-section-head h6{margin:0;font-weight:700;color:#1f2a44;font-size:.92rem;}
    .asn-section-head .asn-pill{background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;padding:2px 10px;border-radius:12px;font-size:.7rem;font-weight:700;}
    .asn-card{
        border:1px solid #e5e7eb;border-left:4px solid #8b5cf6;border-radius:10px;
        padding:14px 18px;margin-bottom:10px;background:#fff;transition:all .15s;
    }
    .asn-card:hover{box-shadow:0 4px 14px rgba(139,92,246,.12);border-left-color:#6366f1;}
    .asn-card-header{display:flex;justify-content:space-between;align-items:flex-start;gap:10px;flex-wrap:wrap;}
    .asn-title{font-weight:700;color:#1f2a44;font-size:.95rem;margin:0 0 3px;}
    .asn-sub{font-size:.75rem;color:#64748b;}
    .asn-sub strong{color:#475569;}
    .asn-badge{
        background:#ede9fe;color:#6d28d9;padding:3px 10px;border-radius:12px;
        font-size:.68rem;font-weight:700;display:inline-flex;align-items:center;gap:4px;
    }
    .asn-kras{display:flex;flex-wrap:wrap;gap:6px;margin-top:10px;}
    .asn-kra-chip{
        background:#f8fafc;border:1px solid #e2e8f0;padding:4px 10px;border-radius:14px;
        font-size:.72rem;color:#475569;font-weight:500;
    }
    .asn-kra-chip strong{color:#1f2a44;}
    .asn-remarks{
        margin-top:8px;padding:7px 12px;background:#eff6ff;border-left:3px solid #3b82f6;
        border-radius:4px;font-size:.75rem;color:#475569;
    }
    .asn-empty{text-align:center;padding:30px 20px;color:#94a3b8;font-size:.82rem;}

    /* Mission Rating UI */
    .m-rating-section{margin-top:10px;padding:10px 14px;background:#f8fafc;border-radius:10px;border:1px solid #e5e7eb;}
    .m-rating-row{display:flex;align-items:center;gap:10px;flex-wrap:wrap;padding:6px 0;}
    .m-rating-row + .m-rating-row{border-top:1px dashed #e5e7eb;margin-top:4px;padding-top:8px;}
    .m-rating-label{font-size:.78rem;font-weight:600;min-width:90px;display:flex;align-items:center;gap:4px;}
    .m-rating-label.self-label{color:#6d28d9;}
    .m-rating-label.mgr-label{color:#92400e;}
    .m-rating-label.hod-label{color:#0e7490;}
    .m-rate-input{width:58px;height:32px;text-align:center;font-weight:700;font-size:.9rem;border:1.5px solid #e2e5ec;border-radius:8px;outline:none;padding:0 4px;}
    .m-rate-input:focus{border-color:#8b5cf6;box-shadow:0 0 0 3px rgba(139,92,246,.12);}
    .m-rate-input.self-input:focus{border-color:#8b5cf6;}
    .m-rate-input.mgr-input:focus{border-color:#f59e0b;box-shadow:0 0 0 3px rgba(245,158,11,.15);}
    .m-rate-input.hod-input:focus{border-color:#06b6d4;box-shadow:0 0 0 3px rgba(6,182,212,.15);}
    .m-rate-input.has-val{border-color:#8b5cf6;background:#faf5ff;color:#6d28d9;}
    .m-rate-input.mgr-input.has-val{border-color:#f59e0b;background:#fffbeb;color:#92400e;}
    .m-rate-input.hod-input.has-val{border-color:#06b6d4;background:#ecfeff;color:#0e7490;}
    .m-rate-input.saved{border-color:#10b981 !important;background:#ecfdf5 !important;}
    .m-rate-input[disabled]{opacity:.5;cursor:not-allowed;}
    .m-rate-suffix{font-size:.75rem;color:#94a3b8;}
    .m-rate-remarks{flex:1;min-width:150px;border:1px solid #e2e5ec;border-radius:7px;padding:5px 10px;font-size:.78rem;}
    .m-rate-remarks:focus{border-color:#8b5cf6;outline:none;box-shadow:0 0 0 3px rgba(139,92,246,.12);}
    .m-rate-remarks.saved{border-color:#10b981;}
    .m-rate-remarks[disabled]{opacity:.5;cursor:not-allowed;}

    /* Document upload */
    .m-upload-btn{
        display:inline-flex;align-items:center;gap:4px;padding:5px 14px;
        background:#faf5ff;color:#6d28d9;border:1px solid #e9d5ff;border-radius:8px;
        font-size:.78rem;font-weight:600;cursor:pointer;transition:all .12s;
    }
    .m-upload-btn:hover{background:#ede9fe;border-color:#8b5cf6;transform:translateY(-1px);}
    .m-doc-chip{
        display:inline-flex;align-items:center;gap:4px;padding:4px 12px;
        background:linear-gradient(135deg,#ede9fe,#faf5ff);color:#6d28d9;
        border:1px solid #d8b4fe;border-radius:8px;font-size:.78rem;font-weight:600;
        text-decoration:none;
    }
    .m-doc-chip:hover{background:#ddd6fe;color:#5b21b6;}
    .m-doc-remove{
        background:transparent;border:1px solid transparent;padding:3px 6px;
        color:#94a3b8;cursor:pointer;border-radius:6px;font-size:.85rem;line-height:1;
    }
    .m-doc-remove:hover{background:#fee2e2;color:#dc2626;border-color:#fecaca;}
</style>
@endpush
@section('content')
    @include('growth_review._nav')
    @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    {{-- Cycle Selector & Add --}}
    <div class="card mb-3">
        <div class="card-body py-3">
            <form class="d-flex align-items-end gap-3 flex-wrap">
                <div>
                    <label class="form-label mb-1">{{ __('Cycle') }}</label>
                    <select name="cycle_id" class="form-control form-control-sm" onchange="this.form.submit()" style="min-width:200px;">
                        @foreach($cycles as $c)
                        <option value="{{ $c->id }}" {{ $cycleId == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addMissionModal"><i class="ti ti-plus me-1"></i>{{ __('Add Mission') }}</button>
            </form>
        </div>
    </div>

    {{-- ── Assigned KRA / KPI (from KPI Generator) ─────────────── --}}
    @if(isset($kpiAssignments) && $kpiAssignments->isNotEmpty())
        <a href="{{ route('growth-review.kpi-generator.my-assigned') }}" class="asn-section-head text-decoration-none" title="{{ __('Open My Assigned KRA / KPI') }}">
            <i class="ti ti-sparkles text-primary"></i>
            <h6>{{ __('Assigned KRA / KPI') }}</h6>
            <span class="asn-pill">{{ $kpiAssignments->count() }}</span>
            <i class="ti ti-external-link text-muted ms-auto" style="font-size:.95rem;"></i>
        </a>

        @foreach($kpiAssignments as $a)
            @php
                $gen = $a->generation;
                if (!$gen) continue;
                $kras = $gen->content_json ? (json_decode($gen->content_json, true) ?? []) : [];
            @endphp
            <div class="asn-card">
                <div class="asn-card-header">
                    <div>
                        <h6 class="asn-title">{{ $gen->job_role }}</h6>
                        <div class="asn-sub">
                            <i class="ti ti-user me-1"></i><strong>{{ $a->employee->name ?? '—' }}</strong>
                            @if($gen->industry) · {{ $gen->industry }} @endif
                            @if($gen->seniority_level) · {{ $gen->seniority_level }} @endif
                            @if($gen->target_timeframe) · {{ $gen->target_timeframe }} @endif
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="asn-badge"><i class="ti ti-check"></i>{{ __('Assigned') }}</span>
                        @if($a->assigned_at)
                            <small class="text-muted" style="font-size:.7rem;">{{ $a->assigned_at->diffForHumans() }}</small>
                        @endif
                        <a href="{{ route('growth-review.kpi-generator.show', $gen->id) }}" class="btn btn-sm btn-outline-primary" title="{{ __('View KPIs') }}"><i class="ti ti-eye"></i></a>
                        <a href="{{ route('growth-review.kpi-generator.pdf', $gen->id) }}" class="btn btn-sm btn-outline-success" title="{{ __('Download PDF') }}"><i class="ti ti-file-type-pdf"></i></a>
                    </div>
                </div>

                @if(!empty($kras))
                    <div class="asn-kras">
                        @foreach($kras as $k)
                            <span class="asn-kra-chip"><strong>{{ $k['kra'] ?? '—' }}</strong> · {{ $k['weightage'] ?? 0 }}%</span>
                        @endforeach
                    </div>
                @endif

                @if($a->remarks)
                    <div class="asn-remarks"><i class="ti ti-message-circle me-1"></i>{{ $a->remarks }}</div>
                @endif
            </div>
        @endforeach

        <div class="asn-section-head">
            <i class="ti ti-target text-danger"></i>
            <h6>{{ __('Missions (Goals)') }}</h6>
            <span class="asn-pill" style="background:linear-gradient(135deg,#ef4444,#f87171);">{{ $missions->count() }}</span>
        </div>
    @endif

    {{-- Mission List --}}
    @forelse($missions as $m)
    <div class="mission-card">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <h6 class="mb-1">{{ $m->title }}</h6>
                <small class="text-muted">{{ $m->employee->name ?? '—' }}</small>
                @if($m->kpi) <small class="text-info ms-2"><i class="ti ti-chart-bar me-1"></i>{{ $m->kpi }}</small> @endif
            </div>
            <div class="d-flex gap-2 align-items-center">
                @if($m->weightage > 0)<span class="badge bg-secondary" style="font-size:.72rem;">{{ $m->weightage }}%</span>@endif
                <span class="m-status m-{{ $m->status }}">{{ ucfirst(str_replace('_',' ',$m->status)) }}</span>
                <span class="m-appr m-appr-{{ $m->approval }}">{{ ucfirst($m->approval) }}</span>
            </div>
        </div>
        @if($m->description)<p class="text-muted mt-1 mb-2" style="font-size:.85rem;">{{ Str::limit($m->description, 150) }}</p>@endif
        <div class="d-flex align-items-center gap-3 flex-wrap mt-2">

            {{-- Actions --}}
            <div class="ms-auto d-flex gap-1">
                @php
                    $user = Auth::user();
                    $viewerEmp = \App\Models\Employee::where('user_id', $user->id)->first();
                    $isViewerManager = $viewerEmp && (int)$m->employee->reporting_manager_id === $viewerEmp->id;
                    $isViewerAdmin = in_array($user->type, ['company', 'super admin'], true);
                @endphp

                {{-- Approve/Reject: only manager of this employee --}}
                @if($m->approval === 'pending' && ($isViewerManager || $isViewerAdmin))
                <form method="POST" action="{{ route('growth-review.missions.approve', $m->id) }}" class="d-inline">@csrf
                    <input type="hidden" name="action" value="approved">
                    <button class="btn btn-sm btn-success" title="Approve"><i class="ti ti-check"></i></button>
                </form>
                <form method="POST" action="{{ route('growth-review.missions.approve', $m->id) }}" class="d-inline">@csrf
                    <input type="hidden" name="action" value="rejected">
                    <button class="btn btn-sm btn-warning" title="Reject"><i class="ti ti-x"></i></button>
                </form>
                @endif

                {{-- Edit/Delete: only when NOT approved, and only by owner or admin --}}
                @if($m->approval !== 'approved')
                <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#editMission{{ $m->id }}"><i class="ti ti-edit"></i></button>
                @if($isViewerAdmin)
                <form method="POST" action="{{ route('growth-review.missions.delete', $m->id) }}" class="d-inline" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')
                    <button class="btn btn-sm btn-danger"><i class="ti ti-trash"></i></button>
                </form>
                @endif
                @endif
            </div>
        </div>

        {{-- Rating & Document Section --}}
        @php
            $mUser = Auth::user();
            $mEmp = \App\Models\Employee::where('user_id', $mUser->id)->first();
            $mIsAdmin = in_array($mUser->type, ['company', 'super admin'], true);
            $mIsOwner = $mEmp && (int)$m->employee_id === $mEmp->id;
            $mIsMgr = $mEmp && (int)($m->employee->reporting_manager_id ?? 0) === $mEmp->id;
            $mIsHod = $mEmp && ((int)($m->employee->hod_id ?? 0) === $mEmp->id || (int)($m->employee->management_id ?? 0) === $mEmp->id);
            $showMgr = $m->manager_rating !== null || $mIsMgr || $mIsAdmin;
            $showHod = $m->hod_rating !== null || $mIsHod || $mIsAdmin;
        @endphp
        <div class="m-rating-section" data-mission-id="{{ $m->id }}" data-rate-url="{{ route('growth-review.missions.rate', $m->id) }}">
            {{-- Self Rating --}}
            <div class="m-rating-row">
                <span class="m-rating-label self-label"><i class="ti ti-user"></i> {{ __('Self') }}</span>
                <input type="number" class="m-rate-input self-input {{ $m->self_rating ? 'has-val' : '' }}" data-field="self_rating" min="0" max="5" step="0.5" value="{{ $m->self_rating ?? '' }}" placeholder="0-5" {{ (!$mIsOwner && !$mIsAdmin) ? 'disabled' : '' }}>
                <span class="m-rate-suffix">/5</span>
                <input type="text" class="m-rate-remarks" data-field="self_remarks" value="{{ $m->self_remarks ?? '' }}" placeholder="{{ __('Self remarks…') }}" maxlength="500" {{ (!$mIsOwner && !$mIsAdmin) ? 'disabled' : '' }}>
            </div>
            {{-- Manager Rating --}}
            @if($showMgr)
            <div class="m-rating-row">
                <span class="m-rating-label mgr-label"><i class="ti ti-user-check"></i> {{ __('Manager') }}</span>
                <input type="number" class="m-rate-input mgr-input {{ $m->manager_rating ? 'has-val' : '' }}" data-field="manager_rating" min="0" max="5" step="0.5" value="{{ $m->manager_rating ?? '' }}" placeholder="0-5" {{ (!$mIsMgr && !$mIsAdmin) ? 'disabled' : '' }}>
                <span class="m-rate-suffix">/5</span>
                <input type="text" class="m-rate-remarks" data-field="manager_rating_remarks" value="{{ $m->manager_rating_remarks ?? '' }}" placeholder="{{ __('Manager remarks…') }}" maxlength="500" {{ (!$mIsMgr && !$mIsAdmin) ? 'disabled' : '' }}>
            </div>
            @endif
            {{-- HOD Rating --}}
            @if($showHod)
            <div class="m-rating-row">
                <span class="m-rating-label hod-label"><i class="ti ti-shield-check"></i> {{ __('HOD') }}</span>
                <input type="number" class="m-rate-input hod-input {{ $m->hod_rating ? 'has-val' : '' }}" data-field="hod_rating" min="0" max="5" step="0.5" value="{{ $m->hod_rating ?? '' }}" placeholder="0-5" {{ (!$mIsHod && !$mIsAdmin) ? 'disabled' : '' }}>
                <span class="m-rate-suffix">/5</span>
                <input type="text" class="m-rate-remarks" data-field="hod_rating_remarks" value="{{ $m->hod_rating_remarks ?? '' }}" placeholder="{{ __('HOD remarks…') }}" maxlength="500" {{ (!$mIsHod && !$mIsAdmin) ? 'disabled' : '' }}>
            </div>
            @endif
            {{-- Document Upload --}}
            <div class="m-rating-row">
                <span class="m-rating-label" style="color:#475569;"><i class="ti ti-paperclip"></i> {{ __('Evidence') }}</span>
                @if($m->document)
                    <a href="{{ asset('storage/'.$m->document) }}" target="_blank" class="m-doc-chip">
                        <i class="ti ti-file-text"></i>
                        <span>{{ \Illuminate\Support\Str::limit($m->document_name ?? basename($m->document), 25) }}</span>
                    </a>
                    @if($mIsOwner || $mIsAdmin)
                    <form method="POST" action="{{ route('growth-review.missions.doc-delete', $m->id) }}" class="d-inline" onsubmit="return confirm('{{ __('Remove file?') }}')">@csrf @method('DELETE')
                        <button type="submit" class="m-doc-remove" title="{{ __('Remove') }}"><i class="ti ti-x"></i></button>
                    </form>
                    @endif
                @else
                    @if($mIsOwner || $mIsAdmin)
                    <form method="POST" action="{{ route('growth-review.missions.upload', $m->id) }}" enctype="multipart/form-data" class="d-inline m-upload-form">@csrf
                        <label class="m-upload-btn" title="{{ __('Upload evidence') }}">
                            <i class="ti ti-upload me-1"></i>{{ __('Upload File') }}
                            <input type="file" name="document" accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg" hidden onchange="this.closest('form').submit()">
                        </label>
                    </form>
                    @else
                    <span class="text-muted" style="font-size:.78rem;">{{ __('No file attached') }}</span>
                    @endif
                @endif
            </div>
        </div>
    </div>

    {{-- Edit Modal --}}
    <div class="modal fade" id="editMission{{ $m->id }}" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="POST" action="{{ route('growth-review.missions.update', $m->id) }}">@csrf @method('PUT')
        <div class="modal-header"><h5 class="modal-title">{{ __('Edit Mission') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="mb-3"><label class="form-label">{{ __('Title') }}</label><input type="text" name="title" class="form-control" value="{{ $m->title }}" required></div>
            <div class="mb-3"><label class="form-label">{{ __('Description') }}</label><textarea name="description" class="form-control" rows="2">{{ $m->description }}</textarea></div>
            <div class="row g-2 mb-3">
                <div class="col-md-6"><label class="form-label">{{ __('KPI') }}</label><input type="text" name="kpi" class="form-control" value="{{ $m->kpi }}"></div>
                <div class="col-md-3"><label class="form-label">{{ __('Weightage %') }}</label><input type="number" name="weightage" class="form-control" value="{{ $m->weightage }}" min="0" max="100"></div>
                <div class="col-md-3"><label class="form-label">{{ __('Progress %') }}</label><input type="number" name="progress" class="form-control" value="{{ $m->progress }}" min="0" max="100"></div>
            </div>
            <div class="row g-2">
                <div class="col-md-6"><label class="form-label">{{ __('Status') }}</label><select name="status" class="form-control">@foreach(['pending','in_progress','completed','cancelled'] as $st)<option value="{{ $st }}" {{ $m->status==$st?'selected':'' }}>{{ ucfirst(str_replace('_',' ',$st)) }}</option>@endforeach</select></div>
                <div class="col-md-6"><label class="form-label">{{ __('Deadline') }}</label><input type="date" name="deadline" class="form-control" value="{{ $m->deadline?->format('Y-m-d') }}"></div>
            </div>
        </div>
        <div class="modal-footer"><button class="btn btn-primary">{{ __('Update') }}</button></div>
    </form></div></div></div>
    @empty
    <div class="card"><div class="card-body text-center text-muted py-5"><i class="ti ti-target" style="font-size:3rem;opacity:.3;"></i><p class="mt-2">{{ __('No missions yet. Click "Add Mission" to set goals.') }}</p></div></div>
    @endforelse

    {{-- Weighted Score Summary --}}
    @if($missions->isNotEmpty())
    @php
        $selfTotal = 0; $mgrTotal = 0; $hodTotal = 0; $totalWeight = 0;
        foreach ($missions as $mi) {
            $w = (float) $mi->weightage;
            $totalWeight += $w;
            if ($mi->self_rating !== null) $selfTotal += (float) $mi->self_rating * ($w / 100);
            if ($mi->manager_rating !== null) $mgrTotal += (float) $mi->manager_rating * ($w / 100);
            if ($mi->hod_rating !== null) $hodTotal += (float) $mi->hod_rating * ($w / 100);
        }
        $selfTotal = round($selfTotal, 2);
        $mgrTotal = round($mgrTotal, 2);
        $hodTotal = round($hodTotal, 2);
    @endphp
    <div class="card mt-3">
        <div class="card-body">
            <h6 class="mb-3"><i class="ti ti-calculator me-1"></i>{{ __('Weighted Score Summary') }} <small class="text-muted">({{ __('Total Weight') }}: {{ $totalWeight }}%)</small></h6>
            <div class="d-flex gap-3 flex-wrap">
                <div class="text-center p-3 flex-fill" style="background:linear-gradient(135deg,#ede9fe,#faf5ff);border-radius:12px;">
                    <small class="text-muted d-block mb-1">{{ __('Self Score') }}</small>
                    <strong style="font-size:1.5rem;color:#6d28d9;">{{ $selfTotal }}</strong><small class="text-muted">/5</small>
                </div>
                <div class="text-center p-3 flex-fill" style="background:linear-gradient(135deg,#fef3c7,#fffbeb);border-radius:12px;">
                    <small class="text-muted d-block mb-1">{{ __('Manager Score') }}</small>
                    <strong style="font-size:1.5rem;color:#92400e;">{{ $mgrTotal }}</strong><small class="text-muted">/5</small>
                </div>
                <div class="text-center p-3 flex-fill" style="background:linear-gradient(135deg,#cffafe,#ecfeff);border-radius:12px;">
                    <small class="text-muted d-block mb-1">{{ __('HOD Score') }}</small>
                    <strong style="font-size:1.5rem;color:#0e7490;">{{ $hodTotal }}</strong><small class="text-muted">/5</small>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Add Mission Modal --}}
    <div class="modal fade" id="addMissionModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="POST" action="{{ route('growth-review.missions.store') }}">@csrf
        <input type="hidden" name="cycle_id" value="{{ $cycleId }}">
        <div class="modal-header"><h5 class="modal-title"><i class="ti ti-target me-2"></i>{{ __('Add Mission') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            @if($isAdmin)
            <div class="mb-3"><label class="form-label">{{ __('Employee') }}</label>
                <select name="employee_id" class="form-control">@foreach($employees as $e)<option value="{{ $e->id }}">{{ $e->name }}</option>@endforeach</select>
            </div>
            @endif
            <div class="mb-3"><label class="form-label">{{ __('Title') }} <span class="text-danger">*</span></label><input type="text" name="title" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">{{ __('Description') }}</label><textarea name="description" class="form-control" rows="2"></textarea></div>
            <div class="row g-2 mb-3">
                <div class="col-md-6"><label class="form-label">{{ __('KPI') }}</label><input type="text" name="kpi" class="form-control" placeholder="e.g. Revenue +20%"></div>
                <div class="col-md-3"><label class="form-label">{{ __('Weight %') }}</label><input type="number" name="weightage" class="form-control" value="0" min="0" max="100"></div>
                <div class="col-md-3"><label class="form-label">{{ __('Deadline') }}</label><input type="date" name="deadline" class="form-control"></div>
            </div>
        </div>
        <div class="modal-footer"><button class="btn btn-primary"><i class="ti ti-check me-1"></i>{{ __('Create Mission') }}</button></div>
    </form></div></div></div>
@endsection

@push('script-page')
<script>
(function(){
    var CSRF = '{{ csrf_token() }}';
    var timers = {};

    document.addEventListener('input', function(e){
        var inp = e.target.closest('.m-rate-input, .m-rate-remarks');
        if (!inp || inp.disabled) return;

        var section = inp.closest('.m-rating-section');
        if (!section) return;

        var url = section.dataset.rateUrl;
        var field = inp.dataset.field;
        var key = section.dataset.missionId + '-' + field;

        clearTimeout(timers[key]);
        timers[key] = setTimeout(function(){
            var value = inp.value.trim();

            // Validate rating fields
            if (field.indexOf('_rating') !== -1 && field.indexOf('_remarks') === -1) {
                var n = parseFloat(value);
                if (value !== '' && (isNaN(n) || n < 0 || n > 5)) return;
                inp.classList.toggle('has-val', value !== '' && n > 0);
            }

            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ field: field, value: value || null }),
            }).then(function(r){ return r.json(); }).then(function(j){
                if (j && j.ok) {
                    inp.classList.add('saved');
                    setTimeout(function(){ inp.classList.remove('saved'); }, 700);
                } else {
                    alert((j && j.error) || 'Failed to save.');
                }
            });
        }, 500);
    });
})();
</script>
@endpush
