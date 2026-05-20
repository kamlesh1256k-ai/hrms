<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaySchedule extends Model
{
    protected $table = 'pay_schedule';

    protected $fillable = [
        'pay_frequency',
        'pay_day',
        'attendance_cycle_start_day',
        'working_days',
        'start_month',
        'status',
        'is_locked',
        'created_by',
    ];

    protected $casts = [
        'status' => 'boolean',
        'is_locked' => 'boolean',
    ];

    /**
     * Resolve the attendance date range for a given payroll month.
     *
     * @param  string  $month       "YYYY-MM"
     * @param  int|null $creatorId   tenant scope; if null uses the given $cycleStartDay directly
     * @param  int|null $cycleStartDay  override (1 = calendar month). If null, read from schedule.
     * @return array{0:string,1:string}  [startDate, endDate] as 'Y-m-d'
     *
     * Examples (month = 2026-05):
     *   start day = 1   -> 2026-05-01 .. 2026-05-31   (calendar month)
     *   start day = 26  -> 2026-04-26 .. 2026-05-25   (26 prev -> 25 current)
     */
    public static function attendanceRangeFor(string $month, ?int $creatorId = null, ?int $cycleStartDay = null): array
    {
        if ($cycleStartDay === null) {
            $startDay = 1;
            if ($creatorId !== null) {
                $sch = self::where('created_by', $creatorId)->first();
                if ($sch && (int) $sch->attendance_cycle_start_day > 1) {
                    $startDay = (int) $sch->attendance_cycle_start_day;
                }
            }
        } else {
            $startDay = max(1, (int) $cycleStartDay);
        }

        // Calendar month (default — no behaviour change)
        if ($startDay <= 1) {
            $start = $month . '-01';
            $end   = date('Y-m-t', strtotime($start));
            return [$start, $end];
        }

        // Custom cycle: day N of previous month .. day (N-1) of current month
        // e.g. month=2026-05, N=26  ->  2026-04-26 .. 2026-05-25
        $currentFirst = \Carbon\Carbon::createFromFormat('Y-m-d', $month . '-01')->startOfDay();
        $prevMonth    = (clone $currentFirst)->subMonthNoOverflow();

        $startDayClamped = min($startDay, (int) $prevMonth->copy()->endOfMonth()->format('d'));
        $start = $prevMonth->copy()->day($startDayClamped)->format('Y-m-d');

        $endDay = $startDay - 1;
        $endDayClamped = min($endDay, (int) $currentFirst->copy()->endOfMonth()->format('d'));
        $end = $currentFirst->copy()->day($endDayClamped)->format('Y-m-d');

        return [$start, $end];
    }
}

