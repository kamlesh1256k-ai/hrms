@extends('layouts.admin')
@section('page-title') {{ __('User Activity') }} @endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('activity-tracker.index') }}">{{ __('Activity Tracker') }}</a></li>
    <li class="breadcrumb-item">{{ __('User Activity') }}</li>
@endsection

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
                                    <th>{{ __('App') }}</th>
                                    <th>{{ __('Window') }}</th>
                                    <th class="text-end">{{ __('Idle (s)') }}</th>
                                    <th class="text-end pe-3">{{ __('K / M') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                            @forelse($activity as $a)
                                <tr>
                                    <td class="ps-3"><small>{{ $a->captured_at->format('d M h:i A') }}</small></td>
                                    <td><strong>{{ $a->active_app ?: '—' }}</strong></td>
                                    <td class="small text-muted">{{ \Illuminate\Support\Str::limit($a->active_window_title, 50) }}</td>
                                    <td class="text-end">{{ $a->idle_seconds }}</td>
                                    <td class="text-end pe-3 small">{{ $a->keyboard_count }} / {{ $a->mouse_count }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">{{ __('No activity for the selected filter.') }}</td></tr>
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
@endsection
