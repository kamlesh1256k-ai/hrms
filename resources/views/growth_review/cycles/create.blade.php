@extends('layouts.admin')
@section('page-title') {{ __('Create Performance Cycle') }} @endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('growth-review.cycles') }}">{{ __('Cycles') }}</a></li>
    <li class="breadcrumb-item">{{ __('Create') }}</li>
@endsection
@section('content')
    @include('growth_review._nav')
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('growth-review.cycles.store') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Cycle Name') }} <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. FY 2025-26 Annual Review" value="{{ old('name') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('Start Date') }} <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" class="form-control" required value="{{ old('start_date') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('End Date') }} <span class="text-danger">*</span></label>
                        <input type="date" name="end_date" class="form-control" required value="{{ old('end_date') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('Goal Deadline') }}</label>
                        <input type="date" name="goal_deadline" class="form-control" value="{{ old('goal_deadline') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('Rating Scale') }}</label>
                        <select name="rating_scale" class="form-control">
                            <option value="1-5">1 — 5</option>
                            <option value="1-10">1 — 10</option>
                        </select>
                    </div>
                    <div class="col-12"><hr><h6 class="text-muted">{{ __('Review Windows (Optional)') }}</h6></div>
                    <div class="col-md-3"><label class="form-label">{{ __('Self Review Start') }}</label><input type="date" name="self_review_start" class="form-control" value="{{ old('self_review_start') }}"></div>
                    <div class="col-md-3"><label class="form-label">{{ __('Self Review End') }}</label><input type="date" name="self_review_end" class="form-control" value="{{ old('self_review_end') }}"></div>
                    <div class="col-md-3"><label class="form-label">{{ __('Manager Review Start') }}</label><input type="date" name="manager_review_start" class="form-control" value="{{ old('manager_review_start') }}"></div>
                    <div class="col-md-3"><label class="form-label">{{ __('Manager Review End') }}</label><input type="date" name="manager_review_end" class="form-control" value="{{ old('manager_review_end') }}"></div>
                    <div class="col-md-3"><label class="form-label">{{ __('Head Review Start') }}</label><input type="date" name="head_review_start" class="form-control" value="{{ old('head_review_start') }}"></div>
                    <div class="col-md-3"><label class="form-label">{{ __('Head Review End') }}</label><input type="date" name="head_review_end" class="form-control" value="{{ old('head_review_end') }}"></div>
                    <div class="col-md-3"><label class="form-label">{{ __('Calibration Start') }}</label><input type="date" name="calibration_start" class="form-control" value="{{ old('calibration_start') }}"></div>
                    <div class="col-md-3"><label class="form-label">{{ __('Calibration End') }}</label><input type="date" name="calibration_end" class="form-control" value="{{ old('calibration_end') }}"></div>
                </div>
                <div class="mt-4">
                    <button class="btn btn-primary"><i class="ti ti-check me-1"></i>{{ __('Create Cycle') }}</button>
                    <a href="{{ route('growth-review.cycles') }}" class="btn btn-secondary ms-2">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
@endsection
