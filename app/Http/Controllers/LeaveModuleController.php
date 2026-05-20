<?php
namespace App\Http\Controllers;

use App\Models\AttendanceEmployee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeaveModuleController extends Controller
{
    public function deductionHistory()
    {
        $employeeId = Auth::user()->employee->id;
        $deductions = AttendanceEmployee::where('employee_id', $employeeId)
            ->where('deduction_units', '>', 0)
            ->orderBy('date', 'desc')
            ->get();
        return view('leave_module.deduction_history', compact('deductions'));
    }
}
