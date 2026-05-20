@extends('layouts.employee')
@section('page-title')
    {{ __('Leave Deduction History') }}
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h5>{{ __('Leave Deduction History') }}</h5>
    </div>
    <div class="card-body">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Deduction Units</th>
                </tr>
            </thead>
            <tbody>
                @foreach($deductions as $deduction)
                    <tr>
                        <td>{{ $deduction->date }}</td>
                        <td>{{ $deduction->deduction_units }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
