<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class AtScreenshot extends Model
{
    protected $table = 'at_screenshots';
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'device_id', 'image_path', 'active_app',
        'active_window_title', 'size_bytes', 'captured_at',
    ];

    protected $casts = [
        'captured_at' => 'datetime',
        'created_at'  => 'datetime',
        'size_bytes'  => 'integer',
    ];

    public function user()   { return $this->belongsTo(User::class, 'user_id'); }
    public function device() { return $this->belongsTo(AtDevice::class, 'device_id'); }

    /** Public URL for serving the image (assumes public disk + storage:link). */
    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->image_path);
    }
}
