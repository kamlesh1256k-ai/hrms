<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreonboardingItem extends Model
{
    protected $table = 'recruitment_preonboarding_items';

    protected $fillable = [
        'candidate_id', 'category', 'item_label', 'status', 'notes',
        'document_path', 'due_by', 'completed_on',
        'owner_user_id', 'created_by',
    ];

    protected $casts = [
        'due_by'       => 'date',
        'completed_on' => 'date',
    ];

    public static $categories = [
        'document' => 'Document',
        'asset'    => 'IT Asset',
        'access'   => 'System Access',
        'training' => 'Training',
        'other'    => 'Other',
    ];

    public static $statuses = [
        'pending'   => 'Pending',
        'received'  => 'Received',
        'completed' => 'Completed',
        'waived'    => 'Waived',
    ];

    public static $statusBadge = [
        'pending'   => 'warning',
        'received'  => 'info',
        'completed' => 'success',
        'waived'    => 'secondary',
    ];

    public function candidate()
    {
        return $this->belongsTo(JobApplication::class, 'candidate_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public static function defaultChecklist(): array
    {
        return [
            ['document', 'Signed offer letter'],
            ['document', 'Latest 3 months payslips'],
            ['document', 'Relieving / experience letter from last employer'],
            ['document', 'Educational certificates (highest qualification)'],
            ['document', 'Government photo ID copy'],
            ['document', 'PAN card copy'],
            ['document', 'Aadhaar card copy'],
            ['document', 'Bank account proof (cancelled cheque / passbook)'],
            ['document', 'Passport-size photographs'],
            ['asset',    'Laptop allocated'],
            ['asset',    'ID card / access badge'],
            ['access',   'Email account created'],
            ['access',   'HRMS account created'],
            ['training', 'Welcome email sent'],
            ['training', 'Day-1 induction scheduled'],
        ];
    }
}
