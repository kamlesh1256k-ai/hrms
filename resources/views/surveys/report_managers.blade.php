@extends('layouts.admin')
@section('page-title') {{ __('Manager-wise Team Summary') }} @endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('surveys.index') }}">{{ __('Surveys') }}</a></li>
    <li class="breadcrumb-item">{{ __('Manager-wise') }}</li>
@endsection

@push('css-page')
<style>
    .r-table th{font-size:.7rem;text-transform:uppercase;letter-spacing:.4px;color:#64748b;font-weight:600;background:#fafafa;}
    .pill{font-weight:700;padding:3px 10px;border-radius:20px;font-size:.72rem;}
    .pill-good{background:#dcfce7;color:#166534;}
    .pill-watch{background:#fef3c7;color:#b45309;}
    .pill-poor{background:#fee2e2;color:#991b1b;}
</style>
@endpush

@section('content')
    <div class="card mb-3">
        <div class="card-body py-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold mb-1">{{ __('Window') }}</label>
                    <select name="weeks" class="form-control form-control-sm" onchange="this.form.submit()">
                        @foreach([4,8,12,16,26] as $w)
                            <option value="{{ $w }}" {{ (int)$weeks === (int)$w ? 'selected' : '' }}>{{ __('Last :n weeks', ['n' => $w]) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-8 text-md-end">
                    <a href="{{ route('surveys.pulse') }}" class="btn btn-light btn-sm border">
                        <i class="ti ti-chart-line me-1"></i>{{ __('Pulse Trends') }}
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="mb-0"><i class="ti ti-users me-2"></i>{{ __('Manager-wise Team Summary') }}</h5>
                <small class="text-muted">{{ __('Pulse survey aggregates grouped by each employee’s manager (reporting manager/HOD/management).') }}</small>
            </div>
            <a href="{{ route('surveys.index') }}" class="btn btn-light btn-sm border"><i class="ti ti-arrow-left me-1"></i>{{ __('Back') }}</a>
        </div>
        <div class="card-body p-0">
            @if(empty($rows) || $rows->isEmpty())
                <div class="text-center py-5 text-muted">{{ __('No data found.') }}</div>
            @else
                <div class="table-responsive">
                    <table class="table r-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">{{ __('Manager') }}</th>
                                <th class="text-center">{{ __('Team Size') }}</th>
                                <th class="text-center">{{ __('Responses') }}</th>
                                <th class="text-center">{{ __('Low Ratings') }}</th>
                                <th class="pe-3 text-end">{{ __('Avg Pulse Score') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rows as $r)
                                @php
                                    $pill = $r['avg_score'] >= 4 ? 'pill-good' : ($r['avg_score'] >= 3 ? 'pill-watch' : 'pill-poor');
                                @endphp
                                <tr>
                                    <td class="ps-3">
                                        <div class="fw-semibold">{{ $r['manager_name'] }}</div>
                                        @if(!empty($r['manager_empid']))
                                            <small class="text-muted">{{ __('Employee ID') }}: {{ $r['manager_empid'] }}</small>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ $r['team_size'] }}</td>
                                    <td class="text-center">{{ $r['responses'] }}</td>
                                    <td class="text-center">{{ $r['low_ratings'] }}</td>
                                    <td class="pe-3 text-end"><span class="pill {{ $pill }}">{{ number_format($r['avg_score'], 2) }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection

