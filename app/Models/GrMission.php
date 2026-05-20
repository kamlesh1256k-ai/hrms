<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GrMission extends Model
{
    protected $fillable = [
        'cycle_id', 'employee_id', 'title', 'description', 'kpi', 'weightage',
        'deadline', 'status', 'approval', 'approved_by', 'approved_at',
        'manager_remarks', 'progress',
        'self_rating', 'self_remarks', 'manager_rating', 'manager_rating_remarks',
        'hod_rating', 'hod_rating_remarks', 'document', 'document_name', 'created_by',
    ];

    protected $casts = ['deadline' => 'date', 'approved_at' => 'datetime'];

    public function cycle()    { return $this->belongsTo(PerformanceCycle::class, 'cycle_id'); }
    public function employee() { return $this->belongsTo(Employee::class, 'employee_id'); }
    public function approver() { return $this->belongsTo(Employee::class, 'approved_by'); }
}
