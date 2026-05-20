<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Leave extends Model
{
    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'applied_on',
        'start_date',
        'end_date',
        'day_type',
        'substitute_employee_id',
        'substitute_status',
        'substitute_token',
        'substitute_responded_at',
        'total_leave_days',
        'leave_reason',
        'remark',
        'status',
        'created_by',
        'professional_days',
        'professional_months',
        'professional_years',
        'calculated_at',
        'medical_certificate',
        'certificate_verified',
        'is_compensatory',
        'compensatory_leave_id',
    ];

    public function leaveType()
    {
        return $this->hasOne('App\Models\LeaveType', 'id', 'leave_type_id');
    }

    public function employees()
    {
        return $this->hasOne('App\Models\Employee', 'id', 'employee_id');
    }

    /**
     * Get formatted professional period display
     */
    public function getProfessionalPeriodDisplay()
    {
        if ($this->professional_years > 0) {
            $label = $this->professional_years . ' year' . ($this->professional_years > 1 ? 's' : '');
            if ($this->professional_months > 0) {
                $label .= ' ' . $this->professional_months . ' month' . ($this->professional_months > 1 ? 's' : '');
            }
            return $label;
        } elseif ($this->professional_months > 0) {
            return $this->professional_months . ' month' . ($this->professional_months > 1 ? 's' : '');
        } else {
            return $this->professional_days . ' day' . ($this->professional_days > 1 ? 's' : '');
        }
    }
}
