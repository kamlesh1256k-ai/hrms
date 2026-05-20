@extends('layouts.admin')
@section('page-title') {{ __('Growth Review') }} @endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item">{{ __('Growth Review') }}</li>
@endsection

@push('css-page')
<style>
    .gr-stat{border-radius:12px;padding:20px;color:#fff;position:relative;overflow:hidden;min-height:110px;}
    .gr-stat .gs-icon{position:absolute;right:16px;top:16px;font-size:2.2rem;opacity:.25;}
    .gr-stat .gs-val{font-size:2rem;font-weight:800;line-height:1;}
    .gr-stat .gs-lbl{font-size:.78rem;opacity:.85;margin-top:4px;}
    .gr-s1{background:linear-gradient(135deg,#4361ee,#3a0ca3);}
    .gr-s2{background:linear-gradient(135deg,#059669,#047857);}
    .gr-s3{background:linear-gradient(135deg,#f59e0b,#d97706);}
    .gr-s4{background:linear-gradient(135deg,#ef4444,#dc2626);}
    .gr-s5{background:linear-gradient(135deg,#8b5cf6,#7c3aed);}
    .gr-s6{background:linear-gradient(135deg,#06b6d4,#0891b2);}
    .shoutout-card{border:1px solid var(--bs-border-color);border-radius:10px;padding:14px;margin-bottom:10px;background:var(--bs-body-bg);}
    .shoutout-badge{display:inline-block;font-size:.8rem;padding:2px 8px;border-radius:20px;background:#fef3c7;color:#92400e;}
    .cycle-badge{font-size:.7rem;padding:3px 10px;border-radius:20px;font-weight:600;}
    .cycle-active{background:#dcfce7;color:#166534;}
    .cycle-draft{background:#f3f4f6;color:#6b7280;}
    .cycle-review{background:#dbeafe;color:#1e40af;}
    .cycle-completed{background:#e0e7ff;color:#3730a3;}
    .cycle-calibration{background:#fef3c7;color:#92400e;}
</style>
@endpush

@section('content')
    @include('growth_review._nav')

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show"><i class="ti ti-check me-1"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    {{-- Active Cycle Banner --}}
    @if($activeCycle)
    <div class="card mb-3" style="border-left:4px solid #4361ee;">
        <div class="card-body py-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h6 class="mb-0"><i class="ti ti-repeat me-1 text-primary"></i>{{ __('Active Cycle') }}: <strong>{{ $activeCycle->name }}</strong></h6>
                    <small class="text-muted">{{ $activeCycle->start_date->format('d M Y') }} — {{ $activeCycle->end_date->format('d M Y') }}</small>
                </div>
                <span class="cycle-badge cycle-{{ $activeCycle->status }}">{{ ucfirst($activeCycle->status) }}</span>
            </div>
        </div>
    </div>
    @else
    <div class="alert alert-warning"><i class="ti ti-alert-triangle me-1"></i>{{ __('No active performance cycle. HR needs to create one.') }}</div>
    @endif

    {{-- Stats --}}
    <div class="row g-3 mb-4">
        @if($isAdmin)
            <div class="col-lg-2 col-md-4 col-6"><div class="gr-stat gr-s1"><div class="gs-icon"><i class="ti ti-target"></i></div><div class="gs-val">{{ $stats['total_missions'] ?? 0 }}</div><div class="gs-lbl">{{ __('Total Missions') }}</div></div></div>
            <div class="col-lg-2 col-md-4 col-6"><div class="gr-stat gr-s3"><div class="gs-icon"><i class="ti ti-clock"></i></div><div class="gs-val">{{ $stats['pending_approvals'] ?? 0 }}</div><div class="gs-lbl">{{ __('Pending Approvals') }}</div></div></div>
            <div class="col-lg-2 col-md-4 col-6"><div class="gr-stat gr-s2"><div class="gs-icon"><i class="ti ti-speakerphone"></i></div><div class="gs-val">{{ $stats['shoutouts'] ?? 0 }}</div><div class="gs-lbl">{{ __('Shoutouts') }}</div></div></div>
            <div class="col-lg-2 col-md-4 col-6"><div class="gr-stat gr-s5"><div class="gs-icon"><i class="ti ti-clipboard-check"></i></div><div class="gs-val">{{ $stats['reviews_submitted'] ?? 0 }}</div><div class="gs-lbl">{{ __('Reviews Done') }}</div></div></div>
            <div class="col-lg-2 col-md-4 col-6"><div class="gr-stat gr-s6"><div class="gs-icon"><i class="ti ti-users"></i></div><div class="gs-val">{{ $stats['total_employees'] ?? 0 }}</div><div class="gs-lbl">{{ __('Employees') }}</div></div></div>
            <div class="col-lg-2 col-md-4 col-6"><div class="gr-stat gr-s4"><div class="gs-icon"><i class="ti ti-lock"></i></div><div class="gs-val">{{ $stats['ratings_frozen'] ?? 0 }}</div><div class="gs-lbl">{{ __('Ratings Frozen') }}</div></div></div>
        @else
            <div class="col-lg-3 col-md-6 col-6"><div class="gr-stat gr-s1"><div class="gs-icon"><i class="ti ti-target"></i></div><div class="gs-val">{{ $stats['my_missions'] ?? 0 }}</div><div class="gs-lbl">{{ __('My Missions') }}</div></div></div>
            <div class="col-lg-3 col-md-6 col-6"><div class="gr-stat gr-s2"><div class="gs-icon"><i class="ti ti-circle-check"></i></div><div class="gs-val">{{ $stats['my_completed'] ?? 0 }}</div><div class="gs-lbl">{{ __('Completed') }}</div></div></div>
            <div class="col-lg-3 col-md-6 col-6"><div class="gr-stat gr-s5"><div class="gs-icon"><i class="ti ti-speakerphone"></i></div><div class="gs-val">{{ $stats['shoutouts_received'] ?? 0 }}</div><div class="gs-lbl">{{ __('Shoutouts Received') }}</div></div></div>
            <div class="col-lg-3 col-md-6 col-6"><div class="gr-stat gr-s3"><div class="gs-icon"><i class="ti ti-clock"></i></div><div class="gs-val">{{ $stats['team_pending_approval'] ?? 0 }}</div><div class="gs-lbl">{{ __('Team Pending') }}</div></div></div>
        @endif
    </div>

    {{-- Recent Shoutouts --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="ti ti-speakerphone me-2"></i>{{ __('Recent Shoutouts') }}</h5>
            <a href="{{ route('growth-review.shoutouts') }}" class="btn btn-sm btn-outline-primary">{{ __('View All') }}</a>
        </div>
        <div class="card-body">
            @forelse($recentShoutouts as $s)
            <div class="shoutout-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <strong>{{ $s->fromEmployee->name ?? 'Unknown' }}</strong>
                        <i class="ti ti-arrow-right mx-1 text-muted"></i>
                        <strong class="text-primary">{{ $s->toEmployee->name ?? 'Unknown' }}</strong>
                        @if($s->badge)<span class="shoutout-badge ms-2">{{ $s->badge }}</span>@endif
                    </div>
                    <small class="text-muted">{{ $s->created_at->diffForHumans() }}</small>
                </div>
                <p class="mb-0 mt-1 text-muted" style="font-size:.88rem;">{{ $s->message }}</p>
            </div>
            @empty
            <p class="text-muted mb-0">{{ __('No shoutouts yet. Be the first to recognize a teammate!') }}</p>
            @endforelse
        </div>
    </div>
@endsection
