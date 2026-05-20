<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PolicyAcknowledgement extends Model
{
    protected $table = 'policy_acknowledgements';

    protected $fillable = [
        'policy_id',
        'user_id',
        'acknowledged_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'acknowledged_at' => 'datetime',
    ];

    public function policy()
    {
        return $this->belongsTo(Policy::class, 'policy_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
