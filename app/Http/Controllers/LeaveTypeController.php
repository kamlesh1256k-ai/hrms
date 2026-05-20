<?php

namespace App\Http\Controllers;

use App\Models\Leave;
use App\Models\LeaveType;
use App\Traits\AddressMasterTrait;
use Illuminate\Http\Request;

class LeaveTypeController extends Controller
{
    use AddressMasterTrait;

    public function index()
    {
        if(\Auth::user()->can('Manage Leave Type'))
        {
            $leavetypes = LeaveType::where('created_by', '=', \Auth::user()->creatorId())->get();
            $masterData = $this->getAddressMasterData();
            return view('leavetype.index', compact('leavetypes', 'masterData'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function create()
    {

        if(\Auth::user()->can('Create Leave Type'))
        {
            return view('leavetype.create');
        }
        else
        {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function store(Request $request)
    {

        if(\Auth::user()->can('Create Leave Type'))
        {

            $validator = \Validator::make(
                $request->all(), [
                'title' => 'required',
                'days' => 'required|gt:0',
                'monthly_credit' => 'nullable|numeric|min:0',
                'annual_credit' => 'nullable|numeric|min:0',
                'approval_requirement' => 'nullable|in:subordinate,na',
            ]
            );

            if($validator->fails())
            {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }

            $leavetype             = new LeaveType();
            $leavetype->title      = $request->title;
            $daysPerYear = (float) $request->days;
            $annualCredit = $request->filled('annual_credit')
                ? (float) $request->annual_credit
                : $daysPerYear;
            $monthlyCredit = $request->filled('monthly_credit')
                ? (float) $request->monthly_credit
                : round($annualCredit / 12, 2);

            $leavetype->days       = $daysPerYear;
            $leavetype->monthly_credit = round($monthlyCredit, 2);
            $leavetype->annual_credit = round($annualCredit, 2);
            $leavetype->approval_requirement = $request->approval_requirement ?? 'na';
            $leavetype->country = $request->input('country');
            $leavetype->state = $request->input('state');
            $leavetype->city = $request->input('city');
            $leavetype->created_by = \Auth::user()->creatorId();
            $leavetype->save();

            return redirect()->route('leavetype.index')->with('success', __('LeaveType  successfully created.'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function show(LeaveType $leavetype)
    {
        return redirect()->route('leavetype.index');
    }

    public function edit(LeaveType $leavetype)
    {
        if(\Auth::user()->can('Edit Leave Type'))
        {
            if($leavetype->created_by == \Auth::user()->creatorId())
            {

                return view('leavetype.edit', compact('leavetype'));
            }
            else
            {
                return response()->json(['error' => __('Permission denied.')], 401);
            }
        }
        else
        {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function update(Request $request, LeaveType $leavetype)
    {
        if(\Auth::user()->can('Edit Leave Type'))
        {
            if($leavetype->created_by == \Auth::user()->creatorId())
            {
                $validator = \Validator::make(
                    $request->all(), [
                    'title' => 'required',
                    'days' => 'required|gt:0',
                    'monthly_credit' => 'nullable|numeric|min:0',
                    'annual_credit' => 'nullable|numeric|min:0',
                    'approval_requirement' => 'nullable|in:subordinate,na',
                ]
                );

                if($validator->fails())
                {
                    $messages = $validator->getMessageBag();

                    return redirect()->back()->with('error', $messages->first());
                }

                $leavetype->title = $request->title;
                $daysPerYear = (float) $request->days;
                $annualCredit = $request->filled('annual_credit')
                    ? (float) $request->annual_credit
                    : $daysPerYear;
                $monthlyCredit = $request->filled('monthly_credit')
                    ? (float) $request->monthly_credit
                    : round($annualCredit / 12, 2);

                $leavetype->days  = $daysPerYear;
                $leavetype->monthly_credit = round($monthlyCredit, 2);
                $leavetype->annual_credit = round($annualCredit, 2);
                $leavetype->approval_requirement = $request->approval_requirement ?? 'na';
                $leavetype->country = $request->input('country');
                $leavetype->state = $request->input('state');
                $leavetype->city = $request->input('city');
                $leavetype->save();

                return redirect()->route('leavetype.index')->with('success', __('LeaveType successfully updated.'));
            }
            else
            {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function destroy(LeaveType $leavetype)
    {
        if(\Auth::user()->can('Delete Leave Type'))
        {
            if($leavetype->created_by == \Auth::user()->creatorId())
            {
                $leave     = Leave::where('leave_type_id',$leavetype->id)->get();
                if(count($leave) == 0)
                {
                    $leavetype->delete();
                }
                else
                {
                    return redirect()->route('leavetype.index')->with('error', __('This leavetype has leave. Please remove the leave from this leavetype.'));
                }

                return redirect()->route('leavetype.index')->with('success', __('LeaveType successfully deleted.'));
            }
            else
            {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
}
