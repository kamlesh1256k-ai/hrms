<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimeSheet extends Model
{
    protected $fillable = [
        'employee_id',
        'client_name',
        'task',
        'category',
        'date',
        'start_time',
        'end_time',
        'hours',
        'billable',
        'status',
        'remark',
        'created_by',
    ];

    public function employee()
    {
        return $this->hasOne('App\Models\User', 'id', 'employee_id');
    }

    public function employees()
    {
        return $this->hasOne('App\Models\Employee', 'id', 'employee_id');
    }

    public static function branches($id)
    {
        $branchs = Employee::where('branch_id', '=', $id);

        return $branchs;
    }
}
