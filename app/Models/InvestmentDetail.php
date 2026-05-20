<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvestmentDetail extends Model
{
    protected $table = 'investment_details';

    protected $fillable = [
        'tax_declaration_id',
        'section_code',
        'investment_type',
        'amount',
        'proof_file',
    ];

    protected $casts = [
        'amount' => 'float',
    ];
}

