<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GrShoutout extends Model
{
    protected $fillable = [
        'from_employee_id', 'to_employee_id', 'message', 'badge', 'cycle_id', 'created_by',
    ];

    public function fromEmployee() { return $this->belongsTo(Employee::class, 'from_employee_id'); }
    public function toEmployee()   { return $this->belongsTo(Employee::class, 'to_employee_id'); }
    public function cycle()        { return $this->belongsTo(PerformanceCycle::class, 'cycle_id'); }
}
