@extends('layouts.admin')
@section('page-title') {{ __('Pulse Survey Trends') }} @endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('surveys.index') }}">{{ __('Surveys') }}</a></li>
    <li class="breadcrumb-item">{{ __('Pulse Trends') }}</li>
@endsection

@push('css-page')
<style>
    .pulse-hero{background:linear-gradient(135deg,#f59e0b 0%,#ef4444 100%);border-radius:14px;padding:22px 24px;color:#fff;box-shadow:0 8px 24px -10px rgba(239,68,68,.4);position:relative;overflow:hidden;}
    .pulse-hero::before{content:"";position:absolute;right:-30px;top:-30px;width:160px;height:160px;border-radius:50%;background:rgba(255,255,255,.08);}
    .pulse-hero h3{margin:0;font-weight:700;font-size:1.05rem;letter-spacing:.3px;}
    .pulse-hero .score{font-size:3rem;font-weight:800;line-height:1;margin:6px 0 4px;letter-spacing:-1px;}
    .pulse-hero .scale{font-size:.78rem;opacity:.85;}

    .kpi-card{border:1px solid #e2e8f0;border-radius:12px;padding:14px 16px;background:#fff;height:100%;}
    .kpi-card .lbl{font-size:.7rem;color:#64748b;text-transform:uppercase;letter-spacing:.4px;font-weight:600;}
    .kpi-card .val{font-size:1.7rem;font-weight:800;color:#0f172a;line-height:1;margin-top:6px;}
    .kpi-card .pct{font-size:.78rem;color:#64748b;margin-top:4px;}
    .kpi-card.t-resp{border-left:4px solid #6366f1;}
    .kpi-card.t-avg {border-left:4px solid #10b981;}
    .kpi-card.t-low {border-left:4px solid #ef4444;}
    .kpi-card.t-q   {border-left:4px solid #0ea5e9;}

    .q-row{display:flex;align-items:center;gap:12px;padding:12px 14px;border:1px solid #e2e8f0;border-radius:10px;background:#fff;margin-bottom:8px;transition:border-color .12s,box-shadow .12s;}
    .q-row.is-low{border-left:4px solid #ef4444;background:linear-gradient(135deg,#fef2f2,#fff 60%);}
    .q-text{flex:1;font-weight:600;color:#0f172a;font-size:.92rem;}
    .q-meta{font-size:.72rem;color:#64748b;margin-top:2px;}
    .q-bar-wrap{flex-basis:42%;height:8px;background:#f1f5f9;border-radius:999px;overflow:hidden;flex-shrink:0;}
    .q-bar-fill{height:100%;border-radius:999px;background:linear-gradient(90deg,#10b981,#059669);}
    .q-bar-fill.low{background:linear-gradient(90deg,#f87171,#ef4444);}
    .q-bar-fill.mid{background:linear-gradient(90deg,#fbbf24,#f59e0b);}
    .q-score{font-weight:700;font-size:1rem;color:#0f172a;min-width:48px;text-align:right;}
    .q-score.low{color:#dc2626;}

    .team-table th{font-size:.7rem;text-transform:uppercase;letter-spacing:.4px;color:#64748b;font-weight:600;background:#fafafa;}
    .team-table td{vertical-align:middle;}
    .team-pill{font-weight:700;padding:3px 10px;border-radius:20px;font-size:.78rem;}
    .pill-good {background:#dcfce7;color:#166534;}
    .pill-watch{background:#fef3c7;color:#b45309;}
    .pill-poor {background:#fee2e2;color:#991b1b;}

    .empty-block{text-align:center;padding:48px 16px;color:#94a3b8;}
    .empty-block i{font-size:3rem;opacity:.3;display:block;margin-bottom:10px;}
</style>
@endpush

@section('content')

    {{-- ── Filters ────────────────────────────────────────────── --}}
    <div class="card mb-3">
        <div class="card-body py-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small fw-semibold mb-1">{{ __('Pulse survey') }}</label>
                    <select name="survey_id" class="form-control form-control-sm" onchange="this.form.submit()">
                        <option value="">{{ __('All pulse surveys') }}</option>
                        @foreach($surveys as $sv)
                            <option value="{{ $sv->id }}" {{ $selectedSurvey == $sv->id ? 'selected' : '' }}>
                                {{ $sv->title }} <small>· {{ ucfirst($sv->status) }} · {{ ucfirst($sv->frequency) }}</small>
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1">{{ __('Trend window') }}</label>
                    <select name="weeks" class="form-control form-control-sm" onchange="this.form.submit()">
                        @foreach([4,8,12,16,26] as $w)
                            <option value="{{ $w }}" {{ $weeks == $w ? 'selected' : '' }}>{{ __('Last :n weeks', ['n' => $w]) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 text-end">
                    <a href="{{ route('surveys.index') }}" class="btn btn-light btn-sm border">
                        <i class="ti ti-arrow-left me-1"></i>{{ __('Back to Surveys') }}
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- ── Hero + KPIs ────────────────────────────────────────── --}}
    <div class="row g-3 mb-3">
        <div class="col-lg-4">
            <div class="pulse-hero">
                <h3><i class="ti ti-activity-heartbeat me-1"></i>{{ __('Pulse Score') }}</h3>
                <div class="score">{{ number_format($summary['avg_score'], 2) }}<small class="ms-1" style="font-size:1rem;font-weight:600;opacity:.85;">/ 5</small></div>
                <div class="scale">{{ __('Average across all rating responses') }}</div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="row g-3">
                <div class="col-md-4 col-6">
                    <div class="kpi-card t-resp">
                        <div class="lbl">{{ __('Total Responses') }}</div>
                        <div class="val">{{ $summary['total_responses'] }}</div>
                        <div class="pct">{{ __('Rating answers in scope') }}</div>
                    </div>
                </div>
                <div class="col-md-4 col-6">
                    <div class="kpi-card t-q">
                        <div class="lbl">{{ __('Questions Tracked') }}</div>
                        <div class="val">{{ $summary['questions_count'] }}</div>
                        <div class="pct">{{ __('With at least 1 response') }}</div>
                    </div>
                </div>
                <div class="col-md-4 col-6">
                    <div class="kpi-card t-low">
                        <div class="lbl"><i class="ti ti-alert-triangle text-danger me-1"></i>{{ __('Low-score Questions') }}</div>
                        <div class="val text-danger">{{ $summary['low_questions'] }}</div>
                        <div class="pct">{{ __('Avg below') }} {{ number_format($lowThreshold, 1) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Per-question breakdown ─────────────────────────────── --}}
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="ti ti-list-check me-1"></i>{{ __('Per-question Average') }}</h6>
            <small class="text-muted">{{ __('Worst scoring shown first') }}</small>
        </div>
        <div class="card-body">
            @if(empty($byQ))
                <div class="empty-block">
                    <i class="ti ti-clipboard-off"></i>
                    <p class="mb-0">{{ __('No pulse responses yet.') }}</p>
                </div>
            @else
                @foreach($byQ as $row)
                    @php
                        $pct = max(0, min(100, ($row['avg'] / 5.0) * 100));
                        $klass = $row['avg'] < $lowThreshold ? 'low' : ($row['avg'] < 4 ? 'mid' : '');
                    @endphp
                    <div class="q-row {{ $row['low'] ? 'is-low' : '' }}">
                        <div style="flex:1;min-width:0;">
                            <div class="q-text">{{ $row['question_text'] }}</div>
                            <div class="q-meta">{{ $row['total'] }} {{ __('responses') }}{{ $row['low'] ? ' · ' . __('Below threshold') : '' }}</div>
                        </div>
                        <div class="q-bar-wrap"><div class="q-bar-fill {{ $klass }}" style="width:{{ $pct }}%;"></div></div>
                        <div class="q-score {{ $klass === 'low' ? 'low' : '' }}">{{ number_format($row['avg'], 2) }}</div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>

    <div class="row g-3">
        {{-- ── Team-wise breakdown ────────────────────────────── --}}
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header"><h6 class="mb-0"><i class="ti ti-building me-1"></i>{{ __('Team-wise Pulse') }}</h6></div>
                <div class="card-body p-0">
                    @if(empty($byTeam))
                        <div class="empty-block">
                            <i class="ti ti-users"></i>
                            <p class="mb-0">{{ __('No team data yet.') }}</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table team-table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-3">{{ __('Department') }}</th>
                                        <th class="text-center">{{ __('Responses') }}</th>
                                        <th class="pe-3 text-end">{{ __('Avg') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($byTeam as $t)
                                        @php
                                            $pill = $t['avg'] >= 4 ? 'pill-good' : ($t['avg'] >= 3 ? 'pill-watch' : 'pill-poor');
                                        @endphp
                                        <tr>
                                            <td class="ps-3"><strong>{{ $t['department_name'] }}</strong></td>
                                            <td class="text-center">{{ $t['total'] }}</td>
                                            <td class="pe-3 text-end">
                                                <span class="team-pill {{ $pill }}">{{ number_format($t['avg'], 2) }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ── Weekly trend chart ─────────────────────────────── --}}
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="ti ti-chart-line me-1"></i>{{ __('Week-by-week Trend') }}</h6>
                    <small class="text-muted">{{ __('Per-question average') }}</small>
                </div>
                <div class="card-body">
                    @if(empty($trend['questions']))
                        <div class="empty-block">
                            <i class="ti ti-mood-empty"></i>
                            <p class="mb-0">{{ __('No data to plot yet.') }}</p>
                        </div>
                    @else
                        <div id="pulse-trend-chart" style="min-height:340px;"></div>
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
    const el = document.querySelector('#pulse-trend-chart');
    if (!el) return;

    // Truncate long question texts in the legend
    const series = trend.questions.map(q => ({
        name: q.question_text.length > 42 ? q.question_text.slice(0, 42) + '…' : q.question_text,
        data: q.series,
    }));

    new ApexCharts(el, {
        chart: {
            type: 'line', height: 340, toolbar: { show: false }, fontFamily: 'inherit', zoom: { enabled: false },
        },
        series: series,
        stroke: { width: 2.5, curve: 'smooth' },
        colors: ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#0ea5e9', '#8b5cf6', '#14b8a6'],
        markers: { size: 4, hover: { size: 6 } },
        xaxis: {
            categories: trend.weeks,
            labels: { style: { fontSize: '11px' }, rotate: -30 },
        },
        yaxis: {
            min: 0, max: 5, tickAmount: 5,
            title: { text: '{{ __("Avg Rating") }}', style: { fontSize: '11px', fontWeight: 600 } },
            labels: { style: { fontSize: '11px' } },
        },
        grid: { borderColor: '#f1f5f9', strokeDashArray: 4 },
        legend: { position: 'top', horizontalAlign: 'left', fontSize: '12px', markers: { width: 10, height: 10, radius: 3 } },
        annotations: {
            yaxis: [{
                y: {{ $lowThreshold }},
                borderColor: '#ef4444',
                strokeDashArray: 6,
                label: {
                    text: '{{ __("Low threshold") }} ({{ number_format($lowThreshold, 1) }})',
                    borderColor: '#ef4444',
                    style: { color: '#fff', background: '#ef4444', fontSize: '10px' },
                    position: 'left',
                },
            }],
        },
        tooltip: { shared: true, y: { formatter: v => v === null ? '—' : v.toFixed(2) } },
    }).render();
})();
</script>
@endpush
