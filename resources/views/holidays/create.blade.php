@extends('layouts.admin')
@section('page-title')
    {{ __('Create Holiday') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('holiday.index') }}">{{ __('Holidays') }}</a></li>
    <li class="breadcrumb-item">{{ __('Create') }}</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header"><h5>{{ __('Create Holiday') }}</h5></div>
    <div class="card-body">
        <form action="{{ route('holiday.store') }}" method="POST">
            @csrf
            <div class="row">
                <div class="col-md-6">
                    <label>{{ __('Title') }}</label>
                    <input type="text" name="title" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label>{{ __('Date') }}</label>
                    <input type="date" name="holiday_date" class="form-control" required>
                </div>

                <div class="col-md-6 mt-3">
                    <label>{{ __('Recurring') }}</label>
                    <select name="recurring" class="form-control">
                        <option value="0">No</option>
                        <option value="1">Yes</option>
                    </select>
                </div>

                @if($settings && in_array($settings->holiday_scope, ['location', 'location_shift']))
                    <div class="col-md-6 mt-3">
                        <label>{{ __('Location') }}</label>
                        <select name="location_id" class="form-control">
                            <option value="">{{ __('All') }}</option>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @if($settings && in_array($settings->holiday_scope, ['shift', 'location_shift']))
                    <div class="col-md-12 mt-3">
                        <label>{{ __('Shifts (select multiple for multi-shift holiday)') }}</label>
                        <select name="shifts[]" class="form-control select2" multiple>
                            @foreach($shifts as $s)
                                <option value="{{ $s->id }}">{{ $s->name ?? $s->shift_name ?? 'Shift '.$s->id }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="col-md-12 mt-3">
                    <label>{{ __('Description') }}</label>
                    <textarea name="description" class="form-control"></textarea>
                </div>

                <div class="col-md-6 mt-3">
                    <label>{{ __('Status') }}</label>
                    <select name="status" class="form-control">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="mt-4">
                <button class="btn btn-primary">{{ __('Save') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
