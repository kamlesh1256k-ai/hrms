<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeSurvey;
use App\Models\SurveyAnswer;
use App\Models\SurveyAlert;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use App\Models\SurveySentimentAnalysis;
use App\Services\EnpsService;
use App\Services\PulseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SurveyController extends Controller
{
    /**
     * HR/Admin landing — list of surveys with filters.
     * Filters: type (employee/pulse/enps), status (draft/active/closed), q (title search).
     * Each row also shows question count + response count for quick scanning.
     */
    public function index(Request $request)
    {
        $this->authorizeManage();

        $creatorId = Auth::user()->creatorId();
        $filters   = $request->only(['type', 'status', 'q']);

        $query = EmployeeSurvey::query()
            ->where('created_by', $creatorId)
            ->withCount(['questions', 'responses'])
            ->orderByDesc('id');

        if (!empty($filters['type']))   $query->where('type', $filters['type']);
        if (!empty($filters['status'])) $query->where('status', $filters['status']);
        if (!empty($filters['q'])) {
            $query->where('title', 'like', '%' . $filters['q'] . '%');
        }

        $surveys = $query->paginate(15)->withQueryString();

        // Top counters (always reflect full company scope, not the filtered view).
        $totals = [
            'all'    => EmployeeSurvey::where('created_by', $creatorId)->count(),
            'draft'  => EmployeeSurvey::where('created_by', $creatorId)->where('status', 'draft')->count(),
            'active' => EmployeeSurvey::where('created_by', $creatorId)->where('status', 'active')->count(),
            'closed' => EmployeeSurvey::where('created_by', $creatorId)->where('status', 'closed')->count(),
        ];

        // Dashboard cards (company-wide scope)
        $totalResponses = SurveyAnswer::query()
            ->join('survey_responses as r', 'r.id', '=', 'survey_answers.response_id')
            ->join('employee_surveys  as s', 's.id', '=', 'r.survey_id')
            ->where('s.created_by', $creatorId)
            ->distinct('r.id')
            ->count('r.id');

        $avgSatisfaction = (float) SurveyAnswer::query()
            ->join('survey_questions as q', 'q.id', '=', 'survey_answers.question_id')
            ->join('survey_responses  as r', 'r.id', '=', 'survey_answers.response_id')
            ->join('employee_surveys   as s', 's.id', '=', 'r.survey_id')
            ->where('s.created_by', $creatorId)
            ->where('q.question_type', 'rating_5')
            ->whereNotNull('survey_answers.rating_value')
            ->avg('survey_answers.rating_value');

        $enpsScore = app(EnpsService::class)->companyScore($creatorId);

        $negativeSentimentCount = 0;
        if (Schema::hasTable('survey_sentiment_analysis')) {
            $negativeSentimentCount = SurveySentimentAnalysis::query()
                ->join('survey_answers as a', 'a.id', '=', 'survey_sentiment_analysis.answer_id')
                ->join('survey_responses as r', 'r.id', '=', 'a.response_id')
                ->join('employee_surveys  as s', 's.id', '=', 'r.survey_id')
                ->where('s.created_by', $creatorId)
                ->where('survey_sentiment_analysis.sentiment', 'negative')
                ->count();
        }

        $lowRatingCount = SurveyAnswer::query()
            ->join('survey_questions as q', 'q.id', '=', 'survey_answers.question_id')
            ->join('survey_responses  as r', 'r.id', '=', 'survey_answers.response_id')
            ->join('employee_surveys   as s', 's.id', '=', 'r.survey_id')
            ->where('s.created_by', $creatorId)
            ->where('q.question_type', 'rating_5')
            ->whereNotNull('survey_answers.rating_value')
            ->where('survey_answers.rating_value', '<', PulseService::LOW_SCORE_THRESHOLD)
            ->count();

        $negativeFeedbackCount = $negativeSentimentCount > 0 ? $negativeSentimentCount : $lowRatingCount;

        $highRiskAlerts = SurveyAlert::where('created_by', $creatorId)
            ->where('status', 'open')
            ->where('risk_level', 'high')
            ->count();

        // ── Pending received responses ──
        // For every ACTIVE survey, count the eligible audience (department-aware)
        // minus employees who have already submitted. Sum across surveys gives
        // the total "yet to come" response count HR can chase.
        // Note: anonymous surveys also stash a guard row keyed by employee_id —
        // so SurveyResponse.where(employee_id, …) catches both anon and identified
        // submissions, no double-count.
        $pendingResponses = 0;
        $activeSurveysList = EmployeeSurvey::where('created_by', $creatorId)
            ->where('status', 'active')
            ->whereHas('questions')
            ->get(['id', 'department_ids']);

        foreach ($activeSurveysList as $s) {
            $eligibleQ = Employee::where('created_by', $creatorId);
            if (!empty($s->department_ids) && is_array($s->department_ids)) {
                $eligibleQ->whereIn('department_id', $s->department_ids);
            }
            $eligibleCount = $eligibleQ->count();
            $respondedCount = SurveyResponse::where('survey_id', $s->id)
                ->whereNotNull('employee_id')
                ->distinct('employee_id')
                ->count('employee_id');
            $pendingResponses += max(0, $eligibleCount - $respondedCount);
        }

        $dashboard = [
            'total_surveys'           => $totals['all'],
            'active_surveys'          => $totals['active'],
            'pending_surveys'         => $totals['draft'] ?? 0,
            'total_responses'         => $totalResponses,
            'pending_responses'       => $pendingResponses,
            'avg_satisfaction'        => $avgSatisfaction,
            'enps_score'              => $enpsScore['score'] ?? 0,
            'negative_feedback_count' => $negativeFeedbackCount,
            'high_risk_alerts'        => $highRiskAlerts,
        ];

        return view('surveys.index', [
            'surveys'     => $surveys,
            'filters'     => $filters,
            'totals'      => $totals,
            'dashboard'   => $dashboard,
            'departments' => Department::where('created_by', $creatorId)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create()
    {
        $this->authorizeManage();
        return view('surveys.create', [
            'departments' => Department::where('created_by', Auth::user()->creatorId())->orderBy('name')->get(['id', 'name']),
            'templates'   => \App\Services\SurveyTemplates::all(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeManage();

        $data = $request->validate([
            'title'           => 'required|string|max:200',
            'description'     => 'nullable|string|max:2000',
            'type'            => 'required|in:employee,pulse,enps',
            'start_date'      => 'nullable|date',
            'end_date'        => 'nullable|date|after_or_equal:start_date',
            'status'          => 'nullable|in:draft,active,closed',
            'is_anonymous'    => 'nullable|boolean',
            'department_ids'  => 'nullable|array',
            'department_ids.*'=> 'integer|exists:departments,id',
            'frequency'       => 'nullable|in:once,weekly,monthly,custom',
            'template'        => 'nullable|string',  // optional template code from SurveyTemplates::all()
        ]);

        // "All departments" → store NULL (means org-wide).
        // Selected departments → keep the array as-is (cast to JSON by model).
        $deptIds = $request->input('department_ids');
        if (is_array($deptIds) && count($deptIds) === 0) $deptIds = null;

        // Resolve template upfront so we can pull audience_rules into the row.
        $templateCode = $request->input('template');
        $template     = $templateCode ? \App\Services\SurveyTemplates::get($templateCode) : null;

        // audience_rules: prefer template's, fall back to none.
        // (Future: HR could override on the create form via a dedicated field.)
        $audienceRules = (is_array($template) && !empty($template['meta']['audience_rules']))
            ? $template['meta']['audience_rules']
            : null;

        $survey = EmployeeSurvey::create([
            'title'          => $data['title'],
            'description'    => $data['description'] ?? null,
            'type'           => $data['type'],
            'start_date'     => $data['start_date'] ?? null,
            'end_date'       => $data['end_date'] ?? null,
            'status'         => $data['status'] ?? 'draft',
            'is_anonymous'   => (bool)($data['is_anonymous'] ?? false),
            'department_ids' => $deptIds,
            'audience_rules' => $audienceRules,
            'frequency'      => $data['frequency'] ?? 'once',
            'created_by'     => Auth::user()->creatorId(),
        ]);

        // ── Question seeding ──
        // 1) If the user picked a template code (e.g. 'engagement_core_001'),
        //    seed every question from that template definition.
        // 2) Otherwise fall back to type-based defaults (existing behavior:
        //    eNPS gets the recommend question, pulse gets the 5 weekly Qs).

        if ($template) {
            foreach ($template['questions'] as $i => $q) {
                SurveyQuestion::create([
                    'survey_id'     => $survey->id,
                    'question_text' => __($q['text']),
                    'question_type' => $q['type'],
                    'options'       => $q['options'] ?? null,
                    'is_required'   => (bool) ($q['required'] ?? true),
                    'is_enps'       => (bool) ($q['is_enps'] ?? false),
                    'order_no'      => $i + 1,
                ]);
            }
        } elseif ($survey->type === 'enps') {
            // Fallback: standard recommend question
            SurveyQuestion::create([
                'survey_id'     => $survey->id,
                'question_text' => __('How likely are you to recommend this company as a place to work?'),
                'question_type' => 'rating_10',
                'options'       => null,
                'is_required'   => true,
                'is_enps'       => true,
                'order_no'      => 1,
            ]);
        } elseif ($survey->type === 'pulse') {
            // Fallback: standard 5-question pulse
            $defaults = [
                ['How are you feeling this week?',                      'rating_5', true],
                ['Is your workload manageable?',                        'rating_5', true],
                ['Are you getting support from your manager?',          'rating_5', true],
                ['Do you feel motivated at work?',                      'rating_5', true],
                ['Any blocker or concern?',                             'text',     false],
            ];
            foreach ($defaults as $i => [$text, $type, $required]) {
                SurveyQuestion::create([
                    'survey_id'     => $survey->id,
                    'question_text' => __($text),
                    'question_type' => $type,
                    'options'       => null,
                    'is_required'   => $required,
                    'is_enps'       => false,
                    'order_no'      => $i + 1,
                ]);
            }
        }

        // After creating a survey, take the user straight to the question builder.
        return redirect()
            ->route('surveys.questions', $survey->id)
            ->with('success', __('Survey created. Now add your questions.'));
    }

    public function edit($id)
    {
        $this->authorizeManage();
        $survey = EmployeeSurvey::where('created_by', Auth::user()->creatorId())->findOrFail($id);
        return view('surveys.edit', [
            'survey'      => $survey,
            'departments' => Department::where('created_by', Auth::user()->creatorId())->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, $id)
    {
        $this->authorizeManage();
        $survey = EmployeeSurvey::where('created_by', Auth::user()->creatorId())->findOrFail($id);

        $data = $request->validate([
            'title'           => 'required|string|max:200',
            'description'     => 'nullable|string|max:2000',
            'type'            => 'required|in:employee,pulse,enps',
            'start_date'      => 'nullable|date',
            'end_date'        => 'nullable|date|after_or_equal:start_date',
            'status'          => 'required|in:draft,active,closed',
            'is_anonymous'    => 'nullable|boolean',
            'department_ids'  => 'nullable|array',
            'department_ids.*'=> 'integer|exists:departments,id',
            'frequency'       => 'nullable|in:once,weekly,monthly,custom',
        ]);

        $deptIds = $request->input('department_ids');
        if (is_array($deptIds) && count($deptIds) === 0) $deptIds = null;

        // Once a survey already has responses, prevent type / anonymous toggle —
        // it would corrupt the meaning of historical data.
        $hasResponses = $survey->responses()->exists();
        if ($hasResponses && ($survey->type !== $data['type'] || (bool)$survey->is_anonymous !== (bool)($data['is_anonymous'] ?? false))) {
            return back()->withInput()->with('error', __('Cannot change type or anonymity after responses exist.'));
        }

        $survey->update([
            'title'          => $data['title'],
            'description'    => $data['description'] ?? null,
            'type'           => $data['type'],
            'start_date'     => $data['start_date'] ?? null,
            'end_date'       => $data['end_date'] ?? null,
            'status'         => $data['status'],
            'is_anonymous'   => (bool)($data['is_anonymous'] ?? false),
            'department_ids' => $deptIds,
            'frequency'      => $data['frequency'] ?? $survey->frequency ?? 'once',
        ]);

        return redirect()->route('surveys.index')->with('success', __('Survey updated.'));
    }

    public function destroy($id)
    {
        $this->authorizeManage();
        $survey = EmployeeSurvey::where('created_by', Auth::user()->creatorId())->findOrFail($id);

        // Active surveys must be closed first (avoid deleting in-flight collection).
        if ($survey->status === 'active') {
            return back()->with('error', __('Close the survey before deleting it.'));
        }

        $survey->delete(); // cascades to questions/responses/answers via FK
        return redirect()->route('surveys.index')->with('success', __('Survey deleted.'));
    }

    public function activate($id)
    {
        $this->authorizeManage();
        $survey = EmployeeSurvey::where('created_by', Auth::user()->creatorId())->findOrFail($id);

        if ($survey->status === 'active') {
            return back()->with('info', __('Survey is already active.'));
        }
        if ($survey->questions()->count() === 0) {
            return back()->with('error', __('Add at least one question before activating.'));
        }

        $survey->update(['status' => 'active', 'last_sent_at' => now()]);

        // For pulse surveys, dispatch invite emails immediately on activation.
        // Best-effort — failures don't block the status change.
        $sent = 0;
        if ($survey->type === 'pulse') {
            try {
                $sent = app(\App\Services\PulseNotifier::class)->dispatch($survey, false);
            } catch (\Throwable $e) {
                \Log::warning('Pulse dispatch failed on activate', ['err' => $e->getMessage()]);
            }
        }

        $msg = __('Survey activated.');
        if ($sent > 0) $msg .= ' ' . __(':n employee(s) notified.', ['n' => $sent]);
        return back()->with('success', $msg);
    }

    public function close($id)
    {
        $this->authorizeManage();
        $survey = EmployeeSurvey::where('created_by', Auth::user()->creatorId())->findOrFail($id);

        if ($survey->status === 'closed') {
            return back()->with('info', __('Survey is already closed.'));
        }
        $survey->update(['status' => 'closed']);
        return back()->with('success', __('Survey closed.'));
    }

    /* ──────────────────────────────────────────────────────────────
     * Question builder
     * ──────────────────────────────────────────────────────────── */
    public function questions($id)
    {
        $this->authorizeManage();
        $survey = EmployeeSurvey::with('questions')
            ->where('created_by', Auth::user()->creatorId())
            ->findOrFail($id);
        return view('surveys.questions', compact('survey'));
    }

    public function questionStore(Request $request, $id)
    {
        $this->authorizeManage();
        $survey = EmployeeSurvey::where('created_by', Auth::user()->creatorId())->findOrFail($id);

        // Locked once active and any responses exist (avoid mid-flight schema change)
        if ($survey->status === 'active' && $survey->responses()->exists()) {
            return back()->with('error', __('Cannot add questions to an active survey that already has responses.'));
        }

        $data = $request->validate([
            'question_text' => 'required|string|max:500',
            'question_type' => 'required|in:rating_5,rating_10,yes_no,multiple_choice,text',
            'options'       => 'nullable|array',
            'options.*'     => 'string|max:200',
            'is_required'   => 'nullable|boolean',
            'is_enps'       => 'nullable|boolean',
        ]);

        // Multiple choice must have ≥2 non-empty options
        $opts = array_values(array_filter(array_map('trim', $data['options'] ?? []), fn($v) => $v !== ''));
        if ($data['question_type'] === 'multiple_choice' && count($opts) < 2) {
            return back()->withInput()->with('error', __('Multiple choice questions need at least 2 options.'));
        }

        // Only one eNPS-flag question per survey makes sense
        $isEnps = (bool)($data['is_enps'] ?? false);
        if ($isEnps && $survey->questions()->where('is_enps', true)->exists()) {
            return back()->withInput()->with('error', __('Only one eNPS question is allowed per survey.'));
        }
        // eNPS must be a 0–10 rating
        if ($isEnps && $data['question_type'] !== 'rating_10') {
            $data['question_type'] = 'rating_10';
        }

        SurveyQuestion::create([
            'survey_id'     => $survey->id,
            'question_text' => $data['question_text'],
            'question_type' => $data['question_type'],
            'options'       => $data['question_type'] === 'multiple_choice' ? $opts : null,
            'is_required'   => (bool)($data['is_required'] ?? true),
            'is_enps'       => $isEnps,
            'order_no'      => ($survey->questions()->max('order_no') ?? 0) + 1,
        ]);

        return back()->with('success', __('Question added.'));
    }

    public function questionUpdate(Request $request, $id, $qid)
    {
        $this->authorizeManage();
        $survey = EmployeeSurvey::where('created_by', Auth::user()->creatorId())->findOrFail($id);
        $q = SurveyQuestion::where('survey_id', $survey->id)->findOrFail($qid);

        if ($survey->responses()->exists()) {
            return back()->with('error', __('Cannot edit questions after responses are submitted.'));
        }

        $data = $request->validate([
            'question_text' => 'required|string|max:500',
            'question_type' => 'required|in:rating_5,rating_10,yes_no,multiple_choice,text',
            'options'       => 'nullable|array',
            'options.*'     => 'string|max:200',
            'is_required'   => 'nullable|boolean',
        ]);

        $opts = array_values(array_filter(array_map('trim', $data['options'] ?? []), fn($v) => $v !== ''));
        if ($data['question_type'] === 'multiple_choice' && count($opts) < 2) {
            return back()->withInput()->with('error', __('Multiple choice questions need at least 2 options.'));
        }

        $q->update([
            'question_text' => $data['question_text'],
            'question_type' => $data['question_type'],
            'options'       => $data['question_type'] === 'multiple_choice' ? $opts : null,
            'is_required'   => (bool)($data['is_required'] ?? true),
        ]);
        return back()->with('success', __('Question updated.'));
    }

    public function questionDestroy($id, $qid)
    {
        $this->authorizeManage();
        $survey = EmployeeSurvey::where('created_by', Auth::user()->creatorId())->findOrFail($id);

        if ($survey->responses()->exists()) {
            return back()->with('error', __('Cannot delete questions after responses are submitted.'));
        }

        SurveyQuestion::where('survey_id', $survey->id)->where('id', $qid)->delete();
        return back()->with('success', __('Question deleted.'));
    }

    public function questionReorder(Request $request, $id)
    {
        $this->authorizeManage();
        $survey = EmployeeSurvey::where('created_by', Auth::user()->creatorId())->findOrFail($id);

        $order = $request->input('order', []); // array of question IDs in new order
        if (!is_array($order)) return response()->json(['ok' => false, 'error' => 'Invalid payload'], 422);

        foreach ($order as $i => $qid) {
            SurveyQuestion::where('survey_id', $survey->id)->where('id', (int)$qid)
                ->update(['order_no' => $i + 1]);
        }
        return response()->json(['ok' => true]);
    }

    /* ──────────────────────────────────────────────────────────────
     * Employee — my surveys
     * ──────────────────────────────────────────────────────────── */

    /**
     * Active surveys the current employee can take.
     * Filters by:
     *   - survey.status = 'active' AND in date window
     *   - audience: department_ids null (all) OR contains employee's department
     *   - has at least one question
     *   - employee hasn't already responded
     */
    public function mySurveys()
    {
        $this->authorizeSubmit();

        $employee = $this->currentEmployee();
        if (!$employee) {
            return view('surveys.my_surveys', ['surveys' => collect(), 'submittedIds' => collect()]);
        }

        $creatorId = Auth::user()->creatorId();
        $today     = now()->toDateString();

        $surveys = EmployeeSurvey::where('created_by', $creatorId)
            ->where('status', 'active')
            ->where(function ($q) use ($today) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', $today);
            })
            ->where(function ($q) use ($today) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $today);
            })
            ->whereHas('questions')
            ->withCount('questions')
            ->get()
            ->filter(function ($s) use ($employee) {
                // 1) Department audience: null = all departments, otherwise must include employee's dept
                $depts = $s->department_ids;
                if (!empty($depts) && is_array($depts)) {
                    if (!in_array((int) $employee->department_id, array_map('intval', $depts), true)) {
                        return false;
                    }
                }
                // 2) Audience rules (tenure-based eligibility)
                return $this->matchesAudienceRules($s, $employee);
            })
            ->values();

        // Surveys this employee has already submitted
        $submittedIds = SurveyResponse::where('employee_id', $employee->id)
            ->whereIn('survey_id', $surveys->pluck('id'))
            ->pluck('survey_id');

        return view('surveys.my_surveys', compact('surveys', 'submittedIds'));
    }

    public function myFill($id)
    {
        $this->authorizeSubmit();

        $employee = $this->currentEmployee();
        if (!$employee) {
            return redirect()->route('dashboard')->with('error', __('Employee record not found.'));
        }

        $survey = EmployeeSurvey::with(['questions' => fn($q) => $q->orderBy('order_no')])
            ->where('created_by', Auth::user()->creatorId())
            ->findOrFail($id);

        // Gate: must be open AND in window
        if (!$survey->isOpen()) {
            return redirect()->route('surveys.my')->with('error', __('This survey is not currently accepting responses.'));
        }

        // Audience check (department + audience rules)
        $depts = $survey->department_ids;
        if (!empty($depts) && is_array($depts)) {
            if (!in_array((int) $employee->department_id, array_map('intval', $depts), true)) {
                return redirect()->route('surveys.my')->with('error', __('This survey is not assigned to your department.'));
            }
        }
        if (!$this->matchesAudienceRules($survey, $employee)) {
            return redirect()->route('surveys.my')->with('error', __('This survey is not available for you (eligibility rules).'));
        }

        // One-time submit guard
        $already = SurveyResponse::where('survey_id', $survey->id)
            ->where('employee_id', $employee->id)->exists();
        if ($already) {
            return redirect()->route('surveys.my')->with('info', __('You have already submitted this survey.'));
        }

        return view('surveys.fill', compact('survey'));
    }

    /**
     * Persist an employee's survey response.
     *
     * Validates:
     *  - survey is active and within window
     *  - audience matches employee's department
     *  - employee hasn't already submitted (DB-level via unique index, plus pre-check)
     *  - all required questions are answered
     *
     * Storage:
     *  - survey_responses row (one per employee per survey)
     *  - survey_answers row per question — splits into rating_value / answer_value / text_value
     *  - if anonymous, employee_id is set to NULL on the response so reports
     *    cannot attribute it back to the user
     */
    public function mySubmit(Request $request, $id)
    {
        $this->authorizeSubmit();

        $employee = $this->currentEmployee();
        if (!$employee) return redirect()->route('dashboard')->with('error', __('Employee record not found.'));

        $survey = EmployeeSurvey::with(['questions' => fn($q) => $q->orderBy('order_no')])
            ->where('created_by', Auth::user()->creatorId())
            ->findOrFail($id);

        if (!$survey->isOpen()) {
            return redirect()->route('surveys.my')->with('error', __('This survey is not currently accepting responses.'));
        }

        $depts = $survey->department_ids;
        if (!empty($depts) && is_array($depts)
            && !in_array((int)$employee->department_id, array_map('intval', $depts), true)) {
            return redirect()->route('surveys.my')->with('error', __('This survey is not assigned to your department.'));
        }

        // One-time guard
        if (SurveyResponse::where('survey_id', $survey->id)->where('employee_id', $employee->id)->exists()) {
            return redirect()->route('surveys.my')->with('info', __('You have already submitted this survey.'));
        }

        $answers = (array) $request->input('answers', []);

        // Required-question validation server-side
        foreach ($survey->questions as $q) {
            if (!$q->is_required) continue;
            $a = $answers[$q->id] ?? [];
            if ($q->isRating()) {
                if (!isset($a['rating']) || $a['rating'] === '') {
                    return back()->withInput()->with('error', __('Please answer all required questions.'));
                }
            } elseif ($q->question_type === 'text') {
                if (empty(trim((string)($a['text'] ?? '')))) {
                    return back()->withInput()->with('error', __('Please answer all required questions.'));
                }
            } else { // yes_no, multiple_choice
                if (empty(($a['value'] ?? null))) {
                    return back()->withInput()->with('error', __('Please answer all required questions.'));
                }
            }
        }

        // Collected during the transaction; consumed by the sentiment pipeline
        // after commit. Pass-by-reference so the closure can append.
        $textAnswerIds = [];

        DB::transaction(function () use ($survey, $employee, $answers, &$textAnswerIds) {
            $responsePayload = [
                // Anonymous → don't store employee_id (NULL) so reports can't link back.
                'employee_id'  => $survey->is_anonymous ? null : $employee->id,
                'survey_id'    => $survey->id,
                'is_anonymous' => $survey->is_anonymous,
                'submitted_at' => now(),
            ];
            if (Schema::hasColumn('survey_responses', 'is_guard')) {
                $responsePayload['is_guard'] = false;
            }
            $response = SurveyResponse::create($responsePayload);

            // BUT: even anonymous surveys must enforce one-submission-per-employee.
            // The unique (survey_id, employee_id) index treats NULL values as
            // distinct — so for anonymous surveys we additionally write a
            // sentinel record into a separate guard table by reusing a hashed
            // marker. Simpler approach: write a non-anonymous "marker" row
            // with employee_id but separate is_anonymous flag — defer to a
            // dedicated 'submission_log' if we ever need full anonymity. For
            // now: track the participant by writing a SECOND guard row with
            // employee_id set + is_anonymous=true, distinct from the answer-
            // bearing row. This keeps reports anonymous (they read answers
            // from the first row) while preventing duplicates.
            if ($survey->is_anonymous) {
                // Best-effort: silently swallow duplicate guard insert if it races.
                try {
                    $guardPayload = [
                        'employee_id'  => $employee->id,
                        'survey_id'    => $survey->id,
                        'is_anonymous' => true,
                        'submitted_at' => now(),
                    ];
                    if (Schema::hasColumn('survey_responses', 'is_guard')) {
                        $guardPayload['is_guard'] = true;
                    }
                    SurveyResponse::create($guardPayload);
                } catch (\Throwable $e) {
                    // unique-index race — ignore
                }
            }

            foreach ($survey->questions as $q) {
                $a = $answers[$q->id] ?? [];
                $rating = null; $val = null; $text = null;

                if ($q->isRating()) {
                    $rating = isset($a['rating']) && $a['rating'] !== '' ? (float) $a['rating'] : null;
                    if ($rating !== null) {
                        $rating = max(0, min($q->ratingMax(), $rating));
                    }
                } elseif ($q->question_type === 'text') {
                    $text = trim((string)($a['text'] ?? ''));
                    $text = $text === '' ? null : mb_substr($text, 0, 5000);
                } else {
                    $val = isset($a['value']) ? mb_substr((string)$a['value'], 0, 500) : null;
                }

                // Skip writing rows for unanswered optional questions
                if ($rating === null && $val === null && $text === null) continue;

                $created = SurveyAnswer::create([
                    'response_id'  => $response->id,
                    'question_id'  => $q->id,
                    'answer_value' => $val,
                    'rating_value' => $rating,
                    'text_value'   => $text,
                ]);

                // Stash text-bearing answers to be sentiment-analyzed AFTER commit.
                // Why post-commit: sentiment may call OpenAI (network); we don't want
                // a 4xx/5xx from the API to roll back the user's submission.
                if (!empty($text) && mb_strlen(trim($text)) >= 3) {
                    $textAnswerIds[] = $created->id;
                }
            }
        });

        // ── Sentiment + alert pipeline (post-commit, best-effort) ──
        if (!empty($textAnswerIds)) {
            try {
                /** @var \App\Services\SentimentService $sentiment */
                $sentiment = app(\App\Services\SentimentService::class);

                foreach ($textAnswerIds as $aid) {
                    $ans = SurveyAnswer::find($aid);
                    if (!$ans) continue;
                    $row = $sentiment->analyzeAndStore($ans);

                    // Auto-alert on Negative + High Risk.
                    if ($row->sentiment === 'negative' && $row->risk_level === 'high') {
                        $this->createNegativeFeedbackAlert($survey, $ans, $row, $employee);
                    }
                }
            } catch (\Throwable $e) {
                \Log::warning('Sentiment pipeline failed', [
                    'survey_id' => $survey->id,
                    'err'       => $e->getMessage(),
                ]);
            }
        }

        return redirect()->route('surveys.my')->with('success', __('Thank you! Your response has been recorded.'));
    }

    /**
     * Create an HR alert for a negative + high-risk text answer.
     * employee_id is intentionally NULL when the survey is anonymous so HR
     * cannot trace identity from the alert UI.
     */
    protected function createNegativeFeedbackAlert(EmployeeSurvey $survey, SurveyAnswer $ans, SurveySentimentAnalysis $sa, ?Employee $employee): void
    {
        $topics = is_array($sa->topic) ? implode(', ', $sa->topic) : (string) $sa->topic;
        $excerpt = mb_substr((string) $ans->text_value, 0, 240);
        $message = json_encode([
            'kind'      => 'negative_high_risk_feedback',
            'topics'    => is_array($sa->topic) ? $sa->topic : [],
            'emotion'   => $sa->emotion,
            'sentiment' => $sa->sentiment,
            'risk'      => $sa->risk_level,
            'excerpt'   => $excerpt,
            'summary'   => $sa->ai_summary,
        ]);

        SurveyAlert::create([
            'survey_id'   => $survey->id,
            'response_id' => $ans->response_id,
            'employee_id' => $survey->is_anonymous ? null : ($employee?->id),
            'alert_type'  => 'negative_feedback',
            'risk_level'  => 'high',
            'message'     => $message,
            'status'      => 'open',
            'created_by'  => $survey->created_by,
        ]);
    }

    public function myHistory()
    {
        $this->authorizeViewOwn();

        $employee = $this->currentEmployee();
        if (!$employee) {
            return view('surveys.my_history', ['responses' => collect()]);
        }

        $responses = SurveyResponse::with('survey')
            ->where('employee_id', $employee->id)
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->paginate(20);

        return view('surveys.my_history', compact('responses'));
    }

    /** Resolve the current logged-in user to an Employee row. */
    protected function currentEmployee(): ?Employee
    {
        $user = Auth::user();
        if (!$user) return null;
        return Employee::where('user_id', $user->id)->first();
    }

    /**
     * Apply per-survey eligibility rules on top of the department audience.
     *
     * Currently supports:
     *   tenure_max_days  : employee.company_doj must be within last N days
     *                      (e.g. 100 → onboarding-only; hides from veterans)
     *   tenure_min_days  : employee.company_doj must be ≥ N days ago
     *                      (e.g. 365 → annual review-only; hides from new hires)
     *   include_inactive : if false (default), is_active=0 employees are hidden
     *
     * Returns true when employee is eligible. Conservative on bad data —
     * a missing DOJ is treated as "no info, allow" so we don't accidentally
     * block real employees.
     */
    protected function matchesAudienceRules(EmployeeSurvey $survey, Employee $employee): bool
    {
        $rules = $survey->audience_rules;
        if (empty($rules) || !is_array($rules)) return true;

        // include_inactive guard
        $includeInactive = (bool) ($rules['include_inactive'] ?? false);
        if (!$includeInactive && property_exists($employee, 'is_active') && $employee->is_active === 0) {
            return false;
        }

        // tenure checks — both keys are optional
        $maxDays = isset($rules['tenure_max_days']) ? (int) $rules['tenure_max_days'] : null;
        $minDays = isset($rules['tenure_min_days']) ? (int) $rules['tenure_min_days'] : null;

        if (($maxDays !== null || $minDays !== null) && !empty($employee->company_doj)) {
            try {
                $tenureDays = (int) \Carbon\Carbon::parse($employee->company_doj)
                    ->diffInDays(now(), false);
            } catch (\Throwable $e) {
                return true;
            }
            if ($maxDays !== null && $tenureDays > $maxDays) return false;
            if ($minDays !== null && $tenureDays < $minDays) return false;
        }

        return true;
    }

    /* ──────────────────────────────────────────────────────────────
     * Analytics — eNPS
     * ──────────────────────────────────────────────────────────── */

    /**
     * Company-wide eNPS dashboard.
     *
     * Aggregates every answer to a question marked `is_enps = true` across all
     * surveys created by this company. Filters:
     *   - survey_id (null = all eNPS-flagged questions)
     *   - months (default 12) for the trend chart
     */
    public function enpsReport(Request $request, EnpsService $enps)
    {
        $this->authorizeAnalytics();

        $creatorId = Auth::user()->creatorId();
        $surveyId  = $request->integer('survey_id') ?: null;
        $months    = max(3, min(24, (int) $request->integer('months', 12)));

        // Surveys list for the filter dropdown — only those that actually have an eNPS question
        $surveysWithEnps = EmployeeSurvey::where('created_by', $creatorId)
            ->whereHas('questions', fn($q) => $q->where('is_enps', true))
            ->orderByDesc('id')
            ->get(['id', 'title', 'type', 'status']);

        $summary    = $enps->companyScore($creatorId, $surveyId);
        $byDept     = $enps->byDepartment($creatorId, $surveyId);
        $trend      = $enps->monthlyTrend($creatorId, $months, $surveyId);

        return view('surveys.enps_report', [
            'summary'         => $summary,
            'byDept'          => $byDept,
            'trend'           => $trend,
            'months'          => $months,
            'surveys'         => $surveysWithEnps,
            'selectedSurvey'  => $surveyId,
        ]);
    }

    /**
     * Pulse Surveys dashboard.
     *
     * Aggregates ratings across all `type=pulse` surveys for this company:
     *  - headline (total responses, avg score, low-question count)
     *  - per-question averages (worst-first)
     *  - team-wise (department) breakdown
     *  - week-by-week trend (one series per question, ISO weeks on x-axis)
     *
     * Filters: survey_id (specific pulse) and weeks (default 12).
     * Side-effect: best-effort generates new low-score alerts on each load
     * so HR sees fresh signals without needing a separate cron yet.
     */
    public function pulseReport(Request $request, PulseService $pulse)
    {
        $this->authorizeAnalytics();

        $creatorId = Auth::user()->creatorId();
        $surveyId  = $request->integer('survey_id') ?: null;
        $weeks     = max(4, min(26, (int) $request->integer('weeks', 12)));

        // Best-effort low-score alert generation. Idempotent — already-open
        // alerts won't be duplicated. Failures here must not break the page.
        try {
            $pulse->generateLowScoreAlerts($creatorId, $surveyId);
        } catch (\Throwable $e) {
            // swallow — analytics page should still render even if alert insert fails
        }

        $surveys = EmployeeSurvey::where('created_by', $creatorId)
            ->where('type', 'pulse')
            ->orderByDesc('id')
            ->get(['id', 'title', 'status', 'frequency']);

        $summary  = $pulse->summary($creatorId, $surveyId);
        $byQ      = $pulse->byQuestion($creatorId, $surveyId);
        $byTeam   = $pulse->byTeam($creatorId, $surveyId);
        $trend    = $pulse->weeklyTrend($creatorId, $weeks, $surveyId);

        return view('surveys.pulse_report', [
            'summary'        => $summary,
            'byQ'            => $byQ,
            'byTeam'         => $byTeam,
            'trend'          => $trend,
            'weeks'          => $weeks,
            'surveys'        => $surveys,
            'selectedSurvey' => $surveyId,
            'lowThreshold'   => PulseService::LOW_SCORE_THRESHOLD,
        ]);
    }

    /**
     * Manager-scoped Team Pulse dashboard.
     *
     * Shows pulse aggregates filtered to the logged-in manager's direct
     * reports only. Identity is never exposed (we show counts/averages,
     * never per-employee scores). Gating:
     *   - permission `view-team-pulse` (granted to all roles)
     *   - PLUS Employee::isManagerLevel() — must actually have direct reports
     *
     * For HR/Admin (who have manage-surveys), we still show the page but
     * surface a prompt linking to the company-wide Pulse Trends since they
     * shouldn't typically be filtered to "their team only".
     */
    public function teamPulse(Request $request, PulseService $pulse)
    {
        $user = Auth::user();
        if (!$user || !$user->can('view-team-pulse')) {
            abort(403, __('You do not have permission to view team pulse.'));
        }

        $employee = $this->currentEmployee();
        if (!$employee) {
            return redirect()->route('dashboard')->with('error', __('Employee record not found.'));
        }

        // Runtime gate: must be a manager (have at least one direct report).
        if (!$employee->isManagerLevel()) {
            return redirect()->route('dashboard')
                ->with('error', __('Team Pulse is only available to managers with direct reports.'));
        }

        $creatorId = Auth::user()->creatorId();
        $teamIds   = $employee->teamMemberIds()->all();
        $weeks     = max(4, min(26, (int) $request->integer('weeks', 12)));

        // Pulse stats limited to this manager's team.
        $summary = $pulse->summary($creatorId, null, null, null, $teamIds);
        $byQ     = $pulse->byQuestion($creatorId, null, null, null, $teamIds);
        $byTeam  = $pulse->byTeam($creatorId, null, null, null, $teamIds);
        $trend   = $pulse->weeklyTrend($creatorId, $weeks, null, $teamIds);

        // Identity blurring guard: if anonymous responses are mixed in (employee_id NULL),
        // we'd never see them here anyway because the whereIn filter on $teamIds excludes
        // them. So team data is naturally identity-free at the aggregate level.
        // Additionally, even when total responses are very small (<3), we show a notice
        // rather than potentially-identifying data.
        $tooFewResponses = $summary['total_responses'] > 0 && $summary['total_responses'] < 3;

        return view('surveys.team_pulse', [
            'employee'        => $employee,
            'teamSize'        => count($teamIds),
            'summary'         => $summary,
            'byQ'             => $byQ,
            'byTeam'          => $byTeam,
            'trend'           => $trend,
            'weeks'           => $weeks,
            'lowThreshold'    => PulseService::LOW_SCORE_THRESHOLD,
            'tooFewResponses' => $tooFewResponses,
        ]);
    }

    public function alerts(Request $request)
    {
        $this->authorizeAlerts();

        $creatorId = Auth::user()->creatorId();
        $status    = $request->input('status', 'open');
        if (!in_array($status, ['open', 'resolved', 'all'], true)) $status = 'open';

        $q = SurveyAlert::with('survey')
            ->where('created_by', $creatorId)
            ->orderByDesc('id');

        if ($status !== 'all') $q->where('status', $status);

        $alerts = $q->paginate(20)->withQueryString();

        return view('surveys.alerts', compact('alerts', 'status'));
    }

    public function alertResolve($id)
    {
        $this->authorizeAlerts();

        $creatorId = Auth::user()->creatorId();
        $alert = SurveyAlert::where('created_by', $creatorId)->findOrFail($id);
        $alert->update(['status' => 'resolved']);

        return back()->with('success', __('Alert marked as resolved.'));
    }

    public function reportDepartments(Request $request)
    {
        $this->authorizeAnalytics();

        $creatorId = Auth::user()->creatorId();
        $surveyId  = $request->integer('survey_id') ?: null;
        $from      = $request->input('from') ? \Carbon\Carbon::parse($request->input('from'))->startOfDay() : null;
        $to        = $request->input('to') ? \Carbon\Carbon::parse($request->input('to'))->endOfDay() : null;

        $surveys = EmployeeSurvey::where('created_by', $creatorId)
            ->orderByDesc('id')
            ->get(['id', 'title', 'type', 'status', 'is_anonymous']);

        $deptNames = Department::where('created_by', $creatorId)->pluck('name', 'id')->toArray();

        $q = SurveyAnswer::query()
            ->join('survey_questions as q', 'q.id', '=', 'survey_answers.question_id')
            ->join('survey_responses as r', 'r.id', '=', 'survey_answers.response_id')
            ->join('employee_surveys  as s', 's.id', '=', 'r.survey_id')
            ->leftJoin('employees as e', 'e.id', '=', 'r.employee_id')
            ->where('s.created_by', $creatorId)
            ->where('q.question_type', 'rating_5')
            ->whereNotNull('survey_answers.rating_value')
            ->when($surveyId, fn($qb) => $qb->where('s.id', $surveyId))
            ->when($from, fn($qb) => $qb->where('r.submitted_at', '>=', $from))
            ->when($to, fn($qb) => $qb->where('r.submitted_at', '<=', $to))
            ->groupBy(DB::raw('IFNULL(e.department_id, 0)'))
            ->select([
                DB::raw('IFNULL(e.department_id, 0) as department_id'),
                DB::raw('COUNT(DISTINCT r.id) as responses'),
                DB::raw('AVG(survey_answers.rating_value) as avg_score'),
                DB::raw('SUM(CASE WHEN survey_answers.rating_value < ' . (float) PulseService::LOW_SCORE_THRESHOLD . ' THEN 1 ELSE 0 END) as low_ratings'),
            ]);

        $rows = $q->get()->map(function ($r) use ($deptNames) {
            $deptId = (int) $r->department_id;
            return [
                'department_id'   => $deptId,
                'department_name' => $deptId && isset($deptNames[$deptId]) ? $deptNames[$deptId] : __('Unassigned'),
                'responses'       => (int) $r->responses,
                'avg_score'       => round((float) $r->avg_score, 2),
                'low_ratings'     => (int) $r->low_ratings,
            ];
        })->sortByDesc('responses')->values();

        return view('surveys.report_departments', [
            'rows'         => $rows,
            'surveys'      => $surveys,
            'selectedSurvey' => $surveyId,
            'from'         => $request->input('from'),
            'to'           => $request->input('to'),
        ]);
    }

    public function reportManagers(Request $request)
    {
        $this->authorizeAnalytics();

        $creatorId = Auth::user()->creatorId();
        $weeks     = max(4, min(26, (int) $request->integer('weeks', 12)));
        $from      = now()->subWeeks($weeks)->startOfDay();

        // Manager id is derived from employee hierarchy (prefers reporting manager).
        $managerExpr = "COALESCE(e.reporting_manager_id, e.hod_id, e.management_id)";
        $managerExprEmp = "COALESCE(reporting_manager_id, hod_id, management_id)";

        $stats = SurveyAnswer::query()
            ->join('survey_questions as q', 'q.id', '=', 'survey_answers.question_id')
            ->join('survey_responses as r', 'r.id', '=', 'survey_answers.response_id')
            ->join('employee_surveys  as s', 's.id', '=', 'r.survey_id')
            ->join('employees as e', 'e.id', '=', 'r.employee_id')
            ->where('s.created_by', $creatorId)
            ->where('s.type', 'pulse')
            ->where('q.question_type', 'rating_5')
            ->whereNotNull('survey_answers.rating_value')
            ->where('r.submitted_at', '>=', $from)
            ->whereRaw($managerExpr . ' IS NOT NULL')
            ->groupBy(DB::raw($managerExpr))
            ->select([
                DB::raw($managerExpr . ' as manager_id'),
                DB::raw('COUNT(DISTINCT r.id) as responses'),
                DB::raw('AVG(survey_answers.rating_value) as avg_score'),
                DB::raw('SUM(CASE WHEN survey_answers.rating_value < ' . (float) PulseService::LOW_SCORE_THRESHOLD . ' THEN 1 ELSE 0 END) as low_ratings'),
            ])
            ->get();

        $mgrIds = $stats->pluck('manager_id')->filter()->unique()->values()->all();
        $mgrMap = Employee::where('created_by', $creatorId)
            ->whereIn('id', $mgrIds)
            ->get(['id', 'name', 'employee_id'])
            ->keyBy('id');

        $teamSizes = Employee::where('created_by', $creatorId)
            ->whereRaw($managerExprEmp . ' IS NOT NULL')
            ->select([DB::raw($managerExprEmp . ' as manager_id'), DB::raw('COUNT(*) as team_size')])
            ->groupBy(DB::raw($managerExprEmp))
            ->get()
            ->keyBy('manager_id');

        $rows = $stats->map(function ($r) use ($mgrMap, $teamSizes) {
            $mid = (int) $r->manager_id;
            $mgr = $mgrMap->get($mid);
            $team = $teamSizes->get($mid);
            return [
                'manager_id'   => $mid,
                'manager_name' => $mgr?->name ?? __('Unknown'),
                'manager_empid'=> $mgr?->employee_id ?? null,
                'team_size'    => (int) ($team?->team_size ?? 0),
                'responses'    => (int) $r->responses,
                'avg_score'    => round((float) $r->avg_score, 2),
                'low_ratings'  => (int) $r->low_ratings,
            ];
        })->sortByDesc('responses')->values();

        return view('surveys.report_managers', [
            'rows'  => $rows,
            'weeks' => $weeks,
        ]);
    }

    public function reportSentiment(Request $request)
    {
        $this->authorizeAnalytics();

        $creatorId = Auth::user()->creatorId();
        $from      = $request->input('from') ? \Carbon\Carbon::parse($request->input('from'))->startOfDay() : null;
        $to        = $request->input('to') ? \Carbon\Carbon::parse($request->input('to'))->endOfDay() : null;

        if (!Schema::hasTable('survey_sentiment_analysis')) {
            return view('surveys.report_sentiment', [
                'bySentiment' => collect(),
                'byRisk' => collect(),
                'recentHighRisk' => collect(),
                'from' => $request->input('from'),
                'to' => $request->input('to'),
            ]);
        }

        $base = SurveySentimentAnalysis::query()
            ->join('survey_answers as a', 'a.id', '=', 'survey_sentiment_analysis.answer_id')
            ->join('survey_responses as r', 'r.id', '=', 'a.response_id')
            ->join('employee_surveys  as s', 's.id', '=', 'r.survey_id')
            ->where('s.created_by', $creatorId)
            ->when($from, fn($qb) => $qb->where('r.submitted_at', '>=', $from))
            ->when($to, fn($qb) => $qb->where('r.submitted_at', '<=', $to));

        $bySentiment = (clone $base)
            ->groupBy('survey_sentiment_analysis.sentiment')
            ->select([
                'survey_sentiment_analysis.sentiment as sentiment',
                DB::raw('COUNT(*) as total'),
            ])->get()->keyBy('sentiment');

        $byRisk = (clone $base)
            ->groupBy('survey_sentiment_analysis.risk_level')
            ->select([
                'survey_sentiment_analysis.risk_level as risk_level',
                DB::raw('COUNT(*) as total'),
            ])->get()->keyBy('risk_level');

        $recentHighRisk = (clone $base)
            ->where('survey_sentiment_analysis.risk_level', 'high')
            ->orderByDesc('survey_sentiment_analysis.id')
            ->limit(25)
            ->select([
                'survey_sentiment_analysis.sentiment',
                'survey_sentiment_analysis.emotion',
                'survey_sentiment_analysis.risk_level',
                'survey_sentiment_analysis.ai_summary',
                'survey_sentiment_analysis.topic',
                'r.submitted_at',
                's.title as survey_title',
                'a.text_value as text_value',
            ])
            ->get();

        return view('surveys.report_sentiment', [
            'bySentiment' => $bySentiment,
            'byRisk'      => $byRisk,
            'recentHighRisk' => $recentHighRisk,
            'from'        => $request->input('from'),
            'to'          => $request->input('to'),
        ]);
    }

    /**
     * Manually re-run sentiment analysis on existing text answers.
     *
     * Modes:
     *   - all     : every text answer in this company (idempotent — overwrites)
     *   - missing : only answers without a sentiment row yet
     *   - survey  : limit to a specific survey via ?survey_id=N
     *
     * Returns a JSON-or-redirect summary. Useful after enabling OpenAI on
     * a company that previously fell back to keyword analysis.
     */
    public function sentimentReanalyze(Request $request, \App\Services\SentimentService $sentiment)
    {
        $this->authorizeAnalytics();

        $creatorId = Auth::user()->creatorId();
        $mode      = $request->input('mode', 'missing');
        $surveyId  = $request->integer('survey_id') ?: null;

        $q = SurveyAnswer::query()
            ->join('survey_questions as q', 'q.id', '=', 'survey_answers.question_id')
            ->join('survey_responses as r', 'r.id', '=', 'survey_answers.response_id')
            ->join('employee_surveys  as s', 's.id', '=', 'r.survey_id')
            ->where('s.created_by', $creatorId)
            ->whereNotNull('survey_answers.text_value')
            ->where('survey_answers.text_value', '!=', '');

        if ($surveyId) $q->where('s.id', $surveyId);
        if ($mode === 'missing') {
            $q->leftJoin('survey_sentiment_analysis as sa', 'sa.answer_id', '=', 'survey_answers.id')
              ->whereNull('sa.id');
        }

        $rows = $q->select('survey_answers.id')->limit(500)->pluck('id');

        $analyzed = 0;
        $alerted  = 0;
        foreach ($rows as $aid) {
            $ans = SurveyAnswer::find($aid);
            if (!$ans) continue;
            try {
                $row = $sentiment->analyzeAndStore($ans);
                $analyzed++;

                // Re-create alert only if no open alert for this response exists
                if ($row->sentiment === 'negative' && $row->risk_level === 'high') {
                    $hasOpen = SurveyAlert::where('response_id', $ans->response_id)
                        ->where('alert_type', 'negative_feedback')
                        ->where('status', 'open')->exists();
                    if (!$hasOpen) {
                        $resp = $ans->response;
                        $survey = $resp ? $resp->survey : null;
                        if ($survey) {
                            $emp = $resp->employee_id ? Employee::find($resp->employee_id) : null;
                            $this->createNegativeFeedbackAlert($survey, $ans, $row, $emp);
                            $alerted++;
                        }
                    }
                }
            } catch (\Throwable $e) {
                \Log::warning('Re-analyze failed for answer', ['id' => $aid, 'err' => $e->getMessage()]);
            }
        }

        return back()->with('success', __(':n answer(s) re-analyzed, :a alert(s) created.', [
            'n' => $analyzed, 'a' => $alerted,
        ]));
    }

    public function export($id)
    {
        $this->authorizeExport();

        $creatorId = Auth::user()->creatorId();
        $survey = EmployeeSurvey::where('created_by', $creatorId)->findOrFail($id);

        $filename = 'survey-export-' . $survey->id . '-' . $survey->type . '-' . now()->format('Ymd_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($survey, $creatorId) {
            $f = fopen('php://output', 'w');
            fprintf($f, chr(0xEF) . chr(0xBB) . chr(0xBF));

            $cols = ['Response ID', 'Submitted At', 'Question', 'Type', 'Rating', 'Answer', 'Text'];
            if (!$survey->is_anonymous) {
                array_splice($cols, 2, 0, ['Employee ID', 'Employee']);
            }
            fputcsv($f, $cols);

            $query = SurveyAnswer::query()
                ->join('survey_responses as r', 'r.id', '=', 'survey_answers.response_id')
                ->join('survey_questions as q', 'q.id', '=', 'survey_answers.question_id')
                ->join('employee_surveys as s', 's.id', '=', 'r.survey_id')
                ->where('s.created_by', $creatorId)
                ->where('s.id', $survey->id)
                ->orderBy('r.id')
                ->orderBy('q.order_no');

            $select = [
                'r.id as response_id',
                'r.submitted_at as submitted_at',
                'q.question_text as question_text',
                'q.question_type as question_type',
                'survey_answers.rating_value as rating_value',
                'survey_answers.answer_value as answer_value',
                'survey_answers.text_value as text_value',
            ];

            if (!$survey->is_anonymous) {
                $query->leftJoin('employees as e', 'e.id', '=', 'r.employee_id');
                $select[] = 'r.employee_id as employee_id';
                $select[] = DB::raw("IFNULL(e.name, '') as employee_name");
            }

            $rows = $query->select($select)->cursor();

            foreach ($rows as $r) {
                $row = [
                    $r->response_id,
                    $r->submitted_at,
                ];

                if (!$survey->is_anonymous) {
                    $row[] = $r->employee_id;
                    $row[] = $r->employee_name;
                }

                $row[] = $r->question_text;
                $row[] = $r->question_type;
                $row[] = $r->rating_value;
                $row[] = $r->answer_value;
                $row[] = $r->text_value;

                fputcsv($f, $row);
            }

            fclose($f);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportPdf($id, EnpsService $enps)
    {
        $this->authorizeExport();

        $creatorId = Auth::user()->creatorId();
        $survey = EmployeeSurvey::with('questions')
            ->where('created_by', $creatorId)
            ->withCount('responses')
            ->findOrFail($id);

        if (!class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            return back()->with('error', __('DomPDF package is not installed.'));
        }

        $questionStats = $this->buildQuestionAnalytics($survey->id, $creatorId);
        $enpsSummary   = $enps->companyScore($creatorId, $survey->id);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('surveys.export_pdf', [
            'survey'        => $survey,
            'questionStats' => $questionStats,
            'enpsSummary'   => $enpsSummary,
        ])->setPaper('a4', 'portrait');

        $filename = 'survey-export-' . $survey->id . '-' . now()->format('Ymd_His') . '.pdf';
        return $pdf->download($filename);
    }

    /**
     * Per-survey analytics (lightweight stub for now).
     * Will be expanded in the next phase (sentiment + question-wise breakdown).
     */
    public function analytics($id, EnpsService $enps)
    {
        $this->authorizeAnalytics();

        $survey = EmployeeSurvey::with('questions')
            ->where('created_by', Auth::user()->creatorId())
            ->withCount('responses')
            ->findOrFail($id);

        // If this survey has an eNPS question, we can still show its score.
        $enpsSummary = $enps->companyScore(Auth::user()->creatorId(), $survey->id);

        $questionStats = $this->buildQuestionAnalytics($survey->id, Auth::user()->creatorId());

        return view('surveys.analytics', compact('survey', 'enpsSummary', 'questionStats'));
    }

    /* ──────────────────────────────────────────────────────────────
     * Helpers
     * ──────────────────────────────────────────────────────────── */
    /**
     * Permission for any HR analytics dashboards (eNPS, sentiment, etc.).
     */
    protected function authorizeAnalytics(): void
    {
        $user = Auth::user();
        if (!$user || !$user->can('view-survey-analytics')) {
            abort(403, __('You do not have permission to view survey analytics.'));
        }
    }

    /**
     * Only HR / Admin / Company / Super-admin may manage surveys.
     * Throws 403 otherwise.
     */
    protected function authorizeManage(): void
    {
        $user = Auth::user();
        if (!$user || !$user->can('manage-surveys')) {
            abort(403, __('You do not have permission to manage surveys.'));
        }
    }

    /** HR/Admin can view survey alerts. */
    protected function authorizeAlerts(): void
    {
        $user = Auth::user();
        if (!$user || !$user->can('view-survey-alerts')) {
            abort(403, __('You do not have permission to view survey alerts.'));
        }
    }

    /** HR/Admin can export survey reports. */
    protected function authorizeExport(): void
    {
        $user = Auth::user();
        if (!$user || !$user->can('export-surveys')) {
            abort(403, __('You do not have permission to export surveys.'));
        }
    }

    /**
     * Per-question analytics for a survey (counts, averages, option breakdowns,
     * and sentiment summaries for text questions where analysis exists).
     *
     * @return array<int, array>
     */
    protected function buildQuestionAnalytics(int $surveyId, int $creatorId): array
    {
        $questions = SurveyQuestion::where('survey_id', $surveyId)
            ->orderBy('order_no')
            ->get();

        $ratingAgg = SurveyAnswer::query()
            ->join('survey_questions as q', 'q.id', '=', 'survey_answers.question_id')
            ->join('survey_responses as r', 'r.id', '=', 'survey_answers.response_id')
            ->join('employee_surveys  as s', 's.id', '=', 'r.survey_id')
            ->where('s.created_by', $creatorId)
            ->where('s.id', $surveyId)
            ->whereIn('q.question_type', ['rating_5', 'rating_10'])
            ->whereNotNull('survey_answers.rating_value')
            ->groupBy('survey_answers.question_id')
            ->select([
                'survey_answers.question_id',
                DB::raw('COUNT(*) as total'),
                DB::raw('AVG(survey_answers.rating_value) as avg_score'),
            ])->get()->keyBy('question_id');

        $choiceAgg = SurveyAnswer::query()
            ->join('survey_questions as q', 'q.id', '=', 'survey_answers.question_id')
            ->join('survey_responses as r', 'r.id', '=', 'survey_answers.response_id')
            ->join('employee_surveys  as s', 's.id', '=', 'r.survey_id')
            ->where('s.created_by', $creatorId)
            ->where('s.id', $surveyId)
            ->whereIn('q.question_type', ['yes_no', 'multiple_choice'])
            ->whereNotNull('survey_answers.answer_value')
            ->groupBy('survey_answers.question_id', 'survey_answers.answer_value')
            ->select([
                'survey_answers.question_id',
                'survey_answers.answer_value',
                DB::raw('COUNT(*) as total'),
            ])->get()->groupBy('question_id');

        $textAgg = SurveyAnswer::query()
            ->join('survey_questions as q', 'q.id', '=', 'survey_answers.question_id')
            ->join('survey_responses as r', 'r.id', '=', 'survey_answers.response_id')
            ->join('employee_surveys  as s', 's.id', '=', 'r.survey_id')
            ->where('s.created_by', $creatorId)
            ->where('s.id', $surveyId)
            ->where('q.question_type', 'text')
            ->whereNotNull('survey_answers.text_value')
            ->groupBy('survey_answers.question_id')
            ->select([
                'survey_answers.question_id',
                DB::raw('COUNT(*) as total'),
            ])->get()->keyBy('question_id');

        $sentimentAgg = collect();
        if (Schema::hasTable('survey_sentiment_analysis')) {
            $sentimentAgg = SurveySentimentAnalysis::query()
                ->join('survey_answers as a', 'a.id', '=', 'survey_sentiment_analysis.answer_id')
                ->join('survey_questions as q', 'q.id', '=', 'a.question_id')
                ->join('survey_responses as r', 'r.id', '=', 'a.response_id')
                ->join('employee_surveys  as s', 's.id', '=', 'r.survey_id')
                ->where('s.created_by', $creatorId)
                ->where('s.id', $surveyId)
                ->where('q.question_type', 'text')
                ->groupBy('a.question_id', 'survey_sentiment_analysis.sentiment')
                ->select([
                    'a.question_id',
                    'survey_sentiment_analysis.sentiment',
                    DB::raw('COUNT(*) as total'),
                ])->get()->groupBy('question_id');
        }

        $out = [];
        foreach ($questions as $q) {
            $row = [
                'id'       => (int) $q->id,
                'order_no' => (int) $q->order_no,
                'text'     => $q->question_text,
                'type'     => $q->question_type,
                'required' => (bool) $q->is_required,
                'total'    => 0,
                'avg'      => null,
                'options'  => [],
                'sentiment'=> [],
            ];

            if ($q->isRating()) {
                $agg = $ratingAgg->get($q->id);
                $row['total'] = (int) ($agg?->total ?? 0);
                $row['avg']   = $agg ? round((float) $agg->avg_score, 2) : null;
            } elseif (in_array($q->question_type, ['yes_no', 'multiple_choice'], true)) {
                $opts = $choiceAgg->get($q->id, collect());
                $row['total'] = (int) $opts->sum('total');
                $row['options'] = $opts->map(fn($o) => ['value' => (string) $o->answer_value, 'total' => (int) $o->total])
                    ->sortByDesc('total')
                    ->values()
                    ->all();
            } elseif ($q->question_type === 'text') {
                $agg = $textAgg->get($q->id);
                $row['total'] = (int) ($agg?->total ?? 0);

                $sents = $sentimentAgg->get($q->id, collect());
                $row['sentiment'] = $sents->map(fn($s) => ['sentiment' => $s->sentiment, 'total' => (int) $s->total])
                    ->sortByDesc('total')
                    ->values()
                    ->all();
            }

            $out[] = $row;
        }

        return $out;
    }

    /**
     * Employees (and roles that include employee capabilities) can view assigned
     * active surveys and submit responses.
     */
    protected function authorizeSubmit(): void
    {
        $user = Auth::user();
        if (!$user || !$user->can('submit-surveys')) {
            abort(403, __('You do not have permission to submit surveys.'));
        }
    }

    /** Employees can view their own submission history. */
    protected function authorizeViewOwn(): void
    {
        $user = Auth::user();
        if (!$user || !$user->can('view-own-surveys')) {
            abort(403, __('You do not have permission to view your survey history.'));
        }
    }
}
