<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GrCycleEmployee extends Model
{
    protected $fillable = [
        'cycle_id', 'employee_id', 'status', 'notified_at', 'goal_deadline', 'created_by',
    ];

    protected $casts = [
        'notified_at' => 'datetime',
        'goal_deadline' => 'date',
    ];

    public function cycle()   { return $this->belongsTo(PerformanceCycle::class, 'cycle_id'); }
    public function employee(){ return $this->belongsTo(Employee::class, 'employee_id'); }
}
