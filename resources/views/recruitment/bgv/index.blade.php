@extends('layouts.admin')

@section('page-title') {{ __('Background Verification') }} @endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('recruitment.dashboard') }}">{{ __('Recruitment') }}</a></li>
    <li class="breadcrumb-item">{{ __('Background Verification') }}</li>
@endsection

@section('content')
<div class="container-fluid">
    @include('recruitment._nav')

    <div class="card">
        <div class="card-header">
            <h6 class="mb-0"><i class="ti ti-shield-check me-1 text-primary"></i>{{ __('Candidates in BGV') }}</h6>
            <p class="text-muted small mb-0 mt-1">
                {{ __('Initiate BGV from a candidate profile (job-application page) — Stage: BGV.') }}
            </p>
        </div>
        <div class="card-body p-0">
            @if($candidates->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="ti ti-shield" style="font-size:3rem;opacity:.4;"></i>
                    <p class="mt-2 mb-0">{{ __('No BGV checks initiated yet.') }}</p>
                    <small>{{ __('Open a candidate from Job Application kanban and click "Initiate BGV".') }}</small>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('Candidate') }}</th>
                                <th>{{ __('Job') }}</th>
                                <th>{{ __('Total Checks') }}</th>
                                <th>{{ __('Cleared') }}</th>
                                <th>{{ __('Pending / In Progress') }}</th>
                                <th>{{ __('Failed') }}</th>
                                <th>{{ __('Progress') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($candidates as $cand)
                                @php
                                    $total    = $cand->bgvChecks->count();
                                    $cleared  = $cand->bgvChecks->where('status','cleared')->count();
                                    $pending  = $cand->bgvChecks->whereIn('status',['pending','in_progress'])->count();
                                    $failed   = $cand->bgvChecks->where('status','failed')->count();
                                    $pct      = $total > 0 ? round(($cleared / $total) * 100) : 0;
                                @endphp
                                <tr>
                                    <td><strong>{{ $cand->name }}</strong><br><small class="text-muted">{{ $cand->email }}</small></td>
                                    <td>{{ $cand->jobs->title ?? '—' }}</td>
                                    <td>{{ $total }}</td>
                                    <td><span class="badge bg-success-subtle text-success">{{ $cleared }}</span></td>
                                    <td><span class="badge bg-warning-subtle text-warning">{{ $pending }}</span></td>
                                    <td><span class="badge bg-danger-subtle text-danger">{{ $failed }}</span></td>
                                    <td style="min-width:140px;">
                                        <div class="progress" style="height:6px;">
                                            <div class="progress-bar bg-success" style="width: {{ $pct }}%"></div>
                                        </div>
                                        <small class="text-muted">{{ $pct }}%</small>
                                    </td>
                                    <td>
                                        <a href="{{ route('recruitment.bgv.show', $cand->id) }}" class="btn btn-sm btn-outline-primary"><i class="ti ti-eye"></i></a>
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
