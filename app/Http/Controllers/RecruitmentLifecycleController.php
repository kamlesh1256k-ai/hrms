<?php

namespace App\Http\Controllers;

use App\Models\BgvCheck;
use App\Models\DecisionNote;
use App\Models\Employee;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\PreonboardingItem;
use App\Models\ProbationReview;
use App\Models\RecruitmentAssessment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RecruitmentLifecycleController extends Controller
{
    private function creatorId(): int
    {
        return Auth::user()->creatorId();
    }

    private function authorizedCandidate(int $id): JobApplication
    {
        return JobApplication::where('created_by', $this->creatorId())->findOrFail($id);
    }

    private function authorizedEmployee(int $id): Employee
    {
        return Employee::where('created_by', $this->creatorId())->findOrFail($id);
    }

    // ════════════════════════════════════════════════════════════════
    // BACKGROUND VERIFICATION (BGV)
    // ════════════════════════════════════════════════════════════════

    public function bgvIndex(Request $request)
    {
        $cid = $this->creatorId();
        // Group BGV rows by candidate, summarising progress
        $candidates = JobApplication::where('created_by', $cid)
            ->whereHas('bgvChecks')
            ->with(['jobs', 'bgvChecks'])
            ->latest()
            ->paginate(20);

        return view('recruitment.bgv.index', compact('candidates'));
    }

    public function bgvShow($candidateId)
    {
        $candidate = $this->authorizedCandidate($candidateId);
        $checks = BgvCheck::where('candidate_id', $candidate->id)
            ->orderBy('check_type')->orderBy('id')->get();
        return view('recruitment.bgv.show', compact('candidate', 'checks'));
    }

    public function bgvInitiate($candidateId)
    {
        $candidate = $this->authorizedCandidate($candidateId);
        if (BgvCheck::where('candidate_id', $candidate->id)->exists()) {
            return back()->with('error', __('BGV already initiated for this candidate.'));
        }
        foreach (BgvCheck::defaultChecklist() as [$type, $label]) {
            BgvCheck::create([
                'candidate_id' => $candidate->id,
                'check_type'   => $type,
                'item_label'   => $label,
                'status'       => 'pending',
                'initiated_on' => now()->toDateString(),
                'created_by'   => $this->creatorId(),
            ]);
        }
        return redirect()->route('recruitment.bgv.show', $candidate->id)
            ->with('success', __('BGV checklist initiated.'));
    }

    public function bgvUpdate(Request $request, $checkId)
    {
        $check = BgvCheck::where('created_by', $this->creatorId())->findOrFail($checkId);
        $data = $request->validate([
            'status'       => 'required|in:pending,in_progress,cleared,failed,na',
            'notes'        => 'nullable|string|max:2000',
            'completed_on' => 'nullable|date',
            'document'     => 'nullable|file|max:10240|mimes:pdf,jpg,jpeg,png,doc,docx',
        ]);

        if ($request->hasFile('document')) {
            $file = $request->file('document');
            $name = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '-' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('recruitment/bgv/' . $check->candidate_id, $name, 'public');
            $check->document_path = $path;
        }

        $check->status              = $data['status'];
        $check->notes               = $data['notes'] ?? null;
        $check->completed_on        = in_array($data['status'], ['cleared', 'failed', 'na'], true)
                                       ? ($data['completed_on'] ?? now()->toDateString())
                                       : null;
        $check->verified_by_user_id = Auth::id();
        $check->save();

        return back()->with('success', __('Verification entry updated.'));
    }

    public function bgvAddCustom(Request $request, $candidateId)
    {
        $candidate = $this->authorizedCandidate($candidateId);
        $data = $request->validate([
            'check_type' => 'required|in:employment,education,id,address,criminal,reference,drug',
            'item_label' => 'required|string|max:200',
        ]);
        BgvCheck::create([
            'candidate_id' => $candidate->id,
            'check_type'   => $data['check_type'],
            'item_label'   => $data['item_label'],
            'status'       => 'pending',
            'initiated_on' => now()->toDateString(),
            'created_by'   => $this->creatorId(),
        ]);
        return back()->with('success', __('Check added.'));
    }

    public function bgvDelete($checkId)
    {
        $check = BgvCheck::where('created_by', $this->creatorId())->findOrFail($checkId);
        if ($check->document_path && Storage::disk('public')->exists($check->document_path)) {
            Storage::disk('public')->delete($check->document_path);
        }
        $check->delete();
        return back()->with('success', __('Check removed.'));
    }

    // ════════════════════════════════════════════════════════════════
    // PRE-ONBOARDING
    // ════════════════════════════════════════════════════════════════

    public function preonIndex()
    {
        $cid = $this->creatorId();
        $candidates = JobApplication::where('created_by', $cid)
            ->whereHas('preonboardingItems')
            ->with(['jobs', 'preonboardingItems'])
            ->latest()
            ->paginate(20);
        return view('recruitment.preonboarding.index', compact('candidates'));
    }

    public function preonShow($candidateId)
    {
        $candidate = $this->authorizedCandidate($candidateId);
        $items = PreonboardingItem::where('candidate_id', $candidate->id)
            ->orderByRaw("FIELD(category, 'document','asset','access','training','other')")
            ->orderBy('id')->get();
        return view('recruitment.preonboarding.show', compact('candidate', 'items'));
    }

    public function preonInitiate($candidateId)
    {
        $candidate = $this->authorizedCandidate($candidateId);
        if (PreonboardingItem::where('candidate_id', $candidate->id)->exists()) {
            return back()->with('error', __('Pre-onboarding already initiated.'));
        }
        foreach (PreonboardingItem::defaultChecklist() as [$cat, $label]) {
            PreonboardingItem::create([
                'candidate_id' => $candidate->id,
                'category'     => $cat,
                'item_label'   => $label,
                'status'       => 'pending',
                'created_by'   => $this->creatorId(),
            ]);
        }
        return redirect()->route('recruitment.preonboarding.show', $candidate->id)
            ->with('success', __('Pre-onboarding checklist initiated.'));
    }

    public function preonUpdate(Request $request, $itemId)
    {
        $item = PreonboardingItem::where('created_by', $this->creatorId())->findOrFail($itemId);
        $data = $request->validate([
            'status'       => 'required|in:pending,received,completed,waived',
            'notes'        => 'nullable|string|max:2000',
            'due_by'       => 'nullable|date',
            'document'     => 'nullable|file|max:10240|mimes:pdf,jpg,jpeg,png,doc,docx',
        ]);

        if ($request->hasFile('document')) {
            $file = $request->file('document');
            $name = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '-' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('recruitment/preonboarding/' . $item->candidate_id, $name, 'public');
            $item->document_path = $path;
        }

        $item->status       = $data['status'];
        $item->notes        = $data['notes'] ?? null;
        $item->due_by       = $data['due_by'] ?? $item->due_by;
        $item->completed_on = in_array($data['status'], ['completed', 'waived'], true)
                               ? now()->toDateString()
                               : null;
        $item->save();

        return back()->with('success', __('Item updated.'));
    }

    public function preonAddCustom(Request $request, $candidateId)
    {
        $candidate = $this->authorizedCandidate($candidateId);
        $data = $request->validate([
            'category'   => 'required|in:document,asset,access,training,other',
            'item_label' => 'required|string|max:200',
        ]);
        PreonboardingItem::create([
            'candidate_id' => $candidate->id,
            'category'     => $data['category'],
            'item_label'   => $data['item_label'],
            'status'       => 'pending',
            'created_by'   => $this->creatorId(),
        ]);
        return back()->with('success', __('Item added.'));
    }

    public function preonDelete($itemId)
    {
        $item = PreonboardingItem::where('created_by', $this->creatorId())->findOrFail($itemId);
        if ($item->document_path && Storage::disk('public')->exists($item->document_path)) {
            Storage::disk('public')->delete($item->document_path);
        }
        $item->delete();
        return back()->with('success', __('Item removed.'));
    }

    // ════════════════════════════════════════════════════════════════
    // PROBATION & CONFIRMATION
    // ════════════════════════════════════════════════════════════════

    public function probationIndex()
    {
        $cid = $this->creatorId();
        // List employees on probation (those with company_doj set within last 6 months)
        $employees = Employee::where('created_by', $cid)
            ->where(function ($q) {
                $q->whereDate('company_doj', '>=', now()->subMonths(6));
            })
            ->orderByDesc('company_doj')
            ->paginate(20);
        return view('recruitment.probation.index', compact('employees'));
    }

    public function probationShow($employeeId)
    {
        $employee = $this->authorizedEmployee($employeeId);
        $reviews = ProbationReview::where('employee_id', $employee->id)
            ->orderBy('day_milestone')->get();

        // Auto-create scaffolding if no reviews exist yet
        if ($reviews->isEmpty() && $employee->company_doj) {
            $joined = \Carbon\Carbon::parse($employee->company_doj);
            foreach (ProbationReview::$milestones as $days) {
                ProbationReview::create([
                    'employee_id'   => $employee->id,
                    'joined_on'     => $joined->toDateString(),
                    'day_milestone' => $days,
                    'review_date'   => $joined->copy()->addDays($days)->toDateString(),
                    'outcome'       => 'pending',
                    'created_by'    => $this->creatorId(),
                ]);
            }
            $reviews = ProbationReview::where('employee_id', $employee->id)
                ->orderBy('day_milestone')->get();
        }

        return view('recruitment.probation.show', compact('employee', 'reviews'));
    }

    public function probationUpdate(Request $request, $reviewId)
    {
        $review = ProbationReview::where('created_by', $this->creatorId())->findOrFail($reviewId);
        $data = $request->validate([
            'outcome'         => 'required|in:pending,on_track,needs_improvement,extend,confirm,terminate',
            'rating'          => 'nullable|integer|min:1|max:5',
            'strengths'       => 'nullable|string|max:2000',
            'improvements'    => 'nullable|string|max:2000',
            'manager_comments'=> 'nullable|string|max:2000',
            'review_date'     => 'nullable|date',
        ]);
        $review->fill($data);
        $review->reviewer_user_id = Auth::id();
        if ($data['outcome'] !== 'pending' && empty($review->review_date)) {
            $review->review_date = now()->toDateString();
        }
        $review->save();
        return back()->with('success', __('Review saved.'));
    }

    // ════════════════════════════════════════════════════════════════
    // ASSESSMENT / TEST SCORECARDS
    // ════════════════════════════════════════════════════════════════

    public function assessmentIndex()
    {
        $cid = $this->creatorId();
        $candidates = JobApplication::where('created_by', $cid)
            ->whereHas('assessments')
            ->with(['jobs', 'assessments'])
            ->latest()
            ->paginate(20);
        return view('recruitment.assessments.index', compact('candidates'));
    }

    public function assessmentShow($candidateId)
    {
        $candidate = $this->authorizedCandidate($candidateId);
        $assessments = RecruitmentAssessment::where('candidate_id', $candidate->id)
            ->orderBy('id')->get();
        return view('recruitment.assessments.show', compact('candidate', 'assessments'));
    }

    public function assessmentStore(Request $request, $candidateId)
    {
        $candidate = $this->authorizedCandidate($candidateId);
        $data = $request->validate([
            'assessment_type' => 'required|in:aptitude,technical,case_study,coding,personality',
            'title'           => 'required|string|max:200',
            'scheduled_on'    => 'nullable|date',
            'max_score'       => 'required|integer|min:1|max:1000',
            'passing_score'   => 'required|integer|min:0',
        ]);
        RecruitmentAssessment::create(array_merge($data, [
            'candidate_id' => $candidate->id,
            'outcome'      => 'pending',
            'created_by'   => $this->creatorId(),
        ]));
        return back()->with('success', __('Assessment scheduled.'));
    }

    public function assessmentUpdate(Request $request, $assessmentId)
    {
        $a = RecruitmentAssessment::where('created_by', $this->creatorId())->findOrFail($assessmentId);
        $data = $request->validate([
            'score'        => 'nullable|integer|min:0',
            'outcome'      => 'required|in:pending,completed,cleared,rejected,no_show',
            'feedback'     => 'nullable|string|max:5000',
            'completed_on' => 'nullable|date',
            'document'     => 'nullable|file|max:10240|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx',
        ]);
        if ($request->hasFile('document')) {
            $file = $request->file('document');
            $name = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '-' . time() . '.' . $file->getClientOriginalExtension();
            $a->document_path = $file->storeAs('recruitment/assessments/' . $a->candidate_id, $name, 'public');
        }
        $a->fill($data);
        $a->evaluator_user_id = Auth::id();
        if ($data['outcome'] !== 'pending' && empty($a->completed_on)) {
            $a->completed_on = now()->toDateString();
        }
        $a->save();
        return back()->with('success', __('Assessment updated.'));
    }

    public function assessmentDelete($assessmentId)
    {
        $a = RecruitmentAssessment::where('created_by', $this->creatorId())->findOrFail($assessmentId);
        if ($a->document_path && Storage::disk('public')->exists($a->document_path)) {
            Storage::disk('public')->delete($a->document_path);
        }
        $a->delete();
        return back()->with('success', __('Assessment removed.'));
    }

    // ════════════════════════════════════════════════════════════════
    // CANDIDATE COMPARE
    // ════════════════════════════════════════════════════════════════

    public function compareForm(Request $request)
    {
        $cid = $this->creatorId();
        $jobId = $request->input('job_id');
        $jobs = Job::where('created_by', $cid)->orderByDesc('id')->get(['id', 'title']);

        $candidates = collect();
        $compare    = collect();
        if ($jobId) {
            $candidates = JobApplication::where('created_by', $cid)
                ->where('job', $jobId)
                ->orderBy('name')->get();
            $ids = (array) $request->input('candidates', []);
            if (!empty($ids)) {
                $compare = JobApplication::with([
                        'jobs', 'recruiter', 'assessments', 'bgvChecks',
                        'decisionNotes.user', 'finalDecidedBy',
                        'interviews.users',
                        'offer',
                    ])
                    ->where('created_by', $cid)
                    ->whereIn('id', $ids)
                    ->get();
            }
        }
        return view('recruitment.compare.index', compact('jobs', 'jobId', 'candidates', 'compare'));
    }

    // ════════════════════════════════════════════════════════════════
    // FINAL EVALUATION & DECISION (Stage 7)
    // ════════════════════════════════════════════════════════════════

    /**
     * POST /recruitment/decisions/{candidate}
     * Mark a candidate as Selected / Backup / Rejected after the final
     * evaluation. The actor's user-id is recorded for accountability.
     */
    public function markDecision(Request $request, $candidateId)
    {
        $candidate = $this->authorizedCandidate($candidateId);
        $data = $request->validate([
            'final_status' => 'required|in:pending,selected,backup,rejected',
            'final_rank'   => 'nullable|integer|min:1|max:99',
            'final_notes'  => 'nullable|string|max:5000',
        ]);
        $candidate->fill($data);
        $candidate->final_decided_by = Auth::id();
        $candidate->final_decided_at = now();
        $candidate->save();

        return back()->with('success', __('Final decision recorded.'));
    }

    /**
     * POST /recruitment/decisions/{candidate}/notes
     * Append an internal-discussion comment.
     */
    public function postDecisionNote(Request $request, $candidateId)
    {
        $candidate = $this->authorizedCandidate($candidateId);
        $data = $request->validate(['note' => 'required|string|max:3000']);
        DecisionNote::create([
            'candidate_id' => $candidate->id,
            'user_id'      => Auth::id(),
            'note'         => $data['note'],
            'created_by'   => $this->creatorId(),
        ]);
        return back()->with('success', __('Note posted.'));
    }

    /**
     * DELETE /recruitment/decisions/notes/{note}
     * Only the author (or company admin) can delete their note.
     */
    public function deleteDecisionNote($noteId)
    {
        $note = DecisionNote::where('created_by', $this->creatorId())->findOrFail($noteId);
        $isAuthor = (int) $note->user_id === Auth::id();
        $isAdmin  = in_array(Auth::user()->type, ['company', 'super admin'], true);
        if (!$isAuthor && !$isAdmin) {
            abort(403, 'You can only delete your own notes.');
        }
        $note->delete();
        return back()->with('success', __('Note removed.'));
    }

    /**
     * GET /recruitment/decisions
     * Lists every candidate grouped by final status, scoped to a job.
     */
    public function decisionsIndex(Request $request)
    {
        $cid = $this->creatorId();
        $jobId = $request->input('job_id');
        $jobs  = Job::where('created_by', $cid)->orderByDesc('id')->get(['id', 'title']);

        $query = JobApplication::with(['jobs', 'finalDecidedBy'])
            ->where('created_by', $cid);
        if ($jobId) $query->where('job', $jobId);

        $candidates = $query->orderByRaw("FIELD(final_status, 'selected','backup','pending','rejected')")
            ->orderBy('final_rank')
            ->orderByDesc('rating')
            ->get();

        $grouped = $candidates->groupBy('final_status');

        return view('recruitment.decisions.index', compact('jobs', 'jobId', 'grouped'));
    }

    // ════════════════════════════════════════════════════════════════
    // SOURCE-OF-HIRE & DASHBOARD ANALYTICS
    // ════════════════════════════════════════════════════════════════

    public function analytics()
    {
        $cid = $this->creatorId();

        // Source breakdown
        $sources = DB::table('job_applications')
            ->where('created_by', $cid)
            ->selectRaw("COALESCE(NULLIF(source,''),'unknown') AS source, COUNT(*) AS total")
            ->groupBy('source')
            ->orderByDesc('total')
            ->get();

        // Hires per source — using stage_id of "Hired"
        $hiredStageIds = DB::table('job_stages')
            ->where('created_by', $cid)
            ->where('title', 'Hired')
            ->pluck('id')->all();
        $hiresBySource = collect();
        if (!empty($hiredStageIds)) {
            $hiresBySource = DB::table('job_applications')
                ->where('created_by', $cid)
                ->whereIn('stage', $hiredStageIds)
                ->selectRaw("COALESCE(NULLIF(source,''),'unknown') AS source, COUNT(*) AS hires")
                ->groupBy('source')
                ->pluck('hires', 'source');
        }

        // Recruiter leaderboard
        $recruiters = DB::table('job_applications')
            ->where('created_by', $cid)
            ->whereNotNull('recruiter_id')
            ->selectRaw('recruiter_id, COUNT(*) AS candidates')
            ->groupBy('recruiter_id')
            ->orderByDesc('candidates')
            ->limit(10)
            ->get();
        $recruiterUsers = User::whereIn('id', $recruiters->pluck('recruiter_id'))->pluck('name', 'id');

        // Funnel — count per stage
        $funnel = DB::table('job_stages')
            ->leftJoin('job_applications', function ($j) use ($cid) {
                $j->on('job_applications.stage', '=', 'job_stages.id')
                  ->where('job_applications.created_by', $cid);
            })
            ->where('job_stages.created_by', $cid)
            ->selectRaw('job_stages.title, job_stages.order, COUNT(job_applications.id) AS total')
            ->groupBy('job_stages.id', 'job_stages.title', 'job_stages.order')
            ->orderBy('job_stages.order')
            ->get();

        return view('recruitment.analytics.index', compact(
            'sources', 'hiresBySource', 'recruiters', 'recruiterUsers', 'funnel'
        ));
    }
}
