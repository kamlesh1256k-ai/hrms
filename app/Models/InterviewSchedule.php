<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterviewSchedule extends Model
{
    protected $fillable = [
        'candidate', 'employee', 'date', 'time',
        'round_type', 'round_label', 'mode', 'meeting_link',
        'status', 'rating', 'feedback', 'recommendation',
        'comment', 'employee_response', 'created_by',
    ];

    public static $roundTypes = [
        'screening'   => 'HR Screening',
        'technical'   => 'Technical Round',
        'managerial'  => 'Managerial Round',
        'hr'          => 'HR Round',
        'final'       => 'Final Round',
        'culture'     => 'Culture Fit',
    ];

    public static $modes = [
        'online'    => 'Online',
        'offline'   => 'In-person',
        'phone'     => 'Phone',
    ];

    public static $statuses = [
        'scheduled' => 'Scheduled',
        'completed' => 'Completed',
        'selected'  => 'Selected',
        'rejected'  => 'Rejected',
        'no_show'   => 'No Show',
        'cancelled' => 'Cancelled',
    ];

    public static $statusBadge = [
        'scheduled' => 'info',
        'completed' => 'primary',
        'selected'  => 'success',
        'rejected'  => 'danger',
        'no_show'   => 'warning',
        'cancelled' => 'secondary',
    ];

    public static $recommendations = [
        'strong_yes'   => 'Strong Yes',
        'yes'          => 'Yes',
        'maybe'        => 'Maybe',
        'no'           => 'No',
        'strong_no'    => 'Strong No',
    ];

    public function applications()
    {
        return $this->hasOne('App\Models\JobApplication', 'id', 'candidate');
    }

    public function users()
    {
        return $this->hasOne('App\Models\User', 'id', 'employee');
    }
}
