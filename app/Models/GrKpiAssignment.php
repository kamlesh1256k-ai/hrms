<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GrKpiAssignment extends Model
{
    protected $table = 'gr_kpi_assignments';
    protected $guarded = ['id'];

    protected $casts = [
        'assigned_at' => 'datetime',
    ];

    public function generation()
    {
        return $this->belongsTo(GrKpiGeneration::class, 'generation_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
