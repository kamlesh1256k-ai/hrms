<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Activity Tracker permissions.
 *
 *  - manage-activity-tracker  : view dashboard, reports, all users (HR/admin)
 *  - use-activity-tracker     : run the agent (token issuance), view own data
 *
 * Idempotent — safe to re-run.
 */
class ActivityTrackerPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $guard = 'web';

        $perms = ['manage-activity-tracker', 'use-activity-tracker'];
        $permModels = [];
        foreach ($perms as $p) {
            $permModels[$p] = Permission::firstOrCreate(['name' => $p, 'guard_name' => $guard]);
        }

        $matrix = [
            'super admin' => $perms,
            'company'     => $perms,
            'hr'          => $perms,
            'employee'    => ['use-activity-tracker'],
        ];

        foreach ($matrix as $roleName => $list) {
            $role = Role::where('name', $roleName)->where('guard_name', $guard)->first();
            if (!$role) {
                $this->command?->warn("Role '{$roleName}' not found — skipped.");
                continue;
            }
            foreach ($list as $p) {
                if (!$role->hasPermissionTo($permModels[$p])) {
                    $role->givePermissionTo($permModels[$p]);
                }
            }
            $this->command?->info("Activity Tracker permissions synced for role: {$roleName}");
        }
    }
}
