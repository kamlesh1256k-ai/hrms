@extends('layouts.admin')

@section('page-title') {{ __('Probation Reviews') }} — {{ $employee->name }} @endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('recruitment.dashboard') }}">{{ __('Recruitment') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('recruitment.probation.index') }}">{{ __('Probation') }}</a></li>
    <li class="breadcrumb-item">{{ $employee->name }}</li>
@endsection

@section('content')
<div class="container-fluid">
    @include('recruitment._nav')

    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif

    <div class="d-flex align-items-start justify-content-between mb-3">
        <div>
            <h4 class="mb-1">{{ $employee->name }}</h4>
            <div class="text-muted small">
                {{ $employee->employee_id ?? '—' }} · {{ $employee->designation->name ?? '—' }}
                @if($employee->company_doj)
                    · {{ __('Joined') }}: <strong>{{ \Carbon\Carbon::parse($employee->company_doj)->format('d M Y') }}</strong>
                @endif
            </div>
        </div>
    </div>

    <div class="row">
        @foreach($reviews as $r)
            <div class="col-md-4 mb-3">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">{{ $r->day_milestone }}-{{ __('Day Review') }}</h6>
                        <span class="badge bg-{{ \App\Models\ProbationReview::$outcomeBadge[$r->outcome] ?? 'secondary' }}">
                            {{ \App\Models\ProbationReview::$outcomes[$r->outcome] ?? $r->outcome }}
                        </span>
                    </div>
                    <form method="POST" action="{{ route('recruitment.probation.update', $r->id) }}" class="card-body">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label small">{{ __('Review Date') }}</label>
                            <input type="date" name="review_date" value="{{ $r->review_date?->toDateString() }}" class="form-control form-control-sm">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">{{ __('Outcome') }}</label>
                            <select name="outcome" class="form-select form-select-sm">
                                @foreach(\App\Models\ProbationReview::$outcomes as $k => $label)
                                    <option value="{{ $k }}" @selected($r->outcome === $k)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">{{ __('Rating (1–5)') }}</label>
                            <select name="rating" class="form-select form-select-sm">
                                <option value="">—</option>
                                @for($i = 1; $i <= 5; $i++)
                                    <option value="{{ $i }}" @selected($r->rating == $i)>{{ str_repeat('★', $i) }}{{ str_repeat('☆', 5 - $i) }} ({{ $i }})</option>
                                @endfor
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">{{ __('Strengths') }}</label>
                            <textarea name="strengths" rows="2" class="form-control form-control-sm" maxlength="2000">{{ $r->strengths }}</textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">{{ __('Areas of Improvement') }}</label>
                            <textarea name="improvements" rows="2" class="form-control form-control-sm" maxlength="2000">{{ $r->improvements }}</textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">{{ __('Manager Comments') }}</label>
                            <textarea name="manager_comments" rows="2" class="form-control form-control-sm" maxlength="2000">{{ $r->manager_comments }}</textarea>
                        </div>
                        <button class="btn btn-sm btn-primary w-100">
                            <i class="ti ti-device-floppy me-1"></i>{{ __('Save Review') }}
                        </button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
