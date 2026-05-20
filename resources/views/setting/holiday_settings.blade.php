@extends('layouts.admin')
@section('page-title')
    {{ __('Holiday Settings') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item">{{ __('Holiday Settings') }}</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h5>{{ __('Configure Holiday Settings') }}</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('holiday-settings.store') }}" method="POST">
            @csrf
            <div class="row">
                <div class="col-md-6">
                    <label>Holiday Scope</label>
                    <select name="holiday_scope" class="form-control">
                        <option value="company">Company Wide</option>
                        <option value="location">Location Based</option>
                        <option value="shift">Shift Based</option>
                        <option value="location_shift">Location + Shift Based</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label>Allow Multiple Holidays on Same Date</label>
                    <select name="allow_multiple_holidays_same_date" class="form-control">
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>
                <div class="col-md-6 mt-3">
                    <label>Weekend Holiday Rule</label>
                    <select name="weekend_holiday_rule" class="form-control">
                        <option value="ignore">Ignore the holiday</option>
                        <option value="carry_forward">Carry forward to next working day</option>
                        <option value="comp_off">Grant compensatory off</option>
                    </select>
                </div>
                <div class="col-md-6 mt-3">
                    <label>Leave on Holiday Rule</label>
                    <select name="leave_on_holiday_rule" class="form-control">
                        <option value="block">Block leave application</option>
                        <option value="exclude">Automatically exclude holiday from leave count</option>
                        <option value="deduct">Deduct leave anyway</option>
                    </select>
                </div>
                <div class="col-md-6 mt-3">
                    <label>Exclude Holidays from Leave Balance</label>
                    <select name="exclude_holidays_from_leave_balance" class="form-control">
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>
                <div class="col-md-6 mt-3">
                    <label>Attendance on Holiday</label>
                    <select name="attendance_on_holiday" class="form-control">
                        <option value="holiday">Holiday</option>
                        <option value="present">Present</option>
                        <option value="none">No attendance required</option>
                    </select>
                </div>
                <div class="col-md-6 mt-3">
                    <label>Ignore Late Entry Rules</label>
                    <select name="ignore_late_entry" class="form-control">
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>
                <div class="col-md-6 mt-3">
                    <label>Ignore Early Exit Rules</label>
                    <select name="ignore_early_exit" class="form-control">
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>
                <div class="col-md-6 mt-3">
                    <label>Do Not Increment Monthly Late Counter</label>
                    <select name="ignore_monthly_late_counter" class="form-control">
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>
                <div class="col-md-6 mt-3">
                    <label>Enable Optional Holidays</label>
                    <select name="enable_optional_holidays" class="form-control">
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>
                <div class="col-md-6 mt-3">
                    <label>Max Optional Holidays Per Year</label>
                    <input type="number" name="max_optional_holidays_per_year" class="form-control" min="0" value="0">
                </div>
                <div class="col-md-6 mt-3">
                    <label>Require Approval for Optional Holidays</label>
                    <select name="require_optional_holiday_approval" class="form-control">
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>
                <div class="col-md-6 mt-3">
                    <label>Enable Recurring Holidays</label>
                    <select name="enable_recurring_holidays" class="form-control">
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>
                <div class="col-md-6 mt-3">
                    <label>Recurring Type</label>
                    <select name="recurring_type" class="form-control">
                        <option value="same_date">Same date every year</option>
                        <option value="custom">Custom recurring rule</option>
                    </select>
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-primary">Save Settings</button>
            </div>
        </form>
    </div>
</div>
@endsection
