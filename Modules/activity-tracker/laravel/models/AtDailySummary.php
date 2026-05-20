<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AtDailySummary extends Model
{
    protected $table = 'at_daily_summaries';

    protected $fillable = [
        'user_id', 'device_id', 'work_date',
        'active_seconds', 'idle_seconds', 'total_screenshots', 'most_used_app',
    ];

    protected $casts = [
        'work_date'         => 'date',
        'active_seconds'    => 'integer',
        'idle_seconds'      => 'integer',
        'total_screenshots' => 'integer',
    ];

    public function user()   { return $this->belongsTo(User::class, 'user_id'); }
    public function device() { return $this->belongsTo(AtDevice::class, 'device_id'); }
}
