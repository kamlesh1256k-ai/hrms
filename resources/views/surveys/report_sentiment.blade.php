@extends('layouts.admin')
@section('page-title') {{ __('Sentiment Analytics') }} @endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('surveys.index') }}">{{ __('Surveys') }}</a></li>
    <li class="breadcrumb-item">{{ __('Sentiment') }}</li>
@endsection

@push('css-page')
<style>
    .kpi{border:1px solid var(--bs-border-color);border-radius:12px;background:#fff;padding:14px 16px;height:100%;}
    .kpi .lbl{font-size:.7rem;color:#64748b;text-transform:uppercase;letter-spacing:.4px;font-weight:700;}
    .kpi .val{font-size:1.6rem;font-weight:800;color:#0f172a;line-height:1.05;margin-top:8px;}
    .pill{font-weight:700;padding:3px 10px;border-radius:20px;font-size:.72rem;}
    .p-pos{background:#dcfce7;color:#166534;}
    .p-neu{background:#e2e8f0;color:#334155;}
    .p-neg{background:#fee2e2;color:#991b1b;}
    .p-low{background:#dbeafe;color:#1d4ed8;}
    .p-med{background:#fef3c7;color:#b45309;}
    .p-high{background:#fee2e2;color:#991b1b;}
    .r-table th{font-size:.7rem;text-transform:uppercase;letter-spacing:.4px;color:#64748b;font-weight:600;background:#fafafa;}
</style>
@endpush

@section('content')
    <div class="card mb-3">
        <div class="card-body py-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1">{{ __('From') }}</label>
                    <input type="date" name="from" value="{{ $from }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1">{{ __('To') }}</label>
                    <input type="date" name="to" value="{{ $to }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button class="btn btn-primary btn-sm flex-grow-1"><i class="ti ti-filter me-1"></i>{{ __('Apply') }}</button>
                    <a href="{{ route('surveys.reports.sentiment') }}" class="btn btn-light btn-sm border" title="{{ __('Clear') }}"><i class="ti ti-x"></i></a>
                </div>
                <div class="col-md-3 text-md-end">
                    <a href="{{ route('surveys.alerts') }}" class="btn btn-light btn-sm border">
                        <i class="ti ti-bell-ringing me-1"></i>{{ __('Alerts') }}
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Re-analyze panel --}}
    <div class="alert alert-info d-flex justify-content-between align-items-center flex-wrap gap-2 py-2">
        <div>
            <i class="ti ti-cpu me-1"></i>
            <strong>{{ __('AI Re-analysis') }}:</strong>
            {{ __('Re-run sentiment analysis on existing text answers (idempotent — overwrites previous results).') }}
        </div>
        <div class="d-flex gap-1">
            <form method="POST" action="{{ route('surveys.sentiment.reanalyze') }}" class="d-inline" onsubmit="return confirm('{{ __('Re-analyze answers that don\'t have a sentiment row yet?') }}');">
                @csrf
                <input type="hidden" name="mode" value="missing">
                <button class="btn btn-sm btn-primary" title="{{ __('Only answers without sentiment yet') }}">
                    <i class="ti ti-refresh me-1"></i>{{ __('Analyze missing') }}
                </button>
            </form>
            <form method="POST" action="{{ route('surveys.sentiment.reanalyze') }}" class="d-inline" onsubmit="return confirm('{{ __('Re-analyze ALL text answers? This will overwrite existing sentiment rows.') }}');">
                @csrf
                <input type="hidden" name="mode" value="all">
                <button class="btn btn-sm btn-light border" title="{{ __('Re-run on all answers (overwrites)') }}">
                    <i class="ti ti-refresh-dot me-1"></i>{{ __('Re-analyze all') }}
                </button>
            </form>
        </div>
    </div>

    @php
        $pos = (int) data_get($bySentiment, 'positive.total', 0);
        $neu = (int) data_get($bySentiment, 'neutral.total', 0);
        $neg = (int) data_get($bySentiment, 'negative.total', 0);
        $low = (int) data_get($byRisk, 'low.total', 0);
        $med = (int) data_get($byRisk, 'medium.total', 0);
        $high= (int) data_get($byRisk, 'high.total', 0);
    @endphp

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="kpi">
                <div class="lbl">{{ __('Positive') }}</div>
                <div class="val">{{ $pos }}</div>
                <div class="mt-2"><span class="pill p-pos">{{ __('positive') }}</span></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="kpi">
                <div class="lbl">{{ __('Neutral') }}</div>
                <div class="val">{{ $neu }}</div>
                <div class="mt-2"><span class="pill p-neu">{{ __('neutral') }}</span></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="kpi">
                <div class="lbl">{{ __('Negative') }}</div>
                <div class="val">{{ $neg }}</div>
                <div class="mt-2"><span class="pill p-neg">{{ __('negative') }}</span></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="kpi">
                <div class="lbl">{{ __('Low Risk') }}</div>
                <div class="val">{{ $low }}</div>
                <div class="mt-2"><span class="pill p-low">{{ __('low') }}</span></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="kpi">
                <div class="lbl">{{ __('Medium Risk') }}</div>
                <div class="val">{{ $med }}</div>
                <div class="mt-2"><span class="pill p-med">{{ __('medium') }}</span></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="kpi">
                <div class="lbl">{{ __('High Risk') }}</div>
                <div class="val">{{ $high }}</div>
                <div class="mt-2"><span class="pill p-high">{{ __('high') }}</span></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="mb-0"><i class="ti ti-shield-exclamation me-2"></i>{{ __('Recent High-Risk Feedback') }}</h5>
                <small class="text-muted">{{ __('Shows text answers with sentiment analysis risk_level=high.') }}</small>
            </div>
            <a href="{{ route('surveys.index') }}" class="btn btn-light btn-sm border"><i class="ti ti-arrow-left me-1"></i>{{ __('Back') }}</a>
        </div>
        <div class="card-body p-0">
            @if($recentHighRisk->isEmpty())
                <div class="text-center py-5 text-muted">{{ __('No high-risk sentiment records found.') }}</div>
            @else
                <div class="table-responsive">
                    <table class="table r-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">{{ __('Survey') }}</th>
                                <th>{{ __('Sentiment') }}</th>
                                <th>{{ __('Emotion') }}</th>
                                <th>{{ __('Summary') }}</th>
                                <th class="pe-3 text-end">{{ __('Submitted') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentHighRisk as $r)
                                @php
                                    $sentPill = $r->sentiment === 'positive' ? 'p-pos' : ($r->sentiment === 'negative' ? 'p-neg' : 'p-neu');
                                @endphp
                                <tr>
                                    <td class="ps-3">
                                        <div class="fw-semibold">{{ $r->survey_title }}</div>
                                        @if(!empty($r->topic))
                                            <small class="text-muted">{{ __('Topics') }}: {{ is_array($r->topic) ? implode(', ', $r->topic) : (string)$r->topic }}</small>
                                        @endif
                                    </td>
                                    <td><span class="pill {{ $sentPill }}">{{ $r->sentiment }}</span></td>
                                    <td><span class="pill p-neu">{{ $r->emotion }}</span></td>
                                    <td>
                                        @if(!empty($r->ai_summary))
                                            <div class="fw-semibold">{{ \Illuminate\Support\Str::limit((string)$r->ai_summary, 140) }}</div>
                                        @endif
                                        @if(!empty($r->text_value))
                                            <small class="text-muted">{{ \Illuminate\Support\Str::limit((string)$r->text_value, 160) }}</small>
                                        @endif
                                    </td>
                                    <td class="pe-3 text-end"><small class="text-muted">{{ $r->submitted_at ? \Carbon\Carbon::parse($r->submitted_at)->format('d M Y, h:i A') : '—' }}</small></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
