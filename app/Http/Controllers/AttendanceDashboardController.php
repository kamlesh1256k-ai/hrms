<?php
namespace App\Http\Controllers;

use App\Models\AttendanceEmployee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceDashboardController extends Controller
{
    public function lateCountSummary()
    {
        $employeeId = Auth::user()->employee->id;
        $month = now()->month;
        $year = now()->year;
        $lateCount = AttendanceEmployee::where('employee_id', $employeeId)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->sum('late_mark');
        return view('attendance_dashboard.late_count', compact('lateCount'));
    }
}
