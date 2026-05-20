@extends('layouts.admin')
@section('page-title')
    {{ __('Attendance Settings') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item">{{ __('Attendance Settings') }}</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h5>{{ __('Configure Attendance Settings') }}</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('attendance-settings.store') }}" method="POST">
            @csrf
            <div class="row">
                <div class="col-md-6">
                    <label>Office Start Time</label>
                    <input type="time" name="office_start_time" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label>Office End Time</label>
                    <input type="time" name="office_end_time" class="form-control" required>
                </div>
                <div class="col-md-6 mt-3">
                    <label>Break Duration (minutes)</label>
                    <input type="number" name="break_duration" class="form-control" min="0" required>
                </div>
                <div class="col-md-6 mt-3">
                    <label>Minimum Working Hours (minutes)</label>
                    <input type="number" name="minimum_working_hours" class="form-control" min="0" required>
                </div>
                <div class="col-md-6 mt-3">
                    <label>Late Entry Grace Time (minutes)</label>
                    <input type="number" name="late_entry_grace_time" class="form-control" min="0" required>
                </div>
                <div class="col-md-6 mt-3">
                    <label>Early Exit Grace Time (minutes)</label>
                    <input type="number" name="early_exit_grace_time" class="form-control" min="0" required>
                </div>
                <div class="col-md-6 mt-3">
                    <label>Monthly Allowed Late Count</label>
                    <input type="number" name="monthly_allowed_late_count" class="form-control" min="0" required>
                </div>
                <div class="col-md-6 mt-3">
                    <label>Late Rule Action</label>
                    <select name="late_rule_action" class="form-control">
                        <option value="half_day">Convert to Half Day</option>
                        <option value="deduct_leave">Deduct Leave</option>
                    </select>
                </div>
                <div class="col-md-6 mt-3">
                    <label>Late Rule Leave Deduction Count</label>
                    <input type="number" name="late_rule_leave_deduction_count" class="form-control" min="1" value="3" required>
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-primary">Save Settings</button>
            </div>
        </form>
    </div>
</div>
@endsection
