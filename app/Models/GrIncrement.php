<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GrIncrement extends Model
{
    protected $fillable = [
        'cycle_id', 'employee_id', 'rating_id', 'old_ctc', 'new_ctc',
        'increment_pct', 'increment_amount', 'effective_date', 'status',
        'approved_by', 'proposed_by', 'proposed_at', 'synced_to_payroll', 'letter_generated', 'remarks', 'created_by',
    ];

    protected $casts = ['effective_date' => 'date', 'proposed_at' => 'datetime', 'synced_to_payroll' => 'boolean', 'letter_generated' => 'boolean'];

    public function proposer(){ return $this->belongsTo(Employee::class, 'proposed_by'); }

    public function cycle()   { return $this->belongsTo(PerformanceCycle::class, 'cycle_id'); }
    public function employee(){ return $this->belongsTo(Employee::class, 'employee_id'); }
    public function rating()  { return $this->belongsTo(GrRating::class, 'rating_id'); }
}
