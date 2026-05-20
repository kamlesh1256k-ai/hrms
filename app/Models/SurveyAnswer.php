<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SurveyAnswer extends Model
{
    protected $table = 'survey_answers';

    protected $fillable = [
        'response_id',
        'question_id',
        'answer_value',
        'rating_value',
        'text_value',
    ];

    protected $casts = [
        'rating_value' => 'float',
    ];

    public function response()
    {
        return $this->belongsTo(SurveyResponse::class, 'response_id');
    }

    public function question()
    {
        return $this->belongsTo(SurveyQuestion::class, 'question_id');
    }

    public function sentiment()
    {
        return $this->hasOne(SurveySentimentAnalysis::class, 'answer_id');
    }
}
