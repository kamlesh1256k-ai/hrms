<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Grievance / Complaint Management permissions.
 *
 *  - raise-grievance     : everyone can submit a complaint
 *  - view-own-grievance  : track own grievances + responses
 *  - manage-grievances   : HR / admin — view all, respond, change status, delete
 *
 * Note: anonymous tracking is a public route (no auth, no permission needed).
 *
 * Idempotent — safe to re-run.
 */
class GrievancePermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $guard = 'web';

        $perms = ['raise-grievance', 'view-own-grievance', 'manage-grievances'];
        $permModels = [];
        foreach ($perms as $p) {
            $permModels[$p] = Permission::firstOrCreate(['name' => $p, 'guard_name' => $guard]);
        }

        $matrix = [
            'super admin' => $perms,
            'company'     => $perms,
            'hr'          => $perms,
            'employee'    => ['raise-grievance', 'view-own-grievance'],
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
            $this->command?->info("Grievance permissions synced for role: {$roleName}");
        }
    }
}
