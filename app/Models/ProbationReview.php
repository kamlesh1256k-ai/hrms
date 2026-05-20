<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProbationReview extends Model
{
    protected $table = 'recruitment_probation_reviews';

    protected $fillable = [
        'employee_id', 'joined_on', 'day_milestone', 'review_date',
        'outcome', 'rating', 'strengths', 'improvements', 'manager_comments',
        'reviewer_user_id', 'created_by',
    ];

    protected $casts = [
        'joined_on'   => 'date',
        'review_date' => 'date',
    ];

    public static $milestones = [30, 60, 90];

    public static $outcomes = [
        'pending'           => 'Pending',
        'on_track'          => 'On Track',
        'needs_improvement' => 'Needs Improvement',
        'extend'            => 'Extend Probation',
        'confirm'           => 'Confirm Employment',
        'terminate'         => 'Terminate',
    ];

    public static $outcomeBadge = [
        'pending'           => 'secondary',
        'on_track'          => 'info',
        'needs_improvement' => 'warning',
        'extend'            => 'warning',
        'confirm'           => 'success',
        'terminate'         => 'danger',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_user_id');
    }
}
