<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GrReview extends Model
{
    protected $fillable = [
        'cycle_id', 'employee_id', 'review_type', 'reviewer_id',
        'rating', 'ratings_json', 'strengths', 'improvements', 'comments',
        'status', 'submitted_at', 'created_by',
    ];

    protected $casts = [
        'ratings_json' => 'array',
        'submitted_at' => 'datetime',
    ];

    public function cycle()   { return $this->belongsTo(PerformanceCycle::class, 'cycle_id'); }
    public function employee(){ return $this->belongsTo(Employee::class, 'employee_id'); }
    public function reviewer(){ return $this->belongsTo(Employee::class, 'reviewer_id'); }
}
