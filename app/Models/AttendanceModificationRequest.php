<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceModificationRequest extends Model
{
    protected $fillable = [
        'attendance_employee_id',
        'employee_id',
        'manager_employee_id',
        'requested_status',
        'requested_clock_in',
        'requested_clock_out',
        'reason',
        'status',
        'manager_comment',
        'reviewed_by',
        'reviewed_at',
        'created_by',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function attendance()
    {
        return $this->belongsTo(AttendanceEmployee::class, 'attendance_employee_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function manager()
    {
        return $this->belongsTo(Employee::class, 'manager_employee_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(Employee::class, 'reviewed_by');
    }
}
