<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AtStopRequest extends Model
{
    protected $fillable = ['user_id', 'device_id', 'status', 'reason', 'reviewed_by', 'reviewed_at'];

    protected $casts = ['reviewed_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function device()
    {
        return $this->belongsTo(AtDevice::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
