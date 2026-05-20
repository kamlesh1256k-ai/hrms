<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceSetting extends Model
{
    protected $fillable = [
        'office_start_time',
        'office_end_time',
        'break_duration',
        'minimum_working_hours',
        'late_entry_grace_time',
        'early_exit_grace_time',
        'monthly_allowed_late_count',
        'late_rule_action', // 'half_day' or 'deduct_leave'
        'late_rule_leave_deduction_count', // e.g., 3
        'created_by',
    ];

    // Optionally, add casts for time fields
    protected $casts = [
        'office_start_time' => 'datetime:H:i',
        'office_end_time' => 'datetime:H:i',
        'break_duration' => 'integer', // minutes
        'minimum_working_hours' => 'integer', // minutes
        'late_entry_grace_time' => 'integer', // minutes
        'early_exit_grace_time' => 'integer', // minutes
        'monthly_allowed_late_count' => 'integer',
        'late_rule_leave_deduction_count' => 'integer',
    ];
}
