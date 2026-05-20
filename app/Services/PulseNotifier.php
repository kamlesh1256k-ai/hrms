<?php

namespace App\Services;

use App\Mail\PulseSurveyInvite;
use App\Models\Employee;
use App\Models\EmployeeSurvey;
use App\Models\SurveyResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Sends pulse-survey invite + reminder emails to assigned employees.
 *
 * Intentionally lightweight: no queue dispatch (mail is sent inline). For
 * large companies, swap to a Queue::push() call later — the audience-resolution
 * logic stays the same.
 */
class PulseNotifier
{
    /**
     * Resolve the list of employees who should receive notifications for a
     * given survey. Honors the survey's department_ids audience.
     */
    public function audience(EmployeeSurvey $survey): \Illuminate\Support\Collection
    {
        $q = Employee::query()->where('created_by', $survey->created_by);

        $depts = $survey->department_ids;
        if (!empty($depts) && is_array($depts)) {
            $q->whereIn('department_id', array_map('intval', $depts));
        }
        // Skip employees with no email
        return $q->whereNotNull('email')->where('email', '!=', '')->get(['id', 'name', 'email']);
    }

    /**
     * Send invites to everyone in the audience who has not yet responded.
     * Returns count of emails sent (best-effort — logs and continues on error).
     *
     * @param bool $isReminder when true, the email is worded as a reminder.
     */
    public function dispatch(EmployeeSurvey $survey, bool $isReminder = false): int
    {
        $audience = $this->audience($survey);
        if ($audience->isEmpty()) return 0;

        // Exclude employees who already responded (so reminders don't go to
        // people who finished). Note: anonymous surveys store a guard row
        // tied to employee_id even though the answer-bearing row is anon —
        // so this filter still works for anon.
        $respondedIds = SurveyResponse::where('survey_id', $survey->id)
            ->whereIn('employee_id', $audience->pluck('id'))
            ->pluck('employee_id')
            ->all();

        $sent = 0;
        foreach ($audience as $emp) {
            if (in_array((int) $emp->id, $respondedIds, true)) continue;
            try {
                Mail::to($emp->email)->send(new PulseSurveyInvite($survey, $emp->name ?: '', $isReminder));
                $sent++;
            } catch (\Throwable $e) {
                Log::warning('Pulse mail failed', [
                    'survey_id' => $survey->id,
                    'employee_id' => $emp->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        return $sent;
    }
}
