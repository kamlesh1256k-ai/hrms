<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\GrKpiIndustry;
use App\Models\GrKpiCompanySize;
use App\Models\GrKpiSeniorityLevel;
use App\Models\GrKpiWorkModel;
use App\Models\GrKpiCompanyType;
use App\Models\GrKpiTimeframe;
use App\Models\GrKpiGeneration;
use App\Models\GrKpiAssignment;
use App\Models\Employee;

class GrKpiGeneratorController extends Controller
{
    private function creatorId(): int
    {
        return Auth::user()->creatorId();
    }

    private function abortIfFullyLocked(GrKpiGeneration $gen)
    {
        if (($gen->status ?? 'draft') === 'hod_reviewed') {
            abort(response()->json([
                'ok' => false,
                'error' => 'This review is finalised by the HOD and fully locked.',
            ], 423));
        }
    }

    /**
     * Field-phase guard.
     * - draft        → only employee fields editable
     * - submitted    → only manager fields editable
     * - manager_reviewed → only HOD fields editable
     * - hod_reviewed → nothing editable (handled by abortIfFullyLocked)
     */
    private function abortIfNotEmployeePhase(GrKpiGeneration $gen, string $field)
    {
        $status = $gen->status ?? 'draft';
        $managerFields = ['manager_rating', 'manager_remarks', 'manager_overall_remarks'];
        $hodFields     = ['head_rating', 'head_remarks', 'head_overall_remarks'];

        // Role-based field restrictions for employee-type users
        $user = Auth::user();
        if ($user->type === 'employee') {
            $empRecord = Employee::where('user_id', $user->id)->first();
            $viewerEmpId = $empRecord ? $empRecord->id : 0;

            // Determine viewer's role relative to assigned employees
            $assignedEmpIds = GrKpiAssignment::where('generation_id', $gen->id)->pluck('employee_id')->all();
            $isAssignedEmp = in_array($viewerEmpId, $assignedEmpIds);
            $isManager = false;
            $isHod = false;
            if (!$isAssignedEmp && $empRecord) {
                $assignedEmps = Employee::whereIn('id', $assignedEmpIds)->get(['reporting_manager_id', 'hod_id', 'management_id']);
                foreach ($assignedEmps as $ae) {
                    if ((int) $ae->reporting_manager_id === $viewerEmpId) $isManager = true;
                    if ((int) $ae->hod_id === $viewerEmpId || (int) $ae->management_id === $viewerEmpId) $isHod = true;
                }
            }

            $employeeFields = ['rating', 'remarks', 'rating_remarks'];

            // Assigned employee → only employee fields
            if ($isAssignedEmp && !in_array($field, $employeeFields, true)) {
                abort(response()->json(['ok' => false, 'error' => 'You can only edit your own rating and remarks.'], 403));
            }
            // Manager → only manager fields
            if ($isManager && !in_array($field, $managerFields, true)) {
                abort(response()->json(['ok' => false, 'error' => 'You can only edit manager rating and remarks.'], 403));
            }
            // HOD → only HOD fields
            if ($isHod && !in_array($field, $hodFields, true)) {
                abort(response()->json(['ok' => false, 'error' => 'You can only edit HOD rating and remarks.'], 403));
            }
        }

        if ($status === 'draft') {
            // everything allowed in draft
            return;
        }
        if ($status === 'submitted') {
            if (!in_array($field, $managerFields, true)) {
                abort(response()->json([
                    'ok' => false,
                    'error' => 'Employee has already submitted. Only manager rating fields are editable.',
                ], 423));
            }
            return;
        }
        if ($status === 'manager_reviewed') {
            if (!in_array($field, $hodFields, true)) {
                abort(response()->json([
                    'ok' => false,
                    'error' => 'Manager has finalised. Only HOD rating fields are editable.',
                ], 423));
            }
            return;
        }
    }

    /**
     * Show the generator form.
     */
    public function index()
    {
        $cid = $this->creatorId();
        $dropdowns = [
            'industries'  => GrKpiIndustry::where('created_by', $cid)->where('is_active', 1)->orderBy('sort_order')->orderBy('name')->get(),
            'sizes'       => GrKpiCompanySize::where('created_by', $cid)->where('is_active', 1)->orderBy('sort_order')->orderBy('name')->get(),
            'seniorities' => GrKpiSeniorityLevel::where('created_by', $cid)->where('is_active', 1)->orderBy('sort_order')->orderBy('name')->get(),
            'workModels'  => GrKpiWorkModel::where('created_by', $cid)->where('is_active', 1)->orderBy('sort_order')->orderBy('name')->get(),
            'compTypes'   => GrKpiCompanyType::where('created_by', $cid)->where('is_active', 1)->orderBy('sort_order')->orderBy('name')->get(),
            'timeframes'  => GrKpiTimeframe::where('created_by', $cid)->where('is_active', 1)->orderBy('sort_order')->orderBy('name')->get(),
        ];

        $countries = $this->countries();
        $cycles = \App\Models\PerformanceCycle::where('created_by', $cid)->orderByDesc('start_date')->get();
        $recent = GrKpiGeneration::with('cycle')->where('created_by', $cid)->latest()->limit(10)->get();

        // Employees dropdown for the "Assign" modal on recent rows
        $employees = Employee::where('created_by', $cid)->orderBy('name')->get(['id','name','employee_id']);

        // Pre-load assignment counts per generation so the table shows an
        // "Assigned: N" badge without an N+1 query loop.
        $assignmentCounts = GrKpiAssignment::where('created_by', $cid)
            ->whereIn('generation_id', $recent->pluck('id'))
            ->selectRaw('generation_id, count(*) as c')
            ->groupBy('generation_id')
            ->pluck('c', 'generation_id');

        return view('growth_review.kpi_generator.index', compact('dropdowns', 'countries', 'cycles', 'recent', 'employees', 'assignmentCounts'));
    }

    /**
     * Handle form submit: generate KRA/KPI, save record, optionally return PDF.
     */
    public function generate(Request $request)
    {
        $data = $request->validate([
            'cycle_id'         => 'nullable|exists:performance_cycles,id',
            'job_role'         => 'required|string|max:150',
            'department'       => 'nullable|string|max:150',
            'company_size'     => 'nullable|string|max:50',
            'industry'         => 'nullable|string|max:100',
            'city'             => 'nullable|string|max:100',
            'country'          => 'nullable|string|max:100',
            'seniority_level'  => 'nullable|string|max:50',
            'work_model'       => 'nullable|string|max:50',
            'company_type'     => 'nullable|string|max:100',
            'target_timeframe' => 'nullable|string|max:50',
            'no_of_items'      => 'nullable|integer|min:1|max:20',
            'output'           => 'nullable|in:view,pdf',
            'ai_mode'          => 'nullable|in:basic,advanced',
        ]);
        $data['no_of_items'] = $data['no_of_items'] ?? 5;

        $kras = $this->generateKras($data);

        $gen = GrKpiGeneration::create([
            'cycle_id'         => $data['cycle_id'] ?? null,
            'job_role'         => $data['job_role'],
            'department'       => $data['department'] ?? null,
            'company_size'     => $data['company_size'] ?? null,
            'industry'         => $data['industry'] ?? null,
            'city'             => $data['city'] ?? null,
            'country'          => $data['country'] ?? null,
            'seniority_level'  => $data['seniority_level'] ?? null,
            'work_model'       => $data['work_model'] ?? null,
            'company_type'     => $data['company_type'] ?? null,
            'target_timeframe' => $data['target_timeframe'] ?? null,
            'no_of_items'      => $data['no_of_items'],
            'content_json'     => json_encode($kras),
            'ai_mode'          => $data['ai_mode'] ?? 'basic',
            'created_by'       => $this->creatorId(),
        ]);

        // Always redirect to the show page so the user sees the new record
        // and the index "Recent Generations" reflects it on next visit.
        // For PDF output, append ?download=1 — the show view auto-triggers
        // the PDF download once the page has rendered.
        $params = ['id' => $gen->id];
        if (($data['output'] ?? 'view') === 'pdf') {
            $params['download'] = 1;
        }
        return redirect()->route('growth-review.kpi-generator.show', $params)
            ->with('success', __('KRA / KPI generated successfully.'));
    }

    public function show($id)
    {
        $user = Auth::user();
        $gen = GrKpiGeneration::where('created_by', $this->creatorId())->findOrFail($id);

        // Allow assigned employees and their managers/HODs to access this page
        if ($user->type === 'employee') {
            $empRecord = Employee::where('user_id', $user->id)->first();
            if ($empRecord) {
                $isAssigned = GrKpiAssignment::where('generation_id', $id)->where('employee_id', $empRecord->id)->exists();
                $assignedEmpIds = GrKpiAssignment::where('generation_id', $id)->pluck('employee_id')->all();
                $isManagerOf = Employee::whereIn('id', $assignedEmpIds)
                    ->where(function($q) use ($empRecord) {
                        $q->where('reporting_manager_id', $empRecord->id)
                          ->orWhere('hod_id', $empRecord->id)
                          ->orWhere('management_id', $empRecord->id);
                    })->exists();
                if (!$isAssigned && !$isManagerOf) abort(403);
            }
        }
        $kras = json_decode($gen->content_json, true) ?? [];

        // Current assignments + full employees list for the assign modal
        $assignments = GrKpiAssignment::with('employee')
            ->where('generation_id', $gen->id)
            ->orderByDesc('assigned_at')
            ->get();
        $assignedIds = $assignments->pluck('employee_id')->all();
        $employees = Employee::where('created_by', $this->creatorId())
            ->orderBy('name')->get(['id','name','employee_id']);

        // Determine viewer role relative to assigned employees
        $user = Auth::user();
        $isViewerEmployee = false;
        $isViewerManager  = false;
        $isViewerHod      = false;
        $isViewerAdmin    = in_array($user->type, ['company', 'super admin'], true);

        if ($user->type === 'employee') {
            $empRecord = Employee::where('user_id', $user->id)->first();
            if ($empRecord) {
                $viewerEmpId = $empRecord->id;
                // Check if viewer is one of the assigned employees
                $isViewerEmployee = in_array($viewerEmpId, $assignedIds);
                // Check if viewer is reporting_manager or hod of any assigned employee
                if (!$isViewerEmployee) {
                    $assignedEmps = Employee::whereIn('id', $assignedIds)->get(['reporting_manager_id', 'hod_id', 'management_id']);
                    foreach ($assignedEmps as $ae) {
                        if ((int) $ae->reporting_manager_id === $viewerEmpId) $isViewerManager = true;
                        if ((int) $ae->hod_id === $viewerEmpId) $isViewerHod = true;
                        if ((int) $ae->management_id === $viewerEmpId) $isViewerHod = true;
                    }
                }
            }
        }

        $viewerRole = 'admin';
        if ($isViewerEmployee) $viewerRole = 'employee';
        elseif ($isViewerManager) $viewerRole = 'manager';
        elseif ($isViewerHod) $viewerRole = 'hod';

        return view('growth_review.kpi_generator.show', compact('gen', 'kras', 'assignments', 'assignedIds', 'employees', 'isViewerEmployee', 'viewerRole'));
    }

    /**
     * Assign a generated KRA/KPI record to one or more employees.
     */
    public function assign(Request $request, $id)
    {
        $gen = GrKpiGeneration::where('created_by', $this->creatorId())->findOrFail($id);

        $data = $request->validate([
            'employee_ids'   => 'required|array|min:1',
            'employee_ids.*' => 'integer|exists:employees,id',
            'remarks'        => 'nullable|string|max:500',
        ]);

        $cid = $this->creatorId();
        $assignedBy = Auth::id();
        $now = now();

        // Scope: only employees that belong to the same creator can be assigned.
        $validEmpIds = Employee::where('created_by', $cid)
            ->whereIn('id', $data['employee_ids'])
            ->pluck('id')->all();

        $inserted = 0;
        foreach ($validEmpIds as $empId) {
            $exists = GrKpiAssignment::where('generation_id', $gen->id)
                ->where('employee_id', $empId)
                ->exists();
            if ($exists) continue;

            GrKpiAssignment::create([
                'generation_id' => $gen->id,
                'employee_id'   => $empId,
                'remarks'       => $data['remarks'] ?? null,
                'assigned_by'   => $assignedBy,
                'assigned_at'   => $now,
                'created_by'    => $cid,
            ]);
            $inserted++;
        }

        return redirect()->route('growth-review.kpi-generator.show', $gen->id)
            ->with('success', __(':n employee(s) assigned.', ['n' => $inserted]));
    }

    /**
     * Remove an assignment (pivot row), leaving the generation record intact.
     */
    public function unassign($id, $assignmentId)
    {
        $gen = GrKpiGeneration::where('created_by', $this->creatorId())->findOrFail($id);
        $a = GrKpiAssignment::where('created_by', $this->creatorId())
            ->where('generation_id', $gen->id)
            ->findOrFail($assignmentId);
        $a->delete();

        return back()->with('success', __('Assignment removed.'));
    }

    /**
     * Generic inline-update for any field in a KRA or KPI node.
     *
     * Accepts two shapes:
     *   - { scope: "kra", kra_index, field: "kra|description|weightage", value }
     *   - { scope: "kpi", kra_index, kpi_index, field: "metric|target|frequency", value }
     *
     * Legacy fallback (pre-generic endpoint): if `scope` is missing but
     * `target` is present it behaves like the old updateTarget signature.
     */
    public function updateTarget(Request $request, $id)
    {
        $gen = GrKpiGeneration::where('created_by', $this->creatorId())->findOrFail($id);
        $this->abortIfFullyLocked($gen);
        $this->abortIfNotEmployeePhase($gen, $request->input('field', ''));

        // Legacy fallback — old clients POST target only.
        if (!$request->filled('scope') && $request->filled('target')) {
            $request->merge(['scope' => 'kpi', 'field' => 'target', 'value' => $request->input('target')]);
        }

        $data = $request->validate([
            'scope'     => 'required|in:kra,kpi',
            'kra_index' => 'required|integer|min:0',
            'kpi_index' => 'nullable|integer|min:0',
            'field'     => 'required|string|max:30',
            'value'     => 'present|string|max:1000',
        ]);

        $kras = json_decode($gen->content_json, true) ?? [];
        if (!isset($kras[$data['kra_index']])) {
            return response()->json(['ok' => false, 'error' => 'KRA index out of range.'], 422);
        }

        if ($data['scope'] === 'kra') {
            $allowed = ['kra', 'description', 'weightage', 'rating', 'rating_remarks', 'manager_overall_remarks', 'head_overall_remarks'];
            if (!in_array($data['field'], $allowed, true)) {
                return response()->json(['ok' => false, 'error' => 'Field not editable.'], 422);
            }
            $value = trim($data['value']);
            if ($data['field'] === 'weightage') {
                $value = max(0, min(100, (int) preg_replace('/[^\d]/', '', $value)));
            }
            if ($data['field'] === 'rating') {
                $value = max(0, min(5, (int) $value));
            }
            $kras[$data['kra_index']][$data['field']] = $value;
        } else {
            if (!isset($data['kpi_index']) || !isset($kras[$data['kra_index']]['kpis'][$data['kpi_index']])) {
                return response()->json(['ok' => false, 'error' => 'KPI index out of range.'], 422);
            }
            $allowed = ['metric', 'target', 'frequency', 'rating', 'remarks', 'manager_rating', 'manager_remarks', 'head_rating', 'head_remarks'];
            if (!in_array($data['field'], $allowed, true)) {
                return response()->json(['ok' => false, 'error' => 'Field not editable.'], 422);
            }
            $value = trim($data['value']);
            if (in_array($data['field'], ['rating', 'manager_rating', 'head_rating'], true)) {
                $value = max(0, min(5, (int) $value));
            }
            $kras[$data['kra_index']]['kpis'][$data['kpi_index']][$data['field']] = $value;
        }

        $gen->content_json = json_encode($kras);
        $gen->save();

        return response()->json([
            'ok'    => true,
            'value' => $data['scope'] === 'kra'
                ? $kras[$data['kra_index']][$data['field']]
                : $kras[$data['kra_index']]['kpis'][$data['kpi_index']][$data['field']],
        ]);
    }

    /**
     * Add a new KPI row under an existing KRA.
     */
    public function addKpi(Request $request, $id)
    {
        $gen = GrKpiGeneration::where('created_by', $this->creatorId())->findOrFail($id);
        $this->abortIfFullyLocked($gen);
        if (($gen->status ?? "draft") !== "draft") { abort(response()->json(["ok" => false, "error" => "Employee has submitted — structure is locked."], 423)); }

        $data = $request->validate([
            'kra_index' => 'required|integer|min:0',
            'metric'    => 'required|string|max:255',
            'target'    => 'required|string|max:255',
            'frequency' => 'nullable|string|max:50',
        ]);

        $kras = json_decode($gen->content_json, true) ?? [];
        if (!isset($kras[$data['kra_index']])) {
            return response()->json(['ok' => false, 'error' => 'KRA index out of range.'], 422);
        }

        $newKpi = [
            'metric'    => trim($data['metric']),
            'target'    => trim($data['target']),
            'frequency' => trim($data['frequency'] ?? ($gen->target_timeframe ?: 'Quarterly')),
        ];
        $kras[$data['kra_index']]['kpis'][] = $newKpi;
        $gen->content_json = json_encode($kras);
        $gen->save();

        return response()->json([
            'ok'        => true,
            'kpi_index' => count($kras[$data['kra_index']]['kpis']) - 1,
            'kpi'       => $newKpi,
        ]);
    }

    /**
     * Delete a KPI row. Only allowed when the KRA has more than one KPI
     * (keeps the data model valid — a KRA without any KPI doesn't make sense).
     */
    public function deleteKpi(Request $request, $id)
    {
        $gen = GrKpiGeneration::where('created_by', $this->creatorId())->findOrFail($id);
        $this->abortIfFullyLocked($gen);
        if (($gen->status ?? "draft") !== "draft") { abort(response()->json(["ok" => false, "error" => "Employee has submitted — structure is locked."], 423)); }

        $data = $request->validate([
            'kra_index' => 'required|integer|min:0',
            'kpi_index' => 'required|integer|min:0',
        ]);

        $kras = json_decode($gen->content_json, true) ?? [];
        if (!isset($kras[$data['kra_index']]['kpis'][$data['kpi_index']])) {
            return response()->json(['ok' => false, 'error' => 'KPI index out of range.'], 422);
        }
        if (count($kras[$data['kra_index']]['kpis']) <= 1) {
            return response()->json(['ok' => false, 'error' => 'At least one KPI is required per KRA.'], 422);
        }

        array_splice($kras[$data['kra_index']]['kpis'], $data['kpi_index'], 1);
        $gen->content_json = json_encode($kras);
        $gen->save();

        return response()->json(['ok' => true]);
    }

    /**
     * Add a new KRA section.
     */
    public function addKra(Request $request, $id)
    {
        $gen = GrKpiGeneration::where('created_by', $this->creatorId())->findOrFail($id);
        $this->abortIfFullyLocked($gen);
        if (($gen->status ?? "draft") !== "draft") { abort(response()->json(["ok" => false, "error" => "Employee has submitted — structure is locked."], 423)); }

        $data = $request->validate([
            'kra'         => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'weightage'   => 'nullable|integer|min:0|max:100',
        ]);

        $kras = json_decode($gen->content_json, true) ?? [];
        $newKra = [
            'kra'         => trim($data['kra']),
            'description' => trim($data['description'] ?? ''),
            'weightage'   => (int) ($data['weightage'] ?? 0),
            'kpis'        => [[
                'metric'    => __('New KPI — edit metric name'),
                'target'    => __('Set target'),
                'frequency' => $gen->target_timeframe ?: 'Quarterly',
            ]],
        ];
        $kras[] = $newKra;
        $gen->content_json = json_encode($kras);
        $gen->save();

        return response()->json([
            'ok'        => true,
            'kra_index' => count($kras) - 1,
            'kra'       => $newKra,
        ]);
    }

    /**
     * Delete an entire KRA section (requires confirmation on the frontend).
     */
    public function deleteKra(Request $request, $id)
    {
        $gen = GrKpiGeneration::where('created_by', $this->creatorId())->findOrFail($id);
        $this->abortIfFullyLocked($gen);
        if (($gen->status ?? "draft") !== "draft") { abort(response()->json(["ok" => false, "error" => "Employee has submitted — structure is locked."], 423)); }

        $data = $request->validate([
            'kra_index' => 'required|integer|min:0',
        ]);

        $kras = json_decode($gen->content_json, true) ?? [];
        if (!isset($kras[$data['kra_index']])) {
            return response()->json(['ok' => false, 'error' => 'KRA index out of range.'], 422);
        }
        if (count($kras) <= 1) {
            return response()->json(['ok' => false, 'error' => 'At least one KRA is required.'], 422);
        }

        array_splice($kras, $data['kra_index'], 1);
        $gen->content_json = json_encode($kras);
        $gen->save();

        return response()->json(['ok' => true]);
    }

    /**
     * Upload a supporting document for a specific KPI row.
     */
    public function uploadKpiDocument(Request $request, $id)
    {
        $gen = GrKpiGeneration::where('created_by', $this->creatorId())->findOrFail($id);
        $this->abortIfFullyLocked($gen);
        if (($gen->status ?? "draft") !== "draft") { abort(response()->json(["ok" => false, "error" => "Employee has submitted — structure is locked."], 423)); }

        $data = $request->validate([
            'kra_index' => 'required|integer|min:0',
            'kpi_index' => 'required|integer|min:0',
            'document'  => 'required|file|max:10240|mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg',
        ]);

        $kras = json_decode($gen->content_json, true) ?? [];
        if (!isset($kras[$data['kra_index']]['kpis'][$data['kpi_index']])) {
            return response()->json(['ok' => false, 'error' => 'KPI not found.'], 422);
        }

        $file = $request->file('document');
        $ext  = $file->getClientOriginalExtension();
        $name = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '-' . time() . '.' . $ext;
        $path = $file->storeAs('gr_kpi_docs/' . $gen->id, $name, 'public');

        // Delete old file if present.
        $existing = $kras[$data['kra_index']]['kpis'][$data['kpi_index']]['document'] ?? null;
        if ($existing && Storage::disk('public')->exists($existing)) {
            Storage::disk('public')->delete($existing);
        }

        $kras[$data['kra_index']]['kpis'][$data['kpi_index']]['document'] = $path;
        $kras[$data['kra_index']]['kpis'][$data['kpi_index']]['document_name'] = $file->getClientOriginalName();
        $gen->content_json = json_encode($kras);
        $gen->save();

        return response()->json([
            'ok'   => true,
            'path' => $path,
            'url'  => Storage::disk('public')->url($path),
            'name' => $file->getClientOriginalName(),
        ]);
    }

    /**
     * Remove a KPI's attached document.
     */
    public function deleteKpiDocument(Request $request, $id)
    {
        $gen = GrKpiGeneration::where('created_by', $this->creatorId())->findOrFail($id);
        $this->abortIfFullyLocked($gen);
        if (($gen->status ?? "draft") !== "draft") { abort(response()->json(["ok" => false, "error" => "Employee has submitted — structure is locked."], 423)); }

        $data = $request->validate([
            'kra_index' => 'required|integer|min:0',
            'kpi_index' => 'required|integer|min:0',
        ]);

        $kras = json_decode($gen->content_json, true) ?? [];
        $existing = $kras[$data['kra_index']]['kpis'][$data['kpi_index']]['document'] ?? null;
        if ($existing && Storage::disk('public')->exists($existing)) {
            Storage::disk('public')->delete($existing);
        }
        unset($kras[$data['kra_index']]['kpis'][$data['kpi_index']]['document']);
        unset($kras[$data['kra_index']]['kpis'][$data['kpi_index']]['document_name']);
        $gen->content_json = json_encode($kras);
        $gen->save();

        return response()->json(['ok' => true]);
    }

    /**
     * Mark the generation as submitted (locks further edits by convention).
     */
    public function submit($id)
    {
        $gen = GrKpiGeneration::where('created_by', $this->creatorId())->findOrFail($id);
        if (($gen->status ?? 'draft') !== 'draft') {
            return redirect()->route('growth-review.kpi-generator.show', $gen->id)
                ->with('error', __('Review has already been submitted.'));
        }
        $gen->status = 'submitted';
        $gen->submitted_at = now();
        $gen->save();
        return redirect()->route('growth-review.kpi-generator.show', $gen->id)
            ->with('success', __('Review submitted. Manager can now add their rating.'));
    }

    /**
     * Manager finalises the review after giving manager ratings/remarks.
     * Moves the record to HOD-review phase (HOD fields unlock).
     */
    public function managerFinalize($id)
    {
        $gen = GrKpiGeneration::where('created_by', $this->creatorId())->findOrFail($id);
        if (($gen->status ?? 'draft') !== 'submitted') {
            return redirect()->route('growth-review.kpi-generator.show', $gen->id)
                ->with('error', __('Employee review must be submitted first.'));
        }
        $gen->status = 'manager_reviewed';
        $gen->manager_reviewed_at = now();
        $gen->save();
        return redirect()->route('growth-review.kpi-generator.show', $gen->id)
            ->with('success', __('Manager review submitted to HOD. HOD can now add their rating.'));
    }

    /**
     * HOD finalises the review after giving head ratings/remarks.
     * This fully locks the record.
     */
    public function hodFinalize($id)
    {
        $gen = GrKpiGeneration::where('created_by', $this->creatorId())->findOrFail($id);
        if (($gen->status ?? 'draft') !== 'manager_reviewed') {
            return redirect()->route('growth-review.kpi-generator.show', $gen->id)
                ->with('error', __('Manager review must be finalised first.'));
        }
        $gen->status = 'hod_reviewed';
        $gen->hod_reviewed_at = now();
        $gen->save();
        return redirect()->route('growth-review.kpi-generator.show', $gen->id)
            ->with('success', __('HOD review finalised. The record is now fully locked.'));
    }

    /**
     * Assigned KRA / KPI list.
     *
     * - Employee-type users see their own assignments only.
     * - Company / HR / admin-type users see every assignment across the
     *   company so they can audit or download anyone's sheet.
     */
    public function myAssigned()
    {
        $user = Auth::user();
        $emp  = Employee::where('user_id', $user->id)->first();
        $isAdmin = in_array($user->type, ['company', 'hr', 'super admin'], true);

        if ($isAdmin) {
            $assignments = GrKpiAssignment::with(['generation', 'employee'])
                ->where('created_by', $user->creatorId())
                ->orderByDesc('assigned_at')
                ->get();
        } elseif ($emp) {
            $assignments = GrKpiAssignment::with(['generation', 'employee'])
                ->where('employee_id', $emp->id)
                ->orderByDesc('assigned_at')
                ->get();
        } else {
            $assignments = collect();
        }

        // Drop orphaned rows (generation deleted) so the view doesn't render
        // an empty <foreach> loop with no content.
        $orphanCount = $assignments->filter(fn($a) => $a->generation === null)->count();
        $assignments = $assignments->filter(fn($a) => $a->generation !== null)->values();

        // Group assignments by cycle for accordion view
        $assignmentsByCycle = $assignments->groupBy(function($a) {
            return $a->generation?->cycle_id ?? 0;
        });
        $cycleIds = $assignmentsByCycle->keys()->filter()->all();
        $cycles = \App\Models\PerformanceCycle::whereIn('id', $cycleIds)->get()->keyBy('id');

        return view('growth_review.kpi_generator.my_assigned', compact('assignments', 'assignmentsByCycle', 'cycles', 'emp', 'isAdmin', 'orphanCount'));
    }

    /**
     * Stream a PDF for an existing generation record.
     */
    public function pdf($id)
    {
        $gen = GrKpiGeneration::where('created_by', $this->creatorId())->findOrFail($id);
        $kras = json_decode($gen->content_json, true) ?? [];

        if (!class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            return redirect()->back()->with('error', 'DomPDF package is not installed. Run: composer require barryvdh/laravel-dompdf');
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('growth_review.kpi_generator.pdf', compact('gen', 'kras'))
            ->setPaper('a4', 'portrait');

        $filename = Str::slug($gen->job_role) . '-kra-kpi-' . $gen->id . '.pdf';
        return $pdf->download($filename);
    }

    public function destroy($id)
    {
        $gen = GrKpiGeneration::where('created_by', $this->creatorId())->findOrFail($id);
        GrKpiAssignment::where('generation_id', $gen->id)->delete();
        $gen->delete();
        return redirect()->route('growth-review.kpi-generator.index')->with('success', 'Record deleted.');
    }

    // ──────────────────────────────────────────────────────────────
    // Template-based KRA/KPI generator (works without external AI).
    // ──────────────────────────────────────────────────────────────
    private function generateKras(array $d): array
    {
        $role      = strtolower($d['job_role']);
        $ind       = strtolower($d['industry'] ?? '');
        $dept      = strtolower($d['department'] ?? '');
        $seniority = strtolower($d['seniority_level'] ?? 'mid');
        $timeframe = $d['target_timeframe'] ?? 'Quarterly';
        $n         = (int) ($d['no_of_items'] ?? 5);

        // A library of KRAs by broad category. We score each by keyword
        // overlap against department + role + industry and pick the top N.
        // Department is the strongest signal (e.g. "Human Resources" → HR KRAs)
        // because role titles like "Head" or "Manager" are too generic to match on.
        $library = $this->kraLibrary();

        // Word-boundary matcher: "production" must not match the keyword "product".
        // Splits the corpus into tokens and compares full tokens against the keyword.
        $tokenize = function (string $s): array {
            return array_values(array_filter(preg_split('/[^a-z0-9&]+/', $s) ?: []));
        };
        $hasWord = function (array $tokens, string $kw): bool {
            return in_array($kw, $tokens, true);
        };

        $deptTokens = $tokenize($dept);
        $roleTokens = $tokenize($role);
        $indTokens  = $tokenize($ind);

        // Deterministic order index keeps output stable for the same input.
        $scored = [];
        foreach ($library as $idx => $item) {
            $isFallback = in_array('', $item['match'], true);
            $score = 0;
            foreach ($item['match'] as $kw) {
                if ($kw === '') continue;
                if ($hasWord($deptTokens, $kw)) $score += 4; // department is the strongest signal
                if ($hasWord($roleTokens, $kw)) $score += 2;
                if ($hasWord($indTokens,  $kw)) $score += 1;
            }
            // Generic fallback rows only win when nothing relevant matched.
            $scored[] = ['score' => $score, 'fallback' => $isFallback, 'idx' => $idx, 'item' => $item];
        }

        usort($scored, function ($a, $b) {
            // Non-fallbacks with score > 0 win first, then by score desc, then by original order.
            $aRank = ($a['score'] > 0 && !$a['fallback']) ? 1 : 0;
            $bRank = ($b['score'] > 0 && !$b['fallback']) ? 1 : 0;
            if ($aRank !== $bRank) return $bRank <=> $aRank;
            if ($a['score'] !== $b['score']) return $b['score'] <=> $a['score'];
            return $a['idx'] <=> $b['idx'];
        });

        $picked = array_slice(array_column($scored, 'item'), 0, $n);

        // Adjust KPI targets based on seniority & timeframe
        $multiplier = match ($seniority) {
            'entry' => 0.7,
            'mid'   => 1.0,
            'senior'=> 1.3,
            default => 1.0,
        };
        $periodLabel = $timeframe ?: 'Quarterly';

        $weight = (int) floor(100 / max(1, count($picked)));
        $remainder = 100 - ($weight * count($picked));

        $out = [];
        foreach ($picked as $i => $item) {
            $kpis = [];
            foreach ($item['kpis'] as $kpi) {
                $target = $kpi['target'];
                if (isset($kpi['scalable']) && $kpi['scalable']) {
                    // Scale numeric targets by multiplier.
                    $target = preg_replace_callback('/(\d+(\.\d+)?)/', function ($m) use ($multiplier) {
                        return (string) round(((float) $m[1]) * $multiplier, 2);
                    }, $target);
                }
                $kpis[] = [
                    'metric' => $kpi['metric'],
                    'target' => $target,
                    'frequency' => $periodLabel,
                ];
            }
            $out[] = [
                'kra'         => $item['kra'],
                'description' => $item['description'],
                'weightage'   => $weight + ($i === 0 ? $remainder : 0),
                'kpis'        => $kpis,
            ];
        }
        return $out;
    }

    private function kraLibrary(): array
    {
        return [
            [
                'match' => ['sales','business','account','revenue','bd'],
                'kra'   => 'Revenue Generation & Target Achievement',
                'description' => 'Drive topline revenue and close deals against assigned targets.',
                'kpis'  => [
                    ['metric' => 'Quarterly revenue target',   'target' => '₹50,00,000',       'scalable' => true],
                    ['metric' => 'New accounts closed',         'target' => '10 accounts',      'scalable' => true],
                    ['metric' => 'Sales pipeline coverage',     'target' => '3x quota',         'scalable' => false],
                ],
            ],
            [
                'match' => ['marketing','digital','content','brand','seo','social'],
                'kra'   => 'Marketing Funnel Performance',
                'description' => 'Own lead generation, content performance and brand KPIs.',
                'kpis'  => [
                    ['metric' => 'Marketing qualified leads (MQL)', 'target' => '500 / quarter', 'scalable' => true],
                    ['metric' => 'Cost per lead (CPL)',              'target' => '< ₹800',        'scalable' => false],
                    ['metric' => 'Organic traffic growth',           'target' => '+20% YoY',      'scalable' => true],
                ],
            ],
            [
                'match' => ['developer','engineer','software','tech','backend','frontend','full','devops'],
                'kra'   => 'Engineering Delivery & Code Quality',
                'description' => 'Ship features on time with high code quality and reliability.',
                'kpis'  => [
                    ['metric' => 'Sprint velocity',           'target' => '30 story points',   'scalable' => true],
                    ['metric' => 'Production defects',        'target' => '< 2 / release',      'scalable' => false],
                    ['metric' => 'Code review turnaround',    'target' => '< 24 hours',         'scalable' => false],
                    ['metric' => 'Test coverage',             'target' => '> 80%',              'scalable' => false],
                ],
            ],
            [
                'match' => ['hr','human','people','recruit','talent'],
                'kra'   => 'Talent Acquisition & Hiring',
                'description' => 'Build a robust pipeline and close roles within target SLAs.',
                'kpis'  => [
                    ['metric' => 'Time to hire',                 'target' => '< 30 days',       'scalable' => false],
                    ['metric' => 'Offer acceptance rate',        'target' => '> 85%',           'scalable' => false],
                    ['metric' => 'Cost per hire',                'target' => '< ₹25,000',       'scalable' => true],
                    ['metric' => 'Source of hire diversity',     'target' => '> 4 channels',    'scalable' => false],
                ],
            ],
            [
                'match' => ['hr','human','people','engagement','culture'],
                'kra'   => 'Employee Engagement & Retention',
                'description' => 'Build a positive employee experience and reduce voluntary attrition.',
                'kpis'  => [
                    ['metric' => 'Employee engagement score',    'target' => '> 4.0 / 5',       'scalable' => false],
                    ['metric' => 'Voluntary attrition',          'target' => '< 12% annual',    'scalable' => false],
                    ['metric' => 'eNPS',                         'target' => '> 30',            'scalable' => false],
                    ['metric' => 'Pulse survey participation',   'target' => '> 80%',           'scalable' => false],
                ],
            ],
            [
                'match' => ['hr','human','people','learning','training','l&d','ld'],
                'kra'   => 'Learning & Development Programs',
                'description' => 'Roll out training programs that build skills and meet completion targets.',
                'kpis'  => [
                    ['metric' => 'Avg. training hours per employee', 'target' => '> 24 / year',  'scalable' => true],
                    ['metric' => 'Mandatory training completion',    'target' => '100%',         'scalable' => false],
                    ['metric' => 'Internal mobility / promotions',   'target' => '> 15% of hires','scalable' => false],
                    ['metric' => 'Post-training assessment score',   'target' => '> 80%',        'scalable' => false],
                ],
            ],
            [
                'match' => ['hr','human','people','performance','review','appraisal'],
                'kra'   => 'Performance Management & Reviews',
                'description' => 'Run timely review cycles and ensure goal-setting compliance.',
                'kpis'  => [
                    ['metric' => 'On-time review completion',    'target' => '> 95%',           'scalable' => false],
                    ['metric' => 'Goal-setting compliance',      'target' => '100%',            'scalable' => false],
                    ['metric' => 'Calibration coverage',         'target' => '100% of managers','scalable' => false],
                    ['metric' => 'PIP closure rate',             'target' => '> 70%',           'scalable' => false],
                ],
            ],
            [
                'match' => ['hr','human','people','payroll','compliance','statutory'],
                'kra'   => 'HR Operations & Compliance',
                'description' => 'Run payroll, statutory filings and HR ops without errors or delays.',
                'kpis'  => [
                    ['metric' => 'Payroll accuracy',             'target' => '> 99.5%',         'scalable' => false],
                    ['metric' => 'Payroll on-time processing',   'target' => '100%',            'scalable' => false],
                    ['metric' => 'Statutory filing on-time',     'target' => '100% (PF/ESI/PT/TDS)', 'scalable' => false],
                    ['metric' => 'HR ticket resolution TAT',     'target' => '< 48 hours',      'scalable' => false],
                ],
            ],
            [
                'match' => ['hr','human','people','diversity','dei','inclusion'],
                'kra'   => 'Diversity, Equity & Inclusion',
                'description' => 'Improve workforce diversity and run inclusion initiatives.',
                'kpis'  => [
                    ['metric' => 'Female workforce share',       'target' => '> 35%',           'scalable' => false],
                    ['metric' => 'Diverse hiring slate',         'target' => '> 40% of openings','scalable' => false],
                    ['metric' => 'DEI training coverage',        'target' => '100% of managers','scalable' => false],
                    ['metric' => 'POSH / grievance closure',     'target' => '< 30 days',       'scalable' => false],
                ],
            ],
            [
                'match' => ['finance','account','cfo','controller','audit'],
                'kra'   => 'Financial Control & Reporting',
                'description' => 'Ensure accurate, timely reporting and tight cost control.',
                'kpis'  => [
                    ['metric' => 'Month-end close cycle',        'target' => '< 5 business days', 'scalable' => false],
                    ['metric' => 'Audit findings',               'target' => '0 material',        'scalable' => false],
                    ['metric' => 'Budget variance',              'target' => '< 5%',              'scalable' => false],
                ],
            ],
            [
                'match' => ['operation','operations','ops','logistic','logistics','supply','warehouse'],
                'kra'   => 'Operational Efficiency',
                'description' => 'Improve throughput, reduce waste and hit SLA consistently.',
                'kpis'  => [
                    ['metric' => 'On-time delivery rate',       'target' => '> 95%',            'scalable' => false],
                    ['metric' => 'Operating cost per unit',     'target' => '-8% YoY',          'scalable' => true],
                    ['metric' => 'SLA adherence',               'target' => '> 98%',            'scalable' => false],
                ],
            ],
            [
                'match' => ['production','manufacturing','plant','factory'],
                'kra'   => 'Production Output & Throughput',
                'description' => 'Meet daily/monthly production targets with optimal line utilisation.',
                'kpis'  => [
                    ['metric' => 'Monthly production volume',   'target' => '> 95% of plan',   'scalable' => true],
                    ['metric' => 'Overall Equipment Effectiveness (OEE)', 'target' => '> 80%', 'scalable' => false],
                    ['metric' => 'Line utilisation',            'target' => '> 85%',           'scalable' => false],
                    ['metric' => 'Cycle time per unit',         'target' => 'meet standard',   'scalable' => false],
                ],
            ],
            [
                'match' => ['production','manufacturing','plant','factory','quality'],
                'kra'   => 'Product Quality & First-Pass Yield',
                'description' => 'Drive process quality so units pass inspection on the first attempt.',
                'kpis'  => [
                    ['metric' => 'First-pass yield (FPY)',      'target' => '> 95%',           'scalable' => false],
                    ['metric' => 'Reject / scrap rate',         'target' => '< 2%',            'scalable' => false],
                    ['metric' => 'Customer rejection PPM',      'target' => '< 500 PPM',       'scalable' => false],
                    ['metric' => 'Internal NCRs raised',        'target' => '< 5 / month',     'scalable' => false],
                ],
            ],
            [
                'match' => ['production','manufacturing','plant','factory','safety','ehs'],
                'kra'   => 'Plant Safety & Compliance (EHS)',
                'description' => 'Maintain a safe shop floor and meet regulatory compliance.',
                'kpis'  => [
                    ['metric' => 'Lost Time Injury Frequency Rate (LTIFR)', 'target' => '< 1', 'scalable' => false],
                    ['metric' => 'Near-miss reporting',         'target' => '> 10 / month',    'scalable' => false],
                    ['metric' => 'Safety training coverage',    'target' => '100% workforce',  'scalable' => false],
                    ['metric' => 'Statutory audit findings',    'target' => '0 critical',      'scalable' => false],
                ],
            ],
            [
                'match' => ['production','manufacturing','plant','factory','maintenance'],
                'kra'   => 'Equipment Reliability & Maintenance',
                'description' => 'Minimise unplanned downtime via preventive maintenance.',
                'kpis'  => [
                    ['metric' => 'Unplanned downtime',          'target' => '< 4% of run time','scalable' => false],
                    ['metric' => 'Preventive maintenance adherence', 'target' => '> 95%',     'scalable' => false],
                    ['metric' => 'Mean Time Between Failures (MTBF)', 'target' => '> 200 hrs','scalable' => true],
                    ['metric' => 'Spares availability',         'target' => '> 95%',           'scalable' => false],
                ],
            ],
            [
                'match' => ['production','manufacturing','plant','factory','cost','lean'],
                'kra'   => 'Cost Control & Lean Manufacturing',
                'description' => 'Reduce conversion cost via lean initiatives and waste elimination.',
                'kpis'  => [
                    ['metric' => 'Conversion cost per unit',    'target' => '-5% YoY',         'scalable' => true],
                    ['metric' => 'Material yield',              'target' => '> 96%',           'scalable' => false],
                    ['metric' => 'Energy consumption per unit', 'target' => '-3% YoY',         'scalable' => true],
                    ['metric' => 'Kaizen / suggestions implemented', 'target' => '> 12 / year','scalable' => true],
                ],
            ],
            [
                'match' => ['production','manufacturing','supply','procurement','sourcing'],
                'kra'   => 'Supply Chain & Inventory Management',
                'description' => 'Keep raw-material flow steady and inventory at target levels.',
                'kpis'  => [
                    ['metric' => 'Inventory turnover',          'target' => '> 8 / year',      'scalable' => true],
                    ['metric' => 'Raw material stock-out events','target' => '0 / month',      'scalable' => false],
                    ['metric' => 'Vendor on-time delivery',     'target' => '> 95%',           'scalable' => false],
                    ['metric' => 'Working capital days',        'target' => '< 60 days',       'scalable' => false],
                ],
            ],
            [
                'match' => ['support','customer','success','cs','service'],
                'kra'   => 'Customer Success & Satisfaction',
                'description' => 'Own CSAT, retention and escalation resolution.',
                'kpis'  => [
                    ['metric' => 'CSAT score',                 'target' => '> 4.5 / 5',         'scalable' => false],
                    ['metric' => 'First response time',        'target' => '< 2 hours',         'scalable' => false],
                    ['metric' => 'Customer retention',         'target' => '> 90%',             'scalable' => false],
                    ['metric' => 'Tickets resolved',           'target' => '200 / quarter',     'scalable' => true],
                ],
            ],
            [
                'match' => ['product','pm','roadmap'],
                'kra'   => 'Product Strategy & Roadmap Execution',
                'description' => 'Deliver roadmap milestones and drive adoption of new features.',
                'kpis'  => [
                    ['metric' => 'Roadmap milestones delivered', 'target' => '> 90%',           'scalable' => false],
                    ['metric' => 'Feature adoption',             'target' => '> 40%',           'scalable' => false],
                    ['metric' => 'NPS',                          'target' => '> 40',            'scalable' => false],
                ],
            ],
            [
                'match' => ['design','ux','ui','graphic','creative'],
                'kra'   => 'Design Quality & Throughput',
                'description' => 'Deliver high-quality design assets aligned to brand & UX.',
                'kpis'  => [
                    ['metric' => 'Design tasks shipped',        'target' => '20 / quarter',    'scalable' => true],
                    ['metric' => 'Stakeholder rating',          'target' => '> 4.3 / 5',       'scalable' => false],
                    ['metric' => 'Rework rate',                 'target' => '< 10%',           'scalable' => false],
                ],
            ],
            [
                'match' => ['quality','qa','test','assurance'],
                'kra'   => 'Quality Assurance',
                'description' => 'Catch defects early, automate regression, improve release quality.',
                'kpis'  => [
                    ['metric' => 'Defect escape rate',          'target' => '< 5%',             'scalable' => false],
                    ['metric' => 'Automation coverage',         'target' => '> 60%',            'scalable' => false],
                    ['metric' => 'Critical bugs post-release',  'target' => '0',                'scalable' => false],
                ],
            ],
            // Universal fallbacks that match everything loosely
            [
                'match' => [''],
                'kra'   => 'Collaboration & Stakeholder Management',
                'description' => 'Work effectively across teams and manage stakeholder expectations.',
                'kpis'  => [
                    ['metric' => 'Peer feedback rating',        'target' => '> 4.0 / 5',        'scalable' => false],
                    ['metric' => 'Cross-team initiatives',      'target' => '2 per quarter',    'scalable' => true],
                ],
            ],
            [
                'match' => [''],
                'kra'   => 'Learning & Development',
                'description' => 'Upskill continuously and share knowledge with the team.',
                'kpis'  => [
                    ['metric' => 'Certifications / courses',    'target' => '1 per quarter',    'scalable' => false],
                    ['metric' => 'Internal knowledge-share sessions', 'target' => '1 per quarter', 'scalable' => false],
                ],
            ],
        ];
    }

    private function countries(): array
    {
        return [
            'India','United States','United Kingdom','Canada','Australia','Germany','France','Singapore',
            'United Arab Emirates','Saudi Arabia','Japan','China','Brazil','South Africa','Netherlands',
            'Sweden','Norway','Spain','Italy','Mexico','Argentina','New Zealand','Ireland','Switzerland',
            'Belgium','Denmark','Finland','Poland','Portugal','Indonesia','Malaysia','Thailand','Vietnam',
            'Philippines','South Korea','Turkey','Israel','Egypt','Kenya','Nigeria','Others',
        ];
    }
}
