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
        <h5>{{ __('Attendance Settings') }}</h5>
    </div>
    <div class="card-body">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Setting</th>
                    <th>Value</th>
                </tr>
            </thead>
            <tbody>
                <tr><td>Office Start Time</td><td>{{ $settings->office_start_time ?? '-' }}</td></tr>
                <tr><td>Office End Time</td><td>{{ $settings->office_end_time ?? '-' }}</td></tr>
                <tr><td>Break Duration</td><td>{{ $settings->break_duration ?? '-' }} minutes</td></tr>
                <tr><td>Minimum Working Hours</td><td>{{ $settings->minimum_working_hours ?? '-' }} minutes</td></tr>
                <tr><td>Late Entry Grace Time</td><td>{{ $settings->late_entry_grace_time ?? '-' }} minutes</td></tr>
                <tr><td>Early Exit Grace Time</td><td>{{ $settings->early_exit_grace_time ?? '-' }} minutes</td></tr>
                <tr><td>Monthly Allowed Late Count</td><td>{{ $settings->monthly_allowed_late_count ?? '-' }}</td></tr>
                <tr><td>Late Rule Action</td><td>{{ $settings->late_rule_action ?? '-' }}</td></tr>
                <tr><td>Late Rule Leave Deduction Count</td><td>{{ $settings->late_rule_leave_deduction_count ?? '-' }}</td></tr>
            </tbody>
        </table>
        <a href="{{ route('attendance-settings.edit') }}" class="btn btn-primary mt-3">Edit Settings</a>
    </div>
</div>
@endsection
