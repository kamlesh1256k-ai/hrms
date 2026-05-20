@extends('layouts.admin')
@section('page-title') {{ __('Edit Cycle') }} — {{ $cycle->name }} @endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('growth-review.cycles') }}">{{ __('Cycles') }}</a></li>
    <li class="breadcrumb-item">{{ __('Edit') }}</li>
@endsection
@section('content')
    @include('growth_review._nav')
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('growth-review.cycles.update', $cycle->id) }}">
                @csrf @method('PUT')
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Cycle Name') }} <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required value="{{ $cycle->name }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">{{ __('Status') }}</label>
                        <select name="status" class="form-control">
                            @foreach(['draft','active','review','calibration','completed'] as $s)
                            <option value="{{ $s }}" {{ $cycle->status === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('Start Date') }} <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" class="form-control" required value="{{ $cycle->start_date->format('Y-m-d') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('End Date') }} <span class="text-danger">*</span></label>
                        <input type="date" name="end_date" class="form-control" required value="{{ $cycle->end_date->format('Y-m-d') }}">
                    </div>
                    <div class="col-md-3"><label class="form-label">{{ __('Goal Deadline') }}</label><input type="date" name="goal_deadline" class="form-control" value="{{ $cycle->goal_deadline }}"></div>
                    <div class="col-md-3"><label class="form-label">{{ __('Rating Scale') }}</label><select name="rating_scale" class="form-control"><option value="1-5" {{ $cycle->rating_scale=='1-5'?'selected':'' }}>1-5</option><option value="1-10" {{ $cycle->rating_scale=='1-10'?'selected':'' }}>1-10</option></select></div>
                    <div class="col-12"><hr><h6 class="text-muted">{{ __('Review Windows') }}</h6></div>
                    <div class="col-md-3"><label class="form-label">{{ __('Self Review Start') }}</label><input type="date" name="self_review_start" class="form-control" value="{{ $cycle->self_review_start }}"></div>
                    <div class="col-md-3"><label class="form-label">{{ __('Self Review End') }}</label><input type="date" name="self_review_end" class="form-control" value="{{ $cycle->self_review_end }}"></div>
                    <div class="col-md-3"><label class="form-label">{{ __('Manager Review Start') }}</label><input type="date" name="manager_review_start" class="form-control" value="{{ $cycle->manager_review_start }}"></div>
                    <div class="col-md-3"><label class="form-label">{{ __('Manager Review End') }}</label><input type="date" name="manager_review_end" class="form-control" value="{{ $cycle->manager_review_end }}"></div>
                    <div class="col-md-3"><label class="form-label">{{ __('Head Review Start') }}</label><input type="date" name="head_review_start" class="form-control" value="{{ $cycle->head_review_start }}"></div>
                    <div class="col-md-3"><label class="form-label">{{ __('Head Review End') }}</label><input type="date" name="head_review_end" class="form-control" value="{{ $cycle->head_review_end }}"></div>
                    <div class="col-md-3"><label class="form-label">{{ __('Calibration Start') }}</label><input type="date" name="calibration_start" class="form-control" value="{{ $cycle->calibration_start }}"></div>
                    <div class="col-md-3"><label class="form-label">{{ __('Calibration End') }}</label><input type="date" name="calibration_end" class="form-control" value="{{ $cycle->calibration_end }}"></div>
                </div>
                <div class="mt-4">
                    <button class="btn btn-primary"><i class="ti ti-check me-1"></i>{{ __('Update Cycle') }}</button>
                    <a href="{{ route('growth-review.cycles') }}" class="btn btn-secondary ms-2">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
@endsection
