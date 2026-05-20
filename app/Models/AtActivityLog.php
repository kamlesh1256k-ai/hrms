<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AtActivityLog extends Model
{
    protected $table = 'at_activity_logs';
    public $timestamps = false;   // we only have created_at

    protected $fillable = [
        'user_id', 'device_id', 'active_app', 'active_window_title',
        'idle_seconds', 'keyboard_count', 'mouse_count', 'captured_at',
    ];

    protected $casts = [
        'captured_at'   => 'datetime',
        'created_at'    => 'datetime',
        'idle_seconds'  => 'integer',
        'keyboard_count'=> 'integer',
        'mouse_count'   => 'integer',
    ];

    public function user()   { return $this->belongsTo(User::class, 'user_id'); }
    public function device() { return $this->belongsTo(AtDevice::class, 'device_id'); }
}
