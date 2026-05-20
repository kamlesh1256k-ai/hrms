<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'created_by',
        'owner_id',
    ];

    public function members()
    {
        return $this->hasMany(ChatGroupMember::class, 'chat_group_id');
    }

    public function messages()
    {
        return $this->hasMany(ChatGroupMessage::class, 'chat_group_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
