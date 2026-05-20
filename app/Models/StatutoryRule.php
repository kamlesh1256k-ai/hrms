<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StatutoryRule extends Model
{
    protected $table = 'statutory_rules';

    protected $fillable = [
        'component_id',
        'state_id',
        'min_salary',
        'max_salary',
        'employee_contribution_type',
        'employee_value',
        'employer_contribution_type',
        'employer_value',
        'max_limit',
        'frequency',
        'applicable_gender',
        'effective_from',
        'status',
        'created_by',
    ];

    protected $casts = [
        'min_salary' => 'float',
        'max_salary' => 'float',
        'employee_value' => 'float',
        'employer_value' => 'float',
        'max_limit' => 'float',
        'effective_from' => 'date',
        'status' => 'boolean',
    ];
}

