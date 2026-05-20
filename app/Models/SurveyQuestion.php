<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SurveyQuestion extends Model
{
    protected $table = 'survey_questions';

    protected $fillable = [
        'survey_id',
        'question_text',
        'question_type',   // rating_5 | rating_10 | yes_no | multiple_choice | text
        'options',
        'is_required',
        'is_enps',
        'order_no',
    ];

    protected $casts = [
        'options'     => 'array',
        'is_required' => 'boolean',
        'is_enps'     => 'boolean',
    ];

    public function survey()
    {
        return $this->belongsTo(EmployeeSurvey::class, 'survey_id');
    }

    public function answers()
    {
        return $this->hasMany(SurveyAnswer::class, 'question_id');
    }

    public function isRating(): bool
    {
        return in_array($this->question_type, ['rating_5', 'rating_10'], true);
    }

    public function ratingMax(): int
    {
        return $this->question_type === 'rating_10' ? 10 : 5;
    }
}
