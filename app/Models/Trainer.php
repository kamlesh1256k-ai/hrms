<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trainer extends Model
{
    protected $fillable = [
        'branch',
        'firstname',
        'lastname',
        'contact',
        'email',
        'address',
        'expertise',
        'country',
        'state',
        'city',
        'created_by',
    ];

    public function branches()
    {
        return $this->hasOne('App\Models\Branch', 'id', 'branch');
    }
}
