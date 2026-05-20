<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeSurvey;
use App\Models\SurveyAnswer;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Employee Net Promoter Score calculator.
 *
 * eNPS uses a single 0–10 question:
 *   "How likely are you to recommend this company as a place to work?"
 *
 * Classification:
 *   Promoters  → rating 9 or 10
 *   Passives   → rating 7 or 8
 *   Detractors → rating 0 to 6
 *
 * Score formula:
 *   eNPS = % Promoters − % Detractors    (range: −100 to +100)
 *
 * The service collects all rating values from any question marked
 * `is_enps = true` across the company's surveys (eNPS-type or otherwise),
 * so a single eNPS question on any survey contributes to the company-wide
 * score.
 */
class EnpsService
{
    public const PROMOTER_MIN  = 9;
    public const PASSIVE_MIN   = 7;
    public const DETRACTOR_MAX = 6;

    /**
     * Pull all eNPS rating values + metadata for a creator scope, optionally
     * narrowed to a single survey or date range. Each row returns:
     *   ['rating' => 9.0, 'employee_id' => 12, 'department_id' => 2, 'submitted_at' => '...']
     *
     * Anonymous responses keep employee_id = NULL (which means the row will
     * show up under "Unknown" department in dept-wise breakdown — by design).
     */
    public function pullRatings(int $creatorId, ?int $surveyId = null, ?Carbon $from = null, ?Carbon $to = null): Collection
    {
        $q = SurveyAnswer::query()
            ->join('survey_questions as q', 'q.id', '=', 'survey_answers.question_id')
            ->join('survey_responses as r', 'r.id', '=', 'survey_answers.response_id')
            ->join('employee_surveys as s', 's.id', '=', 'r.survey_id')
            ->leftJoin('employees as e', 'e.id', '=', 'r.employee_id')
            ->where('s.created_by', $creatorId)
            ->where('q.is_enps', true)
            ->whereNotNull('survey_answers.rating_value');

        if ($surveyId)            $q->where('s.id', $surveyId);
        if ($from)                $q->where('r.submitted_at', '>=', $from);
        if ($to)                  $q->where('r.submitted_at', '<=', $to);

        // Skip the duplicate "guard" rows that anonymous submissions create
        // (they have no answers attached). Joining on survey_answers naturally
        // excludes them, but for an answer-bearing row of an anonymous survey
        // employee_id is NULL — we keep that, it just rolls up as "Unknown".

        return $q->select([
                'survey_answers.rating_value as rating',
                'r.employee_id',
                'e.department_id',
                'r.submitted_at',
                's.is_anonymous',
            ])
            ->orderBy('r.submitted_at')
            ->get();
    }

    /**
     * Classify a single rating value.
     *
     * @return string 'promoter' | 'passive' | 'detractor'
     */
    public function classify(float $rating): string
    {
        if ($rating >= self::PROMOTER_MIN)  return 'promoter';
        if ($rating >= self::PASSIVE_MIN)   return 'passive';
        return 'detractor';
    }

    /**
     * Aggregate counts + score for an arbitrary collection of rating rows.
     *
     * @param Collection $rows objects with ->rating
     * @return array {total, promoters, passives, detractors, score}
     */
    public function summarize(Collection $rows): array
    {
        $total = $rows->count();
        $p = 0; $pa = 0; $d = 0;

        foreach ($rows as $row) {
            switch ($this->classify((float) $row->rating)) {
                case 'promoter':  $p++;  break;
                case 'passive':   $pa++; break;
                default:          $d++;  break;
            }
        }

        $score = $total > 0 ? round((($p - $d) / $total) * 100, 1) : 0.0;

        return [
            'total'      => $total,
            'promoters'  => $p,
            'passives'   => $pa,
            'detractors' => $d,
            'score'      => $score,            // -100 .. +100
            'pct_p'      => $total > 0 ? round(($p  / $total) * 100, 1) : 0.0,
            'pct_pa'     => $total > 0 ? round(($pa / $total) * 100, 1) : 0.0,
            'pct_d'      => $total > 0 ? round(($d  / $total) * 100, 1) : 0.0,
        ];
    }

    /**
     * Department-wise eNPS breakdown.
     *
     * @return array<int, array> rows of {department_id, department_name, ...summary}
     */
    public function byDepartment(int $creatorId, ?int $surveyId = null, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $rows = $this->pullRatings($creatorId, $surveyId, $from, $to);
        $byDept = $rows->groupBy(fn($r) => $r->department_id ?: 0);

        $deptNames = Department::where('created_by', $creatorId)
            ->pluck('name', 'id')
            ->toArray();

        $out = [];
        foreach ($byDept as $deptId => $deptRows) {
            $name = $deptId && isset($deptNames[$deptId])
                ? $deptNames[$deptId]
                : __('Unassigned');
            $out[] = array_merge(
                ['department_id' => (int) $deptId, 'department_name' => $name],
                $this->summarize($deptRows)
            );
        }

        // Sort by score desc, ties broken by total desc (more responses first).
        usort($out, function ($a, $b) {
            return $b['score'] <=> $a['score']
                ?: $b['total'] <=> $a['total'];
        });

        return $out;
    }

    /**
     * Month-wise trend for the last N months.
     *
     * @return array<int, array> rows of {label, year, month, ...summary}
     */
    public function monthlyTrend(int $creatorId, int $monthsBack = 12, ?int $surveyId = null): array
    {
        $end   = now()->endOfMonth();
        $start = now()->copy()->subMonths($monthsBack - 1)->startOfMonth();

        $rows = $this->pullRatings($creatorId, $surveyId, $start, $end);

        // Group by Y-m
        $byMonth = $rows->groupBy(function ($r) {
            return Carbon::parse($r->submitted_at)->format('Y-m');
        });

        $out = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $key = $cursor->format('Y-m');
            $monthRows = $byMonth->get($key, collect());
            $out[] = array_merge(
                [
                    'label' => $cursor->format('M Y'),
                    'year'  => (int) $cursor->year,
                    'month' => (int) $cursor->month,
                ],
                $this->summarize($monthRows)
            );
            $cursor->addMonth();
        }

        return $out;
    }

    /**
     * Top-level company eNPS for a scope. Convenience method.
     */
    public function companyScore(int $creatorId, ?int $surveyId = null, ?Carbon $from = null, ?Carbon $to = null): array
    {
        return $this->summarize($this->pullRatings($creatorId, $surveyId, $from, $to));
    }
}
