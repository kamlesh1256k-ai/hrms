<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BgvCheck extends Model
{
    protected $table = 'recruitment_bgv_checks';

    protected $fillable = [
        'candidate_id', 'check_type', 'item_label', 'status', 'notes',
        'document_path', 'initiated_on', 'completed_on',
        'verified_by_user_id', 'created_by',
    ];

    protected $casts = [
        'initiated_on' => 'date',
        'completed_on' => 'date',
    ];

    public static $types = [
        'employment' => 'Employment History',
        'education'  => 'Education Verification',
        'id'         => 'Government ID Verification',
        'address'    => 'Address Verification',
        'criminal'   => 'Criminal Record Check',
        'reference'  => 'Reference Check',
        'drug'       => 'Drug Test',
    ];

    public static $statuses = [
        'pending'     => 'Pending',
        'in_progress' => 'In Progress',
        'cleared'     => 'Cleared',
        'failed'      => 'Failed',
        'na'          => 'Not Applicable',
    ];

    public static $statusBadge = [
        'pending'     => 'secondary',
        'in_progress' => 'warning',
        'cleared'     => 'success',
        'failed'      => 'danger',
        'na'          => 'light',
    ];

    public function candidate()
    {
        return $this->belongsTo(JobApplication::class, 'candidate_id');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by_user_id');
    }

    /**
     * Default checklist seeded when admin clicks "Initiate BGV" on a candidate.
     * Returns array of [check_type, item_label] tuples.
     */
    public static function defaultChecklist(): array
    {
        return [
            ['employment', 'Latest Employer — verification of role, tenure, exit reason'],
            ['employment', 'Previous Employer — verification of role and tenure'],
            ['education',  'Highest qualification certificate verification'],
            ['id',         'Government photo ID (Aadhaar / PAN / Passport)'],
            ['address',    'Current address proof'],
            ['reference',  'Professional reference 1'],
            ['reference',  'Professional reference 2'],
            ['criminal',   'Court / criminal record check'],
        ];
    }
}
