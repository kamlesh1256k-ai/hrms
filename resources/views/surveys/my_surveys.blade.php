@extends('layouts.admin')
@section('page-title') {{ __('My Surveys') }} @endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item">{{ __('My Surveys') }}</li>
@endsection

@push('css-page')
<style>
    .ms-card{border:1px solid #e2e8f0;border-radius:14px;background:#fff;padding:18px;transition:transform .12s,box-shadow .12s;height:100%;display:flex;flex-direction:column;}
    .ms-card:hover{transform:translateY(-2px);box-shadow:0 8px 22px -10px rgba(15,23,42,.18);}
    .ms-card .ms-icon{width:42px;height:42px;border-radius:11px;display:inline-flex;align-items:center;justify-content:center;color:#fff;font-size:20px;background:linear-gradient(135deg,#6366f1,#8b5cf6);margin-bottom:10px;}
    .ms-card.t-pulse .ms-icon{background:linear-gradient(135deg,#f59e0b,#ef4444);}
    .ms-card.t-enps  .ms-icon{background:linear-gradient(135deg,#06b6d4,#0ea5e9);}
    .ms-title{font-weight:700;font-size:1rem;color:#0f172a;margin:0 0 4px 0;}
    .ms-meta{font-size:.72rem;color:#64748b;}
    .ms-desc{font-size:.84rem;color:#475569;margin:8px 0 12px 0;flex-grow:1;}
    .ms-foot{display:flex;justify-content:space-between;align-items:center;gap:8px;}
    .ms-tag{font-size:.65rem;font-weight:700;padding:2px 8px;border-radius:6px;text-transform:uppercase;letter-spacing:.4px;background:#f1f5f9;color:#475569;}
    .ms-tag.anon{background:#ede9fe;color:#6d28d9;}
    .ms-empty{text-align:center;padding:60px 16px;color:#94a3b8;}
    .ms-empty i{font-size:3rem;opacity:.3;display:block;margin-bottom:10px;}
    .ms-done-pill{font-size:.7rem;padding:3px 10px;border-radius:20px;background:#dcfce7;color:#166534;font-weight:700;}
</style>
@endpush

@section('content')
    @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(session('info'))<div class="alert alert-info alert-dismissible fade show">{{ session('info') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h5 class="mb-1"><i class="ti ti-clipboard-check me-2"></i>{{ __('My Surveys') }}</h5>
                <small class="text-muted">{{ __('Active surveys assigned to you') }}</small>
            </div>
            <a href="{{ route('surveys.my.history') }}" class="btn btn-light btn-sm border">
                <i class="ti ti-history me-1"></i>{{ __('View History') }}
            </a>
        </div>
        <div class="card-body">
            @if($surveys->isEmpty())
                <div class="ms-empty">
                    <i class="ti ti-clipboard-off"></i>
                    <p class="mb-0">{{ __('No active surveys for you right now.') }}</p>
                    <small>{{ __('When HR publishes a survey for your department, it will show up here.') }}</small>
                </div>
            @else
                <div class="row g-3">
                    @foreach($surveys as $s)
                        @php $done = $submittedIds->contains($s->id); @endphp
                        <div class="col-lg-4 col-md-6">
                            <div class="ms-card t-{{ $s->type }}">
                                <span class="ms-icon">
                                    @if($s->type === 'pulse')<i class="ti ti-activity-heartbeat"></i>
                                    @elseif($s->type === 'enps')<i class="ti ti-trending-up"></i>
                                    @else<i class="ti ti-clipboard-list"></i>
                                    @endif
                                </span>
                                <h6 class="ms-title">{{ $s->title }}</h6>
                                <div class="ms-meta">
                                    <span class="ms-tag">{{ ucfirst($s->type) }}</span>
                                    @if($s->is_anonymous)<span class="ms-tag anon"><i class="ti ti-eye-off me-1"></i>{{ __('Anonymous') }}</span>@endif
                                    <span class="ms-tag"><i class="ti ti-list me-1"></i>{{ $s->questions_count }} {{ __('Q') }}</span>
                                </div>
                                @if($s->description)
                                    <p class="ms-desc">{{ \Illuminate\Support\Str::limit($s->description, 110) }}</p>
                                @else
                                    <p class="ms-desc text-muted small fst-italic">{{ __('No description provided.') }}</p>
                                @endif

                                <div class="ms-foot">
                                    <small class="text-muted">
                                        @if($s->end_date)
                                            <i class="ti ti-clock me-1"></i>{{ __('Closes') }} {{ \Auth::user()->dateFormat($s->end_date) }}
                                        @else
                                            <i class="ti ti-infinity me-1"></i>{{ __('Open-ended') }}
                                        @endif
                                    </small>
                                    @if($done)
                                        <span class="ms-done-pill"><i class="ti ti-check me-1"></i>{{ __('Completed') }}</span>
                                    @else
                                        <a href="{{ route('surveys.my.fill', $s->id) }}" class="btn btn-sm btn-primary">
                                            {{ __('Take Survey') }} <i class="ti ti-arrow-right ms-1"></i>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection
