<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatGroupMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'chat_group_id',
        'user_id',
        'message',
        'message_type',
        'voice_path',
    ];

    public function group()
    {
        return $this->belongsTo(ChatGroup::class, 'chat_group_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
