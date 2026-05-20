@extends('layouts.admin')
@section('page-title') {{ __('eNPS Report') }} @endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('surveys.index') }}">{{ __('Surveys') }}</a></li>
    <li class="breadcrumb-item">{{ __('eNPS Report') }}</li>
@endsection

@push('css-page')
<style>
    .enps-hero{background:linear-gradient(135deg,#0ea5e9 0%,#06b6d4 100%);border-radius:14px;padding:24px 26px;color:#fff;box-shadow:0 8px 24px -10px rgba(6,182,212,.4);position:relative;overflow:hidden;}
    .enps-hero::before{content:"";position:absolute;right:-40px;top:-40px;width:180px;height:180px;border-radius:50%;background:rgba(255,255,255,.08);}
    .enps-hero h3{margin:0;font-weight:700;font-size:1.05rem;letter-spacing:.3px;}
    .enps-hero .score{font-size:3.4rem;font-weight:800;line-height:1;margin:6px 0 4px;letter-spacing:-1px;}
    .enps-hero .score small{font-size:.95rem;font-weight:600;opacity:.85;margin-left:6px;}
    .enps-hero .scale{font-size:.78rem;opacity:.85;}
    .enps-hero .badge-pill{background:rgba(255,255,255,.2);padding:4px 12px;border-radius:20px;font-size:.72rem;font-weight:700;letter-spacing:.4px;text-transform:uppercase;display:inline-block;margin-top:8px;}

    .kpi-card{border:1px solid #e2e8f0;border-radius:12px;padding:14px 16px;background:#fff;height:100%;}
    .kpi-card .lbl{font-size:.7rem;color:#64748b;text-transform:uppercase;letter-spacing:.4px;font-weight:600;}
    .kpi-card .val{font-size:1.7rem;font-weight:800;color:#0f172a;line-height:1;margin-top:6px;}
    .kpi-card .pct{font-size:.78rem;color:#64748b;margin-top:4px;}
    .kpi-card.t-promoter{border-left:4px solid #16a34a;}
    .kpi-card.t-passive {border-left:4px solid #f59e0b;}
    .kpi-card.t-detractor{border-left:4px solid #ef4444;}
    .kpi-card.t-total   {border-left:4px solid #6366f1;}

    .dist-bar{display:flex;height:14px;border-radius:8px;overflow:hidden;background:#f1f5f9;margin-top:14px;}
    .dist-bar > span{display:block;height:100%;}
    .dist-bar .seg-p{background:linear-gradient(90deg,#22c55e,#16a34a);}
    .dist-bar .seg-pa{background:linear-gradient(90deg,#fbbf24,#f59e0b);}
    .dist-bar .seg-d{background:linear-gradient(90deg,#f87171,#ef4444);}
    .dist-legend{display:flex;gap:14px;font-size:.74rem;color:#475569;margin-top:6px;flex-wrap:wrap;}
    .dist-legend .dot{display:inline-block;width:10px;height:10px;border-radius:3px;margin-right:5px;}

    .enps-table th{font-size:.7rem;text-transform:uppercase;letter-spacing:.4px;color:#64748b;font-weight:600;background:#fafafa;}
    .enps-table td{vertical-align:middle;}
    .enps-score-pill{font-weight:700;padding:3px 10px;border-radius:20px;font-size:.78rem;}
    .pill-good {background:#dcfce7;color:#166534;}
    .pill-watch{background:#fef3c7;color:#b45309;}
    .pill-poor {background:#fee2e2;color:#991b1b;}

    .empty-block{text-align:center;padding:48px 16px;color:#94a3b8;}
    .empty-block i{font-size:3rem;opacity:.3;display:block;margin-bottom:10px;}
</style>
@endpush

@section('content')

    {{-- ── Filters ──────────────────────────────────────────────── --}}
    <div class="card mb-3">
        <div class="card-body py-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small fw-semibold mb-1">{{ __('Survey scope') }}</label>
                    <select name="survey_id" class="form-control form-control-sm" onchange="this.form.submit()">
                        <option value="">{{ __('All surveys (company-wide)') }}</option>
                        @foreach($surveys as $sv)
                            <option value="{{ $sv->id }}" {{ $selectedSurvey == $sv->id ? 'selected' : '' }}>
                                {{ $sv->title }} <small>· {{ ucfirst($sv->type) }} · {{ ucfirst($sv->status) }}</small>
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1">{{ __('Trend window') }}</label>
                    <select name="months" class="form-control form-control-sm" onchange="this.form.submit()">
                        @foreach([3,6,12,18,24] as $m)
                            <option value="{{ $m }}" {{ $months == $m ? 'selected' : '' }}>{{ __('Last :n months', ['n' => $m]) }}</option>
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

    {{-- ── Hero score + KPIs ────────────────────────────────────── --}}
    <div class="row g-3 mb-3">
        <div class="col-lg-5">
            @php
                $score = $summary['score'];
                $rating = $score >= 30 ? __('Great') : ($score >= 0 ? __('Good') : ($score >= -30 ? __('Needs Attention') : __('Critical')));
            @endphp
            <div class="enps-hero">
                <h3><i class="ti ti-trending-up me-1"></i>{{ __('Employee Net Promoter Score') }}</h3>
                <div class="score">
                    {{ $score > 0 ? '+' : '' }}{{ number_format($score, 1) }}
                    <small>/ 100</small>
                </div>
                <div class="scale">{{ __('Range −100 to +100') }}</div>
                <span class="badge-pill">{{ $rating }}</span>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="row g-3">
                <div class="col-md-6 col-6">
                    <div class="kpi-card t-total">
                        <div class="lbl">{{ __('Total Responses') }}</div>
                        <div class="val">{{ $summary['total'] }}</div>
                        <div class="pct">{{ __('Counted toward score') }}</div>
                    </div>
                </div>
                <div class="col-md-6 col-6">
                    <div class="kpi-card t-promoter">
                        <div class="lbl"><i class="ti ti-mood-happy text-success me-1"></i>{{ __('Promoters (9-10)') }}</div>
                        <div class="val text-success">{{ $summary['promoters'] }}</div>
                        <div class="pct">{{ $summary['pct_p'] }}%</div>
                    </div>
                </div>
                <div class="col-md-6 col-6">
                    <div class="kpi-card t-passive">
                        <div class="lbl"><i class="ti ti-mood-neutral text-warning me-1"></i>{{ __('Passives (7-8)') }}</div>
                        <div class="val text-warning">{{ $summary['passives'] }}</div>
                        <div class="pct">{{ $summary['pct_pa'] }}%</div>
                    </div>
                </div>
                <div class="col-md-6 col-6">
                    <div class="kpi-card t-detractor">
                        <div class="lbl"><i class="ti ti-mood-sad text-danger me-1"></i>{{ __('Detractors (0-6)') }}</div>
                        <div class="val text-danger">{{ $summary['detractors'] }}</div>
                        <div class="pct">{{ $summary['pct_d'] }}%</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Distribution bar ─────────────────────────────────────── --}}
    @if($summary['total'] > 0)
    <div class="card mb-3">
        <div class="card-body">
            <h6 class="mb-2"><i class="ti ti-chart-bar me-1"></i>{{ __('Response Distribution') }}</h6>
            <div class="dist-bar">
                <span class="seg-p"  style="width:{{ $summary['pct_p'] }}%;"  title="{{ __('Promoters') }} {{ $summary['pct_p'] }}%"></span>
                <span class="seg-pa" style="width:{{ $summary['pct_pa'] }}%;" title="{{ __('Passives') }} {{ $summary['pct_pa'] }}%"></span>
                <span class="seg-d"  style="width:{{ $summary['pct_d'] }}%;"  title="{{ __('Detractors') }} {{ $summary['pct_d'] }}%"></span>
            </div>
            <div class="dist-legend mt-2">
                <span><span class="dot" style="background:#16a34a;"></span>{{ __('Promoters') }}: <strong>{{ $summary['pct_p'] }}%</strong></span>
                <span><span class="dot" style="background:#f59e0b;"></span>{{ __('Passives') }}: <strong>{{ $summary['pct_pa'] }}%</strong></span>
                <span><span class="dot" style="background:#ef4444;"></span>{{ __('Detractors') }}: <strong>{{ $summary['pct_d'] }}%</strong></span>
            </div>
        </div>
    </div>
    @endif

    {{-- ── Department-wise eNPS ─────────────────────────────────── --}}
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="ti ti-building me-1"></i>{{ __('Department-wise eNPS') }}</h6>
            <small class="text-muted">{{ __('Sorted by score') }}</small>
        </div>
        <div class="card-body p-0">
            @if(empty($byDept) || $summary['total'] == 0)
                <div class="empty-block">
                    <i class="ti ti-clipboard-off"></i>
                    <p class="mb-0">{{ __('No eNPS responses yet.') }}</p>
                    <small>{{ __('Once employees submit a survey with an eNPS question, the breakdown will appear here.') }}</small>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table enps-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">{{ __('Department') }}</th>
                                <th class="text-center">{{ __('Responses') }}</th>
                                <th class="text-center">{{ __('Promoters') }}</th>
                                <th class="text-center">{{ __('Passives') }}</th>
                                <th class="text-center">{{ __('Detractors') }}</th>
                                <th>{{ __('Distribution') }}</th>
                                <th class="text-end pe-3">{{ __('eNPS') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($byDept as $row)
                                @php
                                    $pillClass = $row['score'] >= 30 ? 'pill-good' : ($row['score'] >= 0 ? 'pill-watch' : 'pill-poor');
                                @endphp
                                <tr>
                                    <td class="ps-3"><strong>{{ $row['department_name'] }}</strong></td>
                                    <td class="text-center">{{ $row['total'] }}</td>
                                    <td class="text-center text-success"><strong>{{ $row['promoters'] }}</strong> <small class="text-muted">({{ $row['pct_p'] }}%)</small></td>
                                    <td class="text-center text-warning"><strong>{{ $row['passives'] }}</strong> <small class="text-muted">({{ $row['pct_pa'] }}%)</small></td>
                                    <td class="text-center text-danger"><strong>{{ $row['detractors'] }}</strong> <small class="text-muted">({{ $row['pct_d'] }}%)</small></td>
                                    <td>
                                        <div class="dist-bar" style="margin:0;">
                                            <span class="seg-p"  style="width:{{ $row['pct_p'] }}%;"></span>
                                            <span class="seg-pa" style="width:{{ $row['pct_pa'] }}%;"></span>
                                            <span class="seg-d"  style="width:{{ $row['pct_d'] }}%;"></span>
                                        </div>
                                    </td>
                                    <td class="pe-3 text-end">
                                        <span class="enps-score-pill {{ $pillClass }}">
                                            {{ $row['score'] > 0 ? '+' : '' }}{{ number_format($row['score'], 1) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- ── Monthly Trend chart ──────────────────────────────────── --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="ti ti-chart-line me-1"></i>{{ __('Month-wise eNPS Trend') }}</h6>
            <small class="text-muted">{{ __('Last :n months', ['n' => $months]) }}</small>
        </div>
        <div class="card-body">
            @if($summary['total'] == 0)
                <div class="empty-block">
                    <i class="ti ti-mood-empty"></i>
                    <p class="mb-0">{{ __('No data to plot yet.') }}</p>
                </div>
            @else
                <div id="enps-trend-chart" style="min-height:320px;"></div>
            @endif
        </div>
    </div>
@endsection

@push('script-page')
<script>
(function(){
    if (typeof ApexCharts === 'undefined') return;
    const trend = @json($trend ?? []);
    if (!trend.length) return;
    const el = document.querySelector('#enps-trend-chart');
    if (!el) return;

    const labels  = trend.map(r => r.label);
    const scores  = trend.map(r => r.score);
    const totals  = trend.map(r => r.total);

    new ApexCharts(el, {
        chart: { type: 'line', height: 320, toolbar: { show: false }, fontFamily: 'inherit', zoom: { enabled: false } },
        series: [
            { name: '{{ __("eNPS Score") }}', type: 'line',   data: scores },
            { name: '{{ __("Responses") }}',   type: 'column', data: totals },
        ],
        stroke: { width: [3, 0], curve: 'smooth' },
        colors: ['#0ea5e9', '#94a3b8'],
        plotOptions: { bar: { columnWidth: '40%', borderRadius: 4 } },
        markers: { size: [5, 0], hover: { size: 7 }, colors: ['#0ea5e9'] },
        dataLabels: {
            enabled: true,
            enabledOnSeries: [0],
            formatter: v => (v > 0 ? '+' : '') + Math.round(v),
            style: { fontSize: '11px', fontWeight: 700, colors: ['#0f172a'] },
            background: { enabled: true, foreColor: '#0f172a', borderRadius: 4, padding: 3, opacity: .9 },
        },
        xaxis: { categories: labels, labels: { style: { fontSize: '11px' } } },
        yaxis: [
            {
                title: { text: '{{ __("eNPS") }}', style: { fontSize: '11px', fontWeight: 600 } },
                min: -100, max: 100, tickAmount: 4,
                labels: { formatter: v => (v > 0 ? '+' : '') + v, style: { fontSize: '11px' } },
            },
            {
                opposite: true,
                title: { text: '{{ __("Responses") }}', style: { fontSize: '11px', fontWeight: 600 } },
                min: 0,
                labels: { style: { fontSize: '11px' } },
            },
        ],
        grid: { borderColor: '#f1f5f9', strokeDashArray: 4 },
        legend: { position: 'top', horizontalAlign: 'left' },
        tooltip: {
            shared: true,
            y: [
                { formatter: v => (v > 0 ? '+' : '') + v },
                { formatter: v => v + ' {{ __("responses") }}' },
            ],
        },
        annotations: {
            yaxis: [{ y: 0, borderColor: '#cbd5e1', strokeDashArray: 6 }],
        },
    }).render();
})();
</script>
@endpush
