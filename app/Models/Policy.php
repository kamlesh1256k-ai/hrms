<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Policy extends Model
{
    protected $table = 'policies';

    /** Standard categories surfaced in the upload form (free-string in DB). */
    public const CATEGORIES = [
        'hr'      => 'HR',
        'leave'   => 'Leave',
        'it'      => 'IT',
        'conduct' => 'Conduct',
        'other'   => 'Other',
    ];

    protected $fillable = [
        'title',
        'category',
        'description',
        'file_path',
        'file_name',
        'file_mime',
        'file_size',
        'version',
        'is_mandatory',
        'status',
        'created_by',
    ];

    protected $casts = [
        'is_mandatory' => 'boolean',
    ];

    public function acknowledgements()
    {
        return $this->hasMany(PolicyAcknowledgement::class, 'policy_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Has the currently logged-in user already acknowledged this policy? */
    public function isAcknowledgedByCurrentUser(): bool
    {
        $uid = Auth::id();
        if (!$uid) return false;
        return $this->acknowledgements()->where('user_id', $uid)->exists();
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? ucfirst((string) $this->category);
    }
}
