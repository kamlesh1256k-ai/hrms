<?php

use App\Models\Employee;
use App\Models\EmployeeStatutoryConfig;
use App\Models\State;
use App\Models\StatutoryComponent;
use App\Models\StatutoryRule;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $effectiveFrom = '2026-04-01';

        $components = [
            'EPF' => 'Employees Provident Fund',
            'ESIC' => 'Employee State Insurance',
            'PT' => 'Professional Tax',
            'LWF' => 'Labour Welfare Fund',
        ];

        $componentIds = [];
        foreach ($components as $code => $name) {
            $component = StatutoryComponent::firstOrCreate(
                ['code' => $code],
                ['name' => $name, 'status' => 1, 'created_by' => null]
            );
            if (!$component->status) {
                $component->status = 1;
                $component->save();
            }
            $componentIds[$code] = (int)$component->id;
        }

        $allStates = [
            'Andhra Pradesh', 'Arunachal Pradesh', 'Assam', 'Bihar', 'Chhattisgarh',
            'Goa', 'Gujarat', 'Haryana', 'Himachal Pradesh', 'Jharkhand',
            'Karnataka', 'Kerala', 'Madhya Pradesh', 'Maharashtra', 'Manipur',
            'Meghalaya', 'Mizoram', 'Nagaland', 'Odisha', 'Punjab',
            'Rajasthan', 'Sikkim', 'Tamil Nadu', 'Telangana', 'Tripura',
            'Uttar Pradesh', 'Uttarakhand', 'West Bengal', 'Andaman and Nicobar Islands',
            'Chandigarh', 'Dadra and Nagar Haveli and Daman and Diu', 'Delhi',
            'Jammu and Kashmir', 'Ladakh', 'Lakshadweep', 'Puducherry',
        ];

        foreach ($allStates as $stateName) {
            State::firstOrCreate(['state_name' => $stateName]);
        }

        $stateIdByName = State::query()->pluck('id', 'state_name')->toArray();

        $upsertRule = function (
            int $componentId,
            ?int $stateId,
            ?float $minSalary,
            ?float $maxSalary,
            string $employeeType,
            float $employeeValue,
            string $employerType,
            float $employerValue,
            string $frequency = 'monthly',
            ?string $gender = null
        ) use ($effectiveFrom): void {
            $query = StatutoryRule::query()
                ->where('component_id', $componentId)
                ->where('effective_from', $effectiveFrom)
                ->where('frequency', $frequency)
                ->where('employee_contribution_type', $employeeType)
                ->where('employer_contribution_type', $employerType)
                ->where('created_by', null);

            if ($stateId === null) {
                $query->whereNull('state_id');
            } else {
                $query->where('state_id', $stateId);
            }

            if ($minSalary === null) {
                $query->whereNull('min_salary');
            } else {
                $query->where('min_salary', $minSalary);
            }

            if ($maxSalary === null) {
                $query->whereNull('max_salary');
            } else {
                $query->where('max_salary', $maxSalary);
            }

            if ($gender === null) {
                $query->whereNull('applicable_gender');
            } else {
                $query->where('applicable_gender', $gender);
            }

            $rule = $query->first() ?: new StatutoryRule();
            $rule->component_id = $componentId;
            $rule->state_id = $stateId;
            $rule->min_salary = $minSalary;
            $rule->max_salary = $maxSalary;
            $rule->employee_contribution_type = $employeeType;
            $rule->employee_value = $employeeValue;
            $rule->employer_contribution_type = $employerType;
            $rule->employer_value = $employerValue;
            $rule->max_limit = null;
            $rule->frequency = $frequency;
            $rule->applicable_gender = $gender;
            $rule->effective_from = $effectiveFrom;
            $rule->status = 1;
            $rule->created_by = null;
            $rule->save();
        };

        // EPF Master (12% each side, cap base up to 15000 via max_salary)
        $upsertRule($componentIds['EPF'], null, null, 15000.00, 'percentage', 12.00, 'percentage', 12.00);

        // ESIC Master (0.75% + 3.25%, salary threshold 21000)
        $upsertRule($componentIds['ESIC'], null, null, 21000.00, 'percentage', 0.75, 'percentage', 3.25);

        $ptComponentId = $componentIds['PT'];

        // Default no-PT master for all states (can be overridden by specific slabs below)
        foreach ($allStates as $stateName) {
            $stateId = (int)($stateIdByName[$stateName] ?? 0);
            if ($stateId > 0) {
                $upsertRule($ptComponentId, $stateId, 0.00, null, 'fixed', 0.00, 'fixed', 0.00);
            }
        }

        $stateRule = function (string $stateName, ?float $min, ?float $max, float $employeeValue, ?string $gender = null) use ($upsertRule, $ptComponentId, $stateIdByName): void {
            $stateId = (int)($stateIdByName[$stateName] ?? 0);
            if ($stateId > 0) {
                $upsertRule($ptComponentId, $stateId, $min, $max, 'fixed', $employeeValue, 'fixed', 0.00, 'monthly', $gender);
            }
        };

        // Maharashtra
        $stateRule('Maharashtra', 0.00, 7500.00, 0.00, 'male');
        $stateRule('Maharashtra', 0.00, 10000.00, 0.00, 'female');
        $stateRule('Maharashtra', 7500.01, 10000.00, 175.00, 'male');
        $stateRule('Maharashtra', 10000.01, null, 200.00);

        // Karnataka
        $stateRule('Karnataka', 0.00, 15000.00, 0.00);
        $stateRule('Karnataka', 15000.01, null, 200.00);

        // Gujarat
        $stateRule('Gujarat', 0.00, 12000.00, 0.00);
        $stateRule('Gujarat', 12000.01, 15000.00, 150.00);
        $stateRule('Gujarat', 15000.01, null, 200.00);

        // West Bengal
        $stateRule('West Bengal', 0.00, 10000.00, 0.00);
        $stateRule('West Bengal', 10000.01, 15000.00, 110.00);
        $stateRule('West Bengal', 15000.01, 25000.00, 130.00);
        $stateRule('West Bengal', 25000.01, 40000.00, 150.00);
        $stateRule('West Bengal', 40000.01, null, 200.00);

        // Tamil Nadu
        $stateRule('Tamil Nadu', 0.00, 3500.00, 0.00);
        $stateRule('Tamil Nadu', 3500.01, 5000.00, 22.50);
        $stateRule('Tamil Nadu', 5000.01, 7500.00, 52.50);
        $stateRule('Tamil Nadu', 7500.01, 10000.00, 115.00);
        $stateRule('Tamil Nadu', 10000.01, 12500.00, 171.00);
        $stateRule('Tamil Nadu', 12500.01, null, 208.00);

        // Andhra Pradesh / Telangana
        foreach (['Andhra Pradesh', 'Telangana'] as $st) {
            $stateRule($st, 0.00, 15000.00, 0.00);
            $stateRule($st, 15000.01, 20000.00, 150.00);
            $stateRule($st, 20000.01, null, 200.00);
        }

        // Madhya Pradesh
        $stateRule('Madhya Pradesh', 0.00, 12500.00, 0.00);
        $stateRule('Madhya Pradesh', 12500.01, 18750.00, 125.00);
        $stateRule('Madhya Pradesh', 18750.01, null, 208.00);

        // Employee location updates
        $niteshIds = Employee::query()
            ->whereRaw('LOWER(name) like ?', ['nitesh%'])
            ->pluck('id')
            ->all();

        $sapnaIds = Employee::query()
            ->whereRaw('LOWER(name) = ?', ['sapna'])
            ->pluck('id')
            ->all();

        $vikramIds = Employee::query()
            ->whereRaw('LOWER(name) = ?', ['vikram'])
            ->pluck('id')
            ->all();

        if (!empty($niteshIds)) {
            Employee::whereIn('id', $niteshIds)->update([
                'present_country' => 'India',
                'present_state' => 'West Bengal',
                'present_city' => 'Kolkata',
                'permanent_country' => 'India',
                'permanent_state' => 'West Bengal',
                'permanent_city' => 'Kolkata',
            ]);
        }

        if (!empty($sapnaIds)) {
            Employee::whereIn('id', $sapnaIds)->update([
                'present_country' => 'India',
                'present_state' => 'Maharashtra',
                'present_city' => 'Mumbai',
                'permanent_country' => 'India',
                'permanent_state' => 'Maharashtra',
                'permanent_city' => 'Mumbai',
            ]);
        }

        if (!empty($vikramIds)) {
            Employee::whereIn('id', $vikramIds)->update([
                'present_country' => 'India',
                'present_state' => 'Karnataka',
                'present_city' => 'Bangalore',
                'permanent_country' => 'India',
                'permanent_state' => 'Karnataka',
                'permanent_city' => 'Bangalore',
            ]);
        }

        $excludeIds = array_values(array_unique(array_merge($niteshIds, $sapnaIds, $vikramIds)));

        Employee::query()
            ->when(!empty($excludeIds), fn($q) => $q->whereNotIn('id', $excludeIds))
            ->update([
                'present_country' => 'India',
                'present_state' => 'Delhi',
                'present_city' => 'Delhi',
                'permanent_country' => 'India',
                'permanent_state' => 'Delhi',
                'permanent_city' => 'Delhi',
            ]);

        // Sync statutory state config from employee present_state.
        $employees = Employee::query()->select('id', 'present_state')->get();
        foreach ($employees as $emp) {
            $stateName = trim((string)$emp->present_state);
            if ($stateName === '') {
                continue;
            }

            $stateId = (int)(State::query()->whereRaw('LOWER(state_name) = ?', [strtolower($stateName)])->value('id') ?? 0);
            if ($stateId <= 0) {
                continue;
            }

            EmployeeStatutoryConfig::updateOrCreate(
                ['employee_id' => (int)$emp->id],
                ['state_id' => $stateId]
            );
        }
    }

    public function down(): void
    {
        // Intentionally left empty to preserve seeded master/config data.
    }
};
