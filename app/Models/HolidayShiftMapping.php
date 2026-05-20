<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HolidayShiftMapping extends Model
{
    protected $fillable = [
        'holiday_id',
        'shift_id',
    ];

    public function shift()
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }
    public function holiday()
    {
        return $this->belongsTo(Holiday::class, 'holiday_id');
    }
}
