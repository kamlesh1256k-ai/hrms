<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Full-and-Final settlement attached to an exit resignation.
 *
 * total_amount   = sum of earnings columns
 * deductions     = sum of deduction columns
 * final_amount   = total_amount - deductions
 *
 * Computed in the controller on save() so the snapshot is always consistent.
 */
class FnfSettlement extends Model
{
    protected $table = 'fnf_settlements';

    protected $fillable = [
        'resignation_id',
        'user_id',
        'pending_salary',
        'leave_encashment',
        'gratuity',
        'bonus',
        'other_earnings',
        'total_amount',
        'notice_recovery',
        'asset_recovery',
        'tax_deduction',
        'other_deductions',
        'deductions',
        'final_amount',
        'status',
        'remarks',
        'paid_on',
        'processed_by',
    ];

    protected $casts = [
        'pending_salary'   => 'decimal:2',
        'leave_encashment' => 'decimal:2',
        'gratuity'         => 'decimal:2',
        'bonus'            => 'decimal:2',
        'other_earnings'   => 'decimal:2',
        'total_amount'     => 'decimal:2',
        'notice_recovery'  => 'decimal:2',
        'asset_recovery'   => 'decimal:2',
        'tax_deduction'    => 'decimal:2',
        'other_deductions' => 'decimal:2',
        'deductions'       => 'decimal:2',
        'final_amount'     => 'decimal:2',
        'paid_on'          => 'date',
    ];

    public function resignation()
    {
        return $this->belongsTo(ExitResignation::class, 'resignation_id');
    }

    /** Recompute total / deductions / final from individual columns. */
    public function recompute(): void
    {
        $this->total_amount = (float) $this->pending_salary
            + (float) $this->leave_encashment
            + (float) $this->gratuity
            + (float) $this->bonus
            + (float) $this->other_earnings;

        $this->deductions = (float) $this->notice_recovery
            + (float) $this->asset_recovery
            + (float) $this->tax_deduction
            + (float) $this->other_deductions;

        $this->final_amount = $this->total_amount - $this->deductions;
    }
}
