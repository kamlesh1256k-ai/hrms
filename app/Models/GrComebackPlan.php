<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GrComebackPlan extends Model
{
    protected $fillable = [
        'employee_id', 'assigned_by', 'cycle_id', 'increment_id', 'title', 'issues', 'action_steps',
        'start_date', 'end_date', 'status', 'auto_initiated', 'final_remarks',
        'final_outcome', 'outcome_decided_at', 'created_by',
    ];

    protected $casts = [
        'action_steps' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'auto_initiated' => 'boolean',
        'outcome_decided_at' => 'datetime',
    ];

    public function employee()   { return $this->belongsTo(Employee::class, 'employee_id'); }
    public function assignedBy() { return $this->belongsTo(Employee::class, 'assigned_by'); }
    public function cycle()      { return $this->belongsTo(PerformanceCycle::class, 'cycle_id'); }
    public function incrementRecord() { return $this->belongsTo(GrIncrement::class, 'increment_id'); }
    public function reviews()    { return $this->hasMany(GrComebackPlanReview::class, 'plan_id')->orderByDesc('review_date'); }
}
