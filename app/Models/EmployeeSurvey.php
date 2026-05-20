<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeSurvey extends Model
{
    protected $table = 'employee_surveys';

    protected $fillable = [
        'title',
        'description',
        'type',                 // employee | pulse | enps
        'start_date',
        'end_date',
        'status',               // draft | active | closed
        'is_anonymous',
        'department_ids',
        'audience_rules',
        'frequency',            // once | weekly | monthly | custom
        'last_sent_at',
        'created_by',
    ];

    protected $casts = [
        'is_anonymous'   => 'boolean',
        'department_ids' => 'array',
        'audience_rules' => 'array',
        'start_date'     => 'date',
        'end_date'       => 'date',
        'last_sent_at'   => 'datetime',
    ];

    public function questions()
    {
        return $this->hasMany(SurveyQuestion::class, 'survey_id')->orderBy('order_no');
    }

    public function responses()
    {
        $rel = $this->hasMany(SurveyResponse::class, 'survey_id');

        // Only count/return answer-bearing responses (excludes anonymous guard rows).
        // Guarded for backward-compatibility if the column isn't migrated yet.
        if (\Illuminate\Support\Facades\Schema::hasColumn('survey_responses', 'is_guard')) {
            $rel->where('is_guard', false);
        }

        return $rel;
    }

    public function alerts()
    {
        return $this->hasMany(SurveyAlert::class, 'survey_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Is the survey currently open for submissions? */
    public function isOpen(): bool
    {
        if ($this->status !== 'active') return false;
        $today = now()->toDateString();
        if ($this->start_date && $this->start_date->toDateString() > $today) return false;
        if ($this->end_date   && $this->end_date->toDateString()   < $today) return false;
        return true;
    }
}
