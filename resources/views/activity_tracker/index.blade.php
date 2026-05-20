@extends('layouts.admin')
@section('page-title') {{ __('Activity Tracker') }} @endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item">{{ __('Activity Tracker') }}</li>
@endsection

@push('css-page')
<style>
    .at-stat{border:1px solid var(--bs-border-color);border-radius:12px;background:#fff;padding:16px 18px;display:flex;align-items:center;gap:12px;}
    .at-stat .at-icon{width:42px;height:42px;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;color:#fff;font-size:18px;}
    .at-stat .at-num{font-size:1.5rem;font-weight:700;line-height:1;color:#0f172a;}
    .at-stat .at-lbl{font-size:.7rem;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-top:3px;}
    .at-stat.t-users  .at-icon{background:linear-gradient(135deg,#6366f1,#8b5cf6);}
    .at-stat.t-online .at-icon{background:linear-gradient(135deg,#10b981,#059669);}
    .at-stat.t-shots  .at-icon{background:linear-gradient(135deg,#f59e0b,#ef4444);}
    .at-stat.t-time   .at-icon{background:linear-gradient(135deg,#06b6d4,#0ea5e9);}

    .at-shot-tile{position:relative;border-radius:10px;overflow:hidden;background:#0f172a;aspect-ratio:16/10;}
    .at-shot-tile img{width:100%;height:100%;object-fit:cover;transition:transform .3s;}
    .at-shot-tile:hover img{transform:scale(1.04);}
    .at-shot-tile .at-shot-meta{position:absolute;left:0;right:0;bottom:0;padding:8px 10px;background:linear-gradient(transparent,rgba(0,0,0,.85));color:#fff;font-size:.7rem;}

    .pill-online {background:#dcfce7;color:#166534;font-size:.66rem;padding:2px 8px;border-radius:20px;font-weight:700;text-transform:uppercase;}
    .pill-offline{background:#fee2e2;color:#991b1b;font-size:.66rem;padding:2px 8px;border-radius:20px;font-weight:700;text-transform:uppercase;}

    .top-app-row{display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px dashed #e2e8f0;}
    .top-app-row:last-child{border-bottom:0;}
    .top-app-row .bar{height:6px;background:linear-gradient(90deg,#6366f1,#8b5cf6);border-radius:3px;}
</style>
@endpush

@section('content')
    @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    {{-- Pending Stop Requests --}}
    @if($pendingStopRequests->isNotEmpty())
    <div class="alert alert-warning d-flex align-items-start gap-3 mb-3" role="alert">
        <i class="ti ti-alert-triangle fs-4 mt-1 text-warning"></i>
        <div class="flex-grow-1">
            <strong>{{ __('Tracking Stop Requests') }}</strong> &mdash;
            {{ $pendingStopRequests->count() }} {{ __('employee(s) have requested to stop tracking. Approve or reject below.') }}
            <div class="mt-2 d-flex flex-column gap-2">
                @foreach($pendingStopRequests as $sr)
                <div class="d-flex align-items-center justify-content-between bg-white rounded px-3 py-2 border">
                    <div>
                        <strong>{{ $sr->user->name ?? '—' }}</strong>
                        <span class="text-muted small ms-1">{{ $sr->user->email ?? '' }}</span>
                        <span class="text-muted small ms-2"><i class="ti ti-device-laptop"></i> {{ $sr->device->device_name ?? '—' }}</span>
                        <span class="text-muted small ms-2"><i class="ti ti-clock"></i> {{ $sr->created_at->diffForHumans() }}</span>
                        @if($sr->reason)
                            <div class="text-muted small mt-1"><i class="ti ti-message"></i> {{ $sr->reason }}</div>
                        @endif
                    </div>
                    <div class="d-flex gap-2">
                        <form method="POST" action="{{ route('activity-tracker.stop-request.review', $sr->id) }}">
                            @csrf
                            <input type="hidden" name="action" value="approved">
                            <button class="btn btn-sm btn-success" onclick="return confirm('{{ __('Approve stop tracking for this employee?') }}')">
                                <i class="ti ti-check"></i> {{ __('Approve') }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('activity-tracker.stop-request.review', $sr->id) }}">
                            @csrf
                            <input type="hidden" name="action" value="rejected">
                            <button class="btn btn-sm btn-danger">
                                <i class="ti ti-x"></i> {{ __('Reject') }}
                            </button>
                        </form>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- KPI Stats --}}
    <div class="row g-3 mb-3">
        <div class="col-md-3 col-6"><div class="at-stat t-users"><span class="at-icon"><i class="ti ti-users"></i></span>
            <div><div class="at-num">{{ $totals['active_users'] }}</div><div class="at-lbl">{{ __('Active Users Today') }}</div></div></div></div>
        <div class="col-md-3 col-6"><div class="at-stat t-online"><span class="at-icon"><i class="ti ti-device-laptop"></i></span>
            <div><div class="at-num">{{ $totals['active_devices'] }} / {{ $totals['total_devices'] }}</div><div class="at-lbl">{{ __('Online Devices') }}</div></div></div></div>
        <div class="col-md-3 col-6"><div class="at-stat t-shots"><span class="at-icon"><i class="ti ti-camera"></i></span>
            <div><div class="at-num">{{ $totals['shots_today'] }}</div><div class="at-lbl">{{ __('Screenshots Today') }}</div></div></div></div>
        <div class="col-md-3 col-6"><div class="at-stat t-time"><span class="at-icon"><i class="ti ti-clock"></i></span>
            <div><div class="at-num">{{ intdiv($totals['avg_active_seconds'], 3600) }}h {{ intdiv($totals['avg_active_seconds'] % 3600, 60) }}m</div><div class="at-lbl">{{ __('Avg Active / User') }}</div></div></div></div>
    </div>

    <div class="row g-3">
        {{-- Recent screenshots grid --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="ti ti-camera me-1"></i>{{ __('Recent Screenshots') }}</h6>
                    <a href="{{ route('activity-tracker.timeline') }}" class="btn btn-sm btn-light border">{{ __('View Timeline') }}</a>
                </div>
                <div class="card-body">
                    @if($recentShots->isEmpty())
                        <div class="text-center text-muted py-4">
                            <i class="ti ti-camera-off fs-1 opacity-25 d-block mb-2"></i>
                            {{ __('No screenshots captured yet.') }}
                        </div>
                    @else
                        <div class="row g-2">
                            @foreach($recentShots as $s)
                                <div class="col-md-3 col-6">
                                    <a href="{{ asset('storage/app/public/' . $s->image_path) }}" target="_blank" class="text-decoration-none">
                                        <div class="at-shot-tile">
                                            <img src="{{ asset('storage/app/public/' . $s->image_path) }}" alt="screenshot" loading="lazy">
                                            <div class="at-shot-meta">
                                                <strong>{{ optional($s->user)->name ?? '—' }}</strong><br>
                                                {{ $s->captured_at->format('h:i A') }} · {{ \Illuminate\Support\Str::limit($s->active_app, 22) }}
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Top apps & devices --}}
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header"><h6 class="mb-0"><i class="ti ti-apps me-1"></i>{{ __('Top Apps Today') }}</h6></div>
                <div class="card-body">
                    @if($topApps->isEmpty())
                        <div class="text-muted small">{{ __('No app usage data yet.') }}</div>
                    @else
                        @php $max = $topApps->max('total') ?: 1; @endphp
                        @foreach($topApps as $a)
                            <div class="top-app-row">
                                <div class="flex-grow-1 me-2">
                                    <div class="small fw-bold">{{ $a->app_name }}</div>
                                    <div class="bar mt-1" style="width:{{ ($a->total / $max) * 100 }}%;"></div>
                                </div>
                                <small class="text-muted">{{ intdiv($a->total, 60) }}m</small>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h6 class="mb-0"><i class="ti ti-device-desktop me-1"></i>{{ __('Devices') }}</h6></div>
                <div class="card-body p-0">
                    @if($devices->isEmpty())
                        <div class="text-muted small p-3">{{ __('No registered devices yet.') }}</div>
                    @else
                        <ul class="list-group list-group-flush">
                            @foreach($devices as $d)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong class="small">{{ $d->device_name }}</strong>
                                        <div class="text-muted" style="font-size:.7rem;">
                                            {{ optional($d->user)->name ?? '—' }} · {{ $d->os ?: 'Windows' }}
                                        </div>
                                    </div>
                                    <span class="{{ $d->isOnline() ? 'pill-online' : 'pill-offline' }}">
                                        {{ $d->isOnline() ? __('Online') : __('Offline') }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
