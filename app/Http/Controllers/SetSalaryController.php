<?php

namespace App\Http\Controllers;

use App\Models\AccountList;
use App\Models\Allowance;
use App\Models\AllowanceOption;
use App\Models\Commission;
use App\Models\DeductionOption;
use App\Models\Employee;
use App\Models\Loan;
use App\Models\LoanOption;
use App\Models\OtherPayment;
use App\Models\Overtime;
use App\Models\PayslipType;
use App\Models\SaturationDeduction;
use App\Models\EmployeeSalary;
use App\Models\SalaryStructure;
use App\Services\SalaryCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class SetSalaryController extends Controller
{
    public function index()
    {
        if (\Auth::user()->can('Manage Set Salary')) {
            $employees = Employee::where(
                [
                    'created_by' => \Auth::user()->creatorId(),
                ]
            )->get();

            return view('setsalary.index', compact('employees'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function edit($id)
    {
        if (\Auth::user()->can('Edit Set Salary')) {
            $creatorId = \Auth::user()->creatorId();
            $structures = SalaryStructure::where('created_by', $creatorId)->get();

            if (\Auth::user()->type == 'employee') {
                $employee = Employee::where('user_id', '=', \Auth::user()->id)->first();
                $empId = $employee->id;
            } else {
                $employee = Employee::find($id);
                $empId = $id;
            }

            $salaryConfig = EmployeeSalary::where('employee_id', $empId)->first();

            // Calculate salary breakdown if config exists
            $salaryBreakdown = null;
            $salaryStructure = null;
            if ($salaryConfig && $salaryConfig->ctc > 0) {
                $salaryStructure = SalaryStructure::find($salaryConfig->structure_id);
                try {
                    $calculator = app(SalaryCalculator::class);
                    $salaryBreakdown = $calculator->calculate($empId);
                } catch (\Throwable $e) {
                    $salaryBreakdown = null;
                }
            }

            return view('setsalary.edit', compact('employee', 'structures', 'salaryConfig', 'salaryStructure', 'salaryBreakdown'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function show($id)
    {
        try {
            $id = Crypt::decrypt($id);
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $empId = $id;
        if (\Auth::user()->type == 'employee') {
            $currentEmployee = Employee::where('user_id', '=', \Auth::user()->id)->first();
            $empId = $currentEmployee->id;
        }

        $employee = Employee::find($empId);

        // Load salary structure breakdown from Payroll module
        $salaryConfig = EmployeeSalary::where('employee_id', $empId)->first();
        $salaryStructure = null;
        $salaryBreakdown = null;

        if ($salaryConfig) {
            $salaryStructure = SalaryStructure::find($salaryConfig->structure_id);
            try {
                $calculator = app(SalaryCalculator::class);
                $salaryBreakdown = $calculator->calculate($empId);
            } catch (\Throwable $e) {
                $salaryBreakdown = null;
            }
        }

        $salaryHistory = \DB::table('salary_increment_history')
            ->where('employee_id', $empId)
            ->where('effective_date', '<=', now()->toDateString())
            ->orderByDesc('effective_date')
            ->get();

        return view('setsalary.employee_salary', compact(
            'employee', 'salaryConfig', 'salaryStructure', 'salaryBreakdown', 'salaryHistory'
        ));
    }


    public function employeeUpdateSalary(Request $request, $id)
    {
        $validator = \Validator::make(
            $request->all(),
            [
                'salary_type' => 'required',
                'salary' => 'required',
                'account_type' => 'required',
            ]
        );
        if ($validator->fails()) {
            $messages = $validator->getMessageBag();

            return redirect()->back()->with('error', $messages->first());
        }
        $employee = Employee::findOrFail($id);
        $input    = $request->all();
        $employee->fill($input)->save();

        return redirect()->back()->with('success', 'Employee Salary Updated.');
    }

    public function employeeSalary()
    {
        if (\Auth::user()->type == "employee") {
            $employees = Employee::where('user_id', \Auth::user()->id)->get();
            return view('setsalary.index', compact('employees'));
        }
    }

    public function employeeBasicSalary($id)
    {
        $payslip_type = PayslipType::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
        $payslip_type->prepend('Select Payslip Type', '');
        $accounts = AccountList::where('created_by', \Auth::user()->creatorId())->get()->pluck('account_name', 'id');
        $accounts->prepend('Select Account Type', '');

        $employee     = Employee::find($id);

        return view('setsalary.basic_salary', compact('employee', 'payslip_type', 'accounts'));
    }
}
