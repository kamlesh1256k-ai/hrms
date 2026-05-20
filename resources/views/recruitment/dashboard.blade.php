@extends('layouts.admin')

@section('page-title') {{ __('Recruitment') }} @endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item">{{ __('Recruitment') }}</li>
@endsection

@push('css-page')
<style>
    .rc-stat { border-radius:14px; padding:18px 20px; color:#fff; position:relative; overflow:hidden; }
    .rc-stat .label { font-size:.78rem; opacity:.85; text-transform:uppercase; letter-spacing:.5px; }
    .rc-stat .num   { font-size:1.9rem; font-weight:700; line-height:1.1; margin-top:6px; }
    .rc-stat .ic    { position:absolute; right:14px; top:14px; font-size:2rem; opacity:.25; }
    .rc-stat.gradient-1 { background:linear-gradient(135deg,#f59e0b,#ef4444); }
    .rc-stat.gradient-2 { background:linear-gradient(135deg,#10b981,#059669); }
    .rc-stat.gradient-3 { background:linear-gradient(135deg,#ef4444,#b91c1c); }
    .rc-stat.gradient-4 { background:linear-gradient(135deg,#3b82f6,#1d4ed8); }
    .rc-stat.gradient-5 { background:linear-gradient(135deg,#6366f1,#4f46e5); }
</style>
@endpush

@push('css-page')
<style>
    .rn-card { border-radius:12px; border:1px solid #e2e8f0; }
    .rn-item { display:flex; gap:12px; padding:12px 14px; border-bottom:1px solid #f1f5f9;
               text-decoration:none; color:inherit; transition:background .15s; }
    .rn-item:last-child { border-bottom:none; }
    .rn-item:hover { background:#f8fafc; color:inherit; }
    .rn-icon { width:36px; height:36px; border-radius:8px; display:flex; align-items:center;
               justify-content:center; font-size:1rem; flex-shrink:0; }
    .rn-icon.warning { background:#fef3c7; color:#92400e; }
    .rn-icon.info    { background:#dbeafe; color:#1e40af; }
    .rn-icon.success { background:#d1fae5; color:#065f46; }
    .rn-icon.danger  { background:#fee2e2; color:#991b1b; }
    .rn-icon.primary { background:#dbeafe; color:#1e3a8a; }
    .rn-icon.secondary { background:#e2e8f0; color:#475569; }
    .rn-title { font-size:.88rem; font-weight:600; color:#111827; line-height:1.3; }
    .rn-sub   { font-size:.76rem; color:#6b7280; margin-top:2px; }
    .rn-when  { font-size:.7rem; color:#9ca3af; margin-left:auto; flex-shrink:0; }
    .rn-empty { text-align:center; padding:32px 16px; color:#94a3b8; }
</style>
@endpush

@section('content')
<div class="container-fluid">
    @include('recruitment._nav')

    {{-- ═══ ACTION-NEEDED NOTIFICATION CENTER ═══ --}}
    @if($notif['total'] > 0)
        <div class="row g-3 mb-4">
            <div class="col-12">
                <div class="rn-card bg-white">
                    <div class="d-flex align-items-center justify-content-between p-3 border-bottom">
                        <h6 class="mb-0">
                            <i class="ti ti-bell-ringing me-1 text-warning"></i>
                            {{ __('Action Needed') }}
                            <span class="badge bg-danger ms-2">{{ $notif['total'] }}</span>
                        </h6>
                        <small class="text-muted">{{ __('Items waiting on you across the recruitment pipeline') }}</small>
                    </div>
                    <div>
                        @foreach($notif['items'] as $it)
                            <a href="{{ $it['url'] }}" class="rn-item">
                                <div class="rn-icon {{ $it['color'] }}">
                                    <i class="ti {{ $it['icon'] }}"></i>
                                </div>
                                <div style="flex:1;min-width:0;">
                                    <div class="rn-title">{{ $it['title'] }}</div>
                                    @if(!empty($it['subtitle']))
                                        <div class="rn-sub">{{ $it['subtitle'] }}</div>
                                    @endif
                                </div>
                                @if(!empty($it['when']))
                                    <span class="rn-when">{{ $it['when'] }}</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="alert alert-success d-flex align-items-center mb-4" role="alert">
            <i class="ti ti-circle-check me-2 fs-4"></i>
            <div>
                <strong>{{ __('All caught up!') }}</strong>
                <span class="text-muted ms-2">{{ __('No pending actions across the recruitment pipeline right now.') }}</span>
            </div>
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3 col-xl">
            <div class="rc-stat gradient-1">
                <div class="label">{{ __('Pending Approval') }}</div>
                <div class="num">{{ $stats['pending'] }}</div>
                <i class="ti ti-clock-hour-4 ic"></i>
            </div>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <div class="rc-stat gradient-2">
                <div class="label">{{ __('Approved') }}</div>
                <div class="num">{{ $stats['approved'] }}</div>
                <i class="ti ti-circle-check ic"></i>
            </div>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <div class="rc-stat gradient-3">
                <div class="label">{{ __('Rejected') }}</div>
                <div class="num">{{ $stats['rejected'] }}</div>
                <i class="ti ti-circle-x ic"></i>
            </div>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <div class="rc-stat gradient-4">
                <div class="label">{{ __('Fulfilled') }}</div>
                <div class="num">{{ $stats['fulfilled'] }}</div>
                <i class="ti ti-user-check ic"></i>
            </div>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <div class="rc-stat gradient-5">
                <div class="label">{{ __('Total Raised') }}</div>
                <div class="num">{{ $stats['total'] }}</div>
                <i class="ti ti-stack-2 ic"></i>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h6 class="mb-0">{{ __('Recent Requisitions') }}</h6>
            <div class="d-flex gap-2">
                <a href="{{ route('recruitment.requisitions.create') }}" class="btn btn-primary btn-sm">
                    <i class="ti ti-plus me-1"></i>{{ __('Raise Requisition') }}
                </a>
                <a href="{{ route('recruitment.requisitions.index') }}" class="btn btn-outline-secondary btn-sm">
                    {{ __('View All') }}
                </a>
            </div>
        </div>
        <div class="card-body p-0">
            @if($recent->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="ti ti-inbox" style="font-size:3rem;opacity:.4;"></i>
                    <p class="mt-2 mb-0">{{ __('No requisitions yet.') }}</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('Title') }}</th>
                                <th>{{ __('Department') }}</th>
                                <th>{{ __('Positions') }}</th>
                                <th>{{ __('Priority') }}</th>
                                <th>{{ __('Raised By') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Created') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recent as $r)
                                <tr>
                                    <td><a href="{{ route('recruitment.requisitions.show', $r->id) }}" class="fw-semibold text-decoration-none">{{ $r->title }}</a></td>
                                    <td>{{ $r->department->name ?? '—' }}</td>
                                    <td>{{ $r->positions }}</td>
                                    <td><span class="badge bg-light text-dark text-capitalize">{{ $r->priority }}</span></td>
                                    <td>{{ $r->raisedBy->name ?? '—' }}</td>
                                    <td><span class="badge bg-{{ $r->status_badge }} text-capitalize">{{ \App\Models\ManpowerRequisition::$statuses[$r->status] ?? $r->status }}</span></td>
                                    <td>{{ $r->created_at->diffForHumans() }}</td>
                                    <td><a href="{{ route('recruitment.requisitions.show', $r->id) }}" class="btn btn-sm btn-outline-primary"><i class="ti ti-eye"></i></a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
