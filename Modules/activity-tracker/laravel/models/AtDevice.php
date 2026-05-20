<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AtDevice extends Model
{
    protected $table = 'at_devices';

    protected $fillable = [
        'user_id', 'created_by', 'device_uuid', 'device_name',
        'os', 'ip_address', 'status', 'last_seen_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function activity()
    {
        return $this->hasMany(AtActivityLog::class, 'device_id');
    }

    public function screenshots()
    {
        return $this->hasMany(AtScreenshot::class, 'device_id');
    }

    /** "Online" = heartbeat in last 3 minutes. */
    public function isOnline(): bool
    {
        return $this->last_seen_at && $this->last_seen_at->gt(now()->subMinutes(3));
    }
}
