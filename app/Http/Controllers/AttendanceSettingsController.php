<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceSettingsController extends Controller
{
    public function index()
    {
        $settings = AttendanceSetting::where('created_by', Auth::id())->first();
        return view('attendance_settings.index', compact('settings'));
    }

    public function create()
    {
        return view('attendance_settings.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'office_start_time' => 'required',
            'office_end_time' => 'required',
            'break_duration' => 'required|integer|min:0',
            'minimum_working_hours' => 'required|integer|min:0',
            'late_entry_grace_time' => 'required|integer|min:0',
            'early_exit_grace_time' => 'required|integer|min:0',
            'monthly_allowed_late_count' => 'required|integer|min:0',
            'late_rule_action' => 'required|in:half_day,deduct_leave',
            'late_rule_leave_deduction_count' => 'required|integer|min:1',
        ]);
        $validated['created_by'] = Auth::id();
        AttendanceSetting::updateOrCreate(['created_by' => Auth::id()], $validated);
        return redirect()->route('attendance-settings.index')->with('success', 'Attendance settings updated successfully.');
    }

    public function edit()
    {
        $settings = AttendanceSetting::where('created_by', Auth::id())->first();
        return view('attendance_settings.create', compact('settings'));
    }
}
