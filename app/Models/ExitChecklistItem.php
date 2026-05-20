<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExitChecklistItem extends Model
{
    protected $table = 'exit_checklist_items';

    protected $fillable = [
        'resignation_id',
        'user_id',
        'item_name',
        'status',
        'notes',
        'completed_at',
        'completed_by',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function resignation()
    {
        return $this->belongsTo(ExitResignation::class, 'resignation_id');
    }

    public function completedBy()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}
