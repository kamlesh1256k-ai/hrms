<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeStatutoryConfig;
use App\Models\State;
use App\Models\StatutoryComponent;
use App\Models\StatutoryRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatutoryComplianceController extends Controller
{
    public function dashboard()
    {
        $creatorId = \Auth::user()->creatorId();
        $this->seedDefaults($creatorId);

        $components = StatutoryComponent::orderBy('id')->get();
        $rulesCount = StatutoryRule::where('created_by', $creatorId)->count();
        $statesCount = State::count();
        $employeeCfgCount = EmployeeStatutoryConfig::where('created_by', $creatorId)->count();

        return view('statutory.dashboard', compact('components', 'rulesCount', 'statesCount', 'employeeCfgCount'));
    }

    public function componentSettings(string $code)
    {
        $creatorId = \Auth::user()->creatorId();
        $this->seedDefaults($creatorId);

        $component = StatutoryComponent::where('code', strtoupper($code))->firstOrFail();
        $rules = StatutoryRule::where('created_by', $creatorId)->where('component_id', $component->id)->orderByDesc('effective_from')->get();
        $states = State::orderBy('state_name')->get();

        return view('statutory.component_settings', compact('component', 'rules', 'states'));
    }

    public function saveComponentSettings(Request $request, string $code)
    {
        $creatorId = \Auth::user()->creatorId();
        $component = StatutoryComponent::where('code', strtoupper($code))->firstOrFail();

        if ($request->has('component_status')) {
            $component->update(['status' => $request->has('status') ? 1 : 0]);
            return back()->with('success', __('Component status updated.'));
        }

        $data = $request->validate([
            'state_id' => 'nullable|integer',
            'min_salary' => 'nullable|numeric|min:0',
            'max_salary' => 'nullable|numeric|min:0',
            'employee_contribution_type' => 'required|in:percentage,fixed',
            'employee_value' => 'required|numeric|min:0',
            'employer_contribution_type' => 'required|in:percentage,fixed',
            'employer_value' => 'required|numeric|min:0',
            'max_limit' => 'nullable|numeric|min:0',
            'frequency' => 'required|in:monthly,yearly,half-yearly',
            'applicable_gender' => 'nullable|in:male,female,other',
            'effective_from' => 'required|date',
            'status' => 'nullable|boolean',
        ]);

        StatutoryRule::create([
            'component_id' => $component->id,
            'state_id' => !empty($data['state_id']) ? (int)$data['state_id'] : null,
            'min_salary' => $data['min_salary'] ?? null,
            'max_salary' => $data['max_salary'] ?? null,
            'employee_contribution_type' => $data['employee_contribution_type'],
            'employee_value' => (float)$data['employee_value'],
            'employer_contribution_type' => $data['employer_contribution_type'],
            'employer_value' => (float)$data['employer_value'],
            'max_limit' => $data['max_limit'] ?? null,
            'frequency' => $data['frequency'],
            'applicable_gender' => $data['applicable_gender'] ?? null,
            'effective_from' => $data['effective_from'],
            'status' => $request->has('status') ? 1 : 0,
            'created_by' => $creatorId,
        ]);

        DB::table('payroll_audit_logs')->insert([
            'company_id' => $creatorId,
            'user_id' => \Auth::id(),
            'action' => 'STATUTORY_RULE_CREATED',
            'entity_type' => 'statutory_rules',
            'entity_id' => null,
            'meta' => json_encode(['component' => $component->code]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', __('Rule added successfully.'));
    }

    public function stateConfiguration()
    {
        $states = State::orderBy('state_name')->get();
        return view('statutory.states', compact('states'));
    }

    public function saveState(Request $request)
    {
        $data = $request->validate([
            'state_name' => 'required|string|max:120',
        ]);
        State::firstOrCreate(['state_name' => $data['state_name']]);
        return back()->with('success', __('State saved.'));
    }

    public function employeeConfig()
    {
        $creatorId = \Auth::user()->creatorId();
        $employees = Employee::where('created_by', $creatorId)->orderBy('name')->get();
        $states = State::orderBy('state_name')->get();
        $configs = EmployeeStatutoryConfig::where('created_by', $creatorId)->get()->keyBy('employee_id');

        return view('statutory.employee_config', compact('employees', 'states', 'configs'));
    }

    public function saveEmployeeConfig(Request $request)
    {
        $creatorId = \Auth::user()->creatorId();
        $data = $request->validate([
            'employee_id' => 'required|integer',
            'state_id' => 'nullable|integer',
            'uan_number' => 'nullable|string|max:50',
            'esic_number' => 'nullable|string|max:50',
            'pf_enabled' => 'nullable|boolean',
            'esic_enabled' => 'nullable|boolean',
            'pt_enabled' => 'nullable|boolean',
            'lwf_enabled' => 'nullable|boolean',
        ]);

        EmployeeStatutoryConfig::updateOrCreate(
            ['employee_id' => (int)$data['employee_id']],
            [
                'state_id' => !empty($data['state_id']) ? (int)$data['state_id'] : null,
                'uan_number' => $data['uan_number'] ?? null,
                'esic_number' => $data['esic_number'] ?? null,
                'pf_enabled' => $request->has('pf_enabled') ? 1 : 0,
                'esic_enabled' => $request->has('esic_enabled') ? 1 : 0,
                'pt_enabled' => $request->has('pt_enabled') ? 1 : 0,
                'lwf_enabled' => $request->has('lwf_enabled') ? 1 : 0,
                'created_by' => $creatorId,
            ]
        );

        return back()->with('success', __('Employee statutory config saved.'));
    }

    protected function seedDefaults(int $creatorId): void
    {
        $components = [
            ['name' => 'Employees Provident Fund', 'code' => 'EPF'],
            ['name' => 'Employee State Insurance', 'code' => 'ESIC'],
            ['name' => 'Professional Tax', 'code' => 'PT'],
            ['name' => 'Labour Welfare Fund', 'code' => 'LWF'],
        ];
        foreach ($components as $item) {
            $component = StatutoryComponent::firstOrCreate(
                ['code' => $item['code']],
                ['name' => $item['name'], 'status' => 1, 'created_by' => $creatorId]
            );
            if (empty($component->name)) {
                $component->name = $item['name'];
                $component->status = 1;
                $component->save();
            }
        }
    }
}

