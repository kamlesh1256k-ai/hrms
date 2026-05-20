<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoginDetail extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'ip',
        'date',
        'Details',
        'created_by',
        'latitude',
        'longitude',
        'location_address',
        'selfie_image',
        'logout_at',
        'logout_selfie',
    ];

    protected $casts = [
        'logout_at' => 'datetime',
    ];

    public function getUSerEmployee($id)
    {
        $employee = Employee::where('user_id', '=', $id)->first();

        return $employee;
    }

    public static function employeeIdFormat($number)
    {
        $settings = Utility::settings();

        return $settings["employee_prefix"] . sprintf("%05d", $number);
    }

    public function employees()
    {
        return $this->hasOne('App\Models\Employee', 'id', 'employee_id');
    }

}
