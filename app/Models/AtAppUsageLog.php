<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AtAppUsageLog extends Model
{
    protected $table = 'at_app_usage_logs';
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'device_id', 'app_name', 'window_title',
        'duration_seconds', 'started_at', 'ended_at',
    ];

    protected $casts = [
        'started_at'        => 'datetime',
        'ended_at'          => 'datetime',
        'created_at'        => 'datetime',
        'duration_seconds'  => 'integer',
    ];

    public function user()   { return $this->belongsTo(User::class, 'user_id'); }
    public function device() { return $this->belongsTo(AtDevice::class, 'device_id'); }
}
