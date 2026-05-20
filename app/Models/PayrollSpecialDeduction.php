<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollSpecialDeduction extends Model
{
    protected $table = 'payroll_special_deductions';

    protected $fillable = [
        'employee_id',
        'month',
        'title',
        'amount',
        'remarks',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'float',
    ];
}

