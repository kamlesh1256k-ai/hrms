<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItIncomeSource extends Model
{
    protected $table = 'income_sources';

    protected $fillable = [
        'tax_declaration_id',
        'income_type',
        'amount',
    ];

    protected $casts = [
        'amount' => 'float',
    ];
}

