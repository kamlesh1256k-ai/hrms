@extends('layouts.admin')
@section('page-title')
    {{ __('Edit Holiday') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('holiday.index') }}">{{ __('Holidays') }}</a></li>
    <li class="breadcrumb-item">{{ __('Edit') }}</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header"><h5>{{ __('Edit Holiday') }}</h5></div>
    <div class="card-body">
        <form action="{{ route('holiday.update', $holiday->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-md-6">
                    <label>{{ __('Title') }}</label>
                    <input type="text" name="title" class="form-control" value="{{ $holiday->title }}" required>
                </div>
                <div class="col-md-6">
                    <label>{{ __('Date') }}</label>
                    <input type="date" name="holiday_date" class="form-control" value="{{ $holiday->holiday_date }}" required>
                </div>

                <div class="col-md-6 mt-3">
                    <label>{{ __('Recurring') }}</label>
                    <select name="recurring" class="form-control">
                        <option value="0" {{ !$holiday->recurring ? 'selected' : '' }}>No</option>
                        <option value="1" {{ $holiday->recurring ? 'selected' : '' }}>Yes</option>
                    </select>
                </div>

                @if($settings && in_array($settings->holiday_scope, ['location', 'location_shift']))
                    <div class="col-md-6 mt-3">
                        <label>{{ __('Location') }}</label>
                        <select name="location_id" class="form-control">
                            <option value="">{{ __('All') }}</option>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}" {{ $holiday->location_id == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @if($settings && in_array($settings->holiday_scope, ['shift', 'location_shift']))
                    <div class="col-md-12 mt-3">
                        <label>{{ __('Shifts (select multiple for multi-shift holiday)') }}</label>
                        <select name="shifts[]" class="form-control select2" multiple>
                            @foreach($shifts as $s)
                                <option value="{{ $s->id }}" {{ in_array($s->id, $mappedShifts) ? 'selected' : '' }}>{{ $s->name ?? $s->shift_name ?? 'Shift '.$s->id }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="col-md-12 mt-3">
                    <label>{{ __('Description') }}</label>
                    <textarea name="description" class="form-control">{{ $holiday->description }}</textarea>
                </div>

                <div class="col-md-6 mt-3">
                    <label>{{ __('Status') }}</label>
                    <select name="status" class="form-control">
                        <option value="active" {{ $holiday->status=='active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ $holiday->status=='inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
            </div>
            <div class="mt-4">
                <button class="btn btn-primary">{{ __('Update') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
