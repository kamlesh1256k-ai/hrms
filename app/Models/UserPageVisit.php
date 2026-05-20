<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPageVisit extends Model
{
    protected $fillable = [
        'user_id', 'tab_id', 'url', 'page_title',
        'started_at', 'last_seen_at',
        'duration_seconds', 'focus_seconds',
        'is_active', 'ip_address',
    ];

    protected $casts = [
        'started_at'    => 'datetime',
        'last_seen_at'  => 'datetime',
        'is_active'     => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Best-effort label for the visited page. Tries to extract a short
     * route segment from the URL when no page_title is available.
     */
    public function getDisplayLabelAttribute(): string
    {
        if ($this->page_title) return $this->page_title;
        $path = parse_url($this->url, PHP_URL_PATH) ?: '/';
        return trim($path, '/') ?: '/';
    }

    public function getDurationHumanAttribute(): string
    {
        $s = (int) $this->duration_seconds;
        if ($s < 60)   return $s . 's';
        if ($s < 3600) return floor($s / 60) . 'm ' . ($s % 60) . 's';
        $h = floor($s / 3600);
        $m = floor(($s % 3600) / 60);
        return $h . 'h ' . $m . 'm';
    }
}
