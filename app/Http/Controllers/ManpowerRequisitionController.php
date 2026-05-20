<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Job;
use App\Models\ManpowerRequisition;
use App\Models\RequisitionApproval;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ManpowerRequisitionController extends Controller
{
    /**
     * Roles that can act as approvers. We treat company/hr as full approvers
     * (HOD/Management role); managers can only raise requisitions, not approve.
     */
    private const APPROVER_TYPES = ['company', 'hr', 'super admin'];

    private function creatorId(): int
    {
        return Auth::user()->creatorId();
    }

    private function isApprover(): bool
    {
        return in_array(Auth::user()->type, self::APPROVER_TYPES, true);
    }

    private function abortIfNotApprover(): void
    {
        if (!$this->isApprover()) {
            abort(403, 'Only HR / Management can approve requisitions.');
        }
    }

    /**
     * Map a Laravel user type to one of the approval-chain "roles" used by
     * the requisition workflow. company/super-admin act as ANY role (can
     * approve every step) — small-team convenience.
     */
    private function userApprovalRoles($user): array
    {
        $type = $user->type;
        if (in_array($type, ['company', 'super admin'], true)) {
            return ['hr', 'finance', 'manager']; // can approve any step
        }
        if ($type === 'hr')      return ['hr'];
        if ($type === 'finance') return ['finance'];
        return [];
    }

    private function canApproveCurrentStep(ManpowerRequisition $req): bool
    {
        if ($req->status !== 'pending') return false;
        $expected = $req->next_approver_role;
        if ($expected === null) return false;
        return in_array($expected, $this->userApprovalRoles(Auth::user()), true);
    }

    // ── DASHBOARD ──────────────────────────────────────────────
    public function dashboard()
    {
        $cid = $this->creatorId();
        $stats = [
            'pending'   => ManpowerRequisition::where('created_by', $cid)->where('status', 'pending')->count(),
            'approved'  => ManpowerRequisition::where('created_by', $cid)->where('status', 'approved')->count(),
            'rejected'  => ManpowerRequisition::where('created_by', $cid)->where('status', 'rejected')->count(),
            'fulfilled' => ManpowerRequisition::where('created_by', $cid)->where('status', 'fulfilled')->count(),
            'total'     => ManpowerRequisition::where('created_by', $cid)->count(),
        ];

        $recent = ManpowerRequisition::with(['department', 'raisedBy'])
            ->where('created_by', $cid)
            ->latest()
            ->limit(8)
            ->get();

        $notif = \App\Support\RecruitmentNotifications::summary();

        return view('recruitment.dashboard', compact('stats', 'recent', 'notif'));
    }

    // ── REQUISITION CRUD ───────────────────────────────────────
    public function index(Request $request)
    {
        $cid = $this->creatorId();
        $query = ManpowerRequisition::with(['department', 'raisedBy'])
            ->where('created_by', $cid);

        // Managers see only their own; approvers see everything for the company.
        if (!$this->isApprover()) {
            $query->where('raised_by_user_id', Auth::id());
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where('title', 'like', "%{$q}%");
        }

        $requisitions = $query->latest()->paginate(20)->withQueryString();
        return view('recruitment.requisitions.index', compact('requisitions'));
    }

    public function create()
    {
        $cid = $this->creatorId();
        $departments  = Department::where('created_by', $cid)->orderBy('name')->get();
        $designations = Designation::where('created_by', $cid)->orderBy('name')->get();
        $branches     = Branch::where('created_by', $cid)->orderBy('name')->get();
        return view('recruitment.requisitions.create', compact('departments', 'designations', 'branches'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'           => 'required|string|max:200',
            'department_id'   => 'nullable|integer|exists:departments,id',
            'designation_id'  => 'nullable|integer|exists:designations,id',
            'branch_id'       => 'nullable|integer|exists:branches,id',
            'skills'          => 'required|string|max:1000',
            'experience'      => 'nullable|string|max:100',
            'positions'       => 'required|integer|min:1|max:100',
            'priority'        => 'required|in:high,medium,low',
            'reason'          => 'required|in:replacement,new_hire,expansion',
            'replacement_for' => 'nullable|string|max:200',
            'salary_range'    => 'nullable|string|max:100',
            'location'        => 'nullable|string|max:200',
            'job_type'        => 'nullable|string|max:50',
            'needed_by'       => 'nullable|date',
            'description'     => 'nullable|string|max:5000',
            'submit_action'   => 'nullable|in:save_draft,submit',
            'approval_chain'  => 'nullable|string|max:200',
        ]);

        $action = $data['submit_action'] ?? 'submit';
        unset($data['submit_action']);

        $data['status']                = $action === 'save_draft' ? 'draft' : 'pending';
        $data['created_by']            = $this->creatorId();
        $data['raised_by_user_id']     = Auth::id();
        $data['approval_chain']        = $data['approval_chain'] ?? 'hr,finance';
        $data['current_approval_step'] = 0;

        $req = ManpowerRequisition::create($data);

        return redirect()->route('recruitment.requisitions.show', $req->id)
            ->with('success', __('Requisition created.'));
    }

    public function show($id)
    {
        $cid = $this->creatorId();
        $req = ManpowerRequisition::with(['department', 'designation', 'branch', 'raisedBy', 'approvals.actor', 'job'])
            ->where('created_by', $cid)
            ->findOrFail($id);

        // Managers may only view their own requisitions.
        if (!$this->isApprover() && (int) $req->raised_by_user_id !== Auth::id()) {
            abort(403);
        }

        $isOwner    = (int) $req->raised_by_user_id === Auth::id();
        $canEditJd  = $req->status === 'approved' && ($this->isApprover() || $isOwner);
        $canApprove = $this->canApproveCurrentStep($req);

        return view('recruitment.requisitions.show', [
            'req'         => $req,
            'isApprover'  => $this->isApprover(),
            'isOwner'     => $isOwner,
            'canGenerate' => $canEditJd,
            'canEditJd'   => $canEditJd,
            'canApprove'  => $canApprove,
        ]);
    }

    public function destroy($id)
    {
        $req = ManpowerRequisition::where('created_by', $this->creatorId())->findOrFail($id);
        if (!$this->isApprover() && (int) $req->raised_by_user_id !== Auth::id()) abort(403);
        if ($req->status === 'fulfilled') {
            return back()->with('error', __('Fulfilled requisitions cannot be deleted.'));
        }
        $req->delete();
        return redirect()->route('recruitment.requisitions.index')
            ->with('success', __('Requisition deleted.'));
    }

    // ── APPROVAL FLOW (multi-step chain) ───────────────────────
    public function approve(Request $request, $id)
    {
        $this->abortIfNotApprover();
        $req = ManpowerRequisition::where('created_by', $this->creatorId())->findOrFail($id);

        if (!$this->canApproveCurrentStep($req)) {
            return back()->with('error', __('You are not the current approver for this step.'));
        }

        DB::transaction(function () use ($request, $req) {
            $stepRole  = $req->next_approver_role;
            $chain     = $req->approval_chain_array;
            $nextStep  = $req->current_approval_step + 1;
            $isFinal   = $nextStep >= count($chain);

            $req->update([
                'status'                => $isFinal ? 'approved' : 'pending',
                'current_approval_step' => $nextStep,
            ]);

            RequisitionApproval::create([
                'requisition_id' => $req->id,
                'actor_user_id'  => Auth::id(),
                'actor_role'     => $stepRole ?? Auth::user()->type,
                'action'         => 'approved',
                'comments'       => $request->input('comments'),
                'created_by'     => $this->creatorId(),
            ]);
        });

        return back()->with('success', $req->fresh()->status === 'approved'
            ? __('Final approval recorded. HR can now generate the JD and post the job.')
            : __('Step approved. Forwarded to the next approver in the chain.'));
    }

    public function reject(Request $request, $id)
    {
        $this->abortIfNotApprover();
        $data = $request->validate(['comments' => 'required|string|max:1000']);

        $req = ManpowerRequisition::where('created_by', $this->creatorId())->findOrFail($id);
        if (!$this->canApproveCurrentStep($req)) {
            return back()->with('error', __('You are not the current approver for this step.'));
        }

        DB::transaction(function () use ($data, $req) {
            $stepRole = $req->next_approver_role;
            $req->update(['status' => 'rejected']);
            RequisitionApproval::create([
                'requisition_id' => $req->id,
                'actor_user_id'  => Auth::id(),
                'actor_role'     => $stepRole ?? Auth::user()->type,
                'action'         => 'rejected',
                'comments'       => $data['comments'],
                'created_by'     => $this->creatorId(),
            ]);
        });

        return back()->with('success', __('Requisition rejected. The full chain is now closed.'));
    }

    /**
     * JD generate / edit / save is allowed for the approver AND for the
     * manager who raised the requisition (collaborative editing). Anyone else
     * trying to act on someone else's requisition is rejected.
     */
    private function abortIfCannotEditJd(ManpowerRequisition $req): void
    {
        if ($this->isApprover()) return;
        if ((int) $req->raised_by_user_id === Auth::id()) return;
        abort(403, 'You are not allowed to edit this JD.');
    }

    // ── AI JD GENERATOR ────────────────────────────────────────
    public function generateJd($id)
    {
        $req = ManpowerRequisition::where('created_by', $this->creatorId())->findOrFail($id);
        $this->abortIfCannotEditJd($req);

        if ($req->status !== 'approved') {
            return response()->json(['ok' => false, 'error' => 'Requisition must be approved before generating JD.'], 422);
        }

        $jd = $this->buildJobDescription($req);
        $req->generated_jd = $jd;
        $req->save();

        return response()->json([
            'ok' => true,
            'jd' => $jd,
        ]);
    }

    public function updateJd(Request $request, $id)
    {
        $req = ManpowerRequisition::where('created_by', $this->creatorId())->findOrFail($id);
        $this->abortIfCannotEditJd($req);

        $data = $request->validate(['generated_jd' => 'required|string|max:20000']);
        $req->generated_jd = $data['generated_jd'];
        $req->save();

        return back()->with('success', __('JD updated.'));
    }

    // ── HANDOFF TO EXISTING JOB MODULE ─────────────────────────
    /**
     * Creates a row in the existing `jobs` table from this requisition and
     * redirects HR to the Job edit page so they can review & post.
     */
    public function createJob($id)
    {
        $this->abortIfNotApprover();
        $req = ManpowerRequisition::where('created_by', $this->creatorId())->findOrFail($id);

        if ($req->status !== 'approved') {
            return back()->with('error', __('Only approved requisitions can be turned into a job.'));
        }
        if ($req->job_id) {
            return redirect()->route('job.edit', $req->job_id)
                ->with('info', __('A job already exists for this requisition.'));
        }

        // Generate JD on the fly if HR clicked Create Job without explicit Generate.
        $jd = $req->generated_jd ?: $this->buildJobDescription($req);

        $job = Job::create([
            'requisition_id'  => $req->id,
            'title'           => $req->title,
            'description'     => $jd,
            'requirement'     => $req->skills,
            'branch'          => (int) ($req->branch_id ?? 0),
            'category'        => 0,
            'skill'           => $req->skills,
            'position'        => $req->positions,
            'start_date'      => now()->toDateString(),
            'end_date'        => now()->addDays(30)->toDateString(),
            'status'          => 'active',
            'applicant'       => 'public',
            'visibility'      => 'public',
            'code'            => Str::lower(Str::random(8)),
            'custom_question' => '',
            'created_by'      => $this->creatorId(),
        ]);

        $req->update(['job_id' => $job->id]);

        return redirect()->route('job.edit', $job->id)
            ->with('success', __('Job created from requisition. Review and publish it.'));
    }

    /**
     * Skills-driven JD generator. Uses keyword matching against a
     * responsibility/requirements library plus the skill list, then assembles
     * a structured Markdown-ish JD. No external API required.
     *
     * The output uses simple Markdown so it renders cleanly as HTML when the
     * existing Job edit form runs it through a rich-text editor.
     */
    private function buildJobDescription(ManpowerRequisition $req): string
    {
        $skills = $req->skills_array;
        $skillsLower = array_map('strtolower', $skills);

        $library = $this->jdLibrary();
        $tokens  = preg_split('/[^a-z0-9&+#]+/i', strtolower(implode(' ', [$req->title, $req->skills, $req->department->name ?? ''])));
        $tokens  = array_values(array_filter($tokens ?: []));

        $scored = [];
        foreach ($library as $idx => $entry) {
            $score = 0;
            foreach ($entry['match'] as $kw) {
                if (in_array($kw, $tokens, true) || in_array($kw, $skillsLower, true)) $score++;
            }
            $scored[] = ['score' => $score, 'idx' => $idx, 'entry' => $entry];
        }
        usort($scored, fn($a, $b) => $b['score'] === $a['score'] ? $a['idx'] <=> $b['idx'] : $b['score'] <=> $a['score']);

        $picked = array_slice(array_filter($scored, fn($s) => $s['score'] > 0), 0, 5);
        if (empty($picked)) {
            $picked = array_slice($scored, 0, 3);
        }

        $responsibilities = [];
        $requirements     = [];
        foreach ($picked as $row) {
            $responsibilities = array_merge($responsibilities, $row['entry']['responsibilities']);
            $requirements     = array_merge($requirements, $row['entry']['requirements']);
        }
        // Always include a skills-line in requirements
        if (!empty($skills)) {
            array_unshift($requirements, 'Hands-on experience with: ' . implode(', ', $skills) . '.');
        }
        if ($req->experience) {
            array_unshift($requirements, "Minimum experience: {$req->experience}.");
        }
        $responsibilities = array_values(array_unique($responsibilities));
        $requirements     = array_values(array_unique($requirements));

        $location = $req->location ?: ($req->branch->name ?? 'On-site / Remote');
        $jobType  = $req->job_type ?: 'Full-time';
        $salary   = $req->salary_range ?: 'Competitive, based on experience';

        $company = $this->companyContext();

        $about = "We are hiring a **{$req->title}** to join our team. " .
                 "This role is critical to our continued growth and you will work closely with cross-functional " .
                 "stakeholders to deliver measurable business impact.";

        $lines = [];
        $lines[] = "## About {$company['name']}";
        $lines[] = $company['about'];
        if ($company['contact']) {
            $lines[] = '';
            $lines[] = $company['contact'];
        }
        $lines[] = '';
        $lines[] = "## About the Role";
        $lines[] = $about;
        $lines[] = '';
        $lines[] = "**Location:** {$location}  ";
        $lines[] = "**Job Type:** {$jobType}  ";
        $lines[] = "**Compensation:** {$salary}  ";
        if ($req->positions > 1) $lines[] = "**Positions Open:** {$req->positions}  ";
        if ($req->needed_by)     $lines[] = "**Needed By:** " . $req->needed_by->format('d M Y') . "  ";
        $lines[] = '';
        $lines[] = "## Roles & Responsibilities";
        foreach ($responsibilities as $r) $lines[] = "- {$r}";
        $lines[] = '';
        $lines[] = "## Requirements";
        foreach ($requirements as $r) $lines[] = "- {$r}";
        $lines[] = '';
        $lines[] = "## What We Offer";
        $lines[] = "- Competitive compensation and performance bonuses";
        $lines[] = "- Health insurance and wellness benefits";
        $lines[] = "- Learning & development budget";
        $lines[] = "- Flexible work culture and supportive team";

        if ($req->description) {
            $lines[] = '';
            $lines[] = "## Additional Notes";
            $lines[] = $req->description;
        }

        return implode("\n", $lines);
    }

    /**
     * Resolve the hiring company's display name, an "About" blurb, and a
     * compact contact line for the JD header.
     *
     * Lookup order:
     *   1. settings table — company_name / company_about / company_address /
     *      company_telephone / company_email / company_website (per creator)
     *   2. User row (creator) — name / email
     *   3. Sensible generic fallbacks
     *
     * Admins can override the auto-generated blurb by adding a
     * `company_about` row in the settings table.
     */
    private function companyContext(): array
    {
        $cid = $this->creatorId();
        $settings = DB::table('settings')
            ->where('created_by', $cid)
            ->whereIn('name', [
                'company_name', 'company_about', 'company_address',
                'company_telephone', 'company_email', 'company_website',
                'site_company_email', 'site_company_phone',
            ])
            ->pluck('value', 'name');

        $creator = \App\Models\User::find($cid);
        $name    = trim($settings['company_name'] ?? ($creator->name ?? 'Our Company'));
        if ($name === '' || strtolower($name) === 'd&d') {
            $name = $creator->name ?? $name;
        }

        $about = trim($settings['company_about'] ?? '');
        if ($about === '') {
            $about = "**{$name}** is a fast-growing organisation focused on building exceptional " .
                     "products and experiences for our customers. We invest in our people, foster " .
                     "a collaborative culture, and look for talent that takes ownership and drives impact.";
        }

        $bits = [];
        if (!empty($settings['company_address']))    $bits[] = "📍 " . $settings['company_address'];
        $phone = $settings['company_telephone'] ?? ($settings['site_company_phone'] ?? '');
        if ($phone)                                   $bits[] = "📞 " . $phone;
        $email = $settings['company_email'] ?? ($settings['site_company_email'] ?? ($creator->email ?? ''));
        if ($email && stripos($email, '@example.com') === false) $bits[] = "✉️ " . $email;
        if (!empty($settings['company_website']))    $bits[] = "🌐 " . $settings['company_website'];

        return [
            'name'    => $name,
            'about'   => $about,
            'contact' => implode('  ·  ', $bits),
        ];
    }

    /**
     * Skill-keyword → responsibilities/requirements snippets. Pure data, no AI.
     * Tune this library with your own role/skill phrases over time.
     */
    private function jdLibrary(): array
    {
        return [
            [
                'match' => ['developer', 'engineer', 'software', 'backend', 'api', 'php', 'laravel', 'node', 'python', 'java'],
                'responsibilities' => [
                    'Design, develop and maintain backend services and APIs.',
                    'Write clean, well-tested, performant code following team standards.',
                    'Collaborate with product and design to deliver features end-to-end.',
                    'Troubleshoot production issues and contribute to post-mortems.',
                ],
                'requirements' => [
                    'Strong understanding of OOP, design patterns and database fundamentals.',
                    'Experience writing automated tests and reviewing pull requests.',
                    'Familiarity with REST APIs, version control (Git) and CI/CD pipelines.',
                ],
            ],
            [
                'match' => ['frontend', 'ui', 'react', 'vue', 'angular', 'next', 'nuxt', 'javascript', 'typescript', 'css'],
                'responsibilities' => [
                    'Build responsive, accessible user interfaces using modern frontend frameworks.',
                    'Translate Figma designs into pixel-perfect, performant components.',
                    'Optimise frontend performance, bundle size and Core Web Vitals.',
                ],
                'requirements' => [
                    'Solid grasp of HTML, CSS, JavaScript/TypeScript and a modern framework.',
                    'Comfortable working with REST/GraphQL APIs and state management.',
                    'Eye for UI detail, accessibility (WCAG) and cross-browser quirks.',
                ],
            ],
            [
                'match' => ['devops', 'sre', 'aws', 'azure', 'gcp', 'kubernetes', 'docker', 'terraform', 'ansible'],
                'responsibilities' => [
                    'Own infrastructure-as-code, CI/CD pipelines and deploy automation.',
                    'Run incident response, capacity planning and on-call rotation.',
                    'Drive observability — metrics, logs, tracing, alerting.',
                ],
                'requirements' => [
                    'Hands-on with at least one major cloud (AWS / Azure / GCP).',
                    'Comfort with containerisation, orchestration and IaC tooling.',
                    'Strong scripting skills (Bash / Python) and Linux fundamentals.',
                ],
            ],
            [
                'match' => ['data', 'analyst', 'analytics', 'sql', 'bi', 'tableau', 'powerbi', 'looker'],
                'responsibilities' => [
                    'Build dashboards and reports that drive business decisions.',
                    'Partner with stakeholders to define KPIs and answer ad-hoc questions.',
                    'Own data-quality checks for the metrics you publish.',
                ],
                'requirements' => [
                    'Advanced SQL and at least one BI tool (Tableau / Power BI / Looker).',
                    'Strong analytical thinking and a knack for storytelling with data.',
                    'Familiarity with a scripting language (Python / R) is a plus.',
                ],
            ],
            [
                'match' => ['hr', 'human', 'recruit', 'talent', 'people'],
                'responsibilities' => [
                    'Own end-to-end recruitment — sourcing, screening, scheduling, offers.',
                    'Drive employer branding initiatives and candidate experience.',
                    'Partner with hiring managers to forecast and close hiring plans.',
                ],
                'requirements' => [
                    'Proven track record closing roles within target SLAs.',
                    'Strong interviewing, negotiation and stakeholder-management skills.',
                    'Familiarity with ATS tools and modern sourcing channels.',
                ],
            ],
            [
                'match' => ['sales', 'business', 'bd', 'account'],
                'responsibilities' => [
                    'Own a quota and drive revenue against assigned targets.',
                    'Build a healthy pipeline through outbound and inbound channels.',
                    'Partner with marketing on demand-generation campaigns.',
                ],
                'requirements' => [
                    'Demonstrated quota attainment in a similar industry.',
                    'Excellent communication and negotiation skills.',
                    'Comfort with CRM tools (Salesforce / HubSpot).',
                ],
            ],
            [
                'match' => ['marketing', 'seo', 'content', 'digital', 'social', 'brand'],
                'responsibilities' => [
                    'Plan and execute multi-channel marketing campaigns.',
                    'Own performance metrics — MQL volume, CPL, conversion.',
                    'Partner with content and design to ship campaign assets on time.',
                ],
                'requirements' => [
                    'Hands-on with at least two of: SEO, paid ads, email, content.',
                    'Comfort with analytics tools (GA4, Mixpanel, etc.).',
                    'Strong copywriting and visual storytelling instincts.',
                ],
            ],
            [
                'match' => ['design', 'ux', 'ui', 'figma', 'product'],
                'responsibilities' => [
                    'Design user-centric experiences from research to high-fidelity mocks.',
                    'Run usability studies and iterate based on feedback.',
                    'Maintain and evolve the design system.',
                ],
                'requirements' => [
                    'Portfolio showing end-to-end product work, not just visuals.',
                    'Fluency in Figma and modern prototyping tools.',
                    'Strong understanding of accessibility and interaction design.',
                ],
            ],
            [
                'match' => ['finance', 'account', 'audit', 'cfo'],
                'responsibilities' => [
                    'Own monthly close, budgeting and variance analysis.',
                    'Drive statutory compliance — GST, TDS, ROC filings.',
                    'Partner with external auditors and internal stakeholders.',
                ],
                'requirements' => [
                    'CA / MBA Finance or equivalent qualification.',
                    'Strong Excel and ERP (Tally / Zoho / SAP) hands-on experience.',
                    'Sharp attention to detail and ability to meet tight close deadlines.',
                ],
            ],
            [
                'match' => ['support', 'customer', 'success', 'cs'],
                'responsibilities' => [
                    'Own customer onboarding, adoption and renewal.',
                    'Resolve escalations and partner with product on feature gaps.',
                    'Drive CSAT, NPS and retention metrics.',
                ],
                'requirements' => [
                    'Excellent written and verbal communication.',
                    'Empathy, patience and a genuine love for solving customer problems.',
                    'Experience with ticketing tools (Zendesk / Intercom / Freshdesk).',
                ],
            ],
        ];
    }
}
