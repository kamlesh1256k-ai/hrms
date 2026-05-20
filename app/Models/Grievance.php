<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Grievance extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'category',
        'title',
        'description',
        'status',
        'is_anonymous',
        'anonymous_token',
        'assigned_to',
        'resolved_at',
    ];

    protected $casts = [
        'is_anonymous' => 'boolean',
        'resolved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Status options
    const STATUS_OPEN = 'open';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_RESOLVED = 'resolved';

    // Category options
    const CATEGORY_HR = 'HR';
    const CATEGORY_SALARY = 'Salary';
    const CATEGORY_MANAGER = 'Manager';
    const CATEGORY_HARASSMENT = 'Harassment';
    const CATEGORY_WORK_CONDITIONS = 'Work Conditions';
    const CATEGORY_POLICIES = 'Policies';
    const CATEGORY_DISCRIMINATION = 'Discrimination';
    const CATEGORY_OTHER = 'Other';

    /**
     * Get the user who raised the grievance.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the HR/Admin assigned to handle the grievance.
     */
    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get all responses for this grievance.
     */
    public function responses()
    {
        return $this->hasMany(GrievanceResponse::class)->orderBy('created_at', 'asc');
    }

    /**
     * Get public responses (excluding internal notes).
     */
    public function publicResponses()
    {
        return $this->hasMany(GrievanceResponse::class)
            ->where('is_internal_note', false)
            ->orderBy('created_at', 'asc');
    }

    /**
     * Get internal notes only.
     */
    public function internalNotes()
    {
        return $this->hasMany(GrievanceResponse::class)
            ->where('is_internal_note', true)
            ->orderBy('created_at', 'desc');
    }

    /**
     * Get the latest response.
     */
    public function latestResponse()
    {
        return $this->hasOne(GrievanceResponse::class)->latest();
    }

    /**
     * Get display name for the complainant (handles anonymous cases).
     */
    public function getComplainantNameAttribute()
    {
        if ($this->is_anonymous) {
            return 'Anonymous Employee';
        }
        
        return $this->user ? $this->user->name : 'Unknown Employee';
    }

    /**
     * Get display name for the complainant with employee info.
     */
    public function getComplainantDisplayNameAttribute()
    {
        if ($this->is_anonymous) {
            return 'Anonymous (Token: ' . substr($this->anonymous_token, 0, 8) . '...)';
        }
        
        if ($this->user) {
            $employee = $this->user->employee;
            return $this->user->name . ($employee ? ' - ' . $employee->employee_id : '');
        }
        
        return 'Unknown Employee';
    }

    /**
     * Check if grievance is resolved.
     */
    public function isResolved()
    {
        return $this->status === self::STATUS_RESOLVED;
    }

    /**
     * Check if grievance is in progress.
     */
    public function isInProgress()
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    /**
     * Check if grievance is open.
     */
    public function isOpen()
    {
        return $this->status === self::STATUS_OPEN;
    }

    /**
     * Mark as in progress.
     */
    public function markAsInProgress()
    {
        $this->status = self::STATUS_IN_PROGRESS;
        $this->save();
    }

    /**
     * Mark as resolved.
     */
    public function markAsResolved()
    {
        $this->status = self::STATUS_RESOLVED;
        $this->resolved_at = now();
        $this->save();
    }

    /**
     * Mark as open (reopen).
     */
    public function markAsOpen()
    {
        $this->status = self::STATUS_OPEN;
        $this->resolved_at = null;
        $this->save();
    }

    /**
     * Get status label with color.
     */
    public function getStatusWithColorAttribute()
    {
        $colors = [
            self::STATUS_OPEN => 'danger',
            self::STATUS_IN_PROGRESS => 'warning',
            self::STATUS_RESOLVED => 'success',
        ];

        return [
            'status' => $this->status,
            'label' => ucfirst(str_replace('_', ' ', $this->status)),
            'color' => $colors[$this->status] ?? 'secondary'
        ];
    }

    /**
     * Get all available categories.
     */
    public static function getCategories()
    {
        return [
            self::CATEGORY_HR => 'HR Related',
            self::CATEGORY_SALARY => 'Salary & Compensation',
            self::CATEGORY_MANAGER => 'Manager Related',
            self::CATEGORY_HARASSMENT => 'Harassment',
            self::CATEGORY_WORK_CONDITIONS => 'Work Conditions',
            self::CATEGORY_POLICIES => 'Company Policies',
            self::CATEGORY_DISCRIMINATION => 'Discrimination',
            self::CATEGORY_OTHER => 'Other',
        ];
    }

    /**
     * Get all available statuses.
     */
    public static function getStatuses()
    {
        return [
            self::STATUS_OPEN => 'Open',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_RESOLVED => 'Resolved',
        ];
    }

    /**
     * Scope for user grievances (excluding anonymous unless user has token).
     */
    public function scopeForUser($query, $user, $anonymousToken = null)
    {
        $query->where(function ($q) use ($user, $anonymousToken) {
            $q->where('user_id', $user->id)
              ->orWhere(function ($subQuery) use ($anonymousToken) {
                  if ($anonymousToken) {
                      $subQuery->where('is_anonymous', true)
                               ->where('anonymous_token', $anonymousToken);
                  }
              });
        });
    }

    /**
     * Scope for HR/Admin accessible grievances.
     */
    public function scopeForHR($query, $user)
    {
        if (in_array($user->type, ['super admin', 'company', 'hr'])) {
            return $query;
        }
        
        // For managers, only show grievances related to their team
        if ($user->type === 'employee') {
            $employee = Employee::where('user_id', $user->id)->first();
            if ($employee) {
                $subordinateIds = Employee::where('reporting_manager_id', $employee->id)
                    ->pluck('user_id')
                    ->toArray();
                
                return $query->whereIn('user_id', $subordinateIds);
            }
        }
        
        return $query->where('user_id', $user->id);
    }

    /**
     * Scope by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope by category.
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Generate anonymous token.
     */
    public static function generateAnonymousToken()
    {
        do {
            $token = 'GRV_' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 12));
        } while (self::where('anonymous_token', $token)->exists());
        
        return $token;
    }
}
