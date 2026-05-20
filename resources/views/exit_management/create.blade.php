@extends('layouts.admin')
@section('page-title') {{ __('Apply Resignation') }} @endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('exit-management.index') }}">{{ __('Exit Management') }}</a></li>
    <li class="breadcrumb-item">{{ __('Apply') }}</li>
@endsection

@push('css-page')
<style>
    .res-form .form-label{font-weight:600;font-size:.84rem;color:#334155;}
    .res-form .help{font-size:.72rem;color:#94a3b8;margin-top:3px;}
    .info-card{background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:14px;}
    .notice-pill{display:inline-block;padding:6px 14px;border-radius:20px;background:#dbeafe;color:#1d4ed8;font-weight:700;font-size:.82rem;}
</style>
@endpush

@section('content')
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('exit-management.store') }}" class="res-form">
        @csrf
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0"><i class="ti ti-logout me-2"></i>{{ __('Resignation Form') }}</h5></div>
                    <div class="card-body">

                        <div class="form-group mb-3">
                            <label class="form-label">{{ __('Reason for Resignation') }} <span class="text-danger">*</span></label>
                            <textarea name="reason" class="form-control" rows="5" required maxlength="2000"
                                      placeholder="{{ __('Briefly explain your reason…') }}">{{ old('reason') }}</textarea>
                        </div>

                        <div class="row">
                            <div class="form-group col-md-6 mb-3">
                                <label class="form-label">{{ __('Resignation Date') }} <span class="text-danger">*</span></label>
                                <input type="date" name="resignation_date" id="resDate" class="form-control" required
                                       value="{{ old('resignation_date', now()->toDateString()) }}">
                                <div class="help">{{ __('Today is the default.') }}</div>
                            </div>
                            <div class="form-group col-md-6 mb-3">
                                <label class="form-label">{{ __('Last Working Day') }} <span class="text-danger">*</span></label>
                                <input type="date" name="last_working_day" id="lwdDate" class="form-control" required
                                       value="{{ old('last_working_day', now()->addDays(60)->toDateString()) }}">
                                <div class="help">{{ __('Default 60 days notice.') }}</div>
                            </div>
                        </div>

                        <div class="info-card">
                            <span class="text-muted small">{{ __('Notice Period') }}</span>
                            <div class="mt-1"><span class="notice-pill"><i class="ti ti-clock me-1"></i><span id="noticeDays">—</span> {{ __('days') }}</span></div>
                        </div>

                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0"><i class="ti ti-user me-2"></i>{{ __('Reporting Manager') }}</h5></div>
                    <div class="card-body">
                        @if($employee && $employee->reportingManager)
                            <div class="info-card">
                                <strong>{{ $employee->reportingManager->name }}</strong>
                                <div class="small text-muted">{{ $employee->reportingManager->email ?? '' }}</div>
                            </div>
                            <p class="small text-muted mt-2">{{ __('Your resignation will be sent to this manager for approval.') }}</p>
                        @else
                            <div class="alert alert-warning small mb-0">
                                <i class="ti ti-alert-triangle me-1"></i>
                                {{ __('No reporting manager set. Your resignation will go directly to HR.') }}
                            </div>
                        @endif
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header"><h6 class="mb-0"><i class="ti ti-info-circle me-1"></i>{{ __('What happens next?') }}</h6></div>
                    <div class="card-body small text-muted">
                        <ol class="mb-0 ps-3">
                            <li>{{ __('Manager reviews & approves') }}</li>
                            <li>{{ __('HR final approval') }}</li>
                            <li>{{ __('Exit checklist (assets etc.)') }}</li>
                            <li>{{ __('Full & Final settlement') }}</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 mt-3">
            <button type="submit" class="btn btn-primary"
                    onclick="return confirm('{{ __('Submit your resignation? This action cannot be undone after manager approval.') }}')">
                <i class="ti ti-send me-1"></i>{{ __('Submit Resignation') }}
            </button>
            <a href="{{ route('exit-management.index') }}" class="btn btn-light">{{ __('Cancel') }}</a>
        </div>
    </form>
@endsection

@push('script-page')
<script>
(function(){
    const r = document.getElementById('resDate');
    const l = document.getElementById('lwdDate');
    const n = document.getElementById('noticeDays');
    function recalc(){
        if(!r.value || !l.value) { n.textContent = '—'; return; }
        const a = new Date(r.value), b = new Date(l.value);
        const days = Math.round((b - a) / 86400000);
        n.textContent = days >= 0 ? days : 0;
    }
    r.addEventListener('change', recalc);
    l.addEventListener('change', recalc);
    recalc();
})();
</script>
@endpush
