@extends('layouts.admin')
@section('page-title') {{ __('Agent Tokens') }} @endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('activity-tracker.index') }}">{{ __('Activity Tracker') }}</a></li>
    <li class="breadcrumb-item">{{ __('Tokens') }}</li>
@endsection

@push('css-page')
<style>
    .plain-token-card{background:linear-gradient(135deg,#f0fdf4,#dcfce7);border:1px solid #86efac;border-radius:12px;padding:16px;margin-bottom:18px;}
    .plain-token-card code{background:#fff;padding:8px 12px;border-radius:8px;display:inline-block;font-size:.92rem;word-break:break-all;border:1px dashed #86efac;}
</style>
@endpush

@section('content')
    @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    @if(session('plain_token'))
        <div class="plain-token-card">
            <strong><i class="ti ti-key me-1"></i>{{ __('New token (copy this now — it will not be shown again)') }}</strong>
            <div class="mt-2">
                <code id="plainTokenVal">{{ session('plain_token') }}</code>
                <button type="button" class="btn btn-sm btn-light border ms-2" onclick="navigator.clipboard.writeText(document.getElementById('plainTokenVal').textContent.trim()).then(()=>alert('{{ __('Copied!') }}'))">
                    <i class="ti ti-copy"></i> {{ __('Copy') }}
                </button>
            </div>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header"><h6 class="mb-0"><i class="ti ti-plus me-1"></i>{{ __('Create Agent Token') }}</h6></div>
                <div class="card-body">
                    <p class="small text-muted">{{ __('Generate a personal access token for the desktop agent. Paste it into the Electron agent\'s settings to authorise this device.') }}</p>
                    <form method="POST" action="{{ route('activity-tracker.token.create') }}">
                        @csrf
                        <div class="form-group mb-3">
                            <label class="form-label small">{{ __('Token Name') }}</label>
                            <input type="text" name="name" class="form-control" required maxlength="80" placeholder="{{ __('e.g. Office Laptop') }}">
                        </div>
                        <button class="btn btn-primary btn-sm w-100"><i class="ti ti-key me-1"></i>{{ __('Generate Token') }}</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card">
                <div class="card-header"><h6 class="mb-0"><i class="ti ti-list me-1"></i>{{ __('Existing Tokens') }}</h6></div>
                <div class="card-body p-0">
                    @if($tokens->isEmpty())
                        <div class="text-muted small p-3">{{ __('No tokens yet.') }}</div>
                    @else
                        <ul class="list-group list-group-flush">
                            @foreach($tokens as $t)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong class="small">{{ $t->name }}</strong>
                                        <div class="text-muted" style="font-size:.7rem;">
                                            {{ __('Created') }} {{ $t->created_at->diffForHumans() }}
                                            @if($t->last_used_at) · {{ __('Last used') }} {{ $t->last_used_at->diffForHumans() }} @endif
                                        </div>
                                    </div>
                                    <form method="POST" action="{{ route('activity-tracker.token.revoke', $t->id) }}" onsubmit="return confirm('{{ __('Revoke this token? The agent using it will be signed out.') }}')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-light border text-danger"><i class="ti ti-trash"></i></button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
