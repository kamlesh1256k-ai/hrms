<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Branch;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PeopleHubController extends Controller
{
    private function creatorId() { return Auth::user()->creatorId(); }

    // ── Crew (Org Chart) ──────────────────────────────────────
    public function crew(Request $request)
    {
        $cid = $this->creatorId();
        $deptId = $request->get('department_id');

        $query = Employee::with(['department', 'designation', 'reportingManager'])
            ->where('created_by', $cid)->where('is_active', 1);
        if ($deptId) $query->where('department_id', $deptId);

        $employees = $query->orderBy('name')->get();
        $departments = Department::where('created_by', $cid)->orderBy('name')->get();

        // Build org tree: top-level = no reporting_manager or manager not in list
        $empById = $employees->keyBy('id');
        $tree = [];
        foreach ($employees as $e) {
            $mgr = $e->reporting_manager_id;
            if (!$mgr || !$empById->has($mgr)) {
                $tree[] = $e;
            }
        }

        return view('people_hub.crew', compact('employees', 'departments', 'tree', 'empById', 'deptId'));
    }

    // ── My Squad (Team Members) ───────────────────────────────
    public function squad()
    {
        $cid = $this->creatorId();
        $user = Auth::user();
        $emp = Employee::where('user_id', $user->id)->first();

        $team = collect();
        $manager = null;
        if ($emp) {
            // My direct reports
            $team = Employee::with(['department', 'designation'])
                ->where('created_by', $cid)
                ->where('reporting_manager_id', $emp->id)
                ->where('is_active', 1)
                ->orderBy('name')->get();

            // My manager
            if ($emp->reporting_manager_id) {
                $manager = Employee::with(['department', 'designation'])->find($emp->reporting_manager_id);
            }
        }

        return view('people_hub.squad', compact('emp', 'team', 'manager'));
    }

    // ── Mentor Buddy / Growth Partner ─────────────────────────
    public function mentor(Request $request)
    {
        $cid = $this->creatorId();
        $user = Auth::user();
        $emp = Employee::where('user_id', $user->id)->first();
        $isAdmin = in_array($user->type, ['company', 'super admin', 'hr'], true);

        $myMentor = null;
        $myMentees = collect();
        if ($emp) {
            if ($emp->mentor_buddy_id) {
                $myMentor = Employee::with(['department', 'designation'])->find($emp->mentor_buddy_id);
            }
            $myMentees = Employee::with(['department', 'designation'])
                ->where('mentor_buddy_id', $emp->id)
                ->where('is_active', 1)->orderBy('name')->get();
        }

        // Admin can assign mentors
        $allEmployees = collect();
        if ($isAdmin) {
            $allEmployees = Employee::where('created_by', $cid)->where('is_active', 1)->orderBy('name')->get();
        }

        return view('people_hub.mentor', compact('emp', 'myMentor', 'myMentees', 'isAdmin', 'allEmployees'));
    }

    public function mentorAssign(Request $request)
    {
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'mentor_buddy_id' => 'required|exists:employees,id',
        ]);

        Employee::where('id', $data['employee_id'])->update(['mentor_buddy_id' => $data['mentor_buddy_id']]);
        return back()->with('success', __('Mentor/Buddy assigned successfully.'));
    }

    // ── Search Crew ───────────────────────────────────────────
    public function search(Request $request)
    {
        $cid = $this->creatorId();
        $q = $request->get('q', '');
        $deptId = $request->get('department_id');

        $query = Employee::with(['department', 'designation', 'branch'])
            ->where('created_by', $cid)->where('is_active', 1);

        if ($q) {
            $query->where(function($qr) use ($q) {
                $qr->where('name', 'like', "%{$q}%")
                   ->orWhere('email', 'like', "%{$q}%")
                   ->orWhere('phone', 'like', "%{$q}%")
                   ->orWhere('employee_id', 'like', "%{$q}%");
            });
        }
        if ($deptId) $query->where('department_id', $deptId);

        $results = $query->orderBy('name')->limit(50)->get();
        $departments = Department::where('created_by', $cid)->orderBy('name')->get();

        return view('people_hub.search', compact('results', 'departments', 'q', 'deptId'));
    }

    // ── Employee Detail Card (AJAX popup) ─────────────────────
    public function detail($id)
    {
        $emp = Employee::with(['department', 'designation', 'branch', 'reportingManager'])
            ->findOrFail($id);
        $mentor = $emp->mentor_buddy_id ? Employee::find($emp->mentor_buddy_id) : null;

        return view('people_hub._detail_card', compact('emp', 'mentor'));
    }
}
