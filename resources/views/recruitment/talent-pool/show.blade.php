@extends('layouts.admin')

@section('page-title') {{ $candidate->name }} — {{ __('Talent Pool') }} @endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('recruitment.dashboard') }}">{{ __('Recruitment') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('recruitment.talent-pool.index') }}">{{ __('Talent Pool') }}</a></li>
    <li class="breadcrumb-item">{{ $candidate->name }}</li>
@endsection

@push('css-page')
<style>
    .tp-attr-label { font-size:.72rem; text-transform:uppercase; letter-spacing:.4px; color:#64748b; font-weight:600; }
    .tp-attr-val { font-size:.92rem; }
    .tp-skill-pill { display:inline-block; background:#eef2ff; color:#4338ca;
                     padding:3px 10px; border-radius:14px; font-size:.78rem; margin:2px 3px; }
    .tp-tag-pill { display:inline-block; background:#fef3c7; color:#92400e;
                   padding:3px 10px; border-radius:14px; font-size:.78rem; margin:2px 3px; }
</style>
@endpush

@section('content')
<div class="container-fluid">
    @include('recruitment._nav')

    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if(session('info'))    <div class="alert alert-info">{{ session('info') }}</div>      @endif
    @if(session('error'))   <div class="alert alert-danger">{{ session('error') }}</div>   @endif

    <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h4 class="mb-1">
                {{ $candidate->name }}
                <span class="badge bg-{{ \App\Models\TalentPoolCandidate::$statusBadge[$candidate->status] }} ms-2">
                    {{ \App\Models\TalentPoolCandidate::$statuses[$candidate->status] }}
                </span>
            </h4>
            <div class="text-muted small">
                <i class="ti ti-mail"></i> {{ $candidate->email }}
                @if($candidate->phone) · <i class="ti ti-phone"></i> {{ $candidate->phone }} @endif
                · {{ \App\Models\TalentPoolCandidate::$sources[$candidate->source] ?? $candidate->source }}
            </div>
        </div>
        <div class="d-flex gap-2">
            @if($candidate->linkedin_url)
                <a href="{{ $candidate->linkedin_url }}" target="_blank" class="btn btn-sm btn-outline-primary">
                    <i class="ti ti-brand-linkedin me-1"></i>{{ __('LinkedIn') }}
                </a>
            @endif
            @if($candidate->resume_path)
                <a href="{{ asset(str_starts_with($candidate->resume_path, 'uploads/') ? $candidate->resume_path : 'storage/'.$candidate->resume_path) }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                    <i class="ti ti-file-cv me-1"></i>{{ __('Resume') }}
                </a>
            @endif
            <form method="POST" action="{{ route('recruitment.talent-pool.delete', $candidate->id) }}" class="d-inline" onsubmit="return confirm('Remove from talent pool?')">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-outline-danger"><i class="ti ti-trash"></i></button>
            </form>
        </div>
    </div>

    <div class="row">
        {{-- LEFT: profile (editable) --}}
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header"><h6 class="mb-0">{{ __('Profile') }}</h6></div>
                <form method="POST" action="{{ route('recruitment.talent-pool.update', $candidate->id) }}" enctype="multipart/form-data" class="card-body">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small">{{ __('Name') }}</label>
                            <input type="text" name="name" value="{{ $candidate->name }}" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">{{ __('Email') }}</label>
                            <input type="email" name="email" value="{{ $candidate->email }}" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">{{ __('Phone') }}</label>
                            <input type="text" name="phone" value="{{ $candidate->phone }}" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">{{ __('Current Company') }}</label>
                            <input type="text" name="current_company" value="{{ $candidate->current_company }}" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">{{ __('Designation') }}</label>
                            <input type="text" name="current_designation" value="{{ $candidate->current_designation }}" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">{{ __('Experience (yrs)') }}</label>
                            <input type="number" step="0.5" name="experience_years" value="{{ $candidate->experience_years }}" class="form-control form-control-sm" min="0" max="60">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">{{ __('Notice Period (days)') }}</label>
                            <input type="number" name="notice_period_days" value="{{ $candidate->notice_period_days }}" class="form-control form-control-sm" min="0" max="365">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">{{ __('Current CTC') }}</label>
                            <input type="number" step="0.01" name="current_ctc" value="{{ $candidate->current_ctc }}" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">{{ __('Expected CTC') }}</label>
                            <input type="number" step="0.01" name="expected_ctc" value="{{ $candidate->expected_ctc }}" class="form-control form-control-sm">
                        </div>
                        <div class="col-12">
                            <label class="form-label small">{{ __('Skills') }}</label>
                            <input type="text" name="skills" value="{{ $candidate->skills }}" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">{{ __('Preferred Locations') }}</label>
                            <input type="text" name="preferred_locations" value="{{ $candidate->preferred_locations }}" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">{{ __('Tags') }}</label>
                            <input type="text" name="tags" value="{{ $candidate->tags }}" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">{{ __('LinkedIn') }}</label>
                            <input type="url" name="linkedin_url" value="{{ $candidate->linkedin_url }}" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">{{ __('Portfolio') }}</label>
                            <input type="url" name="portfolio_url" value="{{ $candidate->portfolio_url }}" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">{{ __('Source') }}</label>
                            <select name="source" class="form-select form-select-sm">
                                @foreach(\App\Models\TalentPoolCandidate::$sources as $k => $label)
                                    <option value="{{ $k }}" @selected($candidate->source === $k)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">{{ __('Source Detail') }}</label>
                            <input type="text" name="source_detail" value="{{ $candidate->source_detail }}" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">{{ __('Assigned Recruiter') }}</label>
                            <select name="assigned_recruiter_id" class="form-select form-select-sm">
                                <option value="">{{ __('-- Unassigned --') }}</option>
                                @foreach(\App\Models\User::where('created_by', \Auth::user()->creatorId())->whereIn('type', ['hr','company','employee'])->orderBy('name')->get() as $u)
                                    <option value="{{ $u->id }}" @selected($candidate->assigned_recruiter_id == $u->id)>{{ $u->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">{{ __('Replace Resume') }}</label>
                            <input type="file" name="resume" class="form-control form-control-sm" accept=".pdf,.doc,.docx">
                        </div>
                    </div>
                    <div class="text-end mt-3">
                        <button class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>{{ __('Save Profile') }}</button>
                    </div>
                </form>
            </div>

            {{-- Engagement notes --}}
            <div class="card">
                <div class="card-header"><h6 class="mb-0"><i class="ti ti-messages me-1 text-primary"></i>{{ __('Engagement Notes') }}</h6></div>
                <div class="card-body">
                    @if($candidate->notes)
                        <pre class="bg-light p-3 rounded small" style="white-space:pre-wrap;font-family:inherit;">{{ $candidate->notes }}</pre>
                    @else
                        <div class="text-muted small">{{ __('No notes yet. Use the status update on the right to add one.') }}</div>
                    @endif
                </div>
            </div>
        </div>

        {{-- RIGHT: status + tags + skills summary --}}
        <div class="col-lg-4">
            {{-- Status updater --}}
            <div class="card mb-3">
                <div class="card-header"><h6 class="mb-0"><i class="ti ti-pin me-1"></i>{{ __('Update Status') }}</h6></div>
                <form method="POST" action="{{ route('recruitment.talent-pool.status', $candidate->id) }}" class="card-body">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label small">{{ __('Status') }}</label>
                        <select name="status" class="form-select" required>
                            @foreach(\App\Models\TalentPoolCandidate::$statuses as $k => $label)
                                <option value="{{ $k }}" @selected($candidate->status === $k)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">{{ __('Add a Note (optional)') }}</label>
                        <textarea name="notes" rows="3" class="form-control" maxlength="5000"
                                  placeholder="{{ __('Reached out on LinkedIn / Asked about availability / etc.') }}"></textarea>
                    </div>
                    <button class="btn btn-primary w-100"><i class="ti ti-device-floppy me-1"></i>{{ __('Save') }}</button>
                </form>
            </div>

            {{-- Skills summary --}}
            @if(!empty($candidate->skills_array))
                <div class="card mb-3">
                    <div class="card-header"><h6 class="mb-0"><i class="ti ti-bulb me-1"></i>{{ __('Skills') }}</h6></div>
                    <div class="card-body">
                        @foreach($candidate->skills_array as $sk)
                            <span class="tp-skill-pill">{{ $sk }}</span>
                        @endforeach
                    </div>
                </div>
            @endif

            @if(!empty($candidate->tags_array))
                <div class="card mb-3">
                    <div class="card-header"><h6 class="mb-0"><i class="ti ti-tags me-1"></i>{{ __('Tags') }}</h6></div>
                    <div class="card-body">
                        @foreach($candidate->tags_array as $tag)
                            <span class="tp-tag-pill">{{ $tag }}</span>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Linked source --}}
            @if($candidate->linkedApplication)
                <div class="card">
                    <div class="card-header"><h6 class="mb-0"><i class="ti ti-link me-1"></i>{{ __('Linked Application') }}</h6></div>
                    <div class="card-body small">
                        {{ __('Originally applied for') }} <strong>{{ $candidate->linkedApplication->jobs->title ?? '—' }}</strong>
                        <br>
                        <span class="text-muted">{{ $candidate->linkedApplication->created_at->format('d M Y') }}</span>
                    </div>
                </div>
            @endif

            <div class="text-muted small mt-2">
                {{ __('Added') }}: {{ $candidate->created_at->format('d M Y') }}
                @if($candidate->last_engaged_at)
                    · {{ __('Last engaged') }}: {{ $candidate->last_engaged_at->diffForHumans() }}
                @endif
                @if($candidate->recruiter)
                    <br>{{ __('Owner') }}: <strong>{{ $candidate->recruiter->name }}</strong>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
