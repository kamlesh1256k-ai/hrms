<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalaryComponent extends Model
{
    protected $table = 'salary_components';

    protected $fillable = [
        'name',
        'category',
        'type',
        'calculation_type',
        'value',
        'formula',
        'max_limit',
        'is_taxable',
        'is_pf_applicable',
        'is_esic_applicable',
        'frequency',
        'condition_rule',
        'status',
        'created_by',
    ];

    protected $casts = [
        'value' => 'float',
        'max_limit' => 'float',
        'is_taxable' => 'boolean',
        'is_pf_applicable' => 'boolean',
        'is_esic_applicable' => 'boolean',
        'status' => 'boolean',
    ];
}

