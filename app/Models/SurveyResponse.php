<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SurveyResponse extends Model
{
    protected $table = 'survey_responses';

    protected $fillable = [
        'survey_id',
        'employee_id',
        'is_anonymous',
        'is_guard',
        'submitted_at',
    ];

    protected $casts = [
        'is_anonymous' => 'boolean',
        'is_guard'     => 'boolean',
        'submitted_at' => 'datetime',
    ];

    public function survey()
    {
        return $this->belongsTo(EmployeeSurvey::class, 'survey_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function answers()
    {
        return $this->hasMany(SurveyAnswer::class, 'response_id');
    }
}
