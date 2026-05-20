@extends('layouts.admin')
@section('page-title') {{ __('User Activity') }} @endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('activity-tracker.index') }}">{{ __('Activity Tracker') }}</a></li>
    <li class="breadcrumb-item">{{ __('User Activity') }}</li>
@endsection

@push('css-page')
<style>
    .ua-shot-tile{position:relative;border-radius:10px;overflow:hidden;background:#0f172a;aspect-ratio:16/10;cursor:zoom-in;}
    .ua-shot-tile img{width:100%;height:100%;object-fit:cover;transition:transform .25s;}
    .ua-shot-tile:hover img{transform:scale(1.05);}
    .ua-shot-tile .ua-shot-meta{position:absolute;left:0;right:0;bottom:0;padding:6px 8px;background:linear-gradient(transparent,rgba(0,0,0,.85));color:#fff;font-size:.66rem;line-height:1.3;}

    .ua-thumb{width:60px;height:38px;border-radius:6px;object-fit:cover;background:#0f172a;cursor:zoom-in;border:1px solid #e2e8f0;}
    .ua-thumb:hover{transform:scale(1.05);transition:.2s;}
    .ua-no-shot{width:60px;height:38px;border-radius:6px;background:#f1f5f9;display:inline-flex;align-items:center;justify-content:center;color:#cbd5e1;font-size:1rem;}

    /* Lightbox */
    .ua-lightbox{position:fixed;inset:0;background:rgba(0,0,0,.92);display:none;align-items:center;justify-content:center;z-index:9999;padding:30px;cursor:zoom-out;}
    .ua-lightbox.open{display:flex;}
    .ua-lightbox img{max-width:96vw;max-height:88vh;object-fit:contain;border-radius:8px;box-shadow:0 30px 80px rgba(0,0,0,.6);}
    .ua-lightbox .ua-lb-close{position:absolute;top:20px;right:24px;color:#fff;font-size:2rem;cursor:pointer;background:rgba(255,255,255,.1);width:42px;height:42px;border-radius:50%;display:flex;align-items:center;justify-content:center;}
    .ua-lightbox .ua-lb-meta{position:absolute;bottom:24px;left:0;right:0;text-align:center;color:rgba(255,255,255,.85);font-size:.85rem;}
</style>
@endpush

@section('content')
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small mb-1">{{ __('User') }}</label>
                    <select name="user_id" class="form-control form-control-sm">
                        <option value="">{{ __('All users') }}</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}" {{ (string) $userId === (string) $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">{{ __('From') }}</label>
                    <input type="date" name="from" value="{{ $fromDate }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">{{ __('To') }}</label>
                    <input type="date" name="to" value="{{ $toDate }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary btn-sm w-100"><i class="ti ti-filter me-1"></i>{{ __('Filter') }}</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Aggregates --}}
    <div class="row g-3 mb-3">
        <div class="col-md-3 col-6">
            <div class="card"><div class="card-body py-3">
                <small class="text-muted text-uppercase fw-bold" style="font-size:.7rem;">{{ __('Active Time') }}</small>
                <h4 class="mb-0 mt-1 text-success">{{ intdiv($agg->active_s ?? 0, 3600) }}h {{ intdiv(($agg->active_s ?? 0) % 3600, 60) }}m</h4>
            </div></div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card"><div class="card-body py-3">
                <small class="text-muted text-uppercase fw-bold" style="font-size:.7rem;">{{ __('Idle Time') }}</small>
                <h4 class="mb-0 mt-1 text-warning">{{ intdiv($agg->idle_s ?? 0, 3600) }}h {{ intdiv(($agg->idle_s ?? 0) % 3600, 60) }}m</h4>
            </div></div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card"><div class="card-body py-3">
                <small class="text-muted text-uppercase fw-bold" style="font-size:.7rem;">{{ __('Keyboard Events') }}</small>
                <h4 class="mb-0 mt-1">{{ number_format($agg->kb ?? 0) }}</h4>
            </div></div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card"><div class="card-body py-3">
                <small class="text-muted text-uppercase fw-bold" style="font-size:.7rem;">{{ __('Mouse Events') }}</small>
                <h4 class="mb-0 mt-1">{{ number_format($agg->mouse ?? 0) }}</h4>
            </div></div>
        </div>
    </div>

    @php
        // Build a captured_at timestamp -> screenshot map for fast lookup,
        // then for each activity sample we find the nearest screenshot
        // (within 5 minutes) and show its thumbnail inline.
        $shotsByTime = ($screenshots ?? collect())->keyBy(fn($s) => $s->captured_at->timestamp);
        $shotKeys    = $shotsByTime->keys()->sort()->values();
    @endphp

    {{-- ── Screenshot gallery (full-window, this user, this date range) ── --}}
    @if($screenshotsCount > 0)
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="ti ti-camera me-1"></i>{{ __('Screenshots') }}
                    <span class="badge bg-primary ms-2">{{ $screenshotsCount }}</span>
                </h6>
                <small class="text-muted">{{ __('Click any thumbnail to view full size') }}</small>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    @foreach($screenshots->take(24) as $s)
                        @php $url = asset('storage/app/public/' . $s->image_path); @endphp
                        <div class="col-lg-2 col-md-3 col-sm-4 col-6">
                            <div class="ua-shot-tile" onclick="uaShowLightbox('{{ $url }}', {{ json_encode(($s->user->name ?? '—') . ' · ' . $s->captured_at->format('d M Y · h:i A') . ' — ' . ($s->active_app ?: '')) }})">
                                <img src="{{ $url }}" loading="lazy" alt="screenshot">
                                <div class="ua-shot-meta">
                                    <strong style="color:#fff;background:rgba(0,0,0,0.55);padding:1px 5px;border-radius:3px;">{{ $s->user->name ?? '—' }}</strong><br>
                                    <strong>{{ $s->captured_at->format('h:i A') }}</strong><br>
                                    <small>{{ \Illuminate\Support\Str::limit($s->active_app ?: '', 18) }}</small>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                @if($screenshotsCount > 24)
                    <div class="text-center mt-3">
                        <a href="{{ route('activity-tracker.timeline', ['user_id' => $userId, 'date' => $fromDate]) }}" class="btn btn-light border btn-sm">
                            <i class="ti ti-photo me-1"></i>{{ __('View all :n screenshots', ['n' => $screenshotsCount]) }}
                        </a>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header"><h6 class="mb-0"><i class="ti ti-list me-1"></i>{{ __('Activity Samples') }}</h6></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead style="background:#fafafa;">
                                <tr style="font-size:.7rem;text-transform:uppercase;color:#64748b;">
                                    <th class="ps-3">{{ __('Time') }}</th>
                                    <th>{{ __('Screen') }}</th>
                                    <th>{{ __('App') }}</th>
                                    <th>{{ __('Window') }}</th>
                                    <th class="text-end">{{ __('Idle') }}</th>
                                    <th class="text-end pe-3">{{ __('K / M') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                            @forelse($activity as $a)
                                @php
                                    // Find nearest screenshot within 5 minutes of this sample
                                    $sampleTs = $a->captured_at->timestamp;
                                    $nearest = null;
                                    $nearestDiff = PHP_INT_MAX;
                                    foreach ($shotKeys as $ts) {
                                        $diff = abs($ts - $sampleTs);
                                        if ($diff < $nearestDiff && $diff <= 300) { // 300s = 5min
                                            $nearestDiff = $diff;
                                            $nearest = $shotsByTime[$ts];
                                        }
                                    }
                                    $nearestUrl = $nearest ? asset('storage/' . $nearest->image_path) : null;
                                @endphp
                                <tr>
                                    <td class="ps-3"><small>{{ $a->captured_at->format('d M h:i A') }}</small></td>
                                    <td>
                                        @if($nearestUrl)
                                            <img src="{{ $nearestUrl }}" class="ua-thumb"
                                                 onclick="uaShowLightbox('{{ $nearestUrl }}', {{ json_encode($nearest->captured_at->format('d M Y · h:i A') . ' — ' . ($nearest->active_app ?: '')) }})"
                                                 alt="screen">
                                        @else
                                            <span class="ua-no-shot" title="{{ __('No screenshot near this time') }}"><i class="ti ti-camera-off"></i></span>
                                        @endif
                                    </td>
                                    <td><strong>{{ $a->active_app ?: '—' }}</strong></td>
                                    <td class="small text-muted">{{ \Illuminate\Support\Str::limit($a->active_window_title, 40) }}</td>
                                    <td class="text-end small">{{ $a->idle_seconds }}s</td>
                                    <td class="text-end pe-3 small">{{ $a->keyboard_count }} / {{ $a->mouse_count }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-4">{{ __('No activity for the selected filter.') }}</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="px-3 py-2">{{ $activity->links() }}</div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card">
                <div class="card-header"><h6 class="mb-0"><i class="ti ti-apps me-1"></i>{{ __('App Usage in Window') }}</h6></div>
                <div class="card-body">
                    @if($appUsage->isEmpty())
                        <div class="text-muted small">{{ __('No app usage data.') }}</div>
                    @else
                        @php $max = $appUsage->max('total') ?: 1; @endphp
                        @foreach($appUsage as $a)
                            <div style="padding:8px 0;border-bottom:1px dashed #e2e8f0;">
                                <div class="d-flex justify-content-between"><strong class="small">{{ $a->app_name }}</strong><small class="text-muted">{{ intdiv($a->total, 60) }}m</small></div>
                                <div style="height:5px;background:#f1f5f9;border-radius:3px;margin-top:4px;overflow:hidden;">
                                    <div style="height:100%;width:{{ ($a->total / $max) * 100 }}%;background:linear-gradient(90deg,#6366f1,#8b5cf6);"></div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Lightbox overlay (shared across all clickable thumbnails) --}}
    <div class="ua-lightbox" id="uaLightbox" onclick="uaCloseLightbox(event)">
        <span class="ua-lb-close" onclick="uaCloseLightbox(event)">&times;</span>
        <img id="uaLightboxImg" src="" alt="">
        <div class="ua-lb-meta" id="uaLightboxMeta"></div>
    </div>
@endsection

@push('script-page')
<script>
function uaShowLightbox(url, meta) {
    const lb   = document.getElementById('uaLightbox');
    const img  = document.getElementById('uaLightboxImg');
    const mEl  = document.getElementById('uaLightboxMeta');
    img.src    = url;
    mEl.textContent = meta || '';
    lb.classList.add('open');
    document.body.style.overflow = 'hidden';
}
function uaCloseLightbox(e) {
    if (e && e.target && e.target.tagName === 'IMG' && e.target.id === 'uaLightboxImg') return;
    document.getElementById('uaLightbox').classList.remove('open');
    document.body.style.overflow = '';
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') uaCloseLightbox(); });
</script>
@endpush
