@extends('layouts.admin')
@section('page-title') {{ __('Department-wise Survey Result') }} @endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('surveys.index') }}">{{ __('Surveys') }}</a></li>
    <li class="breadcrumb-item">{{ __('Department-wise') }}</li>
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
                <div class="col-md-5">
                    <label class="form-label small fw-semibold mb-1">{{ __('Survey scope') }}</label>
                    <select name="survey_id" class="form-control form-control-sm" onchange="this.form.submit()">
                        <option value="">{{ __('All surveys (rating 1–5 questions)') }}</option>
                        @foreach($surveys as $sv)
                            <option value="{{ $sv->id }}" {{ (int)($selectedSurvey ?? 0) === (int)$sv->id ? 'selected' : '' }}>
                                {{ $sv->title }} <small>· {{ ucfirst($sv->type) }} · {{ ucfirst($sv->status) }}</small>
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">{{ __('From') }}</label>
                    <input type="date" name="from" value="{{ $from }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">{{ __('To') }}</label>
                    <input type="date" name="to" value="{{ $to }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button class="btn btn-primary btn-sm flex-grow-1"><i class="ti ti-filter me-1"></i>{{ __('Apply') }}</button>
                    <a href="{{ route('surveys.reports.departments') }}" class="btn btn-light btn-sm border" title="{{ __('Clear') }}"><i class="ti ti-x"></i></a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="mb-0"><i class="ti ti-building me-2"></i>{{ __('Department-wise Result') }}</h5>
                <small class="text-muted">{{ __('Average rating across rating (1–5) questions.') }}</small>
            </div>
            <a href="{{ route('surveys.index') }}" class="btn btn-light btn-sm border"><i class="ti ti-arrow-left me-1"></i>{{ __('Back') }}</a>
        </div>
        <div class="card-body p-0">
            @if(empty($rows) || $rows->isEmpty())
                <div class="text-center py-5 text-muted">{{ __('No data found for the selected filters.') }}</div>
            @else
                <div class="table-responsive">
                    <table class="table r-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">{{ __('Department') }}</th>
                                <th class="text-center">{{ __('Responses') }}</th>
                                <th class="text-center">{{ __('Low Ratings') }}</th>
                                <th class="pe-3 text-end">{{ __('Avg Score') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rows as $r)
                                @php
                                    $pill = $r['avg_score'] >= 4 ? 'pill-good' : ($r['avg_score'] >= 3 ? 'pill-watch' : 'pill-poor');
                                @endphp
                                <tr>
                                    <td class="ps-3"><strong>{{ $r['department_name'] }}</strong></td>
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

