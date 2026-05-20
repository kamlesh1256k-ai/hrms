@extends('layouts.admin')

@section('page-title') {{ __('Assessments') }} — {{ $candidate->name }} @endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('recruitment.dashboard') }}">{{ __('Recruitment') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('recruitment.assessments.index') }}">{{ __('Assessments') }}</a></li>
    <li class="breadcrumb-item">{{ $candidate->name }}</li>
@endsection

@section('content')
<div class="container-fluid">
    @include('recruitment._nav')

    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if(session('error'))   <div class="alert alert-danger">{{ session('error') }}</div>   @endif

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-1">{{ $candidate->name }}</h4>
            <div class="text-muted small">{{ $candidate->email }} · {{ $candidate->phone }}
                @if($candidate->jobs) · <strong>{{ $candidate->jobs->title }}</strong> @endif
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            @if($assessments->isEmpty())
                <div class="card text-center py-5">
                    <div class="card-body text-muted">
                        <i class="ti ti-clipboard-off" style="font-size:3rem;opacity:.4;"></i>
                        <p class="mt-2 mb-0">{{ __('No assessments yet.') }}</p>
                        <small>{{ __('Use the panel on the right to schedule one.') }}</small>
                    </div>
                </div>
            @else
                @foreach($assessments as $a)
                    <div class="card mb-3">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0">{{ $a->title }}</h6>
                                <small class="text-muted">{{ \App\Models\RecruitmentAssessment::$types[$a->assessment_type] ?? $a->assessment_type }}</small>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-{{ \App\Models\RecruitmentAssessment::$outcomeBadge[$a->outcome] ?? 'secondary' }}">
                                    {{ \App\Models\RecruitmentAssessment::$outcomes[$a->outcome] }}
                                </span>
                                @if($a->percentage !== null)
                                    <span class="badge bg-{{ $a->pass_fail_badge }} ms-1" style="font-size:.75rem;letter-spacing:.5px;">
                                        {{ strtoupper($a->pass_fail) }}
                                    </span>
                                    <div class="small mt-1">
                                        <strong class="{{ $a->score >= $a->passing_score ? 'text-success' : 'text-danger' }}">
                                            {{ $a->score }} / {{ $a->max_score }} ({{ $a->percentage }}%)
                                        </strong>
                                        <span class="text-muted">· passing {{ $a->passing_score }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <form method="POST" action="{{ route('recruitment.assessments.update', $a->id) }}" enctype="multipart/form-data" class="card-body">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label small">{{ __('Score') }}</label>
                                    <input type="number" name="score" value="{{ $a->score }}" class="form-control form-control-sm" min="0" max="{{ $a->max_score }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small">{{ __('Outcome') }}</label>
                                    <select name="outcome" class="form-select form-select-sm">
                                        @foreach(\App\Models\RecruitmentAssessment::$outcomes as $k => $label)
                                            <option value="{{ $k }}" @selected($a->outcome === $k)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small">{{ __('Completion Date') }}</label>
                                    <input type="date" name="completed_on" value="{{ $a->completed_on?->toDateString() }}" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small">{{ __('Document') }}</label>
                                    <input type="file" name="document" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx">
                                </div>
                                <div class="col-12">
                                    <label class="form-label small">{{ __('Feedback') }}</label>
                                    <textarea name="feedback" rows="2" class="form-control form-control-sm" maxlength="5000">{{ $a->feedback }}</textarea>
                                </div>
                            </div>
                            <div class="text-end mt-3">
                                @if($a->document_path)
                                    <a href="{{ asset('storage/'.$a->document_path) }}" target="_blank" class="btn btn-sm btn-outline-secondary me-2">
                                        <i class="ti ti-paperclip me-1"></i>{{ __('View document') }}
                                    </a>
                                @endif
                                <button class="btn btn-sm btn-primary"><i class="ti ti-device-floppy me-1"></i>{{ __('Save') }}</button>
                            </div>
                        </form>
                        <div class="card-footer text-end">
                            <form method="POST" action="{{ route('recruitment.assessments.delete', $a->id) }}" class="d-inline" onsubmit="return confirm('Delete this assessment?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="ti ti-trash me-1"></i>{{ __('Remove') }}</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h6 class="mb-0"><i class="ti ti-plus me-1"></i>{{ __('Schedule Assessment') }}</h6></div>
                <form method="POST" action="{{ route('recruitment.assessments.store', $candidate->id) }}" class="card-body">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">{{ __('Type') }}</label>
                        <select name="assessment_type" class="form-select" required>
                            @foreach(\App\Models\RecruitmentAssessment::$types as $k => $label)
                                <option value="{{ $k }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Title') }}</label>
                        <input type="text" name="title" class="form-control" required maxlength="200" placeholder="e.g. SQL & Data Modelling Test">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Scheduled On') }}</label>
                        <input type="date" name="scheduled_on" class="form-control">
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label">{{ __('Max Score') }}</label>
                            <input type="number" name="max_score" value="100" class="form-control" required min="1" max="1000">
                        </div>
                        <div class="col-6">
                            <label class="form-label">{{ __('Passing') }}</label>
                            <input type="number" name="passing_score" value="60" class="form-control" required min="0">
                        </div>
                    </div>
                    <button class="btn btn-primary w-100 mt-3"><i class="ti ti-plus me-1"></i>{{ __('Schedule') }}</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
