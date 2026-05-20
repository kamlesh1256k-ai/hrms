<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    protected $fillable = [
        'title',
        'days',
        'monthly_credit',
        'annual_credit',
        'approval_requirement',
        'country',
        'state',
        'city',
        'created_by',
    ];
}
