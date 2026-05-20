<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TalentPoolCandidate extends Model
{
    protected $table = 'talent_pool_candidates';

    protected $fillable = [
        'name', 'email', 'phone',
        'current_company', 'current_designation', 'experience_years',
        'skills', 'preferred_locations',
        'linkedin_url', 'portfolio_url', 'resume_path',
        'current_ctc', 'expected_ctc', 'notice_period_days',
        'source', 'source_detail', 'linked_application_id', 'assigned_recruiter_id',
        'tags', 'notes', 'last_engaged_at', 'status',
        'created_by',
    ];

    protected $casts = [
        'last_engaged_at'   => 'datetime',
        'experience_years'  => 'decimal:1',
        'current_ctc'       => 'decimal:2',
        'expected_ctc'      => 'decimal:2',
    ];

    public static $statuses = [
        'active'         => 'Active',
        'contacted'      => 'Contacted',
        'interested'     => 'Interested',
        'not_interested' => 'Not Interested',
        'placed'         => 'Placed',
        'archived'       => 'Archived',
    ];

    public static $statusBadge = [
        'active'         => 'primary',
        'contacted'      => 'info',
        'interested'     => 'success',
        'not_interested' => 'secondary',
        'placed'         => 'success',
        'archived'       => 'dark',
    ];

    public static $sources = [
        'job_application' => 'From Job Application',
        'referral'        => 'Employee Referral',
        'linkedin'        => 'LinkedIn',
        'naukri'          => 'Naukri',
        'indeed'          => 'Indeed',
        'outbound'        => 'Outbound Sourcing',
        'event'           => 'Career Event / Job Fair',
        'agency'          => 'Recruitment Agency',
        'other'           => 'Other',
    ];

    public function recruiter()
    {
        return $this->belongsTo(User::class, 'assigned_recruiter_id');
    }

    public function linkedApplication()
    {
        return $this->belongsTo(JobApplication::class, 'linked_application_id');
    }

    /** @return array<string> normalised, lowercased skill list */
    public function getSkillsArrayAttribute(): array
    {
        if (!$this->skills) return [];
        $parts = preg_split('/[,;\n]+/', $this->skills);
        return array_values(array_filter(array_map('trim', $parts ?: [])));
    }

    public function getTagsArrayAttribute(): array
    {
        if (!$this->tags) return [];
        $parts = preg_split('/[,;\n]+/', $this->tags);
        return array_values(array_filter(array_map('trim', $parts ?: [])));
    }

    /**
     * Naive skill-match score against a target skill set. Returns 0-100.
     * Used by the "Match candidates for job" page.
     */
    public function matchScore(array $targetSkills): int
    {
        if (empty($targetSkills)) return 0;
        $candidateSkills = array_map('strtolower', $this->skills_array);
        $target          = array_map('strtolower', $targetSkills);
        $hits = 0;
        foreach ($target as $skill) {
            foreach ($candidateSkills as $cs) {
                if ($cs === $skill || str_contains($cs, $skill) || str_contains($skill, $cs)) {
                    $hits++;
                    break;
                }
            }
        }
        return (int) round(($hits / count($target)) * 100);
    }
}
