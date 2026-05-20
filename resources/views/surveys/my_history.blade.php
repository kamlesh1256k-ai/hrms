@extends('layouts.admin')
@section('page-title') {{ __('My Survey History') }} @endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('surveys.my') }}">{{ __('My Surveys') }}</a></li>
    <li class="breadcrumb-item">{{ __('History') }}</li>
@endsection

@push('css-page')
<style>
    .hist-item{display:flex;gap:14px;align-items:center;padding:14px 16px;border:1px solid #e2e8f0;border-radius:12px;background:#fff;margin-bottom:10px;transition:transform .12s,box-shadow .12s;}
    .hist-item:hover{transform:translateY(-1px);box-shadow:0 4px 12px -6px rgba(15,23,42,.12);}
    .hist-icon{width:42px;height:42px;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;color:#fff;background:linear-gradient(135deg,#10b981,#059669);font-size:18px;flex-shrink:0;}
    .hist-icon.t-pulse{background:linear-gradient(135deg,#f59e0b,#ef4444);}
    .hist-icon.t-enps {background:linear-gradient(135deg,#06b6d4,#0ea5e9);}
    .hist-body{flex-grow:1;min-width:0;}
    .hist-title{font-weight:700;color:#0f172a;margin:0;font-size:.95rem;}
    .hist-meta{font-size:.72rem;color:#64748b;margin-top:3px;}
    .hist-meta .ms-tag{font-size:.65rem;font-weight:700;padding:2px 7px;border-radius:5px;background:#f1f5f9;color:#475569;text-transform:uppercase;letter-spacing:.3px;margin-right:4px;}
    .hist-meta .ms-tag.anon{background:#ede9fe;color:#6d28d9;}
    .hist-date{font-size:.78rem;color:#475569;font-weight:600;text-align:right;flex-shrink:0;}
    .hist-empty{text-align:center;padding:60px 16px;color:#94a3b8;}
    .hist-empty i{font-size:3rem;opacity:.3;display:block;margin-bottom:10px;}
</style>
@endpush

@section('content')
    @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h5 class="mb-1"><i class="ti ti-history me-2"></i>{{ __('My Survey History') }}</h5>
                <small class="text-muted">{{ __('All surveys you have submitted') }}</small>
            </div>
            <a href="{{ route('surveys.my') }}" class="btn btn-light btn-sm border">
                <i class="ti ti-arrow-left me-1"></i>{{ __('Back to My Surveys') }}
            </a>
        </div>
        <div class="card-body">
            @if($responses->isEmpty())
                <div class="hist-empty">
                    <i class="ti ti-history-off"></i>
                    <p class="mb-0">{{ __('No submissions yet.') }}</p>
                    <small>{{ __('Once you submit a survey, it will appear here.') }}</small>
                </div>
            @else
                @foreach($responses as $r)
                    @php $survey = $r->survey; @endphp
                    @if(!$survey) @continue @endif
                    <div class="hist-item">
                        <span class="hist-icon t-{{ $survey->type }}">
                            @if($survey->type === 'pulse')<i class="ti ti-activity-heartbeat"></i>
                            @elseif($survey->type === 'enps')<i class="ti ti-trending-up"></i>
                            @else<i class="ti ti-clipboard-check"></i>
                            @endif
                        </span>
                        <div class="hist-body">
                            <h6 class="hist-title">{{ $survey->title }}</h6>
                            <div class="hist-meta">
                                <span class="ms-tag">{{ ucfirst($survey->type) }}</span>
                                @if($r->is_anonymous)
                                    <span class="ms-tag anon"><i class="ti ti-eye-off me-1"></i>{{ __('Anonymous') }}</span>
                                @endif
                                <i class="ti ti-circle-check text-success me-1"></i>{{ __('Submitted') }}
                            </div>
                        </div>
                        <div class="hist-date">
                            <div>{{ \Auth::user()->dateFormat($r->submitted_at ?? $r->created_at) }}</div>
                            <small class="text-muted">{{ \Carbon\Carbon::parse($r->submitted_at ?? $r->created_at)->diffForHumans() }}</small>
                        </div>
                    </div>
                @endforeach

                <div class="d-flex justify-content-between align-items-center mt-3">
                    <small class="text-muted">{{ __('Total submissions') }}: <strong>{{ $responses->total() }}</strong></small>
                    {{ $responses->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
