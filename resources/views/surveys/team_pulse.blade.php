@extends('layouts.admin')
@section('page-title') {{ __('Team Pulse') }} @endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item">{{ __('Team Pulse') }}</li>
@endsection

@push('css-page')
<style>
    .pulse-hero{background:linear-gradient(135deg,#6366f1 0%,#0ea5e9 100%);border-radius:14px;padding:22px 24px;color:#fff;box-shadow:0 8px 24px -10px rgba(99,102,241,.45);position:relative;overflow:hidden;}
    .pulse-hero::before{content:"";position:absolute;right:-30px;top:-30px;width:160px;height:160px;border-radius:50%;background:rgba(255,255,255,.08);}
    .pulse-hero h3{margin:0;font-weight:700;font-size:1.05rem;letter-spacing:.3px;}
    .pulse-hero .score{font-size:3rem;font-weight:800;line-height:1;margin:6px 0 4px;letter-spacing:-1px;}
    .pulse-hero .scale{font-size:.78rem;opacity:.9;}

    .kpi-card{border:1px solid #e2e8f0;border-radius:12px;padding:14px 16px;background:#fff;height:100%;}
    .kpi-card .lbl{font-size:.7rem;color:#64748b;text-transform:uppercase;letter-spacing:.4px;font-weight:600;}
    .kpi-card .val{font-size:1.7rem;font-weight:800;color:#0f172a;line-height:1;margin-top:6px;}
    .kpi-card.t-resp{border-left:4px solid #6366f1;}
    .kpi-card.t-avg {border-left:4px solid #10b981;}
    .kpi-card.t-low {border-left:4px solid #ef4444;}
    .kpi-card.t-team{border-left:4px solid #0ea5e9;}

    .q-row{display:flex;align-items:center;gap:12px;padding:12px 14px;border:1px solid #e2e8f0;border-radius:10px;background:#fff;margin-bottom:8px;}
    .q-row.is-low{border-left:4px solid #ef4444;background:linear-gradient(135deg,#fef2f2,#fff 60%);}
    .q-text{flex:1;font-weight:600;color:#0f172a;font-size:.92rem;}
    .q-meta{font-size:.72rem;color:#64748b;margin-top:2px;}
    .q-bar-wrap{flex-basis:42%;height:8px;background:#f1f5f9;border-radius:999px;overflow:hidden;flex-shrink:0;}
    .q-bar-fill{height:100%;border-radius:999px;background:linear-gradient(90deg,#10b981,#059669);}
    .q-bar-fill.low{background:linear-gradient(90deg,#f87171,#ef4444);}
    .q-bar-fill.mid{background:linear-gradient(90deg,#fbbf24,#f59e0b);}
    .q-score{font-weight:700;font-size:1rem;color:#0f172a;min-width:48px;text-align:right;}
    .q-score.low{color:#dc2626;}

    .empty-block{text-align:center;padding:48px 16px;color:#94a3b8;}
    .empty-block i{font-size:3rem;opacity:.3;display:block;margin-bottom:10px;}
</style>
@endpush

@section('content')
    <div class="row g-3 mb-3">
        <div class="col-lg-5">
            <div class="pulse-hero h-100">
                <h3>{{ __('Team Pulse') }}</h3>
                <div class="score">{{ number_format($summary['avg_score'] ?? 0, 2) }}</div>
                <div class="scale">{{ __('Average score (1–5) · Direct reports only') }}</div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="row g-3">
                <div class="col-sm-6 col-lg-3">
                    <div class="kpi-card t-team">
                        <div class="lbl">{{ __('Team Size') }}</div>
                        <div class="val">{{ (int)($teamSize ?? 0) }}</div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="kpi-card t-resp">
                        <div class="lbl">{{ __('Responses') }}</div>
                        <div class="val">{{ (int)($summary['total_responses'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="kpi-card t-avg">
                        <div class="lbl">{{ __('Avg Score') }}</div>
                        <div class="val">{{ number_format($summary['avg_score'] ?? 0, 2) }}</div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="kpi-card t-low">
                        <div class="lbl">{{ __('Low Questions') }}</div>
                        <div class="val">{{ (int)($summary['low_questions'] ?? 0) }}</div>
                    </div>
                </div>
            </div>

            @if(!empty($tooFewResponses))
                <div class="alert alert-warning mt-3 mb-0">
                    <strong>{{ __('Limited view') }}:</strong>
                    {{ __('Fewer than 3 responses are available. To protect anonymity, detailed breakdowns are hidden until more responses are collected.') }}
                </div>
            @endif
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header"><h6 class="mb-0"><i class="ti ti-list-check me-1"></i>{{ __('Question Breakdown') }}</h6></div>
                <div class="card-body">
                    @if(!empty($tooFewResponses))
                        <div class="empty-block">
                            <i class="ti ti-shield-lock"></i>
                            <p class="mb-0">{{ __('More responses needed for breakdowns.') }}</p>
                        </div>
                    @elseif(empty($byQ))
                        <div class="empty-block">
                            <i class="ti ti-mood-empty"></i>
                            <p class="mb-0">{{ __('No data yet.') }}</p>
                        </div>
                    @else
                        @foreach($byQ as $row)
                            @php
                                $pct = isset($row['avg']) ? max(0, min(100, ($row['avg'] / 5) * 100)) : 0;
                                $cls = $row['avg'] < 3 ? 'low' : ($row['avg'] < 4 ? 'mid' : '');
                            @endphp
                            <div class="q-row {{ !empty($row['low']) ? 'is-low' : '' }}">
                                <div class="q-text">
                                    {{ $row['question_text'] }}
                                    <div class="q-meta">{{ __(':n responses', ['n' => (int)($row['total'] ?? 0)]) }}</div>
                                </div>
                                <div class="q-bar-wrap">
                                    <div class="q-bar-fill {{ $cls }}" style="width:{{ $pct }}%"></div>
                                </div>
                                <div class="q-score {{ !empty($row['low']) ? 'low' : '' }}">{{ number_format($row['avg'] ?? 0, 2) }}</div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="ti ti-chart-line me-1"></i>{{ __('Trend') }}</h6>
                    <small class="text-muted">{{ __('Last :n weeks', ['n' => (int)($weeks ?? 12)]) }}</small>
                </div>
                <div class="card-body">
                    @if(!empty($tooFewResponses))
                        <div class="empty-block">
                            <i class="ti ti-shield-lock"></i>
                            <p class="mb-0">{{ __('More responses needed for trends.') }}</p>
                        </div>
                    @elseif(empty($trend['questions']))
                        <div class="empty-block">
                            <i class="ti ti-chart-line"></i>
                            <p class="mb-0">{{ __('No data to plot yet.') }}</p>
                        </div>
                    @else
                        <div id="team-pulse-trend" style="min-height:340px;"></div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script-page')
<script>
(function(){
    if (typeof ApexCharts === 'undefined') return;
    const trend = @json($trend ?? ['weeks'=>[],'questions'=>[]]);
    if (!trend.questions || !trend.questions.length) return;
    const el = document.querySelector('#team-pulse-trend');
    if (!el) return;

    const series = trend.questions.map(q => ({
        name: (q.question_text || '').length > 42 ? (q.question_text || '').slice(0, 42) + '…' : (q.question_text || ''),
        data: q.series || [],
    }));

    new ApexCharts(el, {
        chart: { type: 'line', height: 340, toolbar: { show: false }, fontFamily: 'inherit', zoom: { enabled: false } },
        series: series,
        stroke: { width: 2.5, curve: 'smooth' },
        colors: ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#0ea5e9', '#8b5cf6', '#14b8a6'],
        markers: { size: 4, hover: { size: 6 } },
        xaxis: { categories: trend.weeks || [], labels: { style: { fontSize: '11px' }, rotate: -30 } },
        yaxis: { min: 0, max: 5, tickAmount: 5, labels: { formatter: v => Number(v).toFixed(0) } },
        grid: { borderColor: '#e2e8f0', strokeDashArray: 4 },
        legend: { position: 'bottom' },
        tooltip: { y: { formatter: v => Number(v).toFixed(2) } },
    }).render();
})();
</script>
@endpush

