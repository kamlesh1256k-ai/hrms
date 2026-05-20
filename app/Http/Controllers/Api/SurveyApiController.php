<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeSurvey;
use App\Models\SurveyAlert;
use App\Models\SurveyAnswer;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use App\Models\SurveySentimentAnalysis;
use App\Services\EnpsService;
use App\Services\PulseService;
use App\Services\SentimentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/**
 * REST API for the Survey module.
 *
 * Auth: Sanctum bearer token (`Authorization: Bearer <token>`).
 * Scope: every action is scoped to the authenticated user's company
 *        (via creatorId()), and permissions are enforced per endpoint.
 *
 * Response envelope:
 *   200/201/204 → { ok: true, data: ... }      (or empty body for 204)
 *   4xx         → { ok: false, error: '...', errors?: {field:[...]}}
 *
 * The web routes (resources/views/surveys/*) remain unchanged. This file
 * gives mobile/external integrations a clean, JSON-only surface that
 * reuses the same services (EnpsService, PulseService, SentimentService).
 */
class SurveyApiController extends Controller
{
    /* ──────────────────────────────────────────────────────────────
     * Survey CRUD
     * ──────────────────────────────────────────────────────────── */

    /** GET /api/surveys?type=&status=&q= */
    public function index(Request $request): JsonResponse
    {
        $this->ensure('manage-surveys');
        $creatorId = $this->creatorId();

        $query = EmployeeSurvey::where('created_by', $creatorId)
            ->withCount(['questions', 'responses'])
            ->orderByDesc('id');

        if ($request->filled('type'))   $query->where('type', $request->input('type'));
        if ($request->filled('status')) $query->where('status', $request->input('status'));
        if ($request->filled('q'))      $query->where('title', 'like', '%' . $request->input('q') . '%');

        return $this->ok($query->paginate(min(100, (int) $request->input('per_page', 20))));
    }

    /** POST /api/surveys */
    public function store(Request $request): JsonResponse
    {
        $this->ensure('manage-surveys');

        $data = $request->validate([
            'title'            => 'required|string|max:200',
            'description'      => 'nullable|string|max:2000',
            'type'             => 'required|in:employee,pulse,enps',
            'start_date'       => 'nullable|date',
            'end_date'         => 'nullable|date|after_or_equal:start_date',
            'status'           => 'nullable|in:draft,active,closed',
            'is_anonymous'     => 'nullable|boolean',
            'department_ids'   => 'nullable|array',
            'department_ids.*' => 'integer|exists:departments,id',
            'frequency'        => 'nullable|in:once,weekly,monthly,custom',
        ]);

        $deptIds = $data['department_ids'] ?? null;
        if (is_array($deptIds) && count($deptIds) === 0) $deptIds = null;

        $survey = EmployeeSurvey::create([
            'title'          => $data['title'],
            'description'    => $data['description'] ?? null,
            'type'           => $data['type'],
            'start_date'     => $data['start_date'] ?? null,
            'end_date'       => $data['end_date'] ?? null,
            'status'         => $data['status'] ?? 'draft',
            'is_anonymous'   => (bool)($data['is_anonymous'] ?? false),
            'department_ids' => $deptIds,
            'frequency'      => $data['frequency'] ?? 'once',
            'created_by'     => $this->creatorId(),
        ]);

        // Auto-seed default questions for eNPS / pulse types (parity with web flow)
        if ($survey->type === 'enps') {
            SurveyQuestion::create([
                'survey_id'     => $survey->id,
                'question_text' => 'How likely are you to recommend this company as a place to work?',
                'question_type' => 'rating_10',
                'is_required'   => true,
                'is_enps'       => true,
                'order_no'      => 1,
            ]);
        } elseif ($survey->type === 'pulse') {
            $defaults = [
                ['How are you feeling this week?',                'rating_5', true],
                ['Is your workload manageable?',                  'rating_5', true],
                ['Are you getting support from your manager?',    'rating_5', true],
                ['Do you feel motivated at work?',                'rating_5', true],
                ['Any blocker or concern?',                       'text',     false],
            ];
            foreach ($defaults as $i => [$txt, $type, $req]) {
                SurveyQuestion::create([
                    'survey_id'     => $survey->id,
                    'question_text' => $txt,
                    'question_type' => $type,
                    'is_required'   => $req,
                    'order_no'      => $i + 1,
                ]);
            }
        }

        return $this->ok($survey->fresh()->load('questions'), 201);
    }

    /** GET /api/surveys/{id} */
    public function show(int $id): JsonResponse
    {
        $this->ensure('manage-surveys');
        $survey = EmployeeSurvey::with(['questions' => fn($q) => $q->orderBy('order_no')])
            ->where('created_by', $this->creatorId())->findOrFail($id);
        return $this->ok($survey);
    }

    /** PUT /api/surveys/{id} */
    public function update(Request $request, int $id): JsonResponse
    {
        $this->ensure('manage-surveys');
        $survey = EmployeeSurvey::where('created_by', $this->creatorId())->findOrFail($id);

        $data = $request->validate([
            'title'            => 'sometimes|required|string|max:200',
            'description'      => 'nullable|string|max:2000',
            'type'             => 'sometimes|required|in:employee,pulse,enps',
            'start_date'       => 'nullable|date',
            'end_date'         => 'nullable|date|after_or_equal:start_date',
            'status'           => 'sometimes|required|in:draft,active,closed',
            'is_anonymous'     => 'nullable|boolean',
            'department_ids'   => 'nullable|array',
            'department_ids.*' => 'integer|exists:departments,id',
            'frequency'        => 'nullable|in:once,weekly,monthly,custom',
        ]);

        if ($survey->responses()->exists()) {
            // Lock structural fields once responses exist
            unset($data['type'], $data['is_anonymous']);
        }

        if (array_key_exists('department_ids', $data) && is_array($data['department_ids']) && count($data['department_ids']) === 0) {
            $data['department_ids'] = null;
        }

        $survey->update($data);
        return $this->ok($survey->fresh());
    }

    /** DELETE /api/surveys/{id} */
    public function destroy(int $id): JsonResponse
    {
        $this->ensure('manage-surveys');
        $survey = EmployeeSurvey::where('created_by', $this->creatorId())->findOrFail($id);

        if ($survey->status === 'active') {
            return $this->fail(__('Close the survey before deleting.'), 422);
        }
        $survey->delete();
        return response()->json(['ok' => true], 200);
    }

    /** POST /api/surveys/{id}/activate */
    public function activate(int $id): JsonResponse
    {
        $this->ensure('manage-surveys');
        $survey = EmployeeSurvey::where('created_by', $this->creatorId())->findOrFail($id);

        if ($survey->status === 'active')          return $this->ok($survey, 200);
        if ($survey->questions()->count() === 0)   return $this->fail(__('Add at least one question first.'), 422);

        $survey->update(['status' => 'active', 'last_sent_at' => now()]);

        // Pulse: dispatch invite mails (non-blocking)
        if ($survey->type === 'pulse') {
            try { app(\App\Services\PulseNotifier::class)->dispatch($survey, false); } catch (\Throwable $e) {}
        }
        return $this->ok($survey->fresh());
    }

    /** POST /api/surveys/{id}/close */
    public function close(int $id): JsonResponse
    {
        $this->ensure('manage-surveys');
        $survey = EmployeeSurvey::where('created_by', $this->creatorId())->findOrFail($id);
        if ($survey->status !== 'closed') $survey->update(['status' => 'closed']);
        return $this->ok($survey->fresh());
    }

    /* ──────────────────────────────────────────────────────────────
     * Question management
     * ──────────────────────────────────────────────────────────── */

    /** GET /api/surveys/{id}/questions */
    public function questions(int $id): JsonResponse
    {
        $this->ensure('manage-surveys');
        $survey = EmployeeSurvey::where('created_by', $this->creatorId())->findOrFail($id);
        return $this->ok($survey->questions()->orderBy('order_no')->get());
    }

    /** POST /api/surveys/{id}/questions */
    public function questionStore(Request $request, int $id): JsonResponse
    {
        $this->ensure('manage-surveys');
        $survey = EmployeeSurvey::where('created_by', $this->creatorId())->findOrFail($id);

        $data = $request->validate([
            'question_text' => 'required|string|max:500',
            'question_type' => 'required|in:rating_5,rating_10,yes_no,multiple_choice,text',
            'options'       => 'nullable|array',
            'options.*'     => 'string|max:200',
            'is_required'   => 'nullable|boolean',
            'is_enps'       => 'nullable|boolean',
        ]);

        $opts = array_values(array_filter(array_map('trim', $data['options'] ?? []), fn($v) => $v !== ''));
        if ($data['question_type'] === 'multiple_choice' && count($opts) < 2) {
            return $this->fail(__('Multiple choice needs at least 2 options.'), 422);
        }

        $q = SurveyQuestion::create([
            'survey_id'     => $survey->id,
            'question_text' => $data['question_text'],
            'question_type' => $data['question_type'],
            'options'       => $data['question_type'] === 'multiple_choice' ? $opts : null,
            'is_required'   => (bool)($data['is_required'] ?? true),
            'is_enps'       => (bool)($data['is_enps'] ?? false),
            'order_no'      => ($survey->questions()->max('order_no') ?? 0) + 1,
        ]);
        return $this->ok($q, 201);
    }

    /* ──────────────────────────────────────────────────────────────
     * Employee — active surveys + submission
     * ──────────────────────────────────────────────────────────── */

    /** GET /api/my-surveys — active surveys assigned to the caller */
    public function myActive(Request $request): JsonResponse
    {
        $this->ensure('submit-surveys');
        $employee = $this->currentEmployee();
        if (!$employee) return $this->fail(__('Employee record not found.'), 404);

        $today = now()->toDateString();
        $surveys = EmployeeSurvey::where('created_by', $this->creatorId())
            ->where('status', 'active')
            ->where(fn($q) => $q->whereNull('start_date')->orWhere('start_date', '<=', $today))
            ->where(fn($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $today))
            ->whereHas('questions')
            ->withCount('questions')
            ->get()
            ->filter(function ($s) use ($employee) {
                $depts = $s->department_ids;
                return empty($depts) || in_array((int)$employee->department_id, array_map('intval', $depts), true);
            })->values();

        $submittedIds = SurveyResponse::where('employee_id', $employee->id)
            ->whereIn('survey_id', $surveys->pluck('id'))->pluck('survey_id');

        $surveys = $surveys->map(function ($s) use ($submittedIds) {
            $arr = $s->toArray();
            $arr['already_submitted'] = $submittedIds->contains($s->id);
            return $arr;
        });
        return $this->ok($surveys);
    }

    /**
     * POST /api/my-surveys/{id}/submit
     * Body: { answers: { "<question_id>": { rating?: number, value?: string, text?: string } } }
     */
    public function submit(Request $request, int $id): JsonResponse
    {
        $this->ensure('submit-surveys');
        $employee = $this->currentEmployee();
        if (!$employee) return $this->fail(__('Employee record not found.'), 404);

        $survey = EmployeeSurvey::with(['questions' => fn($q) => $q->orderBy('order_no')])
            ->where('created_by', $this->creatorId())->findOrFail($id);

        if (!$survey->isOpen()) return $this->fail(__('Survey is not currently accepting responses.'), 409);

        // Audience check
        $depts = $survey->department_ids;
        if (!empty($depts) && !in_array((int)$employee->department_id, array_map('intval', $depts), true)) {
            return $this->fail(__('Survey not assigned to your department.'), 403);
        }

        // One-time guard
        if (SurveyResponse::where('survey_id', $survey->id)->where('employee_id', $employee->id)->exists()) {
            return $this->fail(__('You have already submitted this survey.'), 409);
        }

        $answers = (array) $request->input('answers', []);

        // Validate required questions
        foreach ($survey->questions as $q) {
            if (!$q->is_required) continue;
            $a = $answers[$q->id] ?? [];
            $missing = false;
            if ($q->isRating()) {
                $missing = !isset($a['rating']) || $a['rating'] === '';
            } elseif ($q->question_type === 'text') {
                $missing = empty(trim((string)($a['text'] ?? '')));
            } else {
                $missing = empty(($a['value'] ?? null));
            }
            if ($missing) {
                return $this->fail(__('Please answer all required questions.'), 422, [
                    'question_id' => $q->id,
                ]);
            }
        }

        $textAnswerIds = [];
        DB::transaction(function () use ($survey, $employee, $answers, &$textAnswerIds) {
            $response = SurveyResponse::create([
                'employee_id'  => $survey->is_anonymous ? null : $employee->id,
                'survey_id'    => $survey->id,
                'is_anonymous' => $survey->is_anonymous,
                'submitted_at' => now(),
            ]);
            // Anonymous guard row to enforce one-submit-per-employee
            if ($survey->is_anonymous) {
                try {
                    SurveyResponse::create([
                        'employee_id'  => $employee->id,
                        'survey_id'    => $survey->id,
                        'is_anonymous' => true,
                        'submitted_at' => now(),
                    ]);
                } catch (\Throwable $e) {}
            }

            foreach ($survey->questions as $q) {
                $a = $answers[$q->id] ?? [];
                $rating = null; $val = null; $text = null;
                if ($q->isRating() && isset($a['rating']) && $a['rating'] !== '') {
                    $rating = max(0, min($q->ratingMax(), (float) $a['rating']));
                } elseif ($q->question_type === 'text') {
                    $text = mb_substr(trim((string)($a['text'] ?? '')), 0, 5000) ?: null;
                } else {
                    $val = isset($a['value']) ? mb_substr((string)$a['value'], 0, 500) : null;
                }
                if ($rating === null && $val === null && $text === null) continue;

                $created = SurveyAnswer::create([
                    'response_id'  => $response->id,
                    'question_id'  => $q->id,
                    'answer_value' => $val,
                    'rating_value' => $rating,
                    'text_value'   => $text,
                ]);
                if (!empty($text) && mb_strlen(trim($text)) >= 3) $textAnswerIds[] = $created->id;
            }
        });

        // Sentiment + alert pipeline (post-commit, best-effort)
        if (!empty($textAnswerIds)) {
            try {
                $sentiment = app(SentimentService::class);
                foreach ($textAnswerIds as $aid) {
                    $ans = SurveyAnswer::find($aid);
                    if (!$ans) continue;
                    $row = $sentiment->analyzeAndStore($ans);
                    if ($row->sentiment === 'negative' && $row->risk_level === 'high') {
                        SurveyAlert::create([
                            'survey_id'   => $survey->id,
                            'response_id' => $ans->response_id,
                            'employee_id' => $survey->is_anonymous ? null : $employee->id,
                            'alert_type'  => 'negative_feedback',
                            'risk_level'  => 'high',
                            'message'     => json_encode([
                                'kind'      => 'negative_high_risk_feedback',
                                'topics'    => $row->topic ?? [],
                                'emotion'   => $row->emotion,
                                'sentiment' => $row->sentiment,
                                'risk'      => $row->risk_level,
                                'excerpt'   => mb_substr((string)$ans->text_value, 0, 240),
                                'summary'   => $row->ai_summary,
                            ]),
                            'status'      => 'open',
                            'created_by'  => $survey->created_by,
                        ]);
                    }
                }
            } catch (\Throwable $e) {
                \Log::warning('API submit sentiment failed', ['err' => $e->getMessage()]);
            }
        }

        return $this->ok(['message' => __('Response recorded.')], 201);
    }

    /* ──────────────────────────────────────────────────────────────
     * Analytics
     * ──────────────────────────────────────────────────────────── */

    /** GET /api/surveys/{id}/analytics */
    public function analytics(int $id, EnpsService $enps): JsonResponse
    {
        $this->ensure('view-survey-analytics');
        $survey = EmployeeSurvey::with('questions')->where('created_by', $this->creatorId())
            ->withCount('responses')->findOrFail($id);

        // Per-question stats
        $questionStats = [];
        foreach ($survey->questions as $q) {
            $answers = SurveyAnswer::where('question_id', $q->id);
            $base = ['id' => $q->id, 'text' => $q->question_text, 'type' => $q->question_type, 'order_no' => $q->order_no];
            if ($q->isRating()) {
                $base['total'] = (clone $answers)->whereNotNull('rating_value')->count();
                $base['avg']   = (clone $answers)->whereNotNull('rating_value')->avg('rating_value');
            } else {
                $base['total'] = (clone $answers)->count();
                $base['avg']   = null;
            }
            $questionStats[] = $base;
        }

        return $this->ok([
            'survey'    => $survey,
            'enps'      => $enps->companyScore($this->creatorId(), $survey->id),
            'questions' => $questionStats,
        ]);
    }

    /** GET /api/reports/enps?survey_id=&months= */
    public function enps(Request $request, EnpsService $enps): JsonResponse
    {
        $this->ensure('view-survey-analytics');
        $surveyId = $request->integer('survey_id') ?: null;
        $months   = max(3, min(24, (int)$request->input('months', 12)));

        return $this->ok([
            'summary'  => $enps->companyScore($this->creatorId(), $surveyId),
            'by_dept'  => $enps->byDepartment($this->creatorId(), $surveyId),
            'monthly'  => $enps->monthlyTrend($this->creatorId(), $months, $surveyId),
            'months'   => $months,
        ]);
    }

    /** GET /api/reports/sentiment?from=&to= */
    public function sentiment(Request $request): JsonResponse
    {
        $this->ensure('view-survey-analytics');
        $creatorId = $this->creatorId();

        if (!Schema::hasTable('survey_sentiment_analysis')) {
            return $this->ok(['by_sentiment' => [], 'by_risk' => [], 'recent_high_risk' => []]);
        }

        $from = $request->input('from') ? \Carbon\Carbon::parse($request->input('from'))->startOfDay() : null;
        $to   = $request->input('to')   ? \Carbon\Carbon::parse($request->input('to'))->endOfDay()   : null;

        $base = SurveySentimentAnalysis::query()
            ->join('survey_answers as a', 'a.id', '=', 'survey_sentiment_analysis.answer_id')
            ->join('survey_responses as r', 'r.id', '=', 'a.response_id')
            ->join('employee_surveys  as s', 's.id', '=', 'r.survey_id')
            ->where('s.created_by', $creatorId)
            ->when($from, fn($qb) => $qb->where('r.submitted_at', '>=', $from))
            ->when($to,   fn($qb) => $qb->where('r.submitted_at', '<=', $to));

        $bySentiment = (clone $base)->groupBy('survey_sentiment_analysis.sentiment')
            ->select('survey_sentiment_analysis.sentiment as sentiment', DB::raw('COUNT(*) as total'))
            ->get();

        $byRisk = (clone $base)->groupBy('survey_sentiment_analysis.risk_level')
            ->select('survey_sentiment_analysis.risk_level as risk_level', DB::raw('COUNT(*) as total'))
            ->get();

        $recent = (clone $base)->where('survey_sentiment_analysis.risk_level', 'high')
            ->orderByDesc('survey_sentiment_analysis.id')->limit(25)
            ->select(
                'survey_sentiment_analysis.sentiment',
                'survey_sentiment_analysis.emotion',
                'survey_sentiment_analysis.risk_level',
                'survey_sentiment_analysis.topic',
                'survey_sentiment_analysis.ai_summary',
                'r.submitted_at',
                's.title as survey_title'
            )->get();

        return $this->ok([
            'by_sentiment'     => $bySentiment,
            'by_risk'          => $byRisk,
            'recent_high_risk' => $recent,
        ]);
    }

    /** GET /api/reports/pulse?survey_id=&weeks= */
    public function pulse(Request $request, PulseService $pulse): JsonResponse
    {
        $this->ensure('view-survey-analytics');
        $surveyId = $request->integer('survey_id') ?: null;
        $weeks    = max(4, min(26, (int) $request->input('weeks', 12)));

        return $this->ok([
            'summary'  => $pulse->summary($this->creatorId(), $surveyId),
            'by_q'     => $pulse->byQuestion($this->creatorId(), $surveyId),
            'by_team'  => $pulse->byTeam($this->creatorId(), $surveyId),
            'trend'    => $pulse->weeklyTrend($this->creatorId(), $weeks, $surveyId),
            'weeks'    => $weeks,
            'low_threshold' => PulseService::LOW_SCORE_THRESHOLD,
        ]);
    }

    /** GET /api/survey-alerts?status=open|resolved|all */
    public function alerts(Request $request): JsonResponse
    {
        $this->ensure('view-survey-alerts');
        $status = $request->input('status', 'open');
        if (!in_array($status, ['open', 'resolved', 'all'], true)) $status = 'open';

        $q = SurveyAlert::with('survey')->where('created_by', $this->creatorId())->orderByDesc('id');
        if ($status !== 'all') $q->where('status', $status);

        return $this->ok($q->paginate(min(100, (int)$request->input('per_page', 20))));
    }

    /** GET /api/surveys/{id}/export — CSV download */
    public function export(int $id)
    {
        $this->ensure('export-surveys');
        $survey = EmployeeSurvey::with(['questions' => fn($q) => $q->orderBy('order_no')])
            ->where('created_by', $this->creatorId())->findOrFail($id);

        $filename = 'survey-export-' . $survey->id . '-' . now()->format('Ymd_His') . '.csv';
        return response()->streamDownload(function () use ($survey) {
            $f = fopen('php://output', 'w');
            // Header row: question texts
            $headers = ['response_id', 'submitted_at', 'employee_id'];
            foreach ($survey->questions as $q) $headers[] = '#' . $q->order_no . ' ' . $q->question_text;
            fputcsv($f, $headers);

            $responses = SurveyResponse::where('survey_id', $survey->id)
                ->orderBy('id')->get();
            foreach ($responses as $r) {
                $row = [
                    $r->id,
                    optional($r->submitted_at)->format('Y-m-d H:i:s'),
                    $survey->is_anonymous ? '(anonymous)' : $r->employee_id,
                ];
                $answersMap = SurveyAnswer::where('response_id', $r->id)
                    ->get()->keyBy('question_id');
                foreach ($survey->questions as $q) {
                    $a = $answersMap[$q->id] ?? null;
                    if (!$a)                         { $row[] = ''; continue; }
                    if ($a->rating_value !== null)   { $row[] = $a->rating_value; continue; }
                    if (!empty($a->text_value))      { $row[] = $a->text_value; continue; }
                    $row[] = $a->answer_value ?? '';
                }
                fputcsv($f, $row);
            }
            fclose($f);
        }, $filename, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /* ──────────────────────────────────────────────────────────────
     * Helpers
     * ──────────────────────────────────────────────────────────── */

    protected function creatorId(): int
    {
        return (int) (Auth::user()->creatorId() ?? Auth::id());
    }

    protected function currentEmployee(): ?Employee
    {
        $user = Auth::user();
        if (!$user) return null;
        return Employee::where('user_id', $user->id)->first();
    }

    /** Permission check; aborts with 403 JSON if denied. */
    protected function ensure(string $permission): void
    {
        $user = Auth::user();
        if (!$user || !$user->can($permission)) {
            abort(response()->json(['ok' => false, 'error' => __('Permission denied.')], 403));
        }
    }

    protected function ok($data, int $status = 200): JsonResponse
    {
        return response()->json(['ok' => true, 'data' => $data], $status);
    }

    protected function fail(string $error, int $status = 400, array $extra = []): JsonResponse
    {
        return response()->json(array_merge(['ok' => false, 'error' => $error], $extra), $status);
    }
}
