<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    protected $fillable = [
        'title',
        'description',
        'holiday_date',
        'recurring',
        'status',
        'location_id',
    ];
    
    public function location()
    {
        return $this->belongsTo(Branch::class, 'location_id');
    }
    
    public function shiftMappings()
    {
        return $this->hasMany(HolidayShiftMapping::class, 'holiday_id');
    }
}
