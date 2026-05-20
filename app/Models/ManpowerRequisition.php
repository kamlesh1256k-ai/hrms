<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManpowerRequisition extends Model
{
    protected $table = 'recruitment_requisitions';

    protected $fillable = [
        'title', 'department_id', 'designation_id', 'branch_id',
        'skills', 'experience', 'positions', 'priority', 'reason',
        'replacement_for', 'salary_range', 'location', 'job_type',
        'description', 'generated_jd', 'status', 'needed_by',
        'job_id', 'created_by', 'raised_by_user_id',
        'approval_chain', 'current_approval_step',
    ];

    /** @return array<string> e.g. ['hr','finance'] */
    public function getApprovalChainArrayAttribute(): array
    {
        $raw = trim((string) ($this->approval_chain ?? ''));
        if ($raw === '') return ['hr']; // safe default — single-step HR approval
        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }

    /** Role expected to act on the current step, or null if chain complete. */
    public function getNextApproverRoleAttribute(): ?string
    {
        $chain = $this->approval_chain_array;
        return $chain[$this->current_approval_step] ?? null;
    }

    protected $casts = [
        'needed_by'   => 'date',
        'positions'   => 'integer',
    ];

    public static $statuses = [
        'draft'     => 'Draft',
        'pending'   => 'Pending Approval',
        'approved'  => 'Approved',
        'rejected'  => 'Rejected',
        'fulfilled' => 'Fulfilled',
    ];

    public static $priorities = [
        'high'   => 'High',
        'medium' => 'Medium',
        'low'    => 'Low',
    ];

    public static $reasons = [
        'replacement' => 'Replacement',
        'new_hire'    => 'New Hire',
        'expansion'   => 'Expansion',
    ];

    public function department()
    {
        return $this->belongsTo(\App\Models\Department::class, 'department_id');
    }

    public function designation()
    {
        return $this->belongsTo(\App\Models\Designation::class, 'designation_id');
    }

    public function branch()
    {
        return $this->belongsTo(\App\Models\Branch::class, 'branch_id');
    }

    public function raisedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'raised_by_user_id');
    }

    public function approvals()
    {
        return $this->hasMany(RequisitionApproval::class, 'requisition_id')->latest();
    }

    public function job()
    {
        return $this->belongsTo(\App\Models\Job::class, 'job_id');
    }

    public function getSkillsArrayAttribute(): array
    {
        if (!$this->skills) return [];
        $parts = preg_split('/[,;\n]+/', $this->skills);
        return array_values(array_filter(array_map('trim', $parts ?: [])));
    }

    public function getStatusBadgeAttribute(): string
    {
        return [
            'draft'     => 'secondary',
            'pending'   => 'warning',
            'approved'  => 'success',
            'rejected'  => 'danger',
            'fulfilled' => 'info',
        ][$this->status] ?? 'secondary';
    }
}
