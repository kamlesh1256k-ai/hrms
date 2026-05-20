<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\ExemptionDetail;
use App\Models\InvestmentDetail;
use App\Models\ItIncomeSource;
use App\Models\TaxDeclaration;
use App\Services\TaxRegimeComparisonService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ItDeclarationController extends Controller
{
    public function employeeIndex()
    {
        $user = \Auth::user();
        $isAdmin = in_array($user->type, ['company', 'super admin']);

        if ($isAdmin) {
            $creatorId = $user->creatorId();
            $employees = Employee::where('created_by', $creatorId)->orderBy('name')->get();
            $declarations = TaxDeclaration::whereIn('employee_id', $employees->pluck('id'))
                ->orderByDesc('id')->get();
            return view('it_declaration.index', compact('employees', 'declarations'))->with('employee', null);
        }

        $employee = Employee::where('user_id', $user->id)->firstOrFail();
        $declarations = TaxDeclaration::where('employee_id', $employee->id)->orderByDesc('id')->get();
        return view('it_declaration.index', compact('employee', 'declarations'));
    }

    public function employeeForm(Request $request, ?int $id = null)
    {
        $user = \Auth::user();
        $isAdmin = in_array($user->type, ['company', 'super admin']);

        if ($isAdmin) {
            $creatorId = $user->creatorId();
            $employees = Employee::where('created_by', $creatorId)->orderBy('name')->get();

            if ($id) {
                $declaration = TaxDeclaration::findOrFail($id);
                $employee = Employee::findOrFail($declaration->employee_id);
            } else {
                $empId = $request->get('employee_id');
                $employee = $empId ? Employee::where('created_by', $creatorId)->findOrFail($empId) : null;
            }
        } else {
            $employee = Employee::where('user_id', $user->id)->firstOrFail();
            $employees = collect();
            $declaration = $id ? TaxDeclaration::where('employee_id', $employee->id)->findOrFail($id) : null;
        }

        $declaration = $declaration ?? ($id ? TaxDeclaration::findOrFail($id) : null);
        $investments = $declaration ? InvestmentDetail::where('tax_declaration_id', $declaration->id)->get() : collect();
        $exemptions = $declaration ? ExemptionDetail::where('tax_declaration_id', $declaration->id)->get() : collect();
        $incomes = $declaration ? ItIncomeSource::where('tax_declaration_id', $declaration->id)->get() : collect();

        $fy = $request->get('financial_year', $this->currentFinancialYear());
        return view('it_declaration.form', compact('employee', 'employees', 'declaration', 'investments', 'exemptions', 'incomes', 'fy'));
    }

    public function saveEmployee(Request $request, TaxRegimeComparisonService $comparison, ?int $id = null)
    {
        $user = \Auth::user();
        $isAdmin = in_array($user->type, ['company', 'super admin']);

        if ($isAdmin) {
            $empId = $request->get('employee_id');
            if (!$empId) {
                return back()->withInput()->with('error', __('Please select an employee.'));
            }
            $employee = Employee::where('created_by', $user->creatorId())->findOrFail($empId);
        } else {
            $employee = Employee::where('user_id', $user->id)->firstOrFail();
        }
        $action = $request->get('action_type', 'draft');

        $data = $request->validate([
            'employee_id' => $isAdmin ? 'required|integer' : 'nullable',
            'financial_year' => 'required|string|max:9',
            'tax_regime' => 'required|in:old,new',
            'is_rented_house' => 'nullable',
            'is_home_loan' => 'nullable',
            'is_rental_income' => 'nullable',
            'rent_paid' => 'nullable|numeric|min:0',
            'landlord_name' => 'nullable|string|max:120',
            'landlord_pan' => 'nullable|string|max:20',
            'home_loan_interest' => 'nullable|numeric|min:0',
            'rental_income_amount' => 'nullable|numeric|min:0',
            'investments' => 'nullable|array',
            'investments.*.section_code' => 'nullable|string|max:40',
            'investments.*.type' => 'nullable|string|max:120',
            'investments.*.amount' => 'nullable|numeric|min:0',
            'exemptions' => 'nullable|array',
            'exemptions.*.section_code' => 'nullable|string|max:40',
            'exemptions.*.type' => 'nullable|string|max:120',
            'exemptions.*.amount' => 'nullable|numeric|min:0',
            'incomes' => 'nullable|array',
            'incomes.*.type' => 'nullable|string|max:120',
            'incomes.*.amount' => 'nullable|numeric|min:0',
        ]);

        $isRented = $request->has('is_rented_house');
        $isHomeLoan = $request->has('is_home_loan');
        $isRentalIncome = $request->has('is_rental_income');

        if ($action !== 'draft') {
            if ($isRented && ((float)($data['rent_paid'] ?? 0) <= 0 || empty($data['landlord_name']))) {
                return back()->withInput()->with('error', __('Rent and landlord details are mandatory.'));
            }
            if ($isHomeLoan && (float)($data['home_loan_interest'] ?? 0) <= 0) {
                return back()->withInput()->with('error', __('Home loan interest is mandatory.'));
            }
            if ($isRentalIncome && (float)($data['rental_income_amount'] ?? 0) <= 0) {
                return back()->withInput()->with('error', __('Rental income amount is mandatory.'));
            }
        }

        $investments = collect($data['investments'] ?? [])->filter(fn($row) => !empty($row['type']) && (float)($row['amount'] ?? 0) > 0)->values();
        $exemptions = collect($data['exemptions'] ?? [])->filter(fn($row) => !empty($row['type']) && (float)($row['amount'] ?? 0) > 0)->values();
        $incomes = collect($data['incomes'] ?? [])->filter(fn($row) => !empty($row['type']) && (float)($row['amount'] ?? 0) > 0)->values();

        $limit80C = $investments->where('section_code', '80C')->sum(fn($r) => (float)$r['amount']);
        $limit80D = $exemptions->where('section_code', '80D')->sum(fn($r) => (float)$r['amount']);
        if ($limit80C > 150000) {
            return back()->withInput()->with('error', __('80C amount cannot exceed 150000.'));
        }
        if ($limit80D > 100000) {
            return back()->withInput()->with('error', __('80D amount cannot exceed 100000.'));
        }

        DB::beginTransaction();
        try {
            $declaration = $id
                ? TaxDeclaration::where('employee_id', $employee->id)->findOrFail($id)
                : new TaxDeclaration();

            $declaration->fill([
                'employee_id' => $employee->id,
                'financial_year' => $data['financial_year'],
                'tax_regime' => $data['tax_regime'],
                'declaration_status' => $action === 'submit' ? 'submitted' : 'draft',
                'is_rented_house' => $isRented ? 1 : 0,
                'is_home_loan' => $isHomeLoan ? 1 : 0,
                'is_rental_income' => $isRentalIncome ? 1 : 0,
                'rent_paid' => (float)($data['rent_paid'] ?? 0),
                'landlord_name' => $data['landlord_name'] ?? null,
                'landlord_pan' => $data['landlord_pan'] ?? null,
                'home_loan_interest' => (float)($data['home_loan_interest'] ?? 0),
                'rental_income_amount' => (float)($data['rental_income_amount'] ?? 0),
                'created_by' => \Auth::user()->creatorId(),
            ]);
            $declaration->save();

            InvestmentDetail::where('tax_declaration_id', $declaration->id)->delete();
            ExemptionDetail::where('tax_declaration_id', $declaration->id)->delete();
            ItIncomeSource::where('tax_declaration_id', $declaration->id)->delete();

            foreach ($investments as $row) {
                InvestmentDetail::create([
                    'tax_declaration_id' => $declaration->id,
                    'section_code' => $row['section_code'] ?? '80C',
                    'investment_type' => $row['type'],
                    'amount' => (float)$row['amount'],
                ]);
            }
            foreach ($exemptions as $row) {
                ExemptionDetail::create([
                    'tax_declaration_id' => $declaration->id,
                    'section_code' => $row['section_code'] ?? '80D',
                    'exemption_type' => $row['type'],
                    'amount' => (float)$row['amount'],
                ]);
            }
            foreach ($incomes as $row) {
                ItIncomeSource::create([
                    'tax_declaration_id' => $declaration->id,
                    'income_type' => $row['type'],
                    'amount' => (float)$row['amount'],
                ]);
            }

            if ($request->has('compare')) {
                $empSalary = \App\Models\EmployeeSalary::where('employee_id', $employee->id)->first();
                $tdsCalc = app(\App\Services\TDSCalculator::class);

                // TDS is on GROSS EARNINGS (CTC minus Employer PF only — Gratuity is provision, not paid)
                $ctc = $empSalary ? (float)$empSalary->ctc : ((float)$employee->salary * 12);
                $basicPct = $empSalary ? (float)$empSalary->basic_percentage : 50;
                $basicAnnual = round($ctc * ($basicPct / 100));
                $annualGross = $tdsCalc->ctcToGross($ctc, $basicPct);

                // New regime tax on gross earnings
                $newTax = $tdsCalc->calculateNewRegime($annualGross);

                // Old regime tax (with all declared deductions)
                $oldDeductions = 50000; // standard deduction old regime
                $oldDeductions += min((float)$investments->sum('amount'), 150000); // 80C cap
                $oldDeductions += min((float)$exemptions->sum('amount'), 100000); // 80D cap
                if ($declaration->is_home_loan) {
                    $oldDeductions += min((float)($data['home_loan_interest'] ?? 0), 200000);
                }
                if ($declaration->is_rented_house && $declaration->rent_paid > 0) {
                    $hraAnnual = $basicAnnual * 0.50;
                    $hra40 = $basicAnnual * 0.40;
                    $rentMinusBasic10 = max(($declaration->rent_paid * 12) - ($basicAnnual * 0.10), 0);
                    $oldDeductions += min($hraAnnual, $hra40, $rentMinusBasic10);
                }
                $oldTax = $tdsCalc->calculateOldRegime($annualGross, $oldDeductions);

                $declaration->compare_json = [
                    'annual_ctc' => round($ctc, 2),
                    'annual_gross' => round($annualGross, 2),
                    'employer_deductions' => round($ctc - $annualGross, 2),
                    'old_regime' => [
                        'taxable_income' => round(max($annualGross - $oldDeductions, 0), 2),
                        'estimated_tax' => round($oldTax, 2),
                        'monthly_tds' => round($oldTax / 12, 2),
                        'deductions' => round($oldDeductions, 2),
                    ],
                    'new_regime' => [
                        'taxable_income' => round(max($annualGross - 75000, 0), 2),
                        'estimated_tax' => round($newTax, 2),
                        'monthly_tds' => round($newTax / 12, 2),
                    ],
                    'recommended' => $newTax <= $oldTax ? 'new' : 'old',
                    'saving' => round(abs($oldTax - $newTax), 2),
                ];
                $declaration->save();
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('IT Declaration save failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()->withInput()->with('error', __('Unable to save declaration: ') . $e->getMessage());
        }

        return redirect()->route('it.declaration.index')->with('success', __('Declaration saved successfully.'));
    }

    public function deleteDeclaration(int $id)
    {
        $user = \Auth::user();
        $isAdmin = in_array($user->type, ['company', 'super admin']);

        if ($isAdmin) {
            $empIds = Employee::where('created_by', $user->creatorId())->pluck('id');
            $declaration = TaxDeclaration::whereIn('employee_id', $empIds)->findOrFail($id);
        } else {
            $employee = Employee::where('user_id', $user->id)->firstOrFail();
            $declaration = TaxDeclaration::where('employee_id', $employee->id)->findOrFail($id);
        }

        // Delete related records
        \App\Models\InvestmentDetail::where('tax_declaration_id', $id)->delete();
        \App\Models\ExemptionDetail::where('tax_declaration_id', $id)->delete();
        \App\Models\ItIncomeSource::where('tax_declaration_id', $id)->delete();
        $declaration->delete();

        return redirect()->route('it.declaration.index')->with('success', __('Declaration deleted successfully.'));
    }

    public function adminIndex()
    {
        $creatorId = \Auth::user()->creatorId();
        $employeeIds = Employee::where('created_by', $creatorId)->pluck('id');
        $declarations = TaxDeclaration::whereIn('employee_id', $employeeIds)->orderByDesc('id')->get();
        $employees = Employee::whereIn('id', $employeeIds)->get()->keyBy('id');
        return view('it_declaration.review_index', compact('declarations', 'employees'));
    }

    public function adminShow(int $id)
    {
        $creatorId = \Auth::user()->creatorId();
        $employeeIds = Employee::where('created_by', $creatorId)->pluck('id');
        $declaration = TaxDeclaration::whereIn('employee_id', $employeeIds)->findOrFail($id);
        $employee = Employee::find($declaration->employee_id);
        $investments = InvestmentDetail::where('tax_declaration_id', $id)->get();
        $exemptions = ExemptionDetail::where('tax_declaration_id', $id)->get();
        $incomes = ItIncomeSource::where('tax_declaration_id', $id)->get();
        return view('it_declaration.review_show', compact('declaration', 'employee', 'investments', 'exemptions', 'incomes'));
    }

    public function adminAction(Request $request, int $id)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
            'remarks' => 'nullable|string|max:500',
        ]);
        $creatorId = \Auth::user()->creatorId();
        $employeeIds = Employee::where('created_by', $creatorId)->pluck('id');
        $declaration = TaxDeclaration::whereIn('employee_id', $employeeIds)->findOrFail($id);
        $declaration->update([
            'declaration_status' => $request->status,
            'remarks' => $request->remarks,
            'approved_by' => \Auth::id(),
            'approved_at' => now(),
        ]);
        return back()->with('success', __('Declaration updated.'));
    }

    protected function currentFinancialYear(): string
    {
        $year = (int)date('Y');
        $month = (int)date('m');
        if ($month >= 4) {
            return $year . '-' . ($year + 1);
        }
        return ($year - 1) . '-' . $year;
    }
}

