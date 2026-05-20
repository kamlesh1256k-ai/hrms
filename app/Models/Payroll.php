<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    protected $table = 'payroll';

    protected $fillable = [
        'employee_id',
        'month',
        'earnings_json',
        'deductions_json',
        'benefits_json',
        'reimbursements_json',
        'statutory_json',
        'gross_salary',
        'total_deductions',
        'employer_contribution',
        'net_salary',
        'is_locked',
        'created_by',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id')
            ->with(['department', 'designation']);
    }

    protected $casts = [
        'earnings_json' => 'array',
        'deductions_json' => 'array',
        'benefits_json' => 'array',
        'reimbursements_json' => 'array',
        'statutory_json' => 'array',
        'gross_salary' => 'float',
        'total_deductions' => 'float',
        'employer_contribution' => 'float',
        'net_salary' => 'float',
        'is_locked' => 'boolean',
    ];
}

