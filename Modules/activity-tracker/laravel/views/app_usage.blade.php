@extends('layouts.admin')
@section('page-title') {{ __('App Usage Report') }} @endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('activity-tracker.index') }}">{{ __('Activity Tracker') }}</a></li>
    <li class="breadcrumb-item">{{ __('App Usage') }}</li>
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

    <div class="card">
        <div class="card-header"><h6 class="mb-0"><i class="ti ti-apps me-1"></i>{{ __('App Usage Aggregates') }}</h6></div>
        <div class="card-body p-0">
            @if($rows->isEmpty())
                <div class="text-center text-muted py-5">
                    <i class="ti ti-database-off fs-1 opacity-25 d-block mb-2"></i>
                    {{ __('No app usage data for the filter.') }}
                </div>
            @else
                @php $max = $rows->max('total') ?: 1; @endphp
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead style="background:#fafafa;">
                            <tr style="font-size:.7rem;text-transform:uppercase;color:#64748b;">
                                <th class="ps-3">{{ __('App') }}</th>
                                <th>{{ __('Distribution') }}</th>
                                <th class="text-end">{{ __('Total') }}</th>
                                <th class="text-end pe-3">{{ __('Sessions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($rows as $r)
                            <tr>
                                <td class="ps-3"><strong>{{ $r->app_name }}</strong></td>
                                <td>
                                    <div style="height:8px;background:#f1f5f9;border-radius:4px;overflow:hidden;width:80%;">
                                        <div style="height:100%;width:{{ ($r->total / $max) * 100 }}%;background:linear-gradient(90deg,#6366f1,#8b5cf6);"></div>
                                    </div>
                                </td>
                                <td class="text-end"><strong>{{ intdiv($r->total, 3600) }}h {{ intdiv($r->total % 3600, 60) }}m</strong></td>
                                <td class="text-end pe-3 small text-muted">{{ $r->sessions }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-3 py-2">{{ $rows->links() }}</div>
            @endif
        </div>
    </div>
@endsection
