@extends('layouts.admin')

@section('page-title') {{ __('Assessments') }} @endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('recruitment.dashboard') }}">{{ __('Recruitment') }}</a></li>
    <li class="breadcrumb-item">{{ __('Assessments') }}</li>
@endsection

@section('content')
<div class="container-fluid">
    @include('recruitment._nav')

    <div class="card">
        <div class="card-header">
            <h6 class="mb-0"><i class="ti ti-clipboard-text me-1 text-primary"></i>{{ __('Candidate Assessments') }}</h6>
            <p class="text-muted small mb-0 mt-1">{{ __('Schedule aptitude / technical / case-study tests and capture scorecards.') }}</p>
        </div>
        <div class="card-body p-0">
            @if($candidates->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="ti ti-clipboard-off" style="font-size:3rem;opacity:.4;"></i>
                    <p class="mt-2 mb-0">{{ __('No assessments scheduled yet.') }}</p>
                    <small>{{ __('Open a candidate and click "Schedule Assessment".') }}</small>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('Candidate') }}</th>
                                <th>{{ __('Job') }}</th>
                                <th>{{ __('Total') }}</th>
                                <th>{{ __('Cleared') }}</th>
                                <th>{{ __('Pending') }}</th>
                                <th>{{ __('Avg Score') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($candidates as $cand)
                                @php
                                    $total   = $cand->assessments->count();
                                    $cleared = $cand->assessments->where('outcome','cleared')->count();
                                    $pending = $cand->assessments->where('outcome','pending')->count();
                                    $scores  = $cand->assessments->filter(fn($a) => $a->score !== null && $a->max_score > 0)
                                                                  ->map(fn($a) => round(($a->score / $a->max_score) * 100));
                                    $avg     = $scores->count() ? round($scores->avg()) : null;
                                @endphp
                                <tr>
                                    <td><strong>{{ $cand->name }}</strong><br><small class="text-muted">{{ $cand->email }}</small></td>
                                    <td>{{ $cand->jobs->title ?? '—' }}</td>
                                    <td>{{ $total }}</td>
                                    <td><span class="badge bg-success-subtle text-success">{{ $cleared }}</span></td>
                                    <td><span class="badge bg-warning-subtle text-warning">{{ $pending }}</span></td>
                                    <td>{{ $avg !== null ? $avg.'%' : '—' }}</td>
                                    <td>
                                        <a href="{{ route('recruitment.assessments.show', $cand->id) }}" class="btn btn-sm btn-outline-primary"><i class="ti ti-eye"></i></a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-3">{{ $candidates->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
