<?php

use App\Models\Branch;
use App\Models\Employee;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $employees = Employee::query()->select('id', 'name', 'created_by')->get();
        if ($employees->isEmpty()) {
            return;
        }

        $creatorIds = $employees->pluck('created_by')->filter()->unique()->values();

        $targetBranchesByCreator = [];
        foreach ($creatorIds as $creatorId) {
            $targetBranchesByCreator[$creatorId] = [
                'kolkata' => Branch::firstOrCreate(
                    ['created_by' => $creatorId, 'name' => 'Kolkata Branch'],
                    ['country' => 'India', 'state' => 'West Bengal', 'city' => 'Kolkata']
                ),
                'mumbai' => Branch::firstOrCreate(
                    ['created_by' => $creatorId, 'name' => 'Mumbai Branch'],
                    ['country' => 'India', 'state' => 'Maharashtra', 'city' => 'Mumbai']
                ),
                'bangalore' => Branch::firstOrCreate(
                    ['created_by' => $creatorId, 'name' => 'Bangalore Branch'],
                    ['country' => 'India', 'state' => 'Karnataka', 'city' => 'Bangalore']
                ),
                'delhi' => Branch::firstOrCreate(
                    ['created_by' => $creatorId, 'name' => 'Delhi Branch'],
                    ['country' => 'India', 'state' => 'Delhi', 'city' => 'Delhi']
                ),
            ];

            foreach ($targetBranchesByCreator[$creatorId] as $branch) {
                $branch->country = 'India';
                $branch->state = $branch->state ?: match ($branch->name) {
                    'Kolkata Branch' => 'West Bengal',
                    'Mumbai Branch' => 'Maharashtra',
                    'Bangalore Branch' => 'Karnataka',
                    default => 'Delhi',
                };
                $branch->city = $branch->city ?: match ($branch->name) {
                    'Kolkata Branch' => 'Kolkata',
                    'Mumbai Branch' => 'Mumbai',
                    'Bangalore Branch' => 'Bangalore',
                    default => 'Delhi',
                };
                $branch->save();
            }
        }

        foreach ($employees as $emp) {
            $name = strtolower(trim((string)$emp->name));
            $creatorId = $emp->created_by;
            if (!$creatorId || empty($targetBranchesByCreator[$creatorId])) {
                continue;
            }

            $branches = $targetBranchesByCreator[$creatorId];
            $targetKey = 'delhi';

            if (str_starts_with($name, 'nitesh')) {
                $targetKey = 'kolkata';
            } elseif ($name === 'sapna') {
                $targetKey = 'mumbai';
            } elseif ($name === 'vikram') {
                $targetKey = 'bangalore';
            }

            Employee::where('id', $emp->id)->update([
                'branch_id' => $branches[$targetKey]->id,
            ]);
        }
    }

    public function down(): void
    {
        // no-op to avoid accidental branch/employee mapping rollback
    }
};
