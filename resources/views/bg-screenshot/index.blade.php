@extends('layouts.admin')

@section('page-title', __('Screenshot Capture'))

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item active">{{ __('Screenshot Capture') }}</li>
@endsection

@push('css-page')
<style>
    .sc-card {
        border: 1px solid var(--bs-border-color);
        border-radius: 12px;
        overflow: hidden;
        transition: box-shadow .18s, transform .18s;
        background: var(--bs-card-bg, #fff);
    }
    .sc-card:hover { box-shadow: 0 6px 24px rgba(67,97,238,.13); transform: translateY(-2px); }
    .sc-thumb {
        position: relative;
        height: 150px;
        background: #0f172a;
        overflow: hidden;
    }
    .sc-thumb img {
        width: 100%; height: 100%; object-fit: cover; opacity: .9;
        transition: opacity .2s;
    }
    .sc-thumb:hover img { opacity: 1; }
    .sc-no-shot {
        display: flex; align-items: center; justify-content: center;
        height: 100%; color: #64748b; font-size: .82rem; flex-direction: column; gap: 6px;
    }
    .sc-count {
        position: absolute; bottom: 8px; left: 8px;
        background: rgba(67,97,238,.88); color: #fff; font-size: .68rem;
        padding: 2px 9px; border-radius: 20px; font-weight: 600;
    }
    .sc-info { padding: 10px 12px 12px; }
    .sc-name { font-weight: 600; font-size: .9rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .sc-time { font-size: .72rem; color: var(--bs-secondary-color, #6c757d); margin-top: 2px; }
</style>
@endpush

@section('content')
<div class="container-fluid">

    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
        <h4 class="mb-0 fw-bold">
            <i class="ti ti-camera me-2 text-primary"></i>{{ __('Screenshot Capture') }}
        </h4>
        <form method="GET" action="{{ route('bg-screenshot.index') }}" class="d-flex gap-2">
            <input type="text" name="search" class="form-control form-control-sm" style="max-width:250px;"
                   placeholder="{{ __('Search employee…') }}" value="{{ $search ?? '' }}">
            <button class="btn btn-sm btn-primary px-3">{{ __('Search') }}</button>
            @if($search)
                <a href="{{ route('bg-screenshot.index') }}" class="btn btn-sm btn-outline-secondary">{{ __('Clear') }}</a>
            @endif
        </form>
    </div>

    @php $currentInterval = \App\Models\Utility::getValByName('screenshot_interval') ?: 5; @endphp
    <div class="card mb-4">
        <div class="card-body py-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-2" style="font-size:.84rem;">
                    <i class="ti ti-info-circle fs-5 text-primary"></i>
                    {{ __('Screenshots are captured every') }} <strong>{{ $currentInterval }} {{ __('min') }}</strong>. {{ __('Stored for 7 days.') }}
                </div>
                <form method="POST" action="{{ route('bg-screenshot.interval') }}" class="d-flex align-items-center gap-2">
                    @csrf
                    <label class="form-label mb-0 text-nowrap" style="font-size:.82rem;">{{ __('Interval:') }}</label>
                    <select name="interval" class="form-select form-select-sm" style="width:auto;">
                        @foreach([1, 2, 3, 5, 10, 15, 30, 60] as $opt)
                            <option value="{{ $opt }}" {{ (int)$currentInterval === $opt ? 'selected' : '' }}>{{ $opt }} {{ __('min') }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="ti ti-check"></i> {{ __('Update') }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    @if($employees->isEmpty())
        <div class="text-center py-5 text-muted">
            <i class="ti ti-users-group" style="font-size:3rem;"></i>
            <p class="mt-2">{{ __('No employees found.') }}</p>
        </div>
    @else
        <div class="row g-3">
            @foreach($employees as $emp)
                @php
                    $latestShot = $emp->backgroundScreenshots->first();
                    $count = $todayCounts[$emp->id] ?? 0;
                @endphp
                <div class="col-6 col-sm-4 col-md-3 col-xl-2">
                    <div class="sc-card">
                        <a href="{{ route('bg-screenshot.show', $emp->id) }}" class="text-decoration-none">
                            <div class="sc-thumb">
                                @if($latestShot)
                                    <img src="{{ $latestShot->screenshot_url }}" alt="{{ $emp->name }}"
                                         onerror="this.parentElement.innerHTML='<div class=\'sc-no-shot\'><i class=\'ti ti-photo-off\' style=\'font-size:1.5rem\'></i><span>No Preview</span></div>'">
                                    @if($count > 0)
                                        <span class="sc-count"><i class="ti ti-camera"></i> {{ $count }} {{ __('today') }}</span>
                                    @endif
                                @else
                                    <div class="sc-no-shot">
                                        <i class="ti ti-camera-off" style="font-size:1.6rem;"></i>
                                        <span>{{ __('No screenshot yet') }}</span>
                                    </div>
                                @endif
                            </div>
                        </a>
                        <div class="sc-info">
                            <div class="sc-name" title="{{ $emp->name }}">{{ $emp->name }}</div>
                            <div class="sc-time">
                                @if($latestShot)
                                    <i class="ti ti-clock"></i> {{ $latestShot->captured_at->diffForHumans() }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

</div>

<script>
(function () {
    // Auto-refresh every 60s so timestamps stay live. Skips refresh when the
    // admin is mid-typing in the search box.
    var REFRESH_MS = 60 * 1000;
    var search = document.querySelector('input[name="search"]');
    setInterval(function () {
        if (document.hidden) return;
        if (search && document.activeElement === search) return;
        location.reload();
    }, REFRESH_MS);
})();
</script>
@endsection
