<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScreenMonitor extends Model
{
    protected $fillable = [
        'user_id',
        'screenshot_path',
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

    /**
     * Full public URL for the screenshot.
     */
    public function getScreenshotUrlAttribute(): string
    {
        // screenshot_path is relative to public/ (e.g. uploads/screen-monitors/12/file.jpg)
        return asset($this->screenshot_path);
    }
}
