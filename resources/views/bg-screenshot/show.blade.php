@extends('layouts.admin')

@section('page-title', __('Screenshots') . ' — ' . $employee->name)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('bg-screenshot.index') }}">{{ __('Screenshot Capture') }}</a></li>
    <li class="breadcrumb-item active">{{ $employee->name }}</li>
@endsection

@push('css-page')
<style>
    .sg-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 14px; }
    .sg-item {
        border: 1px solid var(--bs-border-color);
        border-radius: 10px; overflow: hidden;
        background: var(--bs-card-bg, #fff);
        transition: box-shadow .18s, transform .18s;
    }
    .sg-item:hover { box-shadow: 0 6px 22px rgba(67,97,238,.14); transform: translateY(-2px); }
    .sg-img-wrap {
        position: relative; height: 170px; background: #0f172a; overflow: hidden; cursor: zoom-in;
    }
    .sg-img-wrap img { width: 100%; height: 100%; object-fit: cover; transition: transform .25s; }
    .sg-img-wrap:hover img { transform: scale(1.04); }
    .sg-footer { padding: 8px 12px; display: flex; align-items: center; justify-content: space-between; }
    .sg-time { font-size: .74rem; color: var(--bs-secondary-color, #6c757d); }
    .sg-page { font-size: .68rem; color: #94a3b8; max-width: 180px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .sg-del { background: none; border: none; color: #dc3545; padding: 2px 6px; border-radius: 6px; cursor: pointer; font-size: .85rem; }
    .sg-del:hover { background: #dc354520; }

    /* Date sidebar */
    .sg-dates { list-style: none; padding: 0; margin: 0; }
    .sg-dates li a {
        display: flex; justify-content: space-between; align-items: center;
        padding: 8px 14px; font-size: .82rem; color: var(--bs-body-color);
        text-decoration: none; border-radius: 8px; transition: background .15s;
    }
    .sg-dates li a:hover { background: #f1f5f9; }
    .sg-dates li a.active { background: #4361ee; color: #fff; font-weight: 600; }
    .sg-dates li a .badge { font-size: .68rem; }

    /* Page-activity timeline */
    .pa-stat { padding: 14px 16px; border-radius: 12px; color: #fff; }
    .pa-stat .lbl { font-size: .72rem; opacity: .85; text-transform: uppercase; letter-spacing: .4px; }
    .pa-stat .val { font-size: 1.55rem; font-weight: 700; line-height: 1.1; margin-top: 4px; }
    .pa-stat.bg-1 { background: linear-gradient(135deg,#4361ee,#3b3eb5); }
    .pa-stat.bg-2 { background: linear-gradient(135deg,#10b981,#059669); }
    .pa-stat.bg-3 { background: linear-gradient(135deg,#f59e0b,#d97706); }
    .pa-stat.bg-4 { background: linear-gradient(135deg,#8b5cf6,#7c3aed); }
    .pa-stat.bg-5 { background: linear-gradient(135deg,#ec4899,#db2777); }

    .pa-row { display: grid; grid-template-columns: 80px 1fr auto auto; gap: 12px; align-items: center;
              padding: 10px 14px; border-radius: 8px; transition: background .15s; border-bottom: 1px solid #f1f5f9; }
    .pa-row:hover { background: #f8fafc; }
    .pa-time { font-size: .76rem; color: #6b7280; font-variant-numeric: tabular-nums; }
    .pa-page-title { font-size: .88rem; font-weight: 600; color: #111827; }
    .pa-url { font-size: .72rem; color: #94a3b8; max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .pa-dur { font-size: .8rem; font-weight: 600; padding: 3px 9px; border-radius: 14px; background: #eef2ff; color: #4338ca; min-width: 60px; text-align: center; }
    .pa-tab { font-size: .65rem; color: #94a3b8; font-family: ui-monospace, monospace; }
    .pa-active-dot { width: 7px; height: 7px; background: #10b981; border-radius: 50%; display: inline-block;
                     animation: pa-pulse 1.4s ease-in-out infinite; }
    @keyframes pa-pulse { 0%, 100% { opacity: 1; } 50% { opacity: .35; } }

    .pa-bar-row { display: grid; grid-template-columns: 1fr 60px; gap: 10px; align-items: center;
                  padding: 8px 0; border-bottom: 1px dashed #e2e8f0; }
    .pa-bar { background: #eef2ff; border-radius: 4px; height: 8px; overflow: hidden; margin-top: 5px; }
    .pa-bar > span { display: block; height: 100%; background: linear-gradient(90deg,#4361ee,#7c3aed); border-radius: 4px; }
    .pa-bar-label { font-size: .8rem; font-weight: 600; color: #1f2937; }
    .pa-bar-sub { font-size: .68rem; color: #94a3b8; }
    .pa-bar-time { font-size: .75rem; font-weight: 700; color: #4338ca; text-align: right; }

    /* Lightbox */
    #bgLightbox {
        display: none; position: fixed; inset: 0; z-index: 9999;
        background: rgba(0,0,0,.88); align-items: center; justify-content: center;
    }
    #bgLightbox.show { display: flex; }
    #bgLightbox img { max-width: 94vw; max-height: 90vh; border-radius: 6px; box-shadow: 0 8px 40px rgba(0,0,0,.5); }
    #bgLbClose {
        position: absolute; top: 18px; right: 22px; font-size: 2rem; color: #fff; cursor: pointer;
        background: none; border: none; line-height: 1;
    }
    #bgLbInfo {
        position: absolute; bottom: 18px; left: 50%; transform: translateX(-50%);
        color: #fff; font-size: .82rem; background: rgba(0,0,0,.6); padding: 6px 16px; border-radius: 20px;
        text-align: center; max-width: 90vw;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">

    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
        <div>
            <h5 class="mb-0 fw-bold">
                <i class="ti ti-camera me-2 text-primary"></i>{{ $employee->name }}
                <span class="badge bg-primary-subtle text-primary ms-2" style="font-size:.75rem;">
                    {{ $screenshots->total() }} {{ __('screenshots') }}
                </span>
            </h5>
            <small class="text-muted">{{ __('Date') }}: {{ \Carbon\Carbon::parse($date)->format('d M Y') }}</small>
        </div>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <form method="GET" action="{{ route('bg-screenshot.show', $employee->id) }}" class="d-flex gap-2">
                <input type="date" name="date" class="form-control form-control-sm" value="{{ $date }}"
                       max="{{ today()->toDateString() }}" onchange="this.form.submit()">
            </form>
            <a href="{{ route('bg-screenshot.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="ti ti-arrow-left me-1"></i>{{ __('Back') }}
            </a>
        </div>
    </div>

    {{-- Tabs --}}
    <ul class="nav nav-tabs mb-3" role="tablist">
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-shots" type="button">
                <i class="ti ti-camera me-1"></i>{{ __('Screenshots') }}
                <span class="badge bg-light text-dark ms-1">{{ $screenshots->total() }}</span>
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-activity" type="button">
                <i class="ti ti-clock-bolt me-1"></i>{{ __('Page Activity') }}
                <span class="badge bg-light text-dark ms-1">{{ $totals['visits'] }}</span>
            </button>
        </li>
    </ul>

    <div class="tab-content">

    {{-- ─────────────────── TAB 1: SCREENSHOTS ─────────────────── --}}
    <div class="tab-pane fade show active" id="tab-shots">
    <div class="row">
        {{-- Date sidebar --}}
        <div class="col-lg-2 mb-3">
            <div class="card" style="border-radius:10px;">
                <div class="card-header py-2" style="font-size:.8rem;font-weight:700;">
                    <i class="ti ti-calendar me-1"></i>{{ __('Dates') }}
                </div>
                <div class="card-body p-2" style="max-height:400px;overflow-y:auto;">
                    <ul class="sg-dates">
                        @forelse($availableDates as $dt => $cnt)
                        <li>
                            <a href="{{ route('bg-screenshot.show', [$employee->id, 'date' => $dt]) }}"
                               class="{{ $dt == $date ? 'active' : '' }}">
                                <span>{{ \Carbon\Carbon::parse($dt)->format('d M') }}</span>
                                <span class="badge {{ $dt == $date ? 'bg-light text-primary' : 'bg-primary-subtle text-primary' }}">{{ $cnt }}</span>
                            </a>
                        </li>
                        @empty
                        <li class="text-center text-muted py-3" style="font-size:.8rem;">{{ __('No data') }}</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        {{-- Screenshots grid --}}
        <div class="col-lg-10">
            @if($screenshots->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="ti ti-camera-off" style="font-size:3rem;"></i>
                    <p class="mt-2">{{ __('No screenshots found for this date.') }}</p>
                </div>
            @else
                <div class="sg-grid">
                    @foreach($screenshots as $shot)
                    <div class="sg-item" id="sg-{{ $shot->id }}">
                        <div class="sg-img-wrap"
                             onclick="openBgLb('{{ $shot->screenshot_url }}', '{{ $shot->captured_at->format('d M Y, h:i:s A') }}', '{{ $shot->page_url }}')"
                             title="{{ __('Click to enlarge') }}">
                            <img src="{{ $shot->screenshot_url }}" alt="{{ $shot->captured_at }}" loading="lazy"
                                 onerror="this.parentElement.innerHTML='<div style=\'display:flex;align-items:center;justify-content:center;height:100%;color:#555;font-size:.8rem;\'>Preview unavailable</div>'">
                        </div>
                        <div class="sg-footer">
                            <div>
                                <div class="sg-time">
                                    <i class="ti ti-clock"></i> {{ $shot->captured_at->format('h:i:s A') }}
                                    @if($shot->ip_address)
                                        &middot; <i class="ti ti-network"></i> {{ $shot->ip_address }}
                                    @endif
                                </div>
                                @if($shot->page_url)
                                    <div class="sg-page" title="{{ $shot->page_url }}">
                                        <i class="ti ti-link"></i> {{ parse_url($shot->page_url, PHP_URL_PATH) ?: $shot->page_url }}
                                    </div>
                                @endif
                            </div>
                            <button class="sg-del" onclick="deleteBgShot({{ $shot->id }})" title="{{ __('Delete') }}">
                                <i class="ti ti-trash"></i>
                            </button>
                        </div>
                    </div>
                    @endforeach
                </div>

                <div class="mt-4">{{ $screenshots->links() }}</div>
            @endif
        </div>
    </div>
    </div>{{-- /tab-shots --}}

    {{-- ─────────────────── TAB 2: PAGE ACTIVITY ─────────────────── --}}
    <div class="tab-pane fade" id="tab-activity">

        {{-- Daily totals --}}
        <div class="row g-3 mb-3">
            <div class="col-6 col-md">
                <div class="pa-stat bg-1">
                    <div class="lbl">{{ __('Page Views') }}</div>
                    <div class="val">{{ $totals['visits'] }}</div>
                </div>
            </div>
            <div class="col-6 col-md">
                <div class="pa-stat bg-2">
                    <div class="lbl">{{ __('Total Time') }}</div>
                    <div class="val">
                        @php
                            $h = floor($totals['duration'] / 3600);
                            $m = floor(($totals['duration'] % 3600) / 60);
                        @endphp
                        {{ $h > 0 ? $h.'h ' : '' }}{{ $m }}m
                    </div>
                </div>
            </div>
            <div class="col-6 col-md">
                <div class="pa-stat bg-3">
                    <div class="lbl">{{ __('Focused Time') }}</div>
                    <div class="val">
                        @php
                            $fh = floor($totals['focus'] / 3600);
                            $fm = floor(($totals['focus'] % 3600) / 60);
                        @endphp
                        {{ $fh > 0 ? $fh.'h ' : '' }}{{ $fm }}m
                    </div>
                </div>
            </div>
            <div class="col-6 col-md">
                <div class="pa-stat bg-4">
                    <div class="lbl">{{ __('Unique Pages') }}</div>
                    <div class="val">{{ $totals['unique'] }}</div>
                </div>
            </div>
            <div class="col-6 col-md">
                <div class="pa-stat bg-5">
                    <div class="lbl">{{ __('Browser Tabs') }}</div>
                    <div class="val">{{ $totals['tabs'] }}</div>
                </div>
            </div>
        </div>

        <div class="row">
            {{-- Top pages by time --}}
            <div class="col-lg-4 mb-3">
                <div class="card" style="border-radius:10px;">
                    <div class="card-header" style="font-size:.85rem;font-weight:700;">
                        <i class="ti ti-trophy me-1 text-warning"></i>{{ __('Top Pages by Time') }}
                    </div>
                    <div class="card-body" style="max-height:520px;overflow-y:auto;">
                        @if($topPages->isEmpty())
                            <div class="text-center text-muted py-4" style="font-size:.85rem;">
                                <i class="ti ti-clock-off" style="font-size:2rem;opacity:.4;"></i>
                                <p class="mt-2 mb-0">{{ __('No activity for this date') }}</p>
                            </div>
                        @else
                            @php $topMax = max($topPages->max('focus_total') ?: 1, 1); @endphp
                            @foreach($topPages as $row)
                                @php
                                    $secs = (int) ($row->focus_total ?: $row->dur_total);
                                    $pct  = max(2, round(($secs / $topMax) * 100));
                                    $h = floor($secs / 3600); $m = floor(($secs % 3600) / 60); $s = $secs % 60;
                                    $time = $h > 0 ? "{$h}h {$m}m" : ($m > 0 ? "{$m}m {$s}s" : "{$s}s");
                                @endphp
                                <div class="pa-bar-row">
                                    <div>
                                        <div class="pa-bar-label">{{ \Illuminate\Support\Str::limit($row->label, 50) }}</div>
                                        <div class="pa-bar-sub">{{ $row->visits }} {{ __('visits') }} · {{ \Illuminate\Support\Str::limit(parse_url($row->url, PHP_URL_PATH) ?: $row->url, 50) }}</div>
                                        <div class="pa-bar"><span style="width: {{ $pct }}%"></span></div>
                                    </div>
                                    <div class="pa-bar-time">{{ $time }}</div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>

            {{-- Visit timeline --}}
            <div class="col-lg-8">
                <div class="card" style="border-radius:10px;">
                    <div class="card-header d-flex justify-content-between align-items-center" style="font-size:.85rem;font-weight:700;">
                        <span><i class="ti ti-list me-1 text-primary"></i>{{ __('Visit Timeline') }} <small class="text-muted fw-normal">{{ \Carbon\Carbon::parse($date)->format('d M Y') }}</small></span>
                        <small class="text-muted fw-normal">{{ __('Showing latest 200') }}</small>
                    </div>
                    <div class="card-body p-0" style="max-height:520px;overflow-y:auto;">
                        @if($visits->isEmpty())
                            <div class="text-center text-muted py-5">
                                <i class="ti ti-history" style="font-size:3rem;opacity:.4;"></i>
                                <p class="mt-2">{{ __('No page activity recorded for this date.') }}</p>
                                <small>{{ __('Activity is captured automatically when the user is logged in.') }}</small>
                            </div>
                        @else
                            @foreach($visits as $v)
                                <div class="pa-row" title="{{ $v->url }}">
                                    <div class="pa-time">
                                        {{ $v->started_at->format('H:i') }}
                                        @if($v->is_active)<br><span class="pa-active-dot" title="{{ __('Currently active') }}"></span> <span style="color:#10b981;font-size:.65rem;">live</span>@endif
                                    </div>
                                    <div style="min-width:0;">
                                        <div class="pa-page-title">{{ $v->display_label }}</div>
                                        <div class="pa-url">
                                            <i class="ti ti-link" style="font-size:.7rem;"></i>
                                            {{ parse_url($v->url, PHP_URL_PATH) ?: $v->url }}
                                            @if($v->tab_id)
                                                <span class="pa-tab ms-1">· {{ \Illuminate\Support\Str::limit($v->tab_id, 14, '') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div>
                                        <span class="pa-dur">{{ $v->duration_human }}</span>
                                    </div>
                                    <div class="text-end" style="min-width:60px;">
                                        @if($v->focus_seconds > 0 && $v->duration_seconds > 0)
                                            @php $focusPct = round(($v->focus_seconds / max(1, $v->duration_seconds)) * 100); @endphp
                                            <small class="text-muted" title="{{ __('Focused time vs total') }}">{{ $focusPct }}% {{ __('focus') }}</small>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>{{-- /tab-activity --}}

    </div>{{-- /tab-content --}}

</div>

{{-- Lightbox --}}
<div id="bgLightbox">
    <button id="bgLbClose" onclick="closeBgLb()" title="Close">&times;</button>
    <img id="bgLbImg" src="" alt="screenshot">
    <div id="bgLbInfo"></div>
</div>
@endsection

@push('custom-scripts')
<script>
    function openBgLb(src, time, url) {
        document.getElementById('bgLbImg').src = src;
        document.getElementById('bgLbInfo').innerHTML = time + (url ? '<br><small style="opacity:.7">' + url + '</small>' : '');
        document.getElementById('bgLightbox').classList.add('show');
    }
    function closeBgLb() {
        document.getElementById('bgLightbox').classList.remove('show');
        document.getElementById('bgLbImg').src = '';
    }
    document.getElementById('bgLightbox').addEventListener('click', function(e) {
        if (e.target === this) closeBgLb();
    });
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeBgLb();
    });

    function deleteBgShot(id) {
        if (!confirm('{{ __("Delete this screenshot?") }}')) return;
        fetch('{{ url("bg-screenshot") }}/' + id, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
        }).then(r => r.json()).then(data => {
            if (data.ok) {
                var el = document.getElementById('sg-' + id);
                if (el) el.remove();
            }
        });
    }
</script>
@endpush
