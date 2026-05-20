@extends('layouts.admin')
@section('page-title')
    {{ __('IT Declaration Review') }}
@endsection

@section('content')
    <div class="card">
        <div class="card-header"><h5 class="mb-0">{{ __('Submitted Declarations') }}</h5></div>
        <div class="card-body table-border-style">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{ __('Employee') }}</th>
                            <th>{{ __('Financial Year') }}</th>
                            <th>{{ __('Regime') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($declarations as $d)
                        <tr>
                            <td>{{ $employees[$d->employee_id]->name ?? $d->employee_id }}</td>
                            <td>{{ $d->financial_year }}</td>
                            <td>{{ strtoupper($d->tax_regime) }}</td>
                            <td>{{ ucfirst($d->declaration_status) }}</td>
                            <td><a class="btn btn-sm btn-outline-primary" href="{{ route('it.declaration.review.show', $d->id) }}">{{ __('Review') }}</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5">{{ __('No declarations found.') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

