<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StatutoryComponent extends Model
{
    protected $table = 'statutory_components';

    protected $fillable = [
        'name',
        'code',
        'status',
        'created_by',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];
}

