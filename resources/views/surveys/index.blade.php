@extends('layouts.admin')
@section('page-title') {{ __('Surveys') }} @endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item">{{ __('Surveys') }}</li>
@endsection

@push('css-page')
<style>
    .sv-stat{border:1px solid var(--bs-border-color);border-radius:12px;padding:14px 16px;background:#fff;display:flex;align-items:center;gap:12px;}
    .sv-stat .sv-icon{width:40px;height:40px;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;color:#fff;font-size:18px;}
    .sv-stat .sv-num{font-size:1.45rem;font-weight:700;line-height:1;color:#0f172a;}
    .sv-stat .sv-lbl{font-size:.7rem;color:#64748b;text-transform:uppercase;letter-spacing:.4px;margin-top:3px;}
    .sv-stat.t-all   .sv-icon{background:linear-gradient(135deg,#6366f1,#8b5cf6);}
    .sv-stat.t-draft .sv-icon{background:linear-gradient(135deg,#94a3b8,#64748b);}
    .sv-stat.t-active.sv-icon, .sv-stat.t-active .sv-icon{background:linear-gradient(135deg,#10b981,#059669);}
    .sv-stat.t-closed .sv-icon{background:linear-gradient(135deg,#ef4444,#dc2626);}

    .sv-badge{font-size:.68rem;font-weight:700;padding:3px 10px;border-radius:20px;letter-spacing:.3px;text-transform:uppercase;}
    .sv-st-draft  {background:#f1f5f9;color:#475569;}
    .sv-st-active {background:#dcfce7;color:#166534;}
    .sv-st-closed {background:#fee2e2;color:#991b1b;}

    .sv-type{font-size:.7rem;font-weight:600;padding:2px 9px;border-radius:6px;}
    .sv-type-employee {background:#dbeafe;color:#1d4ed8;}
    .sv-type-pulse    {background:#fef3c7;color:#b45309;}
    .sv-type-enps     {background:#ede9fe;color:#6d28d9;}

    .sv-table th{font-size:.7rem;text-transform:uppercase;letter-spacing:.4px;color:#64748b;font-weight:600;background:#fafafa;}
    .sv-table td{vertical-align:middle;}
    .sv-empty{text-align:center;padding:48px 16px;color:#94a3b8;}
    .sv-empty i{font-size:3rem;opacity:.3;display:block;margin-bottom:10px;}

    .dash-card{border:1px solid var(--bs-border-color);border-radius:12px;background:#fff;padding:14px 16px;height:100%;}
    .dash-card .lbl{font-size:.7rem;color:#64748b;text-transform:uppercase;letter-spacing:.4px;font-weight:700;}
    .dash-card .val{font-size:1.65rem;font-weight:800;color:#0f172a;line-height:1.05;margin-top:8px;}
    .dash-card .sub{font-size:.78rem;color:#64748b;margin-top:6px;}
    .dash-card .ico{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:18px;}
    .dash-card .top{display:flex;align-items:center;justify-content:space-between;gap:10px;}
    .ico-sv{background:linear-gradient(135deg,#6366f1,#8b5cf6);}
    .ico-ac{background:linear-gradient(135deg,#10b981,#059669);}
    .ico-rs{background:linear-gradient(135deg,#0ea5e9,#22c55e);}
    .ico-avg{background:linear-gradient(135deg,#f59e0b,#ef4444);}
    .ico-en{background:linear-gradient(135deg,#8b5cf6,#ec4899);}
    .ico-neg{background:linear-gradient(135deg,#ef4444,#dc2626);}
    .ico-hr{background:linear-gradient(135deg,#f97316,#ef4444);}
</style>
@endpush

@section('content')
    @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(session('error'))<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(session('info'))<div class="alert alert-info alert-dismissible fade show">{{ session('info') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    {{-- ── Top counters: Active / Pending Received Response / Total Received Responses ── --}}
    <div class="row g-3 mb-3">
        <div class="col-md-4 col-6">
            <div class="sv-stat t-active">
                <span class="sv-icon"><i class="ti ti-circle-check"></i></span>
                <div>
                    <div class="sv-num">{{ $totals['active'] ?? 0 }}</div>
                    <div class="sv-lbl">{{ __('Active Surveys') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-6">
            <div class="sv-stat t-draft">
                <span class="sv-icon"><i class="ti ti-clock"></i></span>
                <div>
                    <div class="sv-num">{{ $dashboard['pending_responses'] ?? 0 }}</div>
                    <div class="sv-lbl">{{ __('Pending Response') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-12">
            <div class="sv-stat t-all">
                <span class="sv-icon"><i class="ti ti-message-circle-2"></i></span>
                <div>
                    <div class="sv-num">{{ $dashboard['total_responses'] ?? 0 }}</div>
                    <div class="sv-lbl">{{ __('Total Received Responses') }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Secondary counts: the rest of the original dashboard ──── --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-4 col-xl-3">
            <div class="dash-card">
                <div class="top">
                    <div>
                        <div class="lbl">{{ __('Total Surveys') }}</div>
                        <div class="val">{{ $dashboard['total_surveys'] ?? 0 }}</div>
                    </div>
                    <div class="ico ico-sv"><i class="ti ti-clipboard-list"></i></div>
                </div>
                <div class="sub">{{ __('Draft: :n · Closed: :c', ['n'=>$totals['draft'] ?? 0, 'c'=>$totals['closed'] ?? 0]) }}</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-3">
            <div class="dash-card">
                <div class="top">
                    <div>
                        <div class="lbl">{{ __('Avg Satisfaction') }}</div>
                        <div class="val">{{ number_format((float)($dashboard['avg_satisfaction'] ?? 0), 2) }}</div>
                    </div>
                    <div class="ico ico-avg"><i class="ti ti-mood-smile"></i></div>
                </div>
                <div class="sub">{{ __('Rating (1–5) questions') }}</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-3">
            <div class="dash-card">
                <div class="top">
                    <div>
                        <div class="lbl">{{ __('Negative Feedback') }}</div>
                        <div class="val">{{ $dashboard['negative_feedback_count'] ?? 0 }}</div>
                    </div>
                    <div class="ico ico-neg"><i class="ti ti-mood-sad"></i></div>
                </div>
                <div class="sub"><a href="{{ route('surveys.reports.sentiment') }}">{{ __('Sentiment analytics') }}</a></div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-3">
            <div class="dash-card">
                <div class="top">
                    <div>
                        <div class="lbl">{{ __('High Risk Alerts') }}</div>
                        <div class="val">{{ $dashboard['high_risk_alerts'] ?? 0 }}</div>
                    </div>
                    <div class="ico ico-hr"><i class="ti ti-alert-triangle"></i></div>
                </div>
                <div class="sub"><a href="{{ route('surveys.alerts') }}">{{ __('Open alerts') }}</a></div>
            </div>
        </div>
    </div>

    {{-- ── eNPS Score (single score card below) ──────────────────── --}}
    @php
        // Resolve the eNPS color in PHP so the inline style attribute stays
        // clean of Blade ternaries (keeps IDE CSS parsers happy).
        $en       = (float) ($dashboard['enps_score'] ?? 0);
        $enColor  = $en >= 30 ? '#16a34a' : ($en >= 0 ? '#0ea5e9' : '#dc2626');
        $enValStyle = 'color:' . $enColor . ';';
    @endphp
    <div class="row g-3 mb-3">
        <div class="col-md-6 col-lg-5">
            <div class="dash-card" style="border-left:4px solid #6366f1;">
                <div class="top">
                    <div>
                        <div class="lbl">{{ __('eNPS Score') }}</div>
                        <div class="val" style="{{ $enValStyle }}">
                            {{ $en > 0 ? '+' : '' }}{{ number_format($en, 1) }}
                            <small class="text-muted" style="font-size:.85rem;font-weight:600;"> / 100</small>
                        </div>
                    </div>
                    <div class="ico ico-en"><i class="ti ti-trending-up"></i></div>
                </div>
                <div class="sub">
                    <a href="{{ route('surveys.enps') }}">{{ __('Open full eNPS report') }} <i class="ti ti-arrow-right"></i></a>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="mb-1"><i class="ti ti-clipboard-list me-2"></i>{{ __('Surveys') }}</h5>
                <small class="text-muted">{{ __('Manage employee surveys, pulse surveys, and eNPS') }}</small>
            </div>
            <a href="{{ route('surveys.create') }}" class="btn btn-primary btn-sm">
                <i class="ti ti-plus me-1"></i>{{ __('Create Survey') }}
            </a>
        </div>

        {{-- ── Filters ──────────────────────────────────────────── --}}
        <div class="card-body border-bottom">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label mb-1 small">{{ __('Search') }}</label>
                    <input type="text" name="q" class="form-control form-control-sm" placeholder="{{ __('Search by title…') }}" value="{{ $filters['q'] ?? '' }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1 small">{{ __('Type') }}</label>
                    <select name="type" class="form-control form-control-sm">
                        <option value="">{{ __('All types') }}</option>
                        <option value="employee" {{ ($filters['type'] ?? '') === 'employee' ? 'selected' : '' }}>{{ __('Employee Survey') }}</option>
                        <option value="pulse"    {{ ($filters['type'] ?? '') === 'pulse'    ? 'selected' : '' }}>{{ __('Pulse Survey') }}</option>
                        <option value="enps"     {{ ($filters['type'] ?? '') === 'enps'     ? 'selected' : '' }}>{{ __('eNPS Survey') }}</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1 small">{{ __('Status') }}</label>
                    <select name="status" class="form-control form-control-sm">
                        <option value="">{{ __('All statuses') }}</option>
                        <option value="draft"  {{ ($filters['status'] ?? '') === 'draft'  ? 'selected' : '' }}>{{ __('Draft') }}</option>
                        <option value="active" {{ ($filters['status'] ?? '') === 'active' ? 'selected' : '' }}>{{ __('Active') }}</option>
                        <option value="closed" {{ ($filters['status'] ?? '') === 'closed' ? 'selected' : '' }}>{{ __('Closed') }}</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-1">
                    <button class="btn btn-primary btn-sm flex-grow-1"><i class="ti ti-filter me-1"></i>{{ __('Filter') }}</button>
                    @if(!empty($filters['q']) || !empty($filters['type']) || !empty($filters['status']))
                    <a href="{{ route('surveys.index') }}" class="btn btn-light btn-sm" title="{{ __('Clear') }}"><i class="ti ti-x"></i></a>
                    @endif
                </div>
            </form>
        </div>

        {{-- ── Table ────────────────────────────────────────────── --}}
        <div class="card-body p-0">
            @if($surveys->isEmpty())
                <div class="sv-empty">
                    <i class="ti ti-clipboard-off"></i>
                    <p class="mb-2">{{ __('No surveys yet.') }}</p>
                    <a href="{{ route('surveys.create') }}" class="btn btn-primary btn-sm">
                        <i class="ti ti-plus me-1"></i>{{ __('Create your first survey') }}
                    </a>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table sv-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">{{ __('Title') }}</th>
                                <th>{{ __('Type') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th class="text-center">{{ __('Questions') }}</th>
                                <th class="text-center">{{ __('Responses') }}</th>
                                <th>{{ __('Window') }}</th>
                                <th class="text-center">{{ __('Anon') }}</th>
                                <th class="pe-3 text-end">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($surveys as $s)
                            <tr>
                                <td class="ps-3">
                                    <strong>{{ $s->title }}</strong>
                                    @if($s->description)
                                        <div class="text-muted small">{{ \Illuminate\Support\Str::limit($s->description, 80) }}</div>
                                    @endif
                                </td>
                                <td>
                                    <span class="sv-type sv-type-{{ $s->type }}">
                                        {{ ['employee'=>__('Employee'),'pulse'=>__('Pulse'),'enps'=>__('eNPS')][$s->type] ?? $s->type }}
                                    </span>
                                </td>
                                <td><span class="sv-badge sv-st-{{ $s->status }}">{{ ucfirst($s->status) }}</span></td>
                                <td class="text-center"><strong>{{ $s->questions_count ?? 0 }}</strong></td>
                                <td class="text-center"><strong>{{ $s->responses_count ?? 0 }}</strong></td>
                                <td>
                                    @if($s->start_date || $s->end_date)
                                        <small class="text-muted">
                                            {{ $s->start_date ? $s->start_date->format('d M') : '—' }}
                                            <span class="mx-1">→</span>
                                            {{ $s->end_date   ? $s->end_date->format('d M Y')   : '—' }}
                                        </small>
                                    @else
                                        <small class="text-muted">—</small>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($s->is_anonymous)
                                        <i class="ti ti-eye-off text-warning" title="{{ __('Anonymous') }}"></i>
                                    @else
                                        <i class="ti ti-eye text-muted" title="{{ __('Identified') }}"></i>
                                    @endif
                                </td>
                                <td class="pe-3 text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('surveys.questions', $s->id) }}" class="btn btn-light border" title="{{ __('Questions') }}">
                                            <i class="ti ti-list-check"></i>
                                        </a>
                                        @if(Auth::user() && Auth::user()->can('view-survey-analytics'))
                                            <a href="{{ route('surveys.analytics', $s->id) }}" class="btn btn-light border" title="{{ __('Analytics') }}">
                                                <i class="ti ti-chart-bar"></i>
                                            </a>
                                        @endif
                                        @if(Auth::user() && Auth::user()->can('export-surveys'))
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-light border dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="{{ __('Export') }}">
                                                    <i class="ti ti-download"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li><a class="dropdown-item" href="{{ route('surveys.export', $s->id) }}">{{ __('Export CSV') }}</a></li>
                                                    <li><a class="dropdown-item" href="{{ route('surveys.export.pdf', $s->id) }}">{{ __('Export PDF') }}</a></li>
                                                </ul>
                                            </div>
                                        @endif
                                        <a href="{{ route('surveys.edit', $s->id) }}" class="btn btn-light border" title="{{ __('Edit') }}">
                                            <i class="ti ti-pencil"></i>
                                        </a>
                                        @if($s->status === 'draft')
                                            <form method="POST" action="{{ route('surveys.activate', $s->id) }}" class="d-inline" onsubmit="return confirm('{{ __('Activate this survey?') }}')">
                                                @csrf
                                                <button class="btn btn-light border text-success" title="{{ __('Activate') }}"><i class="ti ti-rocket"></i></button>
                                            </form>
                                        @elseif($s->status === 'active')
                                            <form method="POST" action="{{ route('surveys.close', $s->id) }}" class="d-inline" onsubmit="return confirm('{{ __('Close this survey? No further responses will be accepted.') }}')">
                                                @csrf
                                                <button class="btn btn-light border text-danger" title="{{ __('Close') }}"><i class="ti ti-lock"></i></button>
                                            </form>
                                        @endif
                                        <form method="POST" action="{{ route('surveys.destroy', $s->id) }}" class="d-inline" onsubmit="return confirm('{{ __('Delete this survey? This cannot be undone.') }}')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-light border text-danger" title="{{ __('Delete') }}"><i class="ti ti-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-between align-items-center px-3 py-2">
                    <small class="text-muted">{{ __('Showing :from to :to of :total', ['from' => $surveys->firstItem(), 'to' => $surveys->lastItem(), 'total' => $surveys->total()]) }}</small>
                    {{ $surveys->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
