<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\JobApplication;
use App\Models\TalentPoolCandidate;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TalentPoolController extends Controller
{
    private function creatorId(): int
    {
        return Auth::user()->creatorId();
    }

    private function authorized(int $id): TalentPoolCandidate
    {
        return TalentPoolCandidate::where('created_by', $this->creatorId())->findOrFail($id);
    }

    // ── INDEX (search + filters) ──────────────────────────────────
    public function index(Request $request)
    {
        $cid = $this->creatorId();
        $query = TalentPoolCandidate::where('created_by', $cid);

        if ($q = trim((string) $request->input('q'))) {
            $query->where(function ($qq) use ($q) {
                $qq->where('name', 'like', "%{$q}%")
                   ->orWhere('email', 'like', "%{$q}%")
                   ->orWhere('skills', 'like', "%{$q}%")
                   ->orWhere('current_company', 'like', "%{$q}%")
                   ->orWhere('tags', 'like', "%{$q}%");
            });
        }
        if ($s = $request->input('status'))   $query->where('status', $s);
        if ($src = $request->input('source')) $query->where('source', $src);

        $candidates = $query->orderByDesc('id')->paginate(20)->withQueryString();

        $statusCounts = TalentPoolCandidate::where('created_by', $cid)
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        return view('recruitment.talent-pool.index', compact('candidates', 'statusCounts'));
    }

    // ── CREATE / STORE ────────────────────────────────────────────
    public function create()
    {
        return view('recruitment.talent-pool.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateForm($request);
        if ($request->hasFile('resume')) {
            $data['resume_path'] = $this->saveResume($request->file('resume'));
        }
        $data['status']      = 'active';
        $data['created_by']  = $this->creatorId();
        // If added by employee, override source to referral and set their name
        if (!in_array(Auth::user()->type, ['company', 'super admin', 'hr'])) {
            $data['source']        = 'referral';
            $data['source_detail'] = Auth::user()->name;
        } else {
            $data['source'] = $data['source'] ?? 'outbound';
        }
        $tpc = TalentPoolCandidate::create($data);
        return redirect()->route('recruitment.talent-pool.show', $tpc->id)
            ->with('success', __('Candidate added to talent pool.'));
    }

    // ── SHOW + EDIT + UPDATE ──────────────────────────────────────
    public function show($id)
    {
        $candidate = $this->authorized($id);
        $candidate->load(['recruiter', 'linkedApplication.jobs']);
        return view('recruitment.talent-pool.show', compact('candidate'));
    }

    public function update(Request $request, $id)
    {
        $candidate = $this->authorized($id);
        $data = $this->validateForm($request);
        if ($request->hasFile('resume')) {
            // Delete old resume
            if ($candidate->resume_path && Storage::disk('public')->exists($candidate->resume_path)) {
                Storage::disk('public')->delete($candidate->resume_path);
            }
            $data['resume_path'] = $this->saveResume($request->file('resume'));
        }
        $candidate->fill($data);
        $candidate->save();
        return back()->with('success', __('Talent pool entry updated.'));
    }

    public function destroy($id)
    {
        $candidate = $this->authorized($id);
        if ($candidate->resume_path && Storage::disk('public')->exists($candidate->resume_path)) {
            Storage::disk('public')->delete($candidate->resume_path);
        }
        $candidate->delete();
        return redirect()->route('recruitment.talent-pool.index')
            ->with('success', __('Removed from talent pool.'));
    }

    public function updateStatus(Request $request, $id)
    {
        $candidate = $this->authorized($id);
        $data = $request->validate([
            'status' => 'required|in:active,contacted,interested,not_interested,placed,archived',
            'notes'  => 'nullable|string|max:5000',
        ]);
        $candidate->status = $data['status'];
        if ($candidate->status !== 'active') $candidate->last_engaged_at = now();
        if (!empty($data['notes'])) {
            $candidate->notes = trim(($candidate->notes ? $candidate->notes . "\n\n" : '')
                . '— ' . Auth::user()->name . ' · ' . now()->format('d M Y H:i') . "\n"
                . $data['notes']);
        }
        $candidate->save();
        return back()->with('success', __('Status updated.'));
    }

    // ── IMPORT FROM JOB APPLICATION ───────────────────────────────
    /**
     * One-click action — promote any JobApplication into the talent pool so
     * HR can re-engage them later for a different role.
     */
    public function importFromApplication(Request $request, $applicationId)
    {
        $cid = $this->creatorId();
        $app = JobApplication::where('created_by', $cid)->findOrFail($applicationId);

        // Skip if already imported
        $existing = TalentPoolCandidate::where('created_by', $cid)
            ->where(function ($q) use ($app) {
                $q->where('linked_application_id', $app->id)
                  ->orWhere('email', $app->email);
            })->first();
        if ($existing) {
            return redirect()->route('recruitment.talent-pool.show', $existing->id)
                ->with('info', __('Already in talent pool.'));
        }

        $tpc = TalentPoolCandidate::create([
            'name'                  => $app->name,
            'email'                 => $app->email,
            'phone'                 => $app->phone,
            'skills'                => $app->skill,
            'resume_path'           => $app->resume ? 'uploads/job/resume/' . $app->resume : null,
            'source'                => 'job_application',
            'source_detail'         => 'Imported from ' . ($app->jobs->title ?? 'application #' . $app->id),
            'linked_application_id' => $app->id,
            'assigned_recruiter_id' => $app->recruiter_id,
            'status'                => 'active',
            'notes'                 => $request->input('notes'),
            'created_by'            => $cid,
        ]);

        return redirect()->route('recruitment.talent-pool.show', $tpc->id)
            ->with('success', __('Candidate imported into talent pool.'));
    }

    // ── MATCH FOR A JOB ───────────────────────────────────────────
    /**
     * Given a job, score everyone in the talent pool by skill overlap and
     * return the top matches. Useful when HR opens a new role and wants
     * to revisit prior conversations.
     */
    public function matchForJob(Request $request)
    {
        $cid   = $this->creatorId();
        $jobId = $request->input('job_id');

        $jobs = Job::where('created_by', $cid)->orderByDesc('id')->get(['id', 'title', 'skill']);

        $matches  = collect();
        $job      = null;
        $targets  = [];
        if ($jobId) {
            $job = $jobs->firstWhere('id', (int) $jobId);
            if ($job) {
                $targets = array_values(array_filter(array_map('trim', explode(',', (string) $job->skill))));
            }
        }
        if ($job && !empty($targets)) {
            $candidates = TalentPoolCandidate::where('created_by', $cid)
                ->whereIn('status', ['active', 'contacted', 'interested'])
                ->get();
            $matches = $candidates->map(function ($c) use ($targets) {
                $c->setAttribute('_match_score', $c->matchScore($targets));
                return $c;
            })
            ->filter(fn($c) => $c->getAttribute('_match_score') > 0)
            ->sortByDesc(fn($c) => $c->getAttribute('_match_score'))
            ->values();
        }

        return view('recruitment.talent-pool.match', compact('jobs', 'job', 'targets', 'matches'));
    }

    // ── helpers ───────────────────────────────────────────────────
    private function validateForm(Request $request): array
    {
        return $request->validate([
            'name'                  => 'required|string|max:200',
            'email'                 => 'required|email|max:200',
            'phone'                 => 'nullable|string|max:50',
            'current_company'       => 'nullable|string|max:200',
            'current_designation'   => 'nullable|string|max:200',
            'experience_years'      => 'nullable|numeric|min:0|max:60',
            'skills'                => 'nullable|string|max:1000',
            'preferred_locations'   => 'nullable|string|max:500',
            'linkedin_url'          => 'nullable|url|max:500',
            'portfolio_url'         => 'nullable|url|max:500',
            'current_ctc'           => 'nullable|numeric|min:0',
            'expected_ctc'          => 'nullable|numeric|min:0',
            'notice_period_days'    => 'nullable|integer|min:0|max:365',
            'source'                => 'nullable|in:job_application,referral,linkedin,naukri,indeed,outbound,event,agency,other',
            'source_detail'         => 'nullable|string|max:200',
            'tags'                  => 'nullable|string|max:500',
            'notes'                 => 'nullable|string|max:5000',
            'assigned_recruiter_id' => 'nullable|integer|exists:users,id',
            'resume'                => 'nullable|file|mimes:pdf,doc,docx|max:10240',
        ]);
    }

    private function saveResume($file): string
    {
        $name = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
              . '-' . time() . '.' . $file->getClientOriginalExtension();
        return $file->storeAs('recruitment/talent-pool', $name, 'public');
    }
}
