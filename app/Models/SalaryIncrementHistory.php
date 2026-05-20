<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalaryIncrementHistory extends Model
{
    protected $table = 'salary_increment_history';

    protected $fillable = [
        'employee_id',
        'old_ctc',
        'new_ctc',
        'increment_amount',
        'increment_percentage',
        'effective_date',
        'arrears_month',
        'arrears_paid',
        'arrears_amount',
        'remarks',
        'created_by',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'arrears_paid' => 'boolean',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
