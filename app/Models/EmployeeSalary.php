<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeSalary extends Model
{
    protected $table = 'employee_salaries';

    protected $fillable = [
        'employee_id',
        'ctc',
        'basic_percentage',
        'is_pf_enabled',
        'is_esic_enabled',
        'overtime_enabled',
        'overtime_formula',
        'structure_id',
    ];
}

