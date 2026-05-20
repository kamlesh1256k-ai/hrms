<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompensatoryLeave extends Model
{
    protected $fillable = [
        'employee_id',
        'days',
        'reason',
        'earned_date',
        'expiry_date',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'earned_date' => 'date',
        'expiry_date' => 'date',
        'days' => 'float',
    ];

    public function employee()
    {
        return $this->belongsTo('App\Models\Employee', 'employee_id');
    }
}
