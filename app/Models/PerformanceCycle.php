<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PerformanceCycle extends Model
{
    protected $fillable = [
        'name', 'start_date', 'end_date', 'goal_deadline',
        'self_review_start', 'self_review_end', 'manager_review_start', 'manager_review_end',
        'head_review_start', 'head_review_end', 'calibration_start', 'calibration_end',
        'status', 'rating_scale', 'settings_json', 'created_by',
    ];

    protected $casts = [
        'settings_json' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'goal_deadline' => 'date',
        'self_review_start' => 'date',
        'self_review_end' => 'date',
        'manager_review_start' => 'date',
        'manager_review_end' => 'date',
        'head_review_start' => 'date',
        'head_review_end' => 'date',
    ];

    public function assignedEmployees() { return $this->hasMany(GrCycleEmployee::class, 'cycle_id'); }
    public function missions()    { return $this->hasMany(GrMission::class, 'cycle_id'); }
    public function reviews()     { return $this->hasMany(GrReview::class, 'cycle_id'); }
    public function ratings()     { return $this->hasMany(GrRating::class, 'cycle_id'); }
    public function increments()  { return $this->hasMany(GrIncrement::class, 'cycle_id'); }
}
