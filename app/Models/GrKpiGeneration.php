<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GrKpiGeneration extends Model
{
    protected $table = 'gr_kpi_generations';
    protected $guarded = ['id'];
    protected $casts = [
        'submitted_at' => 'datetime',
        'manager_reviewed_at' => 'datetime',
        'hod_reviewed_at' => 'datetime',
    ];

    public function getContentAttribute()
    {
        return $this->content_json ? json_decode($this->content_json, true) : [];
    }

    public function cycle()
    {
        return $this->belongsTo(PerformanceCycle::class, 'cycle_id');
    }
}
