<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SurveyAlert extends Model
{
    protected $table = 'survey_alerts';

    protected $fillable = [
        'survey_id',
        'response_id',
        'employee_id',
        'alert_type',
        'risk_level',
        'message',
        'status',
        'created_by',
    ];

    public function survey()
    {
        return $this->belongsTo(EmployeeSurvey::class, 'survey_id');
    }

    public function response()
    {
        return $this->belongsTo(SurveyResponse::class, 'response_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
