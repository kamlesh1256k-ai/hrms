<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Exit Management — main resignation/exit record.
 *
 * Workflow: pending → manager_approved → hr_approved → completed.
 * Rejection at any stage closes the flow with status manager_rejected / hr_rejected.
 */
class ExitResignation extends Model
{
    protected $table = 'exit_resignations';

    /** Default checklist seeded the moment HR approves the resignation. */
    public const DEFAULT_CHECKLIST = [
        'Laptop returned',
        'ID Card returned',
        'Access Card / Keys returned',
        'Email account access revoked',
        'Knowledge Transfer (KT) completed',
        'Pending tasks handed over',
        'No-Dues clearance from Finance',
        'No-Dues clearance from IT',
        'Exit interview completed',
    ];

    protected $fillable = [
        'user_id',
        'created_by',
        'reason',
        'resignation_date',
        'last_working_day',
        'notice_period_days',
        'status',
        'manager_id',
        'manager_action_at',
        'manager_note',
        'hr_id',
        'hr_action_at',
        'hr_note',
        'completed_at',
    ];

    protected $casts = [
        'resignation_date'   => 'date',
        'last_working_day'   => 'date',
        'manager_action_at'  => 'datetime',
        'hr_action_at'       => 'datetime',
        'completed_at'       => 'datetime',
    ];

    /* ───── Relations ───── */

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function hr()
    {
        return $this->belongsTo(User::class, 'hr_id');
    }

    public function checklist()
    {
        return $this->hasMany(ExitChecklistItem::class, 'resignation_id')->orderBy('id');
    }

    public function fnf()
    {
        return $this->hasOne(FnfSettlement::class, 'resignation_id');
    }

    /* ───── Helpers ───── */

    /**
     * Workflow phase used by the timeline UI:
     *  1=submitted, 2=manager review, 3=HR review, 4=checklist, 5=FNF, 6=done.
     */
    public function timelineStep(): int
    {
        return match (true) {
            $this->status === 'completed'        => 6,
            $this->status === 'hr_rejected'      => 3,
            $this->status === 'manager_rejected' => 2,
            $this->status === 'hr_approved'      => 4,
            $this->status === 'manager_approved' => 3,
            default                              => 1,
        };
    }

    public function isOpen(): bool
    {
        return !in_array($this->status, ['manager_rejected', 'hr_rejected', 'completed'], true);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending'          => 'Pending Manager',
            'manager_approved' => 'Pending HR',
            'manager_rejected' => 'Rejected by Manager',
            'hr_approved'      => 'Approved · Exit in Progress',
            'hr_rejected'      => 'Rejected by HR',
            'completed'        => 'Completed',
            default            => ucfirst((string) $this->status),
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'pending'          => 'badge-pending',
            'manager_approved' => 'badge-mgr-ok',
            'manager_rejected',
            'hr_rejected'      => 'badge-rejected',
            'hr_approved'      => 'badge-hr-ok',
            'completed'        => 'badge-done',
            default            => 'badge-pending',
        };
    }

    /** True if every checklist item is completed. Empty checklist = false (must be seeded first). */
    public function checklistComplete(): bool
    {
        $items = $this->relationLoaded('checklist') ? $this->checklist : $this->checklist()->get();
        if ($items->isEmpty()) return false;
        return $items->every(fn ($i) => $i->status === 'completed');
    }
}
