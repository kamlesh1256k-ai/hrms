<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BackgroundScreenshot extends Model
{
    protected $fillable = [
        'user_id',
        'screenshot_path',
        'page_url',
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

    public function getScreenshotUrlAttribute(): string
    {
        return asset($this->screenshot_path);
    }
}
