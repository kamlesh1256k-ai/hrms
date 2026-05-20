<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SurveySentimentAnalysis extends Model
{
    protected $table = 'survey_sentiment_analysis';

    protected $fillable = [
        'answer_id',
        'sentiment',     // positive | neutral | negative
        'topic',         // array: salary, manager, workload, culture, growth, policy, benefits
        'emotion',       // happy | frustrated | stressed | motivated | neutral
        'risk_level',    // low | medium | high
        'hr_alert',
        'ai_summary',
    ];

    protected $casts = [
        'topic'    => 'array',
        'hr_alert' => 'boolean',
    ];

    public function answer()
    {
        return $this->belongsTo(SurveyAnswer::class, 'answer_id');
    }
}
