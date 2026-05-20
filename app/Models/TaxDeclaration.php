<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxDeclaration extends Model
{
    protected $table = 'tax_declarations';

    protected $fillable = [
        'employee_id',
        'financial_year',
        'tax_regime',
        'declaration_status',
        'is_rented_house',
        'is_home_loan',
        'is_rental_income',
        'rent_paid',
        'landlord_name',
        'landlord_pan',
        'home_loan_interest',
        'rental_income_amount',
        'compare_json',
        'approved_by',
        'approved_at',
        'remarks',
        'created_by',
    ];

    protected $casts = [
        'is_rented_house' => 'boolean',
        'is_home_loan' => 'boolean',
        'is_rental_income' => 'boolean',
        'rent_paid' => 'float',
        'home_loan_interest' => 'float',
        'rental_income_amount' => 'float',
        'compare_json' => 'array',
        'approved_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}

