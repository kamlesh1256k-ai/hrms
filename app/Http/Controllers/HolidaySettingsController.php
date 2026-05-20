<?php

namespace App\Http\Controllers;

use App\Models\HolidaySetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HolidaySettingsController extends Controller
{
    public function show()
    {
        $settings = HolidaySetting::first();
        return view('setting.holiday_settings', compact('settings'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'holiday_scope' => 'required|in:company,location,shift,location_shift',
            'allow_multiple_holidays_same_date' => 'required|boolean',
            'weekend_holiday_rule' => 'required|in:ignore,carry_forward,comp_off',
            'leave_on_holiday_rule' => 'required|in:block,exclude,deduct',
            'exclude_holidays_from_leave_balance' => 'required|boolean',
            'attendance_on_holiday' => 'required|in:holiday,present,none',
            'ignore_late_entry' => 'required|boolean',
            'ignore_early_exit' => 'required|boolean',
            'ignore_monthly_late_counter' => 'required|boolean',
            'enable_optional_holidays' => 'required|boolean',
            'max_optional_holidays_per_year' => 'nullable|integer|min:0',
            'require_optional_holiday_approval' => 'required|boolean',
            'enable_recurring_holidays' => 'required|boolean',
            'recurring_type' => 'nullable|in:same_date,custom',
        ]);

        $settings = HolidaySetting::first();
        if (!$settings) {
            $settings = HolidaySetting::create($data);
        } else {
            $settings->update($data);
        }

        return redirect()->back()->with('success', __('Holiday settings saved'));
    }
}
