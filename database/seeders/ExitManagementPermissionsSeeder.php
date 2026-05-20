<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Exit Management permissions.
 *
 *  - apply-resignation         : employee submits a resignation (everyone)
 *  - view-own-resignation      : see own status / timeline    (everyone)
 *  - manager-approve-exit      : manager approves/rejects     (managers)
 *  - manage-exits              : HR final approval, checklist, FNF (HR + admins)
 *
 * Idempotent — safe to re-run.
 */
class ExitManagementPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $guard = 'web';

        $perms = [
            'apply-resignation',
            'view-own-resignation',
            'manager-approve-exit',
            'manage-exits',
        ];

        $permModels = [];
        foreach ($perms as $p) {
            $permModels[$p] = Permission::firstOrCreate(['name' => $p, 'guard_name' => $guard]);
        }

        $matrix = [
            'super admin' => $perms,
            'company'     => $perms,
            'hr'          => ['apply-resignation', 'view-own-resignation', 'manage-exits'],
            'manager'     => ['apply-resignation', 'view-own-resignation', 'manager-approve-exit'],
            'management'  => ['apply-resignation', 'view-own-resignation', 'manager-approve-exit'],
            // Note: manager-approve-exit is granted broadly here; the controller
            // gates real access by hierarchy (reporting_manager_id / hod_id /
            // management_id), so non-managers see no resignations to act on.
            'employee'    => ['apply-resignation', 'view-own-resignation', 'manager-approve-exit'],
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
            $this->command?->info("Exit Management permissions synced for role: {$roleName}");
        }
    }
}
