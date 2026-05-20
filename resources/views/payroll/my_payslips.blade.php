@extends('layouts.admin')
@section('page-title', __('My Payslips'))

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item active">{{ __('My Payslips') }}</li>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="ti ti-file-invoice me-1"></i>{{ __('My Payslips') }}</h5>
                @if($payslips->isNotEmpty())
                    <span class="badge bg-primary">{{ $payslips->count() }} {{ __('slips') }}</span>
                @endif
            </div>
            <div class="card-body table-border-style p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('Month') }}</th>
                                <th class="text-end">{{ __('Gross Salary') }}</th>
                                <th class="text-end">{{ __('Deductions') }}</th>
                                <th class="text-end">{{ __('Net Salary') }}</th>
                                <th class="text-center">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($payslips as $slip)
                                <tr>
                                    <td><strong>{{ \Carbon\Carbon::parse($slip->month . '-01')->format('F Y') }}</strong></td>
                                    <td class="text-end">{{ \Auth::user()->priceFormat($slip->gross_salary) }}</td>
                                    <td class="text-end text-danger">{{ \Auth::user()->priceFormat($slip->total_deductions) }}</td>
                                    <td class="text-end"><strong>{{ \Auth::user()->priceFormat($slip->net_salary) }}</strong></td>
                                    <td class="text-center">
                                        <a href="{{ route('payroll.slip', $slip->id) }}" class="btn btn-sm btn-primary" target="_blank">
                                            <i class="ti ti-eye me-1"></i>{{ __('View') }}
                                        </a>
                                        <a href="{{ route('payroll.slip', $slip->id) }}?download=1" class="btn btn-sm btn-success">
                                            <i class="ti ti-download me-1"></i>{{ __('Download') }}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        <i class="ti ti-file-off" style="font-size:2rem;"></i>
                                        <p class="mt-2 mb-0">{{ __('No payslips available yet.') }}</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
