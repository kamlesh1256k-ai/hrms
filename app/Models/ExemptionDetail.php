<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExemptionDetail extends Model
{
    protected $table = 'exemption_details';

    protected $fillable = [
        'tax_declaration_id',
        'section_code',
        'exemption_type',
        'amount',
        'proof_file',
    ];

    protected $casts = [
        'amount' => 'float',
    ];
}

