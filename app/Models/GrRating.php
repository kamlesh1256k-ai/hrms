<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GrRating extends Model
{
    protected $fillable = [
        'cycle_id', 'employee_id', 'self_rating', 'manager_rating', 'head_rating',
        'final_rating', 'grade', 'calibration_category', 'is_calibrated', 'is_frozen',
        'calibration_notes', 'calibrated_by', 'frozen_at', 'created_by',
    ];

    protected $casts = ['frozen_at' => 'datetime', 'is_calibrated' => 'boolean', 'is_frozen' => 'boolean'];

    public function cycle()   { return $this->belongsTo(PerformanceCycle::class, 'cycle_id'); }
    public function employee(){ return $this->belongsTo(Employee::class, 'employee_id'); }
}
