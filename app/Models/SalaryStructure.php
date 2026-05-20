<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalaryStructure extends Model
{
    protected $table = 'salary_structures';

    protected $fillable = [
        'name',
        'country',
        'created_by',
    ];
}

