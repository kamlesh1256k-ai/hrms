<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollSupplementaryAdjustment extends Model
{
    protected $table = 'payroll_supplementary_adjustments';

    protected $fillable = [
        'employee_id',
        'source_month',
        'payout_month',
        'adjustment_type',
        'title',
        'days',
        'amount',
        'remarks',
        'status',
        'created_by',
    ];

    protected $casts = [
        'days' => 'float',
        'amount' => 'float',
        'status' => 'boolean',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }
}
