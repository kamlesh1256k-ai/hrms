@extends('layouts.admin')
@section('page-title') {{ __('Screenshot Timeline') }} @endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('activity-tracker.index') }}">{{ __('Activity Tracker') }}</a></li>
    <li class="breadcrumb-item">{{ __('Timeline') }}</li>
@endsection

@push('css-page')
<style>
    .at-shot-tile{position:relative;border-radius:10px;overflow:hidden;background:#0f172a;aspect-ratio:16/10;}
    .at-shot-tile img{width:100%;height:100%;object-fit:cover;transition:transform .3s;}
    .at-shot-tile:hover img{transform:scale(1.04);}
    .at-shot-tile .at-shot-meta{position:absolute;left:0;right:0;bottom:0;padding:8px 10px;background:linear-gradient(transparent,rgba(0,0,0,.85));color:#fff;font-size:.7rem;}
</style>
@endpush

@section('content')
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small mb-1">{{ __('User') }}</label>
                    <select name="user_id" class="form-control form-control-sm">
                        <option value="">{{ __('All users') }}</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}" {{ (string) $userId === (string) $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label small mb-1">{{ __('Date') }}</label>
                    <input type="date" name="date" value="{{ $date }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary btn-sm w-100"><i class="ti ti-filter me-1"></i>{{ __('Filter') }}</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            @if($shots->isEmpty())
                <div class="text-center text-muted py-5">
                    <i class="ti ti-camera-off fs-1 opacity-25 d-block mb-2"></i>
                    {{ __('No screenshots for the selected day.') }}
                </div>
            @else
                <div class="row g-3">
                    @foreach($shots as $s)
                        <div class="col-lg-3 col-md-4 col-sm-6">
                            <a href="{{ \Storage::disk('public')->url($s->image_path) }}" target="_blank" class="text-decoration-none">
                                <div class="at-shot-tile">
                                    <img src="{{ \Storage::disk('public')->url($s->image_path) }}" alt="screenshot" loading="lazy">
                                    <div class="at-shot-meta">
                                        <strong>{{ optional($s->user)->name ?? '—' }}</strong> · {{ $s->captured_at->format('h:i A') }}<br>
                                        <small>{{ \Illuminate\Support\Str::limit($s->active_app . ' — ' . ($s->active_window_title ?: ''), 60) }}</small>
                                    </div>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3">
                    <small class="text-muted">{{ __('Showing :from to :to of :total', ['from' => $shots->firstItem(), 'to' => $shots->lastItem(), 'total' => $shots->total()]) }}</small>
                    {{ $shots->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
