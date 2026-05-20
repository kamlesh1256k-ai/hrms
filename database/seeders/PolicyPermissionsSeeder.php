<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Policy Management permissions.
 *
 *  - view-policies        : list + open policy detail (everyone)
 *  - acknowledge-policies : click "Acknowledge" (everyone)
 *  - manage-policies      : upload / edit / archive / delete (HR + admins)
 *
 * Idempotent — safe to re-run with firstOrCreate + hasPermissionTo guard.
 */
class PolicyPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $guard = 'web';

        $perms = ['view-policies', 'acknowledge-policies', 'manage-policies'];
        $permModels = [];
        foreach ($perms as $p) {
            $permModels[$p] = Permission::firstOrCreate(['name' => $p, 'guard_name' => $guard]);
        }

        $matrix = [
            'super admin' => ['view-policies', 'acknowledge-policies', 'manage-policies'],
            'company'     => ['view-policies', 'acknowledge-policies', 'manage-policies'],
            'hr'          => ['view-policies', 'acknowledge-policies', 'manage-policies'],
            'employee'    => ['view-policies', 'acknowledge-policies'],
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
            $this->command?->info("Policy permissions synced for role: {$roleName}");
        }
    }
}
