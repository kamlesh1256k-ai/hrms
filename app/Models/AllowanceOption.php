<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AllowanceOption extends Model
{
    protected $fillable = [
        'name',
        'country',
        'state',
        'city',
        'created_by',
    ];
}