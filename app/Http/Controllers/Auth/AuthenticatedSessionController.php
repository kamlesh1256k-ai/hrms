<?php

namespace App\Http\Controllers\Auth;

use App\Events\VerifyReCaptchaToken;
use App\Models\Employee;
use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\LoginDetail;
use App\Models\Utility;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use WhichBrowser\Parser;


class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     *
     * @param  \App\Http\Requests\Auth\LoginRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */

    public function __construct()
    {
        if (!file_exists(storage_path() . "/installed")) {
            header('location:install');
            die;
        }
    }

    /*protected function authenticated(Request $request, $user)
    {
        if($user->delete_status == 1)
        {
            auth()->logout();
        }

        return redirect('/check');
    }*/

    public function store(LoginRequest $request)
    {
        $settings = Utility::settings();
        $validation = [];
        if (isset($settings['recaptcha_module']) && $settings['recaptcha_module'] == 'yes') {
            if ($settings['google_recaptcha_version'] == 'v2-checkbox') {
                $validation['g-recaptcha-response'] = 'required';
            } elseif ($settings['google_recaptcha_version'] == 'v3') {
                $result = event(new VerifyReCaptchaToken($request));

                if (!isset($result[0]['status']) || $result[0]['status'] != true) {
                    $key = 'g-recaptcha-response';
                    $request->merge([$key => null]); // Set the key to null

                    $validation['g-recaptcha-response'] = 'required';
                }
            } else {
                $validation = [];
            }
        } else {
            $validation = [];
        }
        $validator = Validator::make(
            $request->all(),
            $validation
        );
        if ($validator->fails()) {
            $messages = $validator->getMessageBag();

            return redirect()->back()->with('error', $messages->first());
        }
        
        $request->authenticate();

        $request->session()->regenerate();

        $user = Auth::user();
        if ($user->is_active == 0) {
            auth()->logout();
            return redirect()->back();
        }

        if ($user->is_disable == 0) {
            auth()->logout();
            return redirect()->back();
        }

        $user = \Auth::user();
        if ($user->type == 'company') {
            $plan = plan::find($user->plan);
            if ($plan) {
                if ($plan->duration != 'Lifetime') {
                    $datetime1 = new \DateTime($user->plan_expire_date);
                    $datetime2 = new \DateTime(date('Y-m-d'));

                    $interval = $datetime2->diff($datetime1);
                    $days     = $interval->format('%r%a');

                    if ($days <= 0) {
                        $user->assignplan(1);

                        return redirect()->intended(RouteServiceProvider::HOME)->with('error', __('Your plan is expired.'));
                    }
                }
            }
        }

        if ($user->type == 'company') {
            $free_plan = Plan::where('price', '=', '0.0')->first();
            $plan      = Plan::find($user->plan);

            if ($user->plan != $free_plan->id) {
                if (date('Y-m-d') > $user->plan_expire_date && $plan->duration != 'Lifetime') {
                    $user->plan             = $free_plan->id;
                    $user->plan_expire_date = null;
                    $user->save();

                    $users     = User::where('created_by', '=', \Auth::user()->creatorId())->get();
                    $employees = Employee::where('created_by', '=', \Auth::user()->creatorId())->get();

                    if ($free_plan->max_users == -1) {
                        foreach ($users as $user) {
                            $user->is_active = 1;
                            $user->save();
                        }
                    } else {
                        $userCount = 0;
                        foreach ($users as $user) {
                            $userCount++;
                            if ($userCount <= $free_plan->max_users) {
                                $user->is_active = 1;
                                $user->save();
                            } else {
                                $user->is_active = 0;
                                $user->save();
                            }
                        }
                    }


                    if ($free_plan->max_employees == -1) {
                        foreach ($employees as $employee) {
                            $employee->is_active = 1;
                            $employee->save();
                        }
                    } else {
                        $employeeCount = 0;
                        foreach ($employees as $employee) {
                            $employeeCount++;
                            if ($employeeCount <= $free_plan->max_customers) {
                                $employee->is_active = 1;
                                $employee->save();
                            } else {
                                $employee->is_active = 0;
                                $employee->save();
                            }
                        }
                    }

                    return redirect()->route('dashboard')->with('error', 'Your plan expired limit is over, please upgrade your plan');
                }
            }
        }


        if ($user->type != 'company' && $user->type != 'super admin') {
            $ip = $_SERVER['REMOTE_ADDR'];

            // Fetch geo data with a short timeout so it never hangs on localhost
            $query = [];
            try {
                $ctx = stream_context_create(['http' => ['timeout' => 5]]);
                $raw = @file_get_contents('http://ip-api.com/php/' . $ip, false, $ctx);
                $parsed = $raw ? @unserialize($raw) : false;
                if (is_array($parsed)) {
                    $query = $parsed;
                }
            } catch (\Throwable $e) {
                // geo lookup failed – proceed without it
            }

            $whichbrowser = new \WhichBrowser\Parser($_SERVER['HTTP_USER_AGENT']);
            if ($whichbrowser->device->type == 'bot') {
                return redirect()->intended(RouteServiceProvider::HOME);
            }
            $referrer = isset($_SERVER['HTTP_REFERER']) ? parse_url($_SERVER['HTTP_REFERER']) : null;

            /* Detect extra details about the user */
            $query['browser_name'] = $whichbrowser->browser->name ?? null;
            $query['os_name']      = $whichbrowser->os->name ?? null;
            $query['browser_language'] = isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])
                ? mb_substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2)
                : null;
            $query['device_type']   = Utility::get_device_type($_SERVER['HTTP_USER_AGENT']);
            $query['referrer_host'] = !empty($referrer['host']);
            $query['referrer_path'] = !empty($referrer['path']);

            if (!empty($query['timezone'])) {
                date_default_timezone_set($query['timezone']);
            }

            $json = json_encode($query);

            // Handle Selfie Image
            $selfiePath = null;
            if ($request->has('selfie_data') && !empty($request->selfie_data)) {
                $image = $request->selfie_data;
                $image = str_replace('data:image/jpeg;base64,', '', $image);
                $image = str_replace(' ', '+', $image);
                $imageName = 'login_selfie_' . Auth::user()->id . '_' . time() . '.jpg';
                
                // Create directory if not exists
                $dir = storage_path('app/public/login_selfies');
                if (!file_exists($dir)) {
                    mkdir($dir, 0777, true);
                }
                
                $imagePath = $dir . '/' . $imageName;
                file_put_contents($imagePath, base64_decode($image));
                $selfiePath = 'login_selfies/' . $imageName;
            }

            $login_detail = new LoginDetail();
            $login_detail->user_id = Auth::user()->id;
            $login_detail->ip = $ip;
            $login_detail->date = date('Y-m-d H:i:s');
            $login_detail->Details = $json;
            $login_detail->created_by = \Auth::user()->creatorId();
            
            // Store location if available
            if ($request->has('latitude')) {
                $login_detail->latitude = $request->latitude;
            }
            if ($request->has('longitude')) {
                $login_detail->longitude = $request->longitude;
            }
            if ($request->has('location_address')) {
                $login_detail->location_address = $request->location_address;
            }
            
            // Store selfie
            if ($selfiePath) {
                $login_detail->selfie_image = $selfiePath;
            }
            
            $login_detail->save();
        }

        // $user->last_login = date('Y-m-d H:i:s');
        // $user->save();
        return redirect()->intended(RouteServiceProvider::HOME);
    }

    public function showLoginForm($lang = '')
    {
        if ($lang == '') {
            $lang = \App\Models\Utility::getValByName('default_language');
        }
        \App::setLocale($lang);

        return view('auth.login', compact('lang'));
    }

    public function showLinkRequestForm($lang = '')
    {
        if ($lang == '') {
            $lang = \App\Models\Utility::getValByName('default_language');
        }

        \App::setLocale($lang);

        return view('auth.forgot-password', compact('lang'));
    }
    public function storeLinkRequestForm(Request $request)
    {
        $settings = Utility::settings();
        if (isset($settings['recaptcha_module']) && $settings['recaptcha_module'] == 'yes') {
            $validation['g-recaptcha-response'] = 'required';
        } else {
            $validation = [];
        }

        $validator = Validator::make(
            $request->all(),
            $validation
        );
        if ($validator->fails()) {
            $messages = $validator->getMessageBag();

            return redirect()->back()->with('error', $messages->first());
        }

        $validator = Validator::make(
            $request->all(),[
                'email' => 'required|email',
            ]
        );
        if ($validator->fails()) {
            $messages = $validator->getMessageBag();

            return redirect()->back()->with('error', $messages->first());
        }

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        try {

            $status = Password::sendResetLink(
                $request->only('email')
            );

            return $status == Password::RESET_LINK_SENT
                ? back()->with('status', __($status))
                : back()->withInput($request->only('email'))
                ->withErrors(['email' => __($status)]);
        } catch (\Exception $e) {

            return redirect()->back()->withErrors('E-Mail has been not sent due to SMTP configuration');
        }
    }

    /**
     * Destroy an authenticated session.
     * Optionally saves logout photo and logout time to the current session's LoginDetail.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request)
    {
        $userId = Auth::id();

        // Save logout time and optional photo to current session's login record (only if migration has run)
        if ($userId && Schema::hasColumn('login_details', 'logout_at')) {
            $loginDetail = LoginDetail::where('user_id', $userId)
                ->whereNull('logout_at')
                ->orderBy('date', 'desc')
                ->first();

            if ($loginDetail) {
                $loginDetail->logout_at = now();

                if (Schema::hasColumn('login_details', 'logout_selfie') && $request->filled('logout_photo_base64')) {
                    $photoData = $request->input('logout_photo_base64');
                    $photoData = preg_replace('/^data:image\/\w+;base64,/', '', $photoData);
                    $photoData = str_replace(' ', '+', $photoData);
                    $decoded = base64_decode($photoData);
                    if ($decoded !== false) {
                        $dir = storage_path('app/public/logout_selfies');
                        if (!is_dir($dir)) {
                            mkdir($dir, 0755, true);
                        }
                        $name = 'logout_selfie_' . $userId . '_' . time() . '.jpg';
                        $path = $dir . '/' . $name;
                        if (file_put_contents($path, $decoded) !== false) {
                            $loginDetail->logout_selfie = 'logout_selfies/' . $name;
                        }
                    }
                }

                $loginDetail->save();
            }
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
