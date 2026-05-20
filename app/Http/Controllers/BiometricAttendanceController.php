<?php

namespace App\Http\Controllers;

use App\Models\AttendanceEmployee;
use App\Models\Employee;
use App\Models\Utility;
use App\Services\FacialRecognitionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

class BiometricAttendanceController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::user()->can('Manage Biometric Attendance')) {

            $company_setting = Utility::settings();
            $api_urls = !empty($company_setting['zkteco_api_url']) ? $company_setting['zkteco_api_url'] : '';
            $token = !empty($company_setting['auth_token']) ? $company_setting['auth_token'] : '';

            if (!empty($request->start_date) && !empty($request->end_date)) {
                $start_date = date('Y-m-d:H:i:s', strtotime($request->start_date));
                $end_date = date('Y-m-d:H:i:s', strtotime($request->end_date) + 86400 - 1);
            } else {
                $start_date = date('Y-m-d', strtotime('-7 days'));
                $end_date = date('Y-m-d');
            }
            $api_url = rtrim($api_urls, '/');

            // Dynamic Api URL Call
            $url = $api_url . '/iclock/api/transactions/?' . http_build_query([
                'start_time' => $start_date,
                'end_time' => $end_date,
                'page_size' => 100,
            ]);
            $curl = curl_init();
            if (!empty($token)) {
                try {
                    curl_setopt_array($curl, array(
                        CURLOPT_URL => $url,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 0,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'GET',
                        CURLOPT_HTTPHEADER => array(
                            'Content-Type: application/json',
                            'Authorization: Token ' . $token
                        ),
                    ));

                    $response = curl_exec($curl);

                    curl_close($curl);

                    $json_attendance = json_decode($response, true);
                    $attendances = $json_attendance['data'];
                } catch (\Throwable $th) {
                    return redirect()->back()->with('error', __('Something went wrong please try again.'));
                }
            } else {
                $attendances = [];
            }

            return view('biometricattendance.index', compact('attendances', 'token'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function update(Request $request)
    {
        if (Auth::user()->can('Manage Biometric Attendance')) {
            $company_settings = Utility::settings();
            if (empty($company_settings['auth_token'])) {
                return redirect()->back()->with('error', __('Please first create auth token'));
            }

            $employee = Employee::where('created_by', Auth::user()->creatorId())->where('biometric_emp_id', $request->biometric_emp_id)->first();

            if (empty($employee)) {
                return redirect()->back()->with('error', __('Please first create employee or edit employee code.'));
            }

            $biometric_code = $employee->biometric_emp_id;

            $startTime  = Utility::getValByName('company_start_time');
            $endTime  = Utility::getValByName('company_end_time');

            $date = date("Y-m-d", strtotime($request->punch_time));
            $time = date("H:i", strtotime($request->punch_time));

            $todayAttendance = AttendanceEmployee::where('attendance_employees.created_by', Auth::user()->creatorId())
                ->where('employees.biometric_emp_id', $biometric_code)
                ->where('clock_in', '=', date("H:i:s", strtotime($time)))
                ->where('date', '=', $date)
                ->leftJoin('employees', 'attendance_employees.employee_id', '=', 'employees.id')
                ->select('attendance_employees.*', 'employees.biometric_emp_id as biometric_id')
                ->first();

            if (!empty($todayAttendance)) {
                return redirect()->back()->with('error', __('This employee is already sync.'));
            }

            $attendance = AttendanceEmployee::where('attendance_employees.created_by', Auth::user()->creatorId())
                ->where('employees.biometric_emp_id', $biometric_code)
                ->where('clock_out', '=', '00:00:00')
                ->where('date', '=', $date)
                ->orderBy('id', 'desc')
                ->leftJoin('employees', 'attendance_employees.employee_id', '=', 'employees.id')
                ->select('attendance_employees.*', 'employees.biometric_emp_id as biometric_id')
                ->first();

            if ($attendance != null) {
                if ($attendance->date == $date && date("H:i", strtotime($attendance->clock_in)) == $time) {
                    return redirect()->back()->with('error', __('This employee is already sync.'));
                }
                $endTimestamp = strtotime($date . $endTime);
                $currentTimestamp = strtotime($date . $time);
                if ($currentTimestamp > $endTimestamp) {
                    $endTimestamp = strtotime($date . ' +1 day ' . $endTime);
                }
                $totalEarlyLeavingSeconds = $endTimestamp - $currentTimestamp;
                if ($totalEarlyLeavingSeconds < 0) {
                    $earlyLeaving = '0:00:00';
                } else {
                    $hours                    = floor($totalEarlyLeavingSeconds / 3600);
                    $mins                     = floor($totalEarlyLeavingSeconds / 60 % 60);
                    $secs                     = floor($totalEarlyLeavingSeconds % 60);
                    $earlyLeaving             = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);
                }

                $endTimeTimestamp = strtotime($date . $endTime);
                $timeTimestamp = strtotime($date . $time);
                if ($timeTimestamp > $endTimeTimestamp) {
                    //Overtime
                    $totalOvertimeSeconds = $timeTimestamp - $endTimeTimestamp;
                    $hours                = floor($totalOvertimeSeconds / 3600);
                    $mins                 = floor(($totalOvertimeSeconds % 3600) / 60);
                    $secs                 = floor($totalOvertimeSeconds % 60);
                    $overtime             = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);
                } else {
                    $overtime = '00:00:00';
                }

                $attendance                = AttendanceEmployee::find($attendance->id);
                $attendance->clock_out     = $time;
                $attendance->early_leaving = $earlyLeaving;
                $attendance->overtime      = $overtime;
                $attendance->save();
            }

            // Find the last clocked out entry for the employee
            $lastClockOutEntry = AttendanceEmployee::where('attendance_employees.created_by', Auth::user()->creatorId())
                ->where('employees.biometric_emp_id', $biometric_code)
                ->where('attendance_employees.employee_id', '=', $employee->id)
                ->where('clock_out', '!=', '00:00:00')
                ->where('date', '=', $date)
                ->orderBy('id', 'desc')
                ->leftJoin('employees', 'attendance_employees.employee_id', '=', 'employees.id')
                ->select('attendance_employees.*', 'employees.biometric_emp_id as biometric_id')
                ->first();


            if (!empty($company_settings['timezone'])) {
                date_default_timezone_set($company_settings['timezone']);
            }

            if ($lastClockOutEntry != null) {
                $lastClockOutTime = $lastClockOutEntry->clock_out;
                $actualClockInTime = $date . ' ' . $time;

                $totalLateSeconds = strtotime($actualClockInTime) - strtotime($date . ' ' . $lastClockOutTime);

                $totalLateSeconds = max($totalLateSeconds, 0);

                $hours = floor($totalLateSeconds / 3600);
                $mins  = floor($totalLateSeconds / 60 % 60);
                $secs  = floor($totalLateSeconds % 60);
                $late  = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);
            } else {
                $expectedStartTime = $date . ' ' . $startTime;
                $actualClockInTime = $date . ' ' . $time;

                $totalLateSeconds = strtotime($actualClockInTime) - strtotime($expectedStartTime);

                $totalLateSeconds = max($totalLateSeconds, 0);

                $hours = floor($totalLateSeconds / 3600);
                $mins  = floor($totalLateSeconds / 60 % 60);
                $secs  = floor($totalLateSeconds % 60);
                $late  = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);
            }

            $attendanceStatus = $this->resolveBiometricAttendanceStatus($totalLateSeconds);

            $checkDb = AttendanceEmployee::where('attendance_employees.created_by', Auth::user()->creatorId())
                ->where('employees.biometric_emp_id', $biometric_code)
                ->where('attendance_employees.employee_id', '=', $employee->id)
                ->where('attendance_employees.date', '=', $date)
                ->leftJoin('employees', 'attendance_employees.employee_id', '=', 'employees.id')
                ->select('attendance_employees.*', 'employees.biometric_emp_id as biometric_id')
                ->get()
                ->toArray();

            if (empty($checkDb)) {
                $employeeAttendance              = new AttendanceEmployee();
                $employeeAttendance->employee_id = $employee->id;
                $employeeAttendance->date          = $date;
                $employeeAttendance->status        = $attendanceStatus;
                $employeeAttendance->clock_in      = $time;
                $employeeAttendance->clock_out     = '00:00:00';
                $employeeAttendance->late          = $late;
                $employeeAttendance->early_leaving = '00:00:00';
                $employeeAttendance->overtime      = '00:00:00';
                $employeeAttendance->total_rest    = '00:00:00';
                $employeeAttendance->created_by  = Auth::user()->creatorId();
                $employeeAttendance->save();
                return redirect()->back()->with('success', __('Employee successfully Sync.'));
            }

            $attendancess = AttendanceEmployee::where('attendance_employees.created_by', Auth::user()->creatorId())
                ->where('employees.biometric_emp_id', $biometric_code)
                ->where('clock_in', '!=', '00:00:00')
                ->where('clock_out', '!=', $time)
                ->orderBy('id', 'desc')
                ->leftJoin('employees', 'attendance_employees.employee_id', '=', 'employees.id')
                ->select('attendance_employees.*', 'employees.biometric_emp_id as biometric_id')
                ->first();

            if (empty($attendance)) {
                $employeeAttendance              = new AttendanceEmployee();
                $employeeAttendance->employee_id = $employee->id;
                $employeeAttendance->date          = $date;
                $employeeAttendance->status        = $attendanceStatus;
                $employeeAttendance->clock_in      = $time;
                $employeeAttendance->clock_out     = '00:00:00';
                $employeeAttendance->late          = $late;
                $employeeAttendance->early_leaving = '00:00:00';
                $employeeAttendance->overtime      = '00:00:00';
                $employeeAttendance->total_rest    = '00:00:00';
                $employeeAttendance->created_by  = Auth::user()->creatorId();
                $employeeAttendance->save();
            }

            return redirect()->back()->with('success', __('Employee successfully Sync.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function AllSync(Request $request)
    {
        if (Auth::user()->can('Manage Biometric Attendance')) {
            $company_setting = Utility::settings();
            $api_urls = !empty($company_setting['zkteco_api_url']) ? $company_setting['zkteco_api_url'] : '';
            $token = !empty($company_setting['auth_token']) ? $company_setting['auth_token'] : '';

            if (!empty($request->start_date) && !empty($request->end_date)) {
                $start_date = date('Y-m-d:H:i:s', strtotime($request->start_date));
                $end_date = date('Y-m-d:H:i:s', strtotime($request->end_date) + 86400 - 1);
            } else {
                $start_date = date('Y-m-d', strtotime('-7 days'));
                $end_date = date('Y-m-d');
            }
            $api_url = rtrim($api_urls, '/');

            // Dynamic Api URL Call
            $url = $api_url . '/iclock/api/transactions/?' . http_build_query([
                'start_time' => $start_date,
                'end_time' => $end_date,
                'page_size' => 10000,
            ]);
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    'Authorization: Token ' . $token
                ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);

            $json_attendance = json_decode($response, true);
            $attendances = $json_attendance['data'];

            if (empty($company_setting['auth_token'])) {
                return redirect()->back()->with('error', __('Please first create auth token'));
            }

            $employeeAttendance = [];
            foreach ($attendances as $bio_attendance) {
                $employees = Employee::where('created_by', Auth::user()->creatorId())->where('biometric_emp_id', $bio_attendance['emp_code'])->get();

                if ($employees->isEmpty()) {
                    return Response::json([
                        'url' => route('biometric-attendance.allsync'),
                        'message' => 'Please first create employee or edit employee code.'
                    ]);
                }

                foreach ($employees as $employee) {
                    $biometric_code = $employee->biometric_emp_id;

                    $startTime  = Utility::getValByName('company_start_time');
                    $endTime  = Utility::getValByName('company_end_time');

                    $date = date("Y-m-d", strtotime($bio_attendance['punch_time']));
                    $time = date("H:i", strtotime($bio_attendance['punch_time']));

                    $todayAttendance = AttendanceEmployee::where('attendance_employees.created_by', Auth::user()->creatorId())
                        ->where('employees.biometric_emp_id', $biometric_code)
                        ->where('clock_in', '=', date("H:i:s", strtotime($time)))
                        ->where('date', '=', $date)
                        ->leftJoin('employees', 'attendance_employees.employee_id', '=', 'employees.id')
                        ->select('attendance_employees.*', 'employees.biometric_emp_id as biometric_id')
                        ->first();

                    if (!empty($todayAttendance)) {
                        return Response::json([
                            'url' => route('biometric-attendance.allsync'),
                            'data' => $todayAttendance,
                            'message' => 'This employee is already sync.' // Make sure this key is set properly
                        ]);
                    }

                    $lastClockOutEntry = AttendanceEmployee::where('attendance_employees.created_by', Auth::user()->creatorId())
                        ->where('employees.biometric_emp_id', $biometric_code)
                        ->where('attendance_employees.employee_id', '=', $employee->id)
                        ->where('clock_out', '!=', '00:00:00')
                        ->where('date', '=', date('Y-m-d'))
                        ->orderBy('id', 'desc')
                        ->leftJoin('employees', 'attendance_employees.employee_id', '=', 'employees.id')
                        ->select('attendance_employees.*', 'employees.biometric_emp_id as biometric_id')
                        ->first();

                    if (!empty($company_settings['defult_timezone'])) {
                        date_default_timezone_set($company_settings['defult_timezone']);
                    }

                    if ($lastClockOutEntry != null) {
                        $lastClockOutTime = $lastClockOutEntry->clock_out;
                        $actualClockInTime = $date . ' ' . $time;

                        $totalLateSeconds = strtotime($actualClockInTime) - strtotime($date . ' ' . $lastClockOutTime);

                        $totalLateSeconds = max($totalLateSeconds, 0);

                        $hours = floor($totalLateSeconds / 3600);
                        $mins  = floor($totalLateSeconds / 60 % 60);
                        $secs  = floor($totalLateSeconds % 60);
                        $late  = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);
                    } else {
                        $expectedStartTime = $date . ' ' . $startTime;
                        $actualClockInTime = $date . ' ' . $time;

                        $totalLateSeconds = strtotime($actualClockInTime) - strtotime($expectedStartTime);

                        $totalLateSeconds = max($totalLateSeconds, 0);

                        $hours = floor($totalLateSeconds / 3600);
                        $mins  = floor($totalLateSeconds / 60 % 60);
                        $secs  = floor($totalLateSeconds % 60);
                        $late  = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);
                    }

                    $attendanceStatus = $this->resolveBiometricAttendanceStatus($totalLateSeconds);

                    $checkDb = AttendanceEmployee::where('attendance_employees.created_by', Auth::user()->creatorId())
                        ->where('employees.biometric_emp_id', $biometric_code)
                        ->where('attendance_employees.employee_id', '=', $employee->id)
                        ->where('attendance_employees.date', '=', $date)
                        ->leftJoin('employees', 'attendance_employees.employee_id', '=', 'employees.id')
                        ->select('attendance_employees.*', 'employees.biometric_emp_id as biometric_id')
                        ->get()
                        ->toArray();

                    $employeeAttendance              = new AttendanceEmployee();
                    $employeeAttendance->employee_id = $employee->id;
                    $employeeAttendance->date          = $date;
                    $employeeAttendance->status        = $attendanceStatus;
                    $employeeAttendance->clock_in      = $time;
                    $employeeAttendance->clock_out     = '00:00:00';
                    $employeeAttendance->late          = $late;
                    $employeeAttendance->early_leaving = '00:00:00';
                    $employeeAttendance->overtime      = '00:00:00';
                    $employeeAttendance->total_rest    = '00:00:00';
                    $employeeAttendance->created_by  = Auth::user()->creatorId();
                    $employeeAttendance->save();
                }
            }

            return Response::json([
                'url' => route('biometric-attendance.allsync'),
                'data' => $employeeAttendance,
                'message' => 'Employee successfully Sync.'
            ]);
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Verify employee identity using facial recognition before clock-in
     * 
     * @param Request $request
     * @return JSON response with verification result
     */
    public function verifyFacialRecognition(Request $request)
    {
        try {
            $request->validate([
                'employee_id' => 'required|exists:employees,id',
                'clock_in_photo' => 'required|image|mimes:jpeg,png,jpg,gif'
            ]);

            $employee = Employee::find($request->employee_id);
            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee not found'
                ], 404);
            }

            // Store the uploaded clock-in photo temporarily
            $clockInPhoto = $request->file('clock_in_photo');
            $clockInPhotoPath = $clockInPhoto->store('temp/clock-in', 'public');

            // Get employee's document photo (assuming it's stored as avatar or in documents)
            $employeePhotoPaths = $this->getEmployeePhotoPaths($employee);

            if (empty($employeePhotoPaths)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No employee photo found in system. Please update employee profile with document photo.'
                ], 422);
            }

            // Initialize facial recognition service
            $facialRecognitionService = new FacialRecognitionService();

            // Check against all employee photos
            $verificationResults = [];
            foreach ($employeePhotoPaths as $photoPath) {
                $result = $facialRecognitionService->verifyFace(
                    storage_path('app/public/' . $clockInPhotoPath),
                    $photoPath
                );

                $verificationResults[] = $result;

                // If we found a match with high confidence, return success
                if ($result['match'] && $result['confidence'] >= 80) {
                    // Clean up temp photo
                    \Storage::disk('public')->delete($clockInPhotoPath);

                    return response()->json([
                        'success' => true,
                        'message' => 'Facial recognition successful. Employee verified.',
                        'confidence' => $result['confidence'],
                        'employee_id' => $employee->id
                    ]);
                }
            }

            // No high confidence match found
            $bestMatch = collect($verificationResults)->sortByDesc('confidence')->first();

            // Clean up temp photo
            \Storage::disk('public')->delete($clockInPhotoPath);

            return response()->json([
                'success' => false,
                'message' => 'Facial recognition failed. Identity does not match employee records.',
                'confidence' => $bestMatch['confidence'] ?? 0,
                'reason' => $bestMatch['message'] ?? 'Unable to verify'
            ], 422);

        } catch (\Exception $e) {
            \Log::error('Facial Recognition Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Verification failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clock in/out with facial recognition verification
     * 
     * @param Request $request
     * @return JSON response
     */
    public function clockInWithFacialRecognition(Request $request)
    {
        try {
            $request->validate([
                'employee_id' => 'required|integer',
                'clock_in_photo' => 'required|image|mimes:jpeg,png,jpg,gif',
                'punch_time' => 'required|date_format:Y-m-d H:i:s',
                'type' => 'required|in:clock_in,clock_out'
            ]);

            $employeeIdParam = $request->employee_id;
            
            // Find employee by ID (primary key)
            $employee = Employee::find($employeeIdParam);
            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee not found'
                ], 404);
            }

            // Check authentication
            if (!Auth::check() || Auth::user()->creatorId() !== $employee->created_by) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            // Store uploaded photo temporarily
            $clockInPhoto = $request->file('clock_in_photo');
            $clockInPhotoPath = $clockInPhoto->store('temp/clock-in', 'public');

            // Initialize facial recognition service
            $facialRecognitionService = new FacialRecognitionService();

            // Verify employee face against document
            $verificationResult = $facialRecognitionService->verifyByEmployeeId(
                $employee->id,
                storage_path('app/public/' . $clockInPhotoPath)
            );

            if (!$verificationResult['match'] || $verificationResult['confidence'] < 80) {
                // Clean up temp photo
                \Storage::disk('public')->delete($clockInPhotoPath);

                return response()->json([
                    'success' => false,
                    'message' => 'Facial recognition failed. Identity does not match employee records.',
                    'confidence' => $verificationResult['confidence'],
                    'reason' => $verificationResult['message'],
                    'error' => $verificationResult['error'] ?? false
                ], 422);
            }

            // Facial verification passed! Now process clock-in/out
            $date = date("Y-m-d", strtotime($request->punch_time));
            $time = date("H:i", strtotime($request->punch_time));

            $company_settings = Utility::settings(Auth::user()->creatorId());
            
            if (!empty($company_settings['timezone'])) {
                date_default_timezone_set($company_settings['timezone']);
            }

            $startTime = Utility::getValByName('company_start_time');
            $endTime = Utility::getValByName('company_end_time');

            // Check if already clocked in today
            $existingRecord = AttendanceEmployee::where('employee_id', $employee->id)
                ->where('date', $date)
                ->first();

            if ($existingRecord && $request->type === 'clock_in') {
                // Already has a record - this might be clock out
                if ($existingRecord->clock_out === '00:00:00') {
                    // Update with clock out
                    $endTimestamp = strtotime($date . $endTime);
                    $currentTimestamp = strtotime($date . ' ' . $time);
                    if ($currentTimestamp > $endTimestamp) {
                        $endTimestamp = strtotime($date . ' +1 day ' . $endTime);
                    }

                    $totalEarlyLeavingSeconds = $endTimestamp - $currentTimestamp;
                    if ($totalEarlyLeavingSeconds < 0) {
                        $earlyLeaving = '0:00:00';
                    } else {
                        $hours = floor($totalEarlyLeavingSeconds / 3600);
                        $mins = floor($totalEarlyLeavingSeconds / 60 % 60);
                        $secs = floor($totalEarlyLeavingSeconds % 60);
                        $earlyLeaving = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);
                    }

                    $timeTimestamp = strtotime($date . $time);
                    $endTimeTimestamp = strtotime($date . $endTime);
                    if ($timeTimestamp > $endTimeTimestamp) {
                        $totalOvertimeSeconds = $timeTimestamp - $endTimeTimestamp;
                        $hours = floor($totalOvertimeSeconds / 3600);
                        $mins = floor(($totalOvertimeSeconds % 3600) / 60);
                        $secs = floor($totalOvertimeSeconds % 60);
                        $overtime = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);
                    } else {
                        $overtime = '00:00:00';
                    }

                    $existingRecord->clock_out = $time;
                    $existingRecord->early_leaving = $earlyLeaving;
                    $existingRecord->overtime = $overtime;
                    $existingRecord->facial_verification_photo = $clockInPhotoPath;
                    $existingRecord->save();

                    \Storage::disk('public')->delete($clockInPhotoPath);

                    return response()->json([
                        'success' => true,
                        'message' => 'Clock out successful. Employee verified via facial recognition.',
                        'confidence' => $verificationResult['confidence'],
                        'type' => 'clock_out',
                        'time' => $time
                    ]);
                }
            }

            // New clock in record
            if ($request->type === 'clock_in') {
                // Calculate late time
                $expectedStartTime = $date . ' ' . $startTime;
                $actualClockInTime = $date . ' ' . $time;
                $totalLateSeconds = strtotime($actualClockInTime) - strtotime($expectedStartTime);
                $totalLateSeconds = max($totalLateSeconds, 0);

                $hours = floor($totalLateSeconds / 3600);
                $mins = floor($totalLateSeconds / 60 % 60);
                $secs = floor($totalLateSeconds % 60);
                $late = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);
                $attendanceStatus = $this->resolveBiometricAttendanceStatus($totalLateSeconds);

                $attendance = new AttendanceEmployee();
                $attendance->employee_id = $employee->id;
                $attendance->date = $date;
                $attendance->status = $attendanceStatus;
                $attendance->clock_in = $time;
                $attendance->clock_out = '00:00:00';
                $attendance->late = $late;
                $attendance->early_leaving = '00:00:00';
                $attendance->overtime = '00:00:00';
                $attendance->total_rest = '00:00:00';
                $attendance->facial_verification_photo = $clockInPhotoPath;
                $attendance->created_by = Auth::user()->creatorId();
                $attendance->save();

                return response()->json([
                    'success' => true,
                    'message' => 'Clock in successful. Employee verified via facial recognition.',
                    'confidence' => $verificationResult['confidence'],
                    'type' => 'clock_in',
                    'time' => $time,
                    'late' => $late
                ]);
            }

            \Storage::disk('public')->delete($clockInPhotoPath);

            return response()->json([
                'success' => false,
                'message' => 'Invalid request type'
            ], 400);

        } catch (\Exception $e) {
            \Log::error('Clock In with Facial Recognition Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    protected function resolveBiometricAttendanceStatus(int $lateSeconds): string
    {
        $settings = Utility::settings();
        $graceLateMinutes = (int) ($settings['attendance_grace_late_minutes'] ?? 0);
        $halfDayMinutes = isset($settings['attendance_half_day_deduction_minutes'])
            ? (int) $settings['attendance_half_day_deduction_minutes']
            : (int) round(((float) ($settings['attendance_half_day_deduction_hours'] ?? 1.5)) * 60);
        $halfDayThresholdSeconds = max(0, ($halfDayMinutes + $graceLateMinutes) * 60);

        if ($lateSeconds > $halfDayThresholdSeconds) {
            return 'Half Day';
        }

        return 'Present';
    }

}


