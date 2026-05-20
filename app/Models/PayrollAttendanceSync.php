<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollAttendanceSync extends Model
{
    protected $table = 'payroll_attendance_sync';

    protected $fillable = [
        'employee_id', 'month', 'working_days', 'present', 'half_day',
        'absent', 'leave', 'late_marks', 'early_marks', 'deduction_units',
        'early_half_day', 'policy_summary_json',
        'present_effective', 'leave_effective', 'absent_effective',
        'hd_deduction', 'weekly_offs', 'month_total_days',
        'details_json', 'synced_by', 'synced_at', 'created_by',
    ];

    protected $casts = [
        'details_json' => 'array',
        'policy_summary_json' => 'array',
        'synced_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
