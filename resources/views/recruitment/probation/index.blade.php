@extends('layouts.admin')

@section('page-title') {{ __('Probation & Confirmation') }} @endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('recruitment.dashboard') }}">{{ __('Recruitment') }}</a></li>
    <li class="breadcrumb-item">{{ __('Probation') }}</li>
@endsection

@section('content')
<div class="container-fluid">
    @include('recruitment._nav')

    <div class="card">
        <div class="card-header">
            <h6 class="mb-0"><i class="ti ti-user-check me-1 text-primary"></i>{{ __('Employees on Probation') }}</h6>
            <p class="text-muted small mb-0 mt-1">
                {{ __('Employees joined within last 6 months — track 30/60/90 day reviews and confirm/extend/terminate.') }}
            </p>
        </div>
        <div class="card-body p-0">
            @if($employees->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="ti ti-users" style="font-size:3rem;opacity:.4;"></i>
                    <p class="mt-2 mb-0">{{ __('No employees currently on probation.') }}</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('Employee') }}</th>
                                <th>{{ __('Designation') }}</th>
                                <th>{{ __('Joined') }}</th>
                                <th>{{ __('Days In') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($employees as $emp)
                                @php
                                    $days = $emp->company_doj ? \Carbon\Carbon::parse($emp->company_doj)->diffInDays(now()) : null;
                                @endphp
                                <tr>
                                    <td><strong>{{ $emp->name }}</strong><br><small class="text-muted">{{ $emp->employee_id ?? '—' }}</small></td>
                                    <td>{{ $emp->designation->name ?? '—' }}</td>
                                    <td>{{ $emp->company_doj ? \Carbon\Carbon::parse($emp->company_doj)->format('d M Y') : '—' }}</td>
                                    <td>{{ $days !== null ? $days.' days' : '—' }}</td>
                                    <td>
                                        <a href="{{ route('recruitment.probation.show', $emp->id) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="ti ti-eye me-1"></i>{{ __('Reviews') }}
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-3">{{ $employees->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
