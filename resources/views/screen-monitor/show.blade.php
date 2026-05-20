@extends('layouts.admin')

@section('page-title', __('Screenshots') . ' — ' . $employee->name)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('screen-monitor.index') }}">{{ __('Screen Monitor') }}</a></li>
    <li class="breadcrumb-item active">{{ $employee->name }}</li>
@endsection

@push('css-page')
<style>
    .shot-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 14px; }
    .shot-item {
        border: 1px solid var(--bs-border-color);
        border-radius: 10px; overflow: hidden;
        background: var(--bs-card-bg, #fff);
        transition: box-shadow .18s, transform .18s;
    }
    .shot-item:hover { box-shadow: 0 6px 22px rgba(67,97,238,.14); transform: translateY(-2px); }
    .shot-img-wrap {
        position: relative; height: 160px; background: #0a0c1b; overflow: hidden; cursor: zoom-in;
    }
    .shot-img-wrap img { width: 100%; height: 100%; object-fit: cover; transition: transform .25s; }
    .shot-img-wrap:hover img { transform: scale(1.04); }
    .shot-footer { padding: 8px 12px; display: flex; align-items: center; justify-content: space-between; }
    .shot-time { font-size: .75rem; color: var(--bs-secondary-color, #6c757d); }
    .shot-del { background: none; border: none; color: #dc3545; padding: 2px 6px; border-radius: 6px; cursor: pointer; font-size: .85rem; }
    .shot-del:hover { background: #dc354520; }

    /* Lightbox */
    #smLightbox {
        display: none; position: fixed; inset: 0; z-index: 9999;
        background: rgba(0,0,0,.88); align-items: center; justify-content: center;
    }
    #smLightbox.show { display: flex; }
    #smLightbox img { max-width: 94vw; max-height: 90vh; border-radius: 6px; box-shadow: 0 8px 40px rgba(0,0,0,.5); }
    #smLightboxClose {
        position: absolute; top: 18px; right: 22px; font-size: 2rem; color: #fff; cursor: pointer;
        background: none; border: none; line-height: 1;
    }
    #smLightboxTime {
        position: absolute; bottom: 18px; left: 50%; transform: translateX(-50%);
        color: #fff; font-size: .82rem; background: rgba(0,0,0,.5); padding: 4px 14px; border-radius: 20px;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">

    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
        <div>
            <h5 class="mb-0 fw-bold">
                <i class="ti ti-device-desktop me-2 text-primary"></i>
                {{ $employee->name }}
                <span class="badge bg-primary-subtle text-primary ms-2" style="font-size:.75rem;font-weight:500;">
                    {{ $screenshots->total() }} {{ __('screenshots') }}
                </span>
            </h5>
            <small class="text-muted">{{ __('Date') }}: {{ \Carbon\Carbon::parse($date)->format('d M Y') }}</small>
        </div>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            {{-- Date picker --}}
            <form method="GET" action="{{ route('screen-monitor.show', $employee->id) }}" class="d-flex gap-2">
                <input type="date" name="date" class="form-control form-control-sm" value="{{ $date }}"
                       max="{{ today()->toDateString() }}" onchange="this.form.submit()">
            </form>
            <a href="{{ route('screen-monitor.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="ti ti-arrow-left me-1"></i>{{ __('Back') }}
            </a>
        </div>
    </div>

    @if($screenshots->isEmpty())
        <div class="text-center py-5 text-muted">
            <i class="ti ti-camera-off" style="font-size:3rem;"></i>
            <p class="mt-2">{{ __('No screenshots found for this date.') }}</p>
        </div>
    @else
        <div class="shot-grid">
            @foreach($screenshots as $shot)
            <div class="shot-item" id="shot-{{ $shot->id }}">
                <div class="shot-img-wrap"
                     onclick="openLightbox('{{ $shot->screenshot_url }}', '{{ $shot->captured_at->format('d M Y, h:i:s A') }}')"
                     title="{{ __('Click to enlarge') }}">
                    <img src="{{ $shot->screenshot_url }}" alt="{{ $shot->captured_at }}"
                         loading="lazy"
                         onerror="this.parentElement.innerHTML='<div style=\'display:flex;align-items:center;justify-content:center;height:100%;color:#555;font-size:.8rem;\'>Preview unavailable</div>'">
                </div>
                <div class="shot-footer">
                    <span class="shot-time">
                        <i class="ti ti-clock"></i>
                        {{ $shot->captured_at->format('h:i:s A') }}
                        @if($shot->ip_address)
                            &nbsp;·&nbsp;<i class="ti ti-network"></i> {{ $shot->ip_address }}
                        @endif
                    </span>
                    <button class="shot-del" onclick="deleteShot({{ $shot->id }})" title="{{ __('Delete') }}">
                        <i class="ti ti-trash"></i>
                    </button>
                </div>
            </div>
            @endforeach
        </div>

        <div class="mt-4">
            {{ $screenshots->links() }}
        </div>
    @endif

</div>

{{-- Lightbox --}}
<div id="smLightbox">
    <button id="smLightboxClose" onclick="closeLightbox()" title="Close">&times;</button>
    <img id="smLightboxImg" src="" alt="screenshot">
    <div id="smLightboxTime"></div>
</div>
@endsection

@push('custom-scripts')
<script>
    function openLightbox(src, time) {
        document.getElementById('smLightboxImg').src = src;
        document.getElementById('smLightboxTime').textContent = time;
        document.getElementById('smLightbox').classList.add('show');
    }
    function closeLightbox() {
        document.getElementById('smLightbox').classList.remove('show');
        document.getElementById('smLightboxImg').src = '';
    }
    document.getElementById('smLightbox').addEventListener('click', function(e) {
        if (e.target === this) closeLightbox();
    });
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeLightbox();
    });

    function deleteShot(id) {
        if (!confirm('{{ __("Delete this screenshot?") }}')) return;
        fetch('/screen-monitor/' + id, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
        }).then(r => r.json()).then(data => {
            if (data.ok) {
                var el = document.getElementById('shot-' + id);
                if (el) el.remove();
            }
        });
    }
</script>
@endpush
