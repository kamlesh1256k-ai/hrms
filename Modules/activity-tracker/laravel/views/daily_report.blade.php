@extends('layouts.admin')
@section('page-title') {{ __('Daily Report') }} @endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('activity-tracker.index') }}">{{ __('Activity Tracker') }}</a></li>
    <li class="breadcrumb-item">{{ __('Daily Report') }}</li>
@endsection

@section('content')
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small mb-1">{{ __('From') }}</label>
                    <input type="date" name="from" value="{{ $fromDate }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-4">
                    <label class="form-label small mb-1">{{ __('To') }}</label>
                    <input type="date" name="to" value="{{ $toDate }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button class="btn btn-primary btn-sm flex-grow-1"><i class="ti ti-filter me-1"></i>{{ __('Filter') }}</button>
                    <a href="{{ route('activity-tracker.daily-report.csv', ['from' => $fromDate, 'to' => $toDate]) }}" class="btn btn-success btn-sm">
                        <i class="ti ti-download me-1"></i>{{ __('Export CSV') }}
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            @if($rows->isEmpty())
                <div class="text-center text-muted py-5">
                    <i class="ti ti-database-off fs-1 opacity-25 d-block mb-2"></i>
                    {{ __('No data for the selected range.') }}
                </div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead style="background:#fafafa;">
                            <tr style="font-size:.7rem;text-transform:uppercase;color:#64748b;">
                                <th class="ps-3">{{ __('Date') }}</th>
                                <th>{{ __('User') }}</th>
                                <th>{{ __('Device') }}</th>
                                <th class="text-end">{{ __('Active') }}</th>
                                <th class="text-end">{{ __('Idle') }}</th>
                                <th class="text-end">{{ __('Shots') }}</th>
                                <th class="pe-3">{{ __('Most Used App') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($rows as $r)
                            <tr>
                                <td class="ps-3 small">{{ \Carbon\Carbon::parse($r->work_date)->format('d M Y') }}</td>
                                <td><strong>{{ $r->user_name }}</strong></td>
                                <td class="small text-muted">{{ $r->device_name }}</td>
                                <td class="text-end text-success"><strong>{{ intdiv($r->active_s, 3600) }}h {{ intdiv($r->active_s % 3600, 60) }}m</strong></td>
                                <td class="text-end text-warning">{{ intdiv($r->idle_s, 3600) }}h {{ intdiv($r->idle_s % 3600, 60) }}m</td>
                                <td class="text-end">{{ $r->shots }}</td>
                                <td class="pe-3 small">{{ $r->most_used_app ?: '—' }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
