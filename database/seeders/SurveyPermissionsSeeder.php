<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Survey module permissions.
 *
 * Permissions:
 *  - submit-surveys           : fill assigned surveys (everyone)
 *  - view-own-surveys         : see own submission history (everyone)
 *  - manage-surveys           : create/edit/delete/activate/close (HR + admins)
 *  - view-survey-analytics    : see aggregate results across the company
 *                               (HR + admins; Management-level employees get this
 *                                via runtime check on `management_id` hierarchy)
 *  - view-survey-alerts       : see HR alerts from negative high-risk feedback
 *  - export-surveys           : CSV/PDF export
 *
 * Idempotent — safe to re-run. Uses firstOrCreate.
 */
class SurveyPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $guard = 'web';

        $permissions = [
            'submit-surveys',
            'view-own-surveys',
            'view-team-pulse',          // managers — runtime gated by isManagerLevel()
            'manage-surveys',
            'view-survey-analytics',
            'view-survey-alerts',
            'export-surveys',
        ];

        // Create each permission once
        $permModels = [];
        foreach ($permissions as $name) {
            $permModels[$name] = Permission::firstOrCreate([
                'name'       => $name,
                'guard_name' => $guard,
            ]);
        }

        // Role → permissions matrix
        // Note: 'view-team-pulse' is granted to ALL roles. The Survey controllers
        // additionally enforce a runtime check (Employee::isManagerLevel()) so
        // only employees who actually have direct reports see Team Pulse links/pages.
        $matrix = [
            'super admin' => [   // existing role; all access
                'submit-surveys', 'view-own-surveys', 'view-team-pulse', 'manage-surveys',
                'view-survey-analytics', 'view-survey-alerts', 'export-surveys',
            ],
            'company' => [       // company owner — full access
                'submit-surveys', 'view-own-surveys', 'view-team-pulse', 'manage-surveys',
                'view-survey-analytics', 'view-survey-alerts', 'export-surveys',
            ],
            'hr' => [            // HR — full access
                'submit-surveys', 'view-own-surveys', 'view-team-pulse', 'manage-surveys',
                'view-survey-analytics', 'view-survey-alerts', 'export-surveys',
            ],
            'employee' => [      // every employee: submit + view own; team-pulse only
                                 // unlocks at runtime if they actually have a team.
                'submit-surveys', 'view-own-surveys', 'view-team-pulse',
            ],
        ];

        foreach ($matrix as $roleName => $perms) {
            $role = Role::where('name', $roleName)->where('guard_name', $guard)->first();
            if (!$role) {
                $this->command?->warn("Role '{$roleName}' not found — skipped.");
                continue;
            }
            foreach ($perms as $p) {
                if (isset($permModels[$p]) && !$role->hasPermissionTo($permModels[$p])) {
                    $role->givePermissionTo($permModels[$p]);
                }
            }
            $this->command?->info("Survey permissions synced for role: {$roleName}");
        }

        // Note: "Management" is not a Spatie role — it's a hierarchy position
        // (any employee whose id appears in another employee's `management_id`).
        // The Survey controllers will check this at runtime to grant analytics
        // access to such employees on top of their base 'employee' role.
    }
}
