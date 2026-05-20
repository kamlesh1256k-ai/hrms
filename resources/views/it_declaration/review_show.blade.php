@extends('layouts.admin')
@section('page-title')
    {{ __('Review IT Declaration') }}
@endsection

@section('content')
    <div class="card mb-3">
        <div class="card-header"><h5 class="mb-0">{{ $employee->name ?? __('Employee') }} - {{ $declaration->financial_year }}</h5></div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3"><strong>{{ __('Regime') }}:</strong> {{ strtoupper($declaration->tax_regime) }}</div>
                <div class="col-md-3"><strong>{{ __('Status') }}:</strong> {{ ucfirst($declaration->declaration_status) }}</div>
                <div class="col-md-3"><strong>{{ __('Rent Paid') }}:</strong> {{ $declaration->rent_paid }}</div>
                <div class="col-md-3"><strong>{{ __('Home Loan Interest') }}:</strong> {{ $declaration->home_loan_interest }}</div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header"><h6 class="mb-0">{{ __('Investments') }}</h6></div>
                <div class="card-body">
                    @foreach($investments as $row)
                        <div class="mb-1">{{ $row->section_code }} - {{ $row->investment_type }}: {{ $row->amount }}</div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header"><h6 class="mb-0">{{ __('Exemptions') }}</h6></div>
                <div class="card-body">
                    @foreach($exemptions as $row)
                        <div class="mb-1">{{ $row->section_code }} - {{ $row->exemption_type }}: {{ $row->amount }}</div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header"><h6 class="mb-0">{{ __('Other Income') }}</h6></div>
                <div class="card-body">
                    @foreach($incomes as $row)
                        <div class="mb-1">{{ $row->income_type }}: {{ $row->amount }}</div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-body">
            <form method="POST" action="{{ route('it.declaration.review.action', $declaration->id) }}" class="d-flex gap-2 align-items-end">
                @csrf
                <div class="flex-fill">
                    <label class="form-label">{{ __('Remarks') }}</label>
                    <input type="text" class="form-control" name="remarks" value="{{ $declaration->remarks }}">
                </div>
                <button class="btn btn-success" name="status" value="approved">{{ __('Approve') }}</button>
                <button class="btn btn-danger" name="status" value="rejected">{{ __('Reject') }}</button>
            </form>
        </div>
    </div>
@endsection

