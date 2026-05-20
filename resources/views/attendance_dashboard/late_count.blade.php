@extends('layouts.employee')
@section('page-title')
    {{ __('Late Count Summary') }}
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h5>{{ __('Your Late Count This Month') }}</h5>
    </div>
    <div class="card-body">
        <p><strong>{{ $lateCount }}</strong> late entries this month.</p>
    </div>
</div>
@endsection
