<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobApplication extends Model
{
    protected $fillable = [
        'job',
        'name',
        'email',
        'phone',
        'source',
        'source_detail',
        'recruiter_id',
        'profile',
        'resume',
        'cover_letter',
        'dob',
        'gender',
        'address',
        'country',
        'state',
        'city',
        'zip_code',
        'stage',
        'order',
        'skill',
        'rating',
        'final_status',
        'final_rank',
        'final_notes',
        'final_decided_by',
        'final_decided_at',
        'is_archive',
        'custom_question',
        'created_by',
    ];

    protected $casts = [
        'final_decided_at' => 'datetime',
    ];

    public static $finalStatuses = [
        'pending'  => 'Pending',
        'selected' => 'Selected',
        'backup'   => 'Backup',
        'rejected' => 'Rejected',
    ];

    public static $finalStatusBadge = [
        'pending'  => 'secondary',
        'selected' => 'success',
        'backup'   => 'warning',
        'rejected' => 'danger',
    ];

    public static $sources = [
        'naukri'    => 'Naukri',
        'linkedin'  => 'LinkedIn',
        'indeed'    => 'Indeed',
        'careers'   => 'Careers Page',
        'referral'  => 'Employee Referral',
        'agency'    => 'Recruitment Agency',
        'walkin'    => 'Walk-in',
        'other'     => 'Other',
    ];

    public function jobs()
    {
        return $this->hasOne('App\Models\Job', 'id', 'job');
    }

    public function recruiter()
    {
        return $this->belongsTo(\App\Models\User::class, 'recruiter_id');
    }

    public function bgvChecks()
    {
        return $this->hasMany(\App\Models\BgvCheck::class, 'candidate_id');
    }

    public function preonboardingItems()
    {
        return $this->hasMany(\App\Models\PreonboardingItem::class, 'candidate_id');
    }

    public function assessments()
    {
        return $this->hasMany(\App\Models\RecruitmentAssessment::class, 'candidate_id');
    }

    public function decisionNotes()
    {
        return $this->hasMany(\App\Models\DecisionNote::class, 'candidate_id')->latest();
    }

    public function finalDecidedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'final_decided_by');
    }

    public function interviews()
    {
        return $this->hasMany(\App\Models\InterviewSchedule::class, 'candidate', 'id')
            ->orderBy('date', 'asc')
            ->orderBy('time', 'asc');
    }

    public function offer()
    {
        return $this->hasOne(\App\Models\JobOnBoard::class, 'application', 'id')->orderByDesc('id');
    }
}
