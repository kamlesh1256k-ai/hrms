@extends('layouts.admin')
@section('page-title')
    {{ __('Create Timesheet') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('timesheet.index') }}">{{ __('Timesheet') }}</a></li>
    <li class="breadcrumb-item">{{ __('Create') }}</li>
@endsection

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5>{{ __('Create New Timesheet') }}</h5>
                </div>
                <div class="card-body">
                    {{ Form::open(['route' => ['timesheet.store'], 'class' => 'needs-validation', 'novalidate']) }}
                    <div class="row">
                        @if (\Auth::user()->type != 'employee')
                            <div class="form-group col-md-12">
                                {{ Form::label('employee_id', __('Employee'), ['class' => 'col-form-label']) }}<x-required></x-required>
                                {!! Form::select('employee_id', $employees, null, [
                                    'class' => 'form-control',
                                    'required' => 'required',
                                ]) !!}
                            </div>
                        @endif
                        <div class="form-group col-md-6">
                            {{ Form::label('date', __('Date'), ['class' => 'col-form-label']) }}<x-required></x-required>
                            {{ Form::date('date', date('Y-m-d'), ['class' => 'form-control', 'required' => 'required']) }}
                        </div>
                        <div class="form-group col-md-6">
                            {{ Form::label('hours', __('Hours'), ['class' => 'col-form-label']) }}<x-required></x-required>
                            {{ Form::number('hours', '', ['class' => 'form-control', 'required' => 'required', 'step' => '0.01', 'placeholder' => __('Enter hours')]) }}
                        </div>
                        <div class="form-group col-md-12">
                            {{ Form::label('remark', __('Remark'), ['class' => 'col-form-label']) }}
                            {!! Form::textarea('remark', null, ['class' => 'form-control', 'rows' => '3', 'placeholder' => __('Enter remark')]) !!}
                        </div>
                    </div>
                    <div class="mt-3 d-flex gap-2">
                        <input type="submit" value="{{ __('Create') }}" class="btn btn-primary">
                        <a href="{{ route('timesheet.index') }}" class="btn btn-light">{{ __('Cancel') }}</a>
                    </div>
                    {{ Form::close() }}
                </div>
            </div>
        </div>
    </div>
@endsection
