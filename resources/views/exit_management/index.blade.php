@extends('layouts.admin')
@section('page-title') {{ __('Exit Management') }} @endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item">{{ __('Exit Management') }}</li>
@endsection

@push('css-page')
<style>
    .ex-stat{border:1px solid var(--bs-border-color);border-radius:12px;background:#fff;padding:14px 16px;display:flex;align-items:center;gap:12px;}
    .ex-stat .ex-icon{width:40px;height:40px;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;color:#fff;font-size:18px;}
    .ex-stat .ex-num{font-size:1.45rem;font-weight:700;line-height:1;color:#0f172a;}
    .ex-stat .ex-lbl{font-size:.7rem;color:#64748b;text-transform:uppercase;letter-spacing:.4px;margin-top:3px;}
    .ex-stat.t-all      .ex-icon{background:linear-gradient(135deg,#6366f1,#8b5cf6);}
    .ex-stat.t-pending  .ex-icon{background:linear-gradient(135deg,#f59e0b,#d97706);}
    .ex-stat.t-approved .ex-icon{background:linear-gradient(135deg,#10b981,#059669);}
    .ex-stat.t-closed   .ex-icon{background:linear-gradient(135deg,#94a3b8,#64748b);}

    .ex-table th{font-size:.7rem;text-transform:uppercase;letter-spacing:.4px;color:#64748b;font-weight:600;background:#fafafa;}
    .ex-table td{vertical-align:middle;}

    .ex-status-pill{font-size:.65rem;font-weight:700;padding:3px 10px;border-radius:20px;text-transform:uppercase;letter-spacing:.3px;}
    .badge-pending  {background:#fef3c7;color:#b45309;}
    .badge-mgr-ok   {background:#dbeafe;color:#1d4ed8;}
    .badge-hr-ok    {background:#dcfce7;color:#166534;}
    .badge-rejected {background:#fee2e2;color:#991b1b;}
    .badge-done     {background:#e0e7ff;color:#3730a3;}

    .ex-empty{text-align:center;padding:48px 16px;color:#94a3b8;}
    .ex-empty i{font-size:3rem;opacity:.3;display:block;margin-bottom:10px;}
</style>
@endpush

@section('content')
    @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(session('info'))<div class="alert alert-info alert-dismissible fade show">{{ session('info') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(session('error'))<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    <div class="row g-3 mb-3">
        <div class="col-md-3 col-6">
            <div class="ex-stat t-all">
                <span class="ex-icon"><i class="ti ti-logout"></i></span>
                <div><div class="ex-num">{{ $totals['all'] }}</div><div class="ex-lbl">{{ __('Total') }}</div></div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="ex-stat t-pending">
                <span class="ex-icon"><i class="ti ti-clock"></i></span>
                <div><div class="ex-num">{{ $totals['pending'] }}</div><div class="ex-lbl">{{ __('Pending Approval') }}</div></div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="ex-stat t-approved">
                <span class="ex-icon"><i class="ti ti-check"></i></span>
                <div><div class="ex-num">{{ $totals['approved'] }}</div><div class="ex-lbl">{{ __('Exit in Progress') }}</div></div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="ex-stat t-closed">
                <span class="ex-icon"><i class="ti ti-archive"></i></span>
                <div><div class="ex-num">{{ $totals['closed'] }}</div><div class="ex-lbl">{{ __('Closed') }}</div></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="mb-1"><i class="ti ti-logout me-2"></i>{{ __('Resignations & Exits') }}</h5>
                <small class="text-muted">{{ __('Resignation workflow, exit checklist & FNF settlement') }}</small>
            </div>
            @if(!$myActive)
                <a href="{{ route('exit-management.create') }}" class="btn btn-primary btn-sm">
                    <i class="ti ti-plus me-1"></i>{{ __('Apply Resignation') }}
                </a>
            @else
                <a href="{{ route('exit-management.show', $myActive->id) }}" class="btn btn-warning btn-sm">
                    <i class="ti ti-eye me-1"></i>{{ __('My Active Resignation') }}
                </a>
            @endif
        </div>

        <div class="card-body border-bottom">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label mb-1 small">{{ __('Search by name / email') }}</label>
                    <input type="text" name="q" class="form-control form-control-sm" value="{{ $filters['q'] }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label mb-1 small">{{ __('Status') }}</label>
                    <select name="status" class="form-control form-control-sm">
                        <option value="all"               {{ $filters['status'] === 'all'              ? 'selected' : '' }}>{{ __('All') }}</option>
                        <option value="pending"           {{ $filters['status'] === 'pending'          ? 'selected' : '' }}>{{ __('Pending Manager') }}</option>
                        <option value="manager_approved"  {{ $filters['status'] === 'manager_approved' ? 'selected' : '' }}>{{ __('Pending HR') }}</option>
                        <option value="hr_approved"       {{ $filters['status'] === 'hr_approved'      ? 'selected' : '' }}>{{ __('Exit in Progress') }}</option>
                        <option value="completed"         {{ $filters['status'] === 'completed'        ? 'selected' : '' }}>{{ __('Completed') }}</option>
                        <option value="manager_rejected"  {{ $filters['status'] === 'manager_rejected' ? 'selected' : '' }}>{{ __('Rejected by Manager') }}</option>
                        <option value="hr_rejected"       {{ $filters['status'] === 'hr_rejected'      ? 'selected' : '' }}>{{ __('Rejected by HR') }}</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-1">
                    <button class="btn btn-primary btn-sm flex-grow-1"><i class="ti ti-filter me-1"></i>{{ __('Filter') }}</button>
                    <a href="{{ route('exit-management.index') }}" class="btn btn-light btn-sm border" title="{{ __('Clear') }}"><i class="ti ti-x"></i></a>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            @if($items->isEmpty())
                <div class="ex-empty">
                    <i class="ti ti-logout"></i>
                    <p class="mb-2">{{ __('No resignations found.') }}</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table ex-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">{{ __('Employee') }}</th>
                                <th>{{ __('Resigned On') }}</th>
                                <th>{{ __('Last Working Day') }}</th>
                                <th>{{ __('Notice') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('FNF') }}</th>
                                <th class="pe-3 text-end">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($items as $r)
                            <tr>
                                <td class="ps-3">
                                    <a href="{{ route('exit-management.show', $r->id) }}" class="text-decoration-none">
                                        <strong>{{ optional($r->user)->name ?? '—' }}</strong>
                                    </a>
                                    <div class="small text-muted">{{ optional($r->user)->email }}</div>
                                </td>
                                <td><small>{{ optional($r->resignation_date)->format('d M Y') }}</small></td>
                                <td><small>{{ optional($r->last_working_day)->format('d M Y') }}</small></td>
                                <td><small class="text-muted">{{ $r->notice_period_days }} {{ __('days') }}</small></td>
                                <td><span class="ex-status-pill {{ $r->statusBadgeClass() }}">{{ $r->statusLabel() }}</span></td>
                                <td>
                                    @if($r->fnf)
                                        <small><strong>₹{{ number_format($r->fnf->final_amount, 2) }}</strong>
                                        <span class="text-muted d-block">{{ ucfirst($r->fnf->status) }}</span></small>
                                    @else
                                        <small class="text-muted">—</small>
                                    @endif
                                </td>
                                <td class="pe-3 text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('exit-management.show', $r->id) }}" class="btn btn-light border" title="{{ __('View') }}">
                                            <i class="ti ti-eye"></i>
                                        </a>
                                        @if($isHr)
                                            <form method="POST" action="{{ route('exit-management.destroy', $r->id) }}" class="d-inline" onsubmit="return confirm('{{ __('Delete this resignation? FNF & checklist will also be removed.') }}');">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-light border text-danger" title="{{ __('Delete') }}"><i class="ti ti-trash"></i></button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center px-3 py-2">
                    <small class="text-muted">{{ __('Showing :from to :to of :total', ['from' => $items->firstItem(), 'to' => $items->lastItem(), 'total' => $items->total()]) }}</small>
                    {{ $items->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
