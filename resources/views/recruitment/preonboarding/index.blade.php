@extends('layouts.admin')

@section('page-title') {{ __('Pre-Onboarding') }} @endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('recruitment.dashboard') }}">{{ __('Recruitment') }}</a></li>
    <li class="breadcrumb-item">{{ __('Pre-Onboarding') }}</li>
@endsection

@section('content')
<div class="container-fluid">
    @include('recruitment._nav')

    <div class="card">
        <div class="card-header">
            <h6 class="mb-0"><i class="ti ti-checklist me-1 text-primary"></i>{{ __('Candidates in Pre-Onboarding') }}</h6>
            <p class="text-muted small mb-0 mt-1">
                {{ __('After offer acceptance, initiate the document & asset checklist to prepare for joining day.') }}
            </p>
        </div>
        <div class="card-body p-0">
            @if($candidates->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="ti ti-clipboard-off" style="font-size:3rem;opacity:.4;"></i>
                    <p class="mt-2 mb-0">{{ __('No pre-onboarding checklists yet.') }}</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('Candidate') }}</th>
                                <th>{{ __('Job') }}</th>
                                <th>{{ __('Total') }}</th>
                                <th>{{ __('Completed') }}</th>
                                <th>{{ __('Pending') }}</th>
                                <th>{{ __('Progress') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($candidates as $cand)
                                @php
                                    $total     = $cand->preonboardingItems->count();
                                    $done      = $cand->preonboardingItems->whereIn('status', ['completed','waived'])->count();
                                    $pending   = $cand->preonboardingItems->where('status','pending')->count();
                                    $pct       = $total > 0 ? round(($done/$total)*100) : 0;
                                @endphp
                                <tr>
                                    <td><strong>{{ $cand->name }}</strong><br><small class="text-muted">{{ $cand->email }}</small></td>
                                    <td>{{ $cand->jobs->title ?? '—' }}</td>
                                    <td>{{ $total }}</td>
                                    <td><span class="badge bg-success-subtle text-success">{{ $done }}</span></td>
                                    <td><span class="badge bg-warning-subtle text-warning">{{ $pending }}</span></td>
                                    <td style="min-width:140px;">
                                        <div class="progress" style="height:6px;">
                                            <div class="progress-bar bg-success" style="width: {{ $pct }}%"></div>
                                        </div>
                                        <small class="text-muted">{{ $pct }}%</small>
                                    </td>
                                    <td>
                                        <a href="{{ route('recruitment.preonboarding.show', $cand->id) }}" class="btn btn-sm btn-outline-primary"><i class="ti ti-eye"></i></a>
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
