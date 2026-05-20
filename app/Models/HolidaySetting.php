<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HolidaySetting extends Model
{
    protected $fillable = [
        'holiday_scope',
        'allow_multiple_holidays_same_date',
        'weekend_holiday_rule',
        'leave_on_holiday_rule',
        'exclude_holidays_from_leave_balance',
        'attendance_on_holiday',
        'ignore_late_entry',
        'ignore_early_exit',
        'ignore_monthly_late_counter',
        'enable_optional_holidays',
        'max_optional_holidays_per_year',
        'require_optional_holiday_approval',
        'enable_recurring_holidays',
        'recurring_type',
    ];
}
