<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentActivity extends Model
{
    protected $fillable = [
        'user_id',
        'active_seconds',
        'idle_seconds',
        'keystrokes',
        'mouse_events',
        'active_window',
        'active_app',
        'active_url',
        'productivity_score',
        'hostname',
        'ip_address',
        'captured_at',
    ];

    protected $casts = [
        'captured_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
