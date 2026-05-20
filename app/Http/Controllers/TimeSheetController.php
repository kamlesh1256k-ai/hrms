<?php

namespace App\Http\Controllers;

use App\Exports\TimesheetExport;
use App\Imports\TimesheetImport;
use App\Models\Employee;
use App\Models\TimeSheet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class TimeSheetController extends Controller
{
    public function index(Request $request)
    {
        if (\Auth::user()->can('Manage TimeSheet')) {
            $employeesList = [];
            $isManager = false;

            if (\Auth::user()->type == 'employee') {
                $emp = Employee::where('user_id', \Auth::user()->id)->first();

                // Check if this employee is a manager (has reportees)
                $teamUserIds = collect([\Auth::user()->id]);
                if ($emp && \Schema::hasColumn('employees', 'reporting_manager_id')) {
                    $reportees = Employee::where('created_by', \Auth::user()->creatorId())
                        ->where('reporting_manager_id', $emp->id)
                        ->get();
                    if ($reportees->isNotEmpty()) {
                        $isManager = true;
                        $teamUserIds = $teamUserIds->merge($reportees->pluck('user_id'))->unique();
                    }
                }

                if ($isManager) {
                    // Manager sees own + team timesheets
                    $employeesList = Employee::where('created_by', \Auth::user()->creatorId())
                        ->whereIn('user_id', $teamUserIds)
                        ->get()->pluck('name', 'user_id');
                    $employeesList->prepend('All Team', '');

                    $timesheets = TimeSheet::where('created_by', \Auth::user()->creatorId())
                        ->whereIn('employee_id', $teamUserIds);
                } else {
                    // Regular employee sees only own
                    $employeesList = collect();
                    $timesheets = TimeSheet::where('created_by', \Auth::user()->creatorId())
                        ->where('employee_id', \Auth::user()->id);
                }

                if (!empty($request->start_date) && !empty($request->end_date)) {
                    $timesheets->where('date', '>=', $request->start_date);
                    $timesheets->where('date', '<=', $request->end_date);
                }
                if ($isManager && !empty($request->employee)) {
                    $timesheets->where('employee_id', $request->employee);
                }
                $timeSheets = $timesheets->get();
            } else {
                $employeesList = Employee::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'user_id');
                $employeesList->prepend('All', '');

                $timesheets = TimeSheet::where('created_by', \Auth::user()->creatorId());

                if (!empty($request->start_date) && !empty($request->end_date)) {
                    $timesheets->where('date', '>=', $request->start_date);
                    $timesheets->where('date', '<=', $request->end_date);
                }

                if (!empty($request->employee)) {
                    $timesheets->where('employee_id', $request->employee);
                }
                $timeSheets = $timesheets->get();
            }

            return view('timeSheet.index', compact('timeSheets', 'employeesList', 'isManager'));
        } else {
            return redirect()->back()->with('error', 'Permission denied.');
        }
    }

    public function create()
    {

        if (\Auth::user()->can('Create TimeSheet')) {
            $employees = Employee::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'user_id');
            $employees->prepend('Select Employee', '');

            return view('timeSheet.create', compact('employees'));
        } else {
            return redirect()->back()->with('error', 'Permission denied.');
        }
    }

    public function store(Request $request)
    {
        if (\Auth::user()->can('Create TimeSheet')) {
            $timeSheet = new Timesheet();
            if (\Auth::user()->type == 'employee') {
                $timeSheet->employee_id = \Auth::user()->id;
            } else {
                $timeSheet->employee_id = $request->employee_id;
            }

            $timeSheet->client_name = $request->client_name;
            $timeSheet->task        = $request->task;
            $timeSheet->category    = $request->category;
            $timeSheet->date        = $request->date;
            $timeSheet->start_time  = $request->start_time;
            $timeSheet->end_time    = $request->end_time;
            $timeSheet->hours       = $request->hours;
            $timeSheet->billable    = $request->billable ?? 'billable';
            $timeSheet->status      = $request->status ?? 'pending';
            $timeSheet->remark      = $request->remark;
            $timeSheet->created_by  = \Auth::user()->creatorId();
            $timeSheet->save();

            return redirect()->route('timesheet.index')->with('success', __('Timesheet successfully created.'));
        } else {
            return redirect()->back()->with('error', 'Permission denied.');
        }
    }

    public function show(TimeSheet $timeSheet)
    {
        //
    }

    public function edit(TimeSheet $timeSheet, $id)
    {

        if (\Auth::user()->can('Edit TimeSheet')) {
            $employees = Employee::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'user_id');
            $timeSheet = Timesheet::find($id);

            return view('timeSheet.edit', compact('timeSheet', 'employees'));
        } else {
            return redirect()->back()->with('error', 'Permission denied.');
        }
    }

    public function update(Request $request, $id)
    {
        if (\Auth::user()->can('Edit TimeSheet')) {

            $timeSheet = Timesheet::find($id);
            if (\Auth::user()->type == 'employee') {
                $timeSheet->employee_id = \Auth::user()->id;
            } else {
                $timeSheet->employee_id = $request->employee_id;
            }

            $timeSheet->client_name = $request->client_name;
            $timeSheet->task        = $request->task;
            $timeSheet->category    = $request->category;
            $timeSheet->date        = $request->date;
            $timeSheet->start_time  = $request->start_time;
            $timeSheet->end_time    = $request->end_time;
            $timeSheet->hours       = $request->hours;
            $timeSheet->billable    = $request->billable ?? 'billable';
            $timeSheet->status      = $request->status ?? 'pending';
            $timeSheet->remark      = $request->remark;
            $timeSheet->save();

            return redirect()->route('timesheet.index')->with('success', __('TimeSheet successfully updated.'));
        } else {
            return redirect()->back()->with('error', 'Permission denied.');
        }
    }

    public function destroy($id)
    {
        if (\Auth::user()->can('Delete TimeSheet')) {
            $timeSheet = Timesheet::find($id);
            $timeSheet->delete();

            return redirect()->route('timesheet.index')->with('success', __('TimeSheet successfully deleted.'));
        } else {
            return redirect()->back()->with('error', 'Permission denied.');
        }
    }

    public function exportExcel(Request $request)
    {
        $user = \Auth::user();
        $query = TimeSheet::with('employee')->where('created_by', $user->creatorId());

        // Role-based filtering
        if ($user->type == 'employee') {
            $emp = Employee::where('user_id', $user->id)->first();
            $teamUserIds = collect([$user->id]);
            if ($emp && \Schema::hasColumn('employees', 'reporting_manager_id')) {
                $reportees = Employee::where('created_by', $user->creatorId())
                    ->where('reporting_manager_id', $emp->id)->pluck('user_id');
                $teamUserIds = $teamUserIds->merge($reportees)->unique();
            }
            $query->whereIn('employee_id', $teamUserIds);
        }

        if (!empty($request->start_date) && !empty($request->end_date)) {
            $query->where('date', '>=', $request->start_date)->where('date', '<=', $request->end_date);
        }
        if (!empty($request->employee)) {
            $query->where('employee_id', $request->employee);
        }

        $timeSheets = $query->orderByDesc('date')->get();
        $filename = 'timesheet-' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($timeSheets) {
            $f = fopen('php://output', 'w');
            fprintf($f, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($f, ['Date', 'Employee', 'Client Name', 'Task / Work Done', 'Category', 'Start Time', 'End Time', 'Total Hours', 'Billable', 'Status', 'Remark']);

            foreach ($timeSheets as $ts) {
                fputcsv($f, [
                    $ts->date ? date('d-m-Y', strtotime($ts->date)) : '',
                    $ts->employee->name ?? '—',
                    $ts->client_name ?? '',
                    $ts->task ?? '',
                    $ts->category ?? '',
                    $ts->start_time ? date('h:i A', strtotime($ts->start_time)) : '',
                    $ts->end_time ? date('h:i A', strtotime($ts->end_time)) : '',
                    $ts->hours,
                    ucfirst($ts->billable ?? 'billable'),
                    ucfirst(str_replace('_', ' ', $ts->status ?? 'pending')),
                    $ts->remark ?? '',
                ]);
            }
            fclose($f);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function export(Request $request)
    {
        $name = 'Timesheet_' . date('Y-m-d i:h:s');
        $data = \Excel::download(new TimesheetExport(), $name . '.xlsx');

        return $data;
    }

    public function exportTimeshhetReport(Request $request)
    {
        $name = 'Timesheet_' . date('Y-m-d i:h:s');
        $data = \Excel::download(new TimesheetExport(), $name . '.xlsx');

        return $data;
    }

    public function importFile(Request $request)
    {
        return view('timeSheet.import');
    }
    // public function import(Request $request)
    // {
    //     $rules = [
    //         'file' => 'required|mimes:csv,txt,xlsx',
    //     ];
    //     $validator = \Validator::make($request->all(), $rules);

    //     if ($validator->fails()) {
    //         $messages = $validator->getMessageBag();

    //         return redirect()->back()->with('error', $messages->first());
    //     }

    //     try {
    //         $timesheet = (new TimesheetImport())->toArray(request()->file('file'))[0];

    //         $totalTimesheet = count($timesheet) - 1;
    //         $errorArray    = [];
    //         for ($i = 1; $i <= $totalTimesheet; $i++) {
    //             $timesheets = $timesheet[$i];
    //             $timesheetData = TimeSheet::where('employee_id', $timesheets[1])->where('date', $timesheets[0])->first();
    //             if (!empty($timesheetData)) {
    //                 $errorArray[] = $timesheetData;
    //             } else {
    //                 $time_sheet = new TimeSheet();
    //                 $time_sheet->employee_id = $timesheets[0];
    //                 $time_sheet->date = $timesheets[1];
    //                 $time_sheet->hours = $timesheets[2];
    //                 $time_sheet->remark = $timesheets[3];
    //                 $time_sheet->created_by = Auth::user()->id;
    //                 $time_sheet->save();
    //             }
    //         }
    //     } catch (\Throwable $th) {
    //         return redirect()->back()->with('error', __('Something went wrong please try again.'));
    //     }

    //     if (empty($errorArray)) {
    //         $data['status'] = 'success';
    //         $data['msg']    = __('Record successfully imported');
    //     } else {

    //         $data['status'] = 'error';
    //         $data['msg']    = count($errorArray) . ' ' . __('Record imported fail out of' . ' ' . $totalTimesheet . ' ' . 'record');


    //         foreach ($errorArray as $errorData) {
    //             $errorRecord[] = implode(',', $errorData->toArray());
    //         }

    //         \Session::put('errorArray', $errorRecord);
    //     }

    //     return redirect()->back()->with($data['status'], $data['msg']);
    // }

    public function timesheetImportdata(Request $request)
    {
        session_start();
        $html = '<h3 class="text-danger text-center">Below data is not inserted</h3></br>';
        $flag = 0;
        $html .= '<table class="table table-bordered"><tr>';
        try {
            $request = $request->data;
            $file_data = $_SESSION['file_data'];

            unset($_SESSION['file_data']);
        } catch (\Throwable $th) {
            $html = '<h3 class="text-danger text-center">Something went wrong, Please try again</h3></br>';
            return response()->json([
                'html' => true,
                'response' => $html,
            ]);
        }
        $user = Auth::user();

        foreach ($file_data as $key => $row) {
            $employeeData = Employee::Where('email', 'like', $row[$request['employee_email']])->where('created_by', Auth::user()->creatorId())->first();

            if (!empty($employeeData)) {
                try {
                    $employeeId = $employeeData->user_id;

                    TimeSheet::create([
                        'employee_id' => $employeeId,
                        'date' => $row[$request['date']],
                        'hours' => $row[$request['hours']],
                        'remark' => $row[$request['remark']],
                        'created_by' => Auth::user()->id,
                    ]);
                } catch (\Throwable $e) {
                    $flag = 1;
                    $html .= '<tr>';

                    $html .= '<td>' . (isset($row[$request['employee_email']]) ? $row[$request['employee_email']] : '-') . '</td>';
                    $html .= '<td>' . (isset($row[$request['date']]) ? $row[$request['date']] : '-') . '</td>';
                    $html .= '<td>' . (isset($row[$request['hours']]) ? $row[$request['hours']] : '-') . '</td>';
                    $html .= '<td>' . (isset($row[$request['remark']]) ? $row[$request['remark']] : '-') . '</td>';

                    $html .= '</tr>';
                }
            } else {
                $flag = 1;
                $html .= '<tr>';

                $html .= '<td>' . (isset($row[$request['employee_email']]) ? $row[$request['employee_email']] : '-') . '</td>';
                $html .= '<td>' . (isset($row[$request['date']]) ? $row[$request['date']] : '-') . '</td>';
                $html .= '<td>' . (isset($row[$request['hours']]) ? $row[$request['hours']] : '-') . '</td>';
                $html .= '<td>' . (isset($row[$request['remark']]) ? $row[$request['remark']] : '-') . '</td>';

                $html .= '</tr>';
            }
        }

        $html .= '
                        </table>
                        <br />
                        ';

        if ($flag == 1) {

            return response()->json([
                'html' => true,
                'response' => $html,
            ]);
        } else {
            return response()->json([
                'html' => false,
                'response' => 'Data Imported Successfully',
            ]);
        }
    }
}
