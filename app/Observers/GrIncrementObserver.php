<?php

namespace App\Observers;

use App\Models\Employee;
use App\Models\GrComebackPlan;
use App\Models\GrIncrement;
use Carbon\Carbon;

class GrIncrementObserver
{
    /**
     * Auto-initiate a Comeback Plan when an increment is saved as 0% and its
     * status indicates a concrete decision (manager_proposed or approved).
     *
     * Idempotent — re-saving the same increment will not create duplicates
     * because we key off increment_id.
     */
    public function saved(GrIncrement $inc): void
    {
        $this->maybeInitiatePlan($inc);
    }

    protected function maybeInitiatePlan(GrIncrement $inc): void
    {
        // Only act on concrete 0% decisions, not draft rows
        if ((float) $inc->increment_pct !== 0.0) {
            return;
        }
        if (!in_array($inc->status, ['manager_proposed', 'approved'], true)) {
            return;
        }

        // Already has a plan for this increment? Skip.
        if (GrComebackPlan::where('increment_id', $inc->id)->exists()) {
            return;
        }

        $employee = Employee::find($inc->employee_id);
        if (!$employee) {
            return;
        }

        // Initiator = reporting manager; fall back to whoever created the emp
        $managerId = $employee->reporting_manager_id ?: $employee->created_by;

        GrComebackPlan::create([
            'employee_id'    => $employee->id,
            'assigned_by'    => $managerId,
            'cycle_id'       => $inc->cycle_id,
            'increment_id'   => $inc->id,
            'title'          => '90-Day Comeback Plan - ' . $employee->name,
            'issues'         => 'Auto-initiated because the latest performance cycle resulted in a 0% increment. Manager to fill in specific performance gaps observed during the review.',
            'action_steps'   => [
                'Define 2-3 measurable improvement goals with the manager',
                'Schedule weekly 1:1 check-ins for the duration of the plan',
                'Document progress in the reviews tab',
            ],
            'start_date'     => Carbon::today(),
            'end_date'       => Carbon::today()->addDays(90),
            'status'         => 'active',
            'auto_initiated' => true,
            'final_outcome'  => 'pending',
            'created_by'     => $inc->created_by ?: $employee->created_by,
        ]);
    }
}
