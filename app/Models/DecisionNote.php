<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DecisionNote extends Model
{
    protected $table = 'recruitment_decision_notes';

    protected $fillable = [
        'candidate_id', 'user_id', 'note', 'created_by',
    ];

    public function candidate()
    {
        return $this->belongsTo(JobApplication::class, 'candidate_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
