<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeType extends Model
{
    protected $table = 'employee_types';

    protected $fillable = [
        'code',
        'name',
        'description',
        'ctc_applicable',
        'pf_applicable',
        'esic_applicable',
        'pt_applicable',
        'lwf_applicable',
        'tds_applicable',
        'flat_tds_rate',
        'attendance_prorata',
        'is_system',
        'is_active',
        'sort_order',
        'created_by',
    ];

    protected $casts = [
        'ctc_applicable'     => 'boolean',
        'pf_applicable'      => 'boolean',
        'esic_applicable'    => 'boolean',
        'pt_applicable'      => 'boolean',
        'lwf_applicable'     => 'boolean',
        'tds_applicable'     => 'boolean',
        'flat_tds_rate'      => 'float',
        'attendance_prorata' => 'boolean',
        'is_system'          => 'boolean',
        'is_active'          => 'boolean',
    ];

    public function employees()
    {
        return $this->hasMany(Employee::class, 'employee_type_id');
    }

    public static function default(): ?self
    {
        return static::where('code', 'full_time')->first();
    }
}
