<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecruitmentAssessment extends Model
{
    protected $table = 'recruitment_assessments';

    protected $fillable = [
        'candidate_id', 'assessment_type', 'title',
        'scheduled_on', 'completed_on',
        'max_score', 'score', 'passing_score',
        'outcome', 'feedback', 'document_path',
        'evaluator_user_id', 'created_by',
    ];

    protected $casts = [
        'scheduled_on' => 'date',
        'completed_on' => 'date',
    ];

    public static $types = [
        'aptitude'    => 'Aptitude Test',
        'technical'   => 'Technical Assignment',
        'case_study'  => 'Case Study',
        'coding'      => 'Coding Test',
        'personality' => 'Personality Test',
    ];

    /** Convenience: derive Pass / Fail / Pending label from score vs passing_score. */
    public function getPassFailAttribute(): string
    {
        if ($this->outcome === 'pending')   return 'Pending';
        if ($this->outcome === 'no_show')   return 'No Show';
        if ($this->score === null)           return 'Not Scored';
        return $this->score >= $this->passing_score ? 'Pass' : 'Fail';
    }

    public function getPassFailBadgeAttribute(): string
    {
        $pf = $this->pass_fail;
        return [
            'Pass'      => 'success',
            'Fail'      => 'danger',
            'Pending'   => 'secondary',
            'No Show'   => 'warning',
            'Not Scored'=> 'secondary',
        ][$pf] ?? 'secondary';
    }

    public static $outcomes = [
        'pending'   => 'Pending',
        'completed' => 'Completed',
        'cleared'   => 'Cleared',
        'rejected'  => 'Rejected',
        'no_show'   => 'No Show',
    ];

    public static $outcomeBadge = [
        'pending'   => 'secondary',
        'completed' => 'info',
        'cleared'   => 'success',
        'rejected'  => 'danger',
        'no_show'   => 'warning',
    ];

    public function candidate()
    {
        return $this->belongsTo(JobApplication::class, 'candidate_id');
    }

    public function evaluator()
    {
        return $this->belongsTo(User::class, 'evaluator_user_id');
    }

    public function getPercentageAttribute(): ?int
    {
        if ($this->score === null || $this->max_score < 1) return null;
        return (int) round(($this->score / $this->max_score) * 100);
    }
}
