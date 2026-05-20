<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GrComebackPlanReview extends Model
{
    protected $table = 'gr_comeback_plan_reviews';

    protected $fillable = [
        'plan_id', 'reviewer_id', 'review_date', 'progress', 'rating',
        'strengths', 'improvements', 'comments', 'created_by',
    ];

    protected $casts = [
        'review_date' => 'date',
        'rating' => 'integer',
    ];

    public function plan()     { return $this->belongsTo(GrComebackPlan::class, 'plan_id'); }
    public function reviewer() { return $this->belongsTo(Employee::class, 'reviewer_id'); }
}
