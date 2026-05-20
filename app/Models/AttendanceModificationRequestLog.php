<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceModificationRequestLog extends Model
{
    protected $fillable = [
        'attendance_modification_request_id',
        'attendance_employee_id',
        'employee_id',
        'manager_employee_id',
        'action',
        'old_snapshot',
        'new_snapshot',
        'remarks',
        'created_by',
    ];

    protected $casts = [
        'old_snapshot' => 'array',
        'new_snapshot' => 'array',
    ];

    public function swipeRequest()
    {
        return $this->belongsTo(AttendanceModificationRequest::class, 'attendance_modification_request_id');
    }
}
