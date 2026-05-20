<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GrievanceResponse extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'grievance_id',
        'responder_id',
        'message',
        'response_type',
        'is_internal_note',
    ];

    protected $casts = [
        'is_internal_note' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Response types
    const TYPE_HR_RESPONSE = 'hr_response';
    const TYPE_EMPLOYEE_REPLY = 'employee_reply';
    const TYPE_SYSTEM_NOTE = 'system_note';

    /**
     * Get the grievance this response belongs to.
     */
    public function grievance()
    {
        return $this->belongsTo(Grievance::class);
    }

    /**
     * Get the user who wrote this response.
     */
    public function responder()
    {
        return $this->belongsTo(User::class, 'responder_id');
    }

    /**
     * Get responder display name.
     */
    public function getResponderNameAttribute()
    {
        if ($this->responder) {
            $employee = $this->responder->employee;
            $name = $this->responder->name;
            
            if ($employee) {
                $name .= ' (' . $employee->employee_id . ')';
            }
            
            // Add role indicator
            $roleIndicator = '';
            if ($this->responder->type === 'super admin') {
                $roleIndicator = ' [Super Admin]';
            } elseif ($this->responder->type === 'company') {
                $roleIndicator = ' [Company]';
            } elseif ($this->responder->type === 'hr') {
                $roleIndicator = ' [HR]';
            } elseif ($this->responder->type === 'employee') {
                $roleIndicator = ' [Employee]';
            }
            
            return $name . $roleIndicator;
        }
        
        return 'Unknown User';
    }

    /**
     * Get response type label with color.
     */
    public function getResponseTypeWithColorAttribute()
    {
        $types = [
            self::TYPE_HR_RESPONSE => ['label' => 'HR Response', 'color' => 'primary'],
            self::TYPE_EMPLOYEE_REPLY => ['label' => 'Employee Reply', 'color' => 'info'],
            self::TYPE_SYSTEM_NOTE => ['label' => 'System Note', 'color' => 'secondary'],
        ];

        return $types[$this->response_type] ?? ['label' => 'Unknown', 'color' => 'secondary'];
    }

    /**
     * Check if this is an HR response.
     */
    public function isHRResponse()
    {
        return $this->response_type === self::TYPE_HR_RESPONSE;
    }

    /**
     * Check if this is an employee reply.
     */
    public function isEmployeeReply()
    {
        return $this->response_type === self::TYPE_EMPLOYEE_REPLY;
    }

    /**
     * Check if this is a system note.
     */
    public function isSystemNote()
    {
        return $this->response_type === self::TYPE_SYSTEM_NOTE;
    }

    /**
     * Check if response is visible to employee.
     */
    public function isVisibleToEmployee()
    {
        return !$this->is_internal_note;
    }

    /**
     * Check if response is visible to HR.
     */
    public function isVisibleToHR()
    {
        return true; // All responses are visible to HR
    }

    /**
     * Scope for public responses only.
     */
    public function scopePublic($query)
    {
        return $query->where('is_internal_note', false);
    }

    /**
     * Scope for internal notes only.
     */
    public function scopeInternal($query)
    {
        return $query->where('is_internal_note', true);
    }

    /**
     * Scope by response type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('response_type', $type);
    }

    /**
     * Create HR response.
     */
    public static function createHRResponse($grievanceId, $responderId, $message, $isInternal = false)
    {
        return self::create([
            'grievance_id' => $grievanceId,
            'responder_id' => $responderId,
            'message' => $message,
            'response_type' => self::TYPE_HR_RESPONSE,
            'is_internal_note' => $isInternal,
        ]);
    }

    /**
     * Create employee reply.
     */
    public static function createEmployeeReply($grievanceId, $responderId, $message)
    {
        return self::create([
            'grievance_id' => $grievanceId,
            'responder_id' => $responderId,
            'message' => $message,
            'response_type' => self::TYPE_EMPLOYEE_REPLY,
            'is_internal_note' => false,
        ]);
    }

    /**
     * Create system note.
     */
    public static function createSystemNote($grievanceId, $responderId, $message)
    {
        return self::create([
            'grievance_id' => $grievanceId,
            'responder_id' => $responderId,
            'message' => $message,
            'response_type' => self::TYPE_SYSTEM_NOTE,
            'is_internal_note' => true,
        ]);
    }

    /**
     * Get formatted message with line breaks.
     */
    public function getFormattedMessageAttribute()
    {
        return nl2br(e($this->message));
    }

    /**
     * Get message preview (first 100 characters).
     */
    public function getMessagePreviewAttribute()
    {
        return Str::limit(strip_tags($this->message), 100);
    }
}
