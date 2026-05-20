<?php

namespace App\Services;

use App\Models\Department;
use App\Models\EmployeeSurvey;
use App\Models\SurveyAnswer;
use App\Models\SurveyAlert;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Pulse Survey analytics.
 *
 * Pulse surveys are short, recurring 3–5 question check-ins (rating_5 mostly +
 * one open text). This service powers:
 *   - Weekly trend (avg score per question, ISO-week buckets)
 *   - Team-wise breakdown (by department)
 *   - Low-score alerts (avg < 3.0 → write to survey_alerts)
 */
class PulseService
{
    /** Threshold below which a question's average is treated as "low" and alerted on. */
    public const LOW_SCORE_THRESHOLD = 3.0;

    /**
     * Pull all rating answers for pulse-type surveys in a creator's scope.
     *
     * @param int|null            $surveyId narrow to a single survey
     * @param Carbon|null         $from
     * @param Carbon|null         $to
     * @param array<int>|null     $empIds   when provided, restrict to responses
     *                                      from these employees (used by Team
     *                                      Pulse for manager-only scope).
     *                                      Anonymous responses are excluded
     *                                      automatically since their employee_id
     *                                      is NULL and won't match any whitelist.
     */
    public function pullPulseRatings(int $creatorId, ?int $surveyId = null, ?Carbon $from = null, ?Carbon $to = null, ?array $empIds = null): Collection
    {
        $q = SurveyAnswer::query()
            ->join('survey_questions as q', 'q.id', '=', 'survey_answers.question_id')
            ->join('survey_responses  as r', 'r.id', '=', 'survey_answers.response_id')
            ->join('employee_surveys  as s', 's.id', '=', 'r.survey_id')
            ->leftJoin('employees     as e', 'e.id', '=', 'r.employee_id')
            ->where('s.created_by', $creatorId)
            ->where('s.type', 'pulse')
            ->whereNotNull('survey_answers.rating_value');

        if ($surveyId)            $q->where('s.id', $surveyId);
        if ($from)                $q->where('r.submitted_at', '>=', $from);
        if ($to)                  $q->where('r.submitted_at', '<=', $to);
        if (is_array($empIds))    $q->whereIn('r.employee_id', $empIds);

        return $q->select([
                'survey_answers.rating_value as rating',
                'q.id as question_id',
                'q.question_text',
                'r.employee_id',
                'e.department_id',
                'r.submitted_at',
                's.id as survey_id',
                's.title as survey_title',
            ])
            ->orderBy('r.submitted_at')
            ->get();
    }

    /**
     * Per-question averages across the entire scope.
     * @param array<int>|null $empIds optional employee whitelist (for manager scope)
     * @return array<int, array> rows: {question_id, question_text, avg, total, low}
     */
    public function byQuestion(int $creatorId, ?int $surveyId = null, ?Carbon $from = null, ?Carbon $to = null, ?array $empIds = null): array
    {
        $rows = $this->pullPulseRatings($creatorId, $surveyId, $from, $to, $empIds);
        $byQ = $rows->groupBy('question_id');

        $out = [];
        foreach ($byQ as $qid => $rs) {
            $total = $rs->count();
            $sum   = $rs->sum('rating');
            $avg   = $total > 0 ? round($sum / $total, 2) : 0.0;
            $out[] = [
                'question_id'   => (int) $qid,
                'question_text' => $rs->first()->question_text ?? '',
                'avg'           => $avg,
                'total'         => $total,
                'low'           => $avg > 0 && $avg < self::LOW_SCORE_THRESHOLD,
            ];
        }
        usort($out, fn($a, $b) => $a['avg'] <=> $b['avg']); // worst first — easier to spot issues
        return $out;
    }

    /**
     * Weekly trend: avg score per ISO week × question (last N weeks).
     *
     * Returns:
     *   [
     *     'weeks'     => ['2026-W14', '2026-W15', ...],     // x-axis labels
     *     'questions' => [
     *         ['question_id' => 12, 'question_text' => '...', 'series' => [3.2, 3.4, ...]],
     *         ...
     *     ]
     *   ]
     */
    public function weeklyTrend(int $creatorId, int $weeksBack = 12, ?int $surveyId = null, ?array $empIds = null): array
    {
        $end   = now()->endOfWeek();
        $start = now()->copy()->subWeeks($weeksBack - 1)->startOfWeek();

        $rows = $this->pullPulseRatings($creatorId, $surveyId, $start, $end, $empIds);

        // Build the week list
        $weeks = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $weeks[] = $cursor->format('o-\WW'); // ISO year-week, e.g. "2026-W14"
            $cursor->addWeek();
        }

        // Group answers by question_id, then by ISO week
        $byQ = $rows->groupBy('question_id');
        $out = ['weeks' => $weeks, 'questions' => []];

        foreach ($byQ as $qid => $rs) {
            $byWeek = $rs->groupBy(fn($r) => Carbon::parse($r->submitted_at)->format('o-\WW'));
            $series = [];
            foreach ($weeks as $wk) {
                $bucket = $byWeek->get($wk, collect());
                $series[] = $bucket->isEmpty() ? null : round($bucket->sum('rating') / $bucket->count(), 2);
            }
            $out['questions'][] = [
                'question_id'   => (int) $qid,
                'question_text' => $rs->first()->question_text ?? '',
                'series'        => $series,
            ];
        }
        return $out;
    }

    /**
     * Team-wise breakdown — overall pulse score by department.
     * @param array<int>|null $empIds optional employee whitelist (for manager scope)
     * Returns rows: {department_id, department_name, avg, total, low}
     */
    public function byTeam(int $creatorId, ?int $surveyId = null, ?Carbon $from = null, ?Carbon $to = null, ?array $empIds = null): array
    {
        $rows = $this->pullPulseRatings($creatorId, $surveyId, $from, $to, $empIds);
        $byDept = $rows->groupBy(fn($r) => $r->department_id ?: 0);

        $deptNames = Department::where('created_by', $creatorId)->pluck('name', 'id')->toArray();

        $out = [];
        foreach ($byDept as $deptId => $rs) {
            $total = $rs->count();
            $sum   = $rs->sum('rating');
            $avg   = $total > 0 ? round($sum / $total, 2) : 0.0;
            $out[] = [
                'department_id'   => (int) $deptId,
                'department_name' => $deptId && isset($deptNames[$deptId]) ? $deptNames[$deptId] : __('Unassigned'),
                'avg'             => $avg,
                'total'           => $total,
                'low'             => $avg > 0 && $avg < self::LOW_SCORE_THRESHOLD,
            ];
        }
        usort($out, fn($a, $b) => $a['avg'] <=> $b['avg']);
        return $out;
    }

    /**
     * Headline: total responses, avg score, low-question count.
     * @param array<int>|null $empIds optional employee whitelist (for manager scope)
     */
    public function summary(int $creatorId, ?int $surveyId = null, ?Carbon $from = null, ?Carbon $to = null, ?array $empIds = null): array
    {
        $rows = $this->pullPulseRatings($creatorId, $surveyId, $from, $to, $empIds);
        $total = $rows->count();
        $sum   = $rows->sum('rating');
        $avg   = $total > 0 ? round($sum / $total, 2) : 0.0;

        $byQ = $this->byQuestion($creatorId, $surveyId, $from, $to, $empIds);
        $lowQ = collect($byQ)->where('low', true)->count();

        return [
            'total_responses' => $total,
            'avg_score'       => $avg,
            'low_questions'   => $lowQ,
            'questions_count' => count($byQ),
        ];
    }

    /**
     * Detect low-scoring questions and create one HR alert per (survey × question)
     * if not already alerted recently. Idempotent — safe to call after each
     * submit or via a scheduled job.
     *
     * @return int count of newly-created alerts
     */
    public function generateLowScoreAlerts(int $creatorId, ?int $surveyId = null): int
    {
        // Look at last 7 days only to keep alerts timely
        $byQ = $this->byQuestion($creatorId, $surveyId, now()->subDays(7), now());
        $created = 0;

        foreach ($byQ as $row) {
            if (!$row['low'] || $row['total'] < 3) continue; // ignore tiny samples

            // The alerts table requires survey_id + response_id. For aggregate
            // alerts (no single response is the cause), we resolve a recent
            // response on this survey/question to attach the FK.
            $surveysWithQ = SurveyAnswer::join('survey_questions as q', 'q.id', '=', 'survey_answers.question_id')
                ->join('survey_responses as r', 'r.id', '=', 'survey_answers.response_id')
                ->join('employee_surveys as s', 's.id', '=', 'r.survey_id')
                ->where('s.created_by', $creatorId)
                ->where('s.type', 'pulse')
                ->where('q.id', $row['question_id'])
                ->when($surveyId, fn($qb) => $qb->where('s.id', $surveyId))
                ->whereNotNull('survey_answers.rating_value')
                ->orderByDesc('r.id')
                ->select('s.id as sid', 'r.id as rid')
                ->first();

            if (!$surveysWithQ) continue;
            $sid = (int) $surveysWithQ->sid;
            $rid = (int) $surveysWithQ->rid;

            // De-dupe: an open alert for this survey+question already exists?
            $exists = SurveyAlert::where('created_by', $creatorId)
                ->where('survey_id', $sid)
                ->where('alert_type', 'low_pulse_score')
                ->where('status', 'open')
                ->whereRaw("JSON_VALID(message) AND JSON_EXTRACT(message, '$.question_id') = ?", [$row['question_id']])
                ->exists();

            if ($exists) continue;

            SurveyAlert::create([
                'survey_id'   => $sid,
                'response_id' => $rid,
                'employee_id' => null,                 // aggregate, not tied to one person
                'alert_type'  => 'low_pulse_score',
                'risk_level'  => $row['avg'] < 2.0 ? 'high' : 'medium',
                // Store structured payload so the alerts page can render context
                'message'     => json_encode([
                    'question_id'   => $row['question_id'],
                    'question_text' => $row['question_text'],
                    'avg'           => $row['avg'],
                    'total'         => $row['total'],
                    'window_days'   => 7,
                ]),
                'status'      => 'open',
                'created_by'  => $creatorId,
            ]);
            $created++;
        }
        return $created;
    }
}
