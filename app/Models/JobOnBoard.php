<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobOnBoard extends Model
{
    protected $fillable = [
        'application',
        'joining_date',
        'job_type',
        'days_of_week',
        'salary',
        'salary_type',
        'salary_duration',
        'compensation_breakup',
        'total_ctc',
        'currency',
        'offer_letter_path',
        'offer_expiry_date',
        'offer_released_at',
        'accepted_declined_at',
        'decline_reason',
        'negotiation_notes',
        'requires_approval',
        'approved_by_user_id',
        'approved_at',
        'status',
        'convert_to_employee',
        'created_by',
    ];

    protected $casts = [
        'compensation_breakup'  => 'array',
        'requires_approval'     => 'boolean',
        'joining_date'          => 'date',
        'offer_expiry_date'     => 'date',
        'offer_released_at'     => 'datetime',
        'accepted_declined_at'  => 'datetime',
        'approved_at'           => 'datetime',
    ];

    public function applications()
    {
        return $this->hasOne('App\Models\JobApplication', 'id', 'application');
    }

    public function approver()
    {
        return $this->belongsTo('App\Models\User', 'approved_by_user_id');
    }

    /**
     * Legacy short list — kept for backwards compatibility with existing
     * job-onboard create/edit forms. The new offer-management lifecycle
     * uses $statuses below.
     */
    public static $status = [
        ''        => 'Select Status',
        'pending' => 'Pending',
        'cancel'  => 'Cancel',
        'confirm' => 'Confirm',
    ];

    public static $job_type = [
        ''           => 'Select Job Type',
        'full time'  => 'Full Time',
        'part time'  => 'Part Time',
    ];

    public static $salary_duration = [
        ''        => 'Select Salary Duration',
        'monthly' => 'Monthly',
        'weekly'  => 'Weekly',
    ];

    /** Full lifecycle states for the offer-management module (Stage 8). */
    public static $statuses = [
        'pending'           => 'Draft',
        'awaiting_approval' => 'Awaiting Approval',
        'offer_released'    => 'Offer Released',
        'negotiation'       => 'Negotiation',
        'accepted'          => 'Accepted',
        'declined'          => 'Declined',
        'cancel'            => 'Cancelled',
        'confirm'           => 'Confirmed (Joined)',
    ];

    public static $statusBadge = [
        'pending'           => 'secondary',
        'awaiting_approval' => 'warning',
        'offer_released'    => 'info',
        'negotiation'       => 'warning',
        'accepted'          => 'success',
        'declined'          => 'danger',
        'cancel'            => 'secondary',
        'confirm'           => 'success',
    ];

    /**
     * Default compensation-breakup template. UI seeds this when an
     * offer is created. Amounts are monthly; UI multiplies by 12 for CTC.
     */
    public static function defaultBreakup(): array
    {
        return [
            ['label' => 'Basic',                 'amount' => 0, 'cadence' => 'monthly'],
            ['label' => 'House Rent Allowance',  'amount' => 0, 'cadence' => 'monthly'],
            ['label' => 'Special Allowance',     'amount' => 0, 'cadence' => 'monthly'],
            ['label' => 'Performance Bonus',     'amount' => 0, 'cadence' => 'annual'],
            ['label' => 'Joining Bonus',         'amount' => 0, 'cadence' => 'one_time'],
            ['label' => 'Insurance / Benefits',  'amount' => 0, 'cadence' => 'annual'],
        ];
    }

    /** Sum of breakup rows, normalised to annual totals. */
    public function computeTotalCtc(): float
    {
        $rows = $this->compensation_breakup ?: [];
        $total = 0.0;
        foreach ($rows as $r) {
            $amt     = (float) ($r['amount'] ?? 0);
            $cadence = $r['cadence'] ?? 'monthly';
            if ($cadence === 'monthly') $total += $amt * 12;
            elseif ($cadence === 'annual') $total += $amt;
            else /* one_time */            $total += $amt;
        }
        return round($total, 2);
    }
}
