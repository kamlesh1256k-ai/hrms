<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeStatutoryConfig extends Model
{
    protected $table = 'employee_statutory_config';

    protected $fillable = [
        'employee_id',
        'state_id',
        'pf_enabled',
        'esic_enabled',
        'pt_enabled',
        'lwf_enabled',
        'uan_number',
        'esic_number',
        'created_by',
    ];

    protected $casts = [
        'pf_enabled' => 'boolean',
        'esic_enabled' => 'boolean',
        'pt_enabled' => 'boolean',
        'lwf_enabled' => 'boolean',
    ];
}

