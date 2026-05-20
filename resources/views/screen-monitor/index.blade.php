@extends('layouts.admin')

@section('page-title', __('Screen Monitor'))

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item active">{{ __('Screen Monitor') }}</li>
@endsection

@push('css-page')
<style>
    .sm-card {
        border: 1px solid var(--bs-border-color);
        border-radius: 12px;
        overflow: hidden;
        transition: box-shadow .18s, transform .18s;
        background: var(--bs-card-bg, #fff);
    }
    .sm-card:hover { box-shadow: 0 6px 24px rgba(67,97,238,.13); transform: translateY(-2px); }
    .sm-thumb-wrap {
        position: relative;
        height: 140px;
        background: #0a0c1b;
        overflow: hidden;
        cursor: pointer;
    }
    .sm-thumb-wrap img {
        width: 100%; height: 100%; object-fit: cover; opacity: .88;
        transition: opacity .2s;
    }
    .sm-thumb-wrap:hover img { opacity: 1; }
    .sm-no-shot {
        display: flex; align-items: center; justify-content: center;
        height: 100%; color: #555; font-size: .85rem; flex-direction: column; gap: 6px;
    }
    .sm-badge-online {
        position: absolute; top: 8px; right: 8px;
        background: #22c55e; color: #fff; font-size: .68rem;
        padding: 2px 8px; border-radius: 20px; font-weight: 600;
    }
    .sm-count-badge {
        position: absolute; bottom: 8px; left: 8px;
        background: rgba(67,97,238,.85); color: #fff; font-size: .7rem;
        padding: 2px 9px; border-radius: 20px;
    }
    .sm-info { padding: 10px 12px 12px; }
    .sm-name { font-weight: 600; font-size: .92rem; color: var(--bs-heading-color); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .sm-time { font-size: .72rem; color: var(--bs-secondary-color, #6c757d); margin-top: 2px; }
    .sm-search { max-width: 280px; }

    /* === Horizontal slider === */
    .sm-slider-wrap { position: relative; }
    .sm-slider {
        display: flex;
        gap: 16px;
        overflow-x: auto;
        scroll-behavior: smooth;
        scroll-snap-type: x mandatory;
        padding: 4px 4px 14px;
        -webkit-overflow-scrolling: touch;
    }
    .sm-slider::-webkit-scrollbar { height: 8px; }
    .sm-slider::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
    .sm-slider::-webkit-scrollbar-track { background: transparent; }
    .sm-slide {
        flex: 0 0 calc((100% - 80px) / 6); /* 6 cards visible on xl */
        min-width: 180px;
        scroll-snap-align: start;
    }
    @media (max-width: 1400px) { .sm-slide { flex-basis: calc((100% - 64px) / 5); } }
    @media (max-width: 1200px) { .sm-slide { flex-basis: calc((100% - 48px) / 4); } }
    @media (max-width: 992px)  { .sm-slide { flex-basis: calc((100% - 32px) / 3); } }
    @media (max-width: 768px)  { .sm-slide { flex-basis: calc((100% - 16px) / 2); } }
    @media (max-width: 480px)  { .sm-slide { flex-basis: 80%; } }

    .sm-slide .sm-card { height: 100%; }

    .sm-nav-btn {
        position: absolute; top: 50%; transform: translateY(-50%);
        width: 42px; height: 42px; border-radius: 50%;
        background: #fff; border: 1px solid #e2e8f0;
        box-shadow: 0 6px 18px rgba(15,23,42,.12);
        display: flex; align-items: center; justify-content: center;
        color: #1e3a8a; font-size: 1.3rem; cursor: pointer;
        z-index: 5; transition: all .18s;
    }
    .sm-nav-btn:hover:not(:disabled) { background: #1e3a8a; color: #fff; transform: translateY(-50%) scale(1.05); }
    .sm-nav-btn:disabled { opacity: .35; cursor: not-allowed; }
    .sm-nav-prev { left: -10px; }
    .sm-nav-next { right: -10px; }
</style>
@endpush

@section('content')
<div class="container-fluid">

    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
        <h4 class="mb-0 fw-bold">
            <i class="ti ti-device-desktop-analytics me-2 text-primary"></i>
            {{ __('Employee Screen Monitor') }}
        </h4>
        <form method="GET" action="{{ route('screen-monitor.index') }}" class="d-flex gap-2">
            <input type="text" name="search" class="form-control form-control-sm sm-search"
                   placeholder="{{ __('Search employee…') }}" value="{{ $search ?? '' }}">
            <button class="btn btn-sm btn-primary px-3">{{ __('Search') }}</button>
            @if($search)
                <a href="{{ route('screen-monitor.index') }}" class="btn btn-sm btn-outline-secondary">{{ __('Clear') }}</a>
            @endif
        </form>
    </div>

    {{-- Info banner --}}
    <div class="alert alert-info d-flex align-items-center gap-2 mb-4 py-2" style="font-size:.85rem;">
        <i class="ti ti-info-circle fs-5"></i>
        {{ __('Screenshots are captured automatically every 5 minutes from employee computers. Stored for 48 hours.') }}
    </div>

    @if($employees->isEmpty())
        <div class="text-center py-5 text-muted">
            <i class="ti ti-users-group" style="font-size:3rem;"></i>
            <p class="mt-2">{{ __('No employees found.') }}</p>
        </div>
    @else
        <div class="sm-slider-wrap">
            <button type="button" class="sm-nav-btn sm-nav-prev" id="smPrev" aria-label="Previous">
                <i class="ti ti-chevron-left"></i>
            </button>
            <div class="sm-slider" id="smSlider">
                @foreach($employees as $emp)
                    @php
                        $latestShot = $emp->screenMonitors->first();
                        $count = $todayCounts[$emp->id] ?? 0;
                    @endphp
                    <div class="sm-slide">
                        <div class="sm-card">
                            <a href="{{ route('screen-monitor.show', $emp->id) }}" class="text-decoration-none">
                                <div class="sm-thumb-wrap">
                                    @if($latestShot)
                                        <img src="{{ $latestShot->screenshot_url }}" alt="{{ $emp->name }}"
                                             onerror="this.parentElement.innerHTML='<div class=\'sm-no-shot\'><i class=\'ti ti-screenshot\' style=\'font-size:1.6rem\'></i><span>No Preview</span></div>'">
                                        @if($count > 0)
                                            <span class="sm-count-badge"><i class="ti ti-camera"></i> {{ $count }} {{ __('today') }}</span>
                                        @endif
                                    @else
                                        <div class="sm-no-shot">
                                            <i class="ti ti-screenshot" style="font-size:1.8rem;"></i>
                                            <span>{{ __('No screenshot yet') }}</span>
                                        </div>
                                    @endif
                                </div>
                            </a>
                            <div class="sm-info">
                                <div class="sm-name" title="{{ $emp->name }}">{{ $emp->name }}</div>
                                <div class="sm-time">
                                    @if($latestShot)
                                        <i class="ti ti-clock"></i>
                                        {{ $latestShot->captured_at->diffForHumans() }}
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <button type="button" class="sm-nav-btn sm-nav-next" id="smNext" aria-label="Next">
                <i class="ti ti-chevron-right"></i>
            </button>
        </div>

        @push('script-page')
        <script>
            (function(){
                var slider = document.getElementById('smSlider');
                var prev   = document.getElementById('smPrev');
                var next   = document.getElementById('smNext');
                if (!slider || !prev || !next) return;

                function step(){
                    var firstSlide = slider.querySelector('.sm-slide');
                    if (!firstSlide) return slider.clientWidth * 0.8;
                    var style = window.getComputedStyle(slider);
                    var gap = parseFloat(style.columnGap || style.gap || 16);
                    return firstSlide.getBoundingClientRect().width + gap;
                }
                function update(){
                    prev.disabled = slider.scrollLeft <= 2;
                    next.disabled = slider.scrollLeft + slider.clientWidth >= slider.scrollWidth - 2;
                }
                prev.addEventListener('click', function(){ slider.scrollBy({ left: -step() * 2, behavior: 'smooth' }); });
                next.addEventListener('click', function(){ slider.scrollBy({ left:  step() * 2, behavior: 'smooth' }); });
                slider.addEventListener('scroll', update);
                window.addEventListener('resize', update);
                update();
            })();
        </script>
        @endpush
    @endif

</div>
@endsection
