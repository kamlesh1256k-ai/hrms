<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AttendanceEmployee extends Model
{
    protected $fillable = [
        'employee_id',
        'date',
        'status',
        'clock_in',
        'clock_out',
        'late',
        'late_mark',
        'early_leaving',
        'early_mark',
        'overtime',
        'less_hours_mark',
        'total_rest',
        'deduction_units',
        'created_by',
        'device_type',
        'latitude',
        'longitude',
        'address',
        'photo',
        'photo_verified',
        'device_type_out',
        'latitude_out',
        'longitude_out',
        'address_out',
        'photo_out',
        'photo_out_verified',
        'professional_days_at_attendance',
        'professional_months_at_attendance',
        'professional_years_at_attendance',
        'in_probation_at_attendance',
    ];

    public function employees()
    {
        return $this->hasOne('App\Models\Employee', 'user_id', 'employee_id');
    }

    public function employee()
    {
        return $this->hasOne('App\Models\Employee', 'id', 'employee_id');
    }

    public function getPhotoUrlAttribute()
    {
        return $this->resolveAttendanceImageUrl($this->photo);
    }

    public function getPhotoOutUrlAttribute()
    {
        return $this->resolveAttendanceImageUrl($this->photo_out);
    }

    protected function resolveAttendanceImageUrl($path)
    {
        if (empty($path)) {
            return null;
        }

        $normalizedPath = str_replace('\\', '/', trim($path));

        if (Str::startsWith($normalizedPath, ['http://', 'https://', 'data:image/'])) {
            return $normalizedPath;
        }

        if (Str::contains($normalizedPath, '/public/')) {
            $normalizedPath = Str::after($normalizedPath, '/public/');
        }

        if (Str::startsWith($normalizedPath, 'public/')) {
            $normalizedPath = Str::after($normalizedPath, 'public/');
        }

        $relativePath = ltrim($normalizedPath, '/');
        $fullPath = public_path($relativePath);

        if (file_exists($fullPath)) {
            return asset('public/' . $relativePath);
        }

        return null;
    }

    /**
     * Get formatted professional period display for attendance
     */
    public function getProfessionalPeriodDisplay()
    {
        if ($this->professional_years_at_attendance > 0) {
            $label = $this->professional_years_at_attendance . ' year' . ($this->professional_years_at_attendance > 1 ? 's' : '');
            if ($this->professional_months_at_attendance > 0) {
                $label .= ' ' . $this->professional_months_at_attendance . ' month' . ($this->professional_months_at_attendance > 1 ? 's' : '');
            }
            return $label;
        } elseif ($this->professional_months_at_attendance > 0) {
            return $this->professional_months_at_attendance . ' month' . ($this->professional_months_at_attendance > 1 ? 's' : '');
        } else {
            return $this->professional_days_at_attendance . ' day' . ($this->professional_days_at_attendance > 1 ? 's' : '');
        }
    }

    /**
     * Get professional period status
     */
    public function getProfessionalPeriodStatus()
    {
        if ($this->in_probation_at_attendance) {
            return 'In Probation';
        }
        return 'Active';
    }
}
