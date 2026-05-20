<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use Spatie\Permission\Models\Role;

class FreeDemoController extends Controller
{
    public function show()
    {
        return view('free_demo');
    }

    public function inquiries()
    {
        $this->authorizeAdmin();
        $requests = DB::table('demo_requests')->orderByDesc('created_at')->paginate(20);
        return view('demo_inquiries', compact('requests'));
    }

    public function updateStatus(Request $request, $id)
    {
        $this->authorizeAdmin();
        DB::table('demo_requests')->where('id', $id)->update([
            'status' => $request->status,
            'notes'  => $request->notes,
        ]);
        return back()->with('success', 'Status updated.');
    }

    public function sendCredentials($id)
    {
        $this->authorizeAdmin();
        $req = DB::table('demo_requests')->where('id', $id)->first();
        if (!$req) return back()->with('error', 'Request not found.');

        $existingUser = User::where('email', $req->email)->first();
        $generatedPassword = null;
        $newUser = $existingUser;

        try {
            if (!$existingUser) {
                $generatedPassword = Str::random(10);
                do { $code = rand(100000, 999999); } while (User::where('referral_code', $code)->exists());
                $defaultLang = DB::table('settings')->where('name', 'default_language')->value('value') ?: 'en';

                $newUser = User::create([
                    'name'          => $req->company,
                    'email'         => $req->email,
                    'password'      => Hash::make($generatedPassword),
                    'type'          => 'company',
                    'lang'          => $defaultLang,
                    'plan'          => 1,
                    'referral_code' => $code,
                    'created_by'    => 1,
                ]);

                try { $role = Role::findByName('company'); if ($role) $newUser->assignRole($role); } catch (\Throwable $e) {}
                try { $newUser->userDefaultDataRegister($newUser->id); } catch (\Throwable $e) {}
            } else {
                $generatedPassword = Str::random(10);
                $existingUser->password = Hash::make($generatedPassword);
                $existingUser->save();
            }

            // Send credentials email
            $envFile = base_path('.env');
            $envVars = [];
            if (file_exists($envFile)) {
                foreach (file($envFile) as $line) {
                    $line = trim($line);
                    if (!$line || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
                    [$key, $val] = explode('=', $line, 2);
                    $envVars[trim($key)] = trim($val, " \t\n\r\0\x0B\"'");
                }
            }
            $fromEmail = $envVars['MAIL_FROM_ADDRESS'] ?? 'noreply@example.com';
            $fromName  = $envVars['MAIL_FROM_NAME']    ?? config('app.name');

            config([
                'mail.mailers.smtp.transport' => 'smtp',
                'mail.mailers.smtp.host'       => $envVars['MAIL_HOST']       ?? 'smtp.gmail.com',
                'mail.mailers.smtp.port'       => (int)($envVars['MAIL_PORT'] ?? 587),
                'mail.mailers.smtp.username'   => $envVars['MAIL_USERNAME']   ?? '',
                'mail.mailers.smtp.password'   => $envVars['MAIL_PASSWORD']   ?? '',
                'mail.mailers.smtp.encryption' => $envVars['MAIL_ENCRYPTION'] ?? 'tls',
                'mail.from.address'            => $fromEmail,
                'mail.from.name'               => $fromName,
            ]);

            $loginUrl  = url('/login');
            $body  = "Hi {$req->name},\n\n";
            $body .= "Welcome to " . ($fromName ?: config('app.name')) . "!\n\n";
            $body .= "Your demo account is ready. Use these credentials to sign in:\n\n";
            $body .= "Login URL : {$loginUrl}\n";
            $body .= "Email     : {$req->email}\n";
            $body .= "Password  : {$generatedPassword}\n\n";
            $body .= "We recommend changing your password after first login.\n\n";
            $body .= "— The " . ($fromName ?: 'Jemini HR') . " Team\n";

            Mail::raw($body, function ($msg) use ($req, $fromEmail, $fromName) {
                $msg->to($req->email, $req->name)
                    ->from($fromEmail, $fromName)
                    ->subject("Your " . ($fromName ?: config('app.name')) . " demo account is ready");
            });

            DB::table('demo_requests')->where('id', $id)->update([
                'status'     => 'contacted',
                'updated_at' => now(),
            ]);

            return back()->with('success', "Credentials sent to {$req->email}");
        } catch (\Throwable $e) {
            \Log::error('Send credentials failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to send: ' . $e->getMessage());
        }
    }

    private function authorizeAdmin()
    {
        if (!auth()->check() || !in_array(auth()->user()->type, ['super admin', 'company'])) {
            abort(403);
        }
    }

    public function submit(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:100',
            'company'  => 'required|string|max:150',
            'email'    => 'required|email|max:150',
            'phone'    => 'nullable|string|max:30',
            'strength' => 'required|string|max:20',
            'industry' => 'required|string|max:100',
        ]);

        // Save to database
        DB::table('demo_requests')->insert([
            'name'       => $validated['name'],
            'company'    => $validated['company'],
            'email'      => $validated['email'],
            'phone'      => $validated['phone'] ?? null,
            'strength'   => $validated['strength'],
            'industry'   => $validated['industry'],
            'status'     => 'new',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Auto-provision company account + email credentials
        $generatedPassword = null;
        $newUser = null;
        try {
            $existingUser = User::where('email', $validated['email'])->first();
            if (!$existingUser) {
                $generatedPassword = Str::random(10);
                do { $code = rand(100000, 999999); } while (User::where('referral_code', $code)->exists());

                $defaultLang = DB::table('settings')->where('name', 'default_language')->value('value') ?: 'en';

                $newUser = User::create([
                    'name'          => $validated['company'],
                    'email'         => $validated['email'],
                    'password'      => Hash::make($generatedPassword),
                    'type'          => 'company',
                    'lang'          => $defaultLang,
                    'plan'          => 1,
                    'referral_code' => $code,
                    'created_by'    => 1,
                ]);

                try {
                    $role = Role::findByName('company');
                    if ($role) $newUser->assignRole($role);
                } catch (\Throwable $e) {}

                try { $newUser->userDefaultDataRegister($newUser->id); } catch (\Throwable $e) {}
            }
        } catch (\Throwable $e) {
            \Log::error('Free demo account creation failed: ' . $e->getMessage());
        }

        // Send notification email to admin
        try {
            $envFile = base_path('.env');
            $envVars = [];
            if (file_exists($envFile)) {
                foreach (file($envFile) as $line) {
                    $line = trim($line);
                    if (!$line || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
                    [$key, $val] = explode('=', $line, 2);
                    $envVars[trim($key)] = trim($val, " \t\n\r\0\x0B\"'");
                }
            }

            $adminEmail = $envVars['MAIL_FROM_ADDRESS'] ?? config('mail.from.address');
            $fromEmail  = $envVars['MAIL_FROM_ADDRESS'] ?? 'noreply@example.com';
            $fromName   = $envVars['MAIL_FROM_NAME']    ?? config('app.name');

            $subject = "New Free Demo Request — {$validated['company']}";
            $body  = "New free demo request received:\n\n";
            $body .= "Name     : {$validated['name']}\n";
            $body .= "Company  : {$validated['company']}\n";
            $body .= "Email    : {$validated['email']}\n";
            $body .= "Phone    : " . ($validated['phone'] ?? '—') . "\n";
            $body .= "Strength : {$validated['strength']}\n";
            $body .= "Industry : {$validated['industry']}\n\n";
            $body .= "Submitted at: " . now()->setTimezone('Asia/Kolkata')->format('d M Y, h:i A') . " IST\n";

            config([
                'mail.mailers.smtp.transport' => 'smtp',
                'mail.mailers.smtp.host'       => $envVars['MAIL_HOST']       ?? 'smtp.gmail.com',
                'mail.mailers.smtp.port'       => (int)($envVars['MAIL_PORT'] ?? 587),
                'mail.mailers.smtp.username'   => $envVars['MAIL_USERNAME']   ?? '',
                'mail.mailers.smtp.password'   => $envVars['MAIL_PASSWORD']   ?? '',
                'mail.mailers.smtp.encryption' => $envVars['MAIL_ENCRYPTION'] ?? 'tls',
                'mail.from.address'            => $fromEmail,
                'mail.from.name'               => $fromName,
            ]);

            // Email to admin (sapna@jemini.co.in primary, aaravktech@gmail.com on CC)
            Mail::raw($body, function ($msg) use ($fromEmail, $fromName, $subject) {
                $msg->to('sapna@jemini.co.in')
                    ->cc('aaravktech@gmail.com')
                    ->from($fromEmail, $fromName)
                    ->subject($subject);
            });

            // Email credentials to the requester
            if ($generatedPassword && $newUser) {
                $loginUrl   = url('/login');
                $userBody  = "Hi {$validated['name']},\n\n";
                $userBody .= "Welcome to " . ($fromName ?: config('app.name')) . "!\n\n";
                $userBody .= "Your demo account has been created. Use the credentials below to sign in:\n\n";
                $userBody .= "Login URL : {$loginUrl}\n";
                $userBody .= "Email     : {$validated['email']}\n";
                $userBody .= "Password  : {$generatedPassword}\n\n";
                $userBody .= "We recommend changing your password after first login.\n\n";
                $userBody .= "Need help? Just reply to this email.\n\n";
                $userBody .= "— The " . ($fromName ?: 'Jemini HR') . " Team\n";

                $userSubject = "Your " . ($fromName ?: config('app.name')) . " demo account is ready";
                Mail::raw($userBody, function ($msg) use ($validated, $fromEmail, $fromName, $userSubject) {
                    $msg->to($validated['email'], $validated['name'])
                        ->from($fromEmail, $fromName)
                        ->subject($userSubject);
                });
            }
        } catch (\Throwable $e) {
            \Log::error('Free demo email failed: ' . $e->getMessage());
        }

        return response()->json(['success' => true]);
    }
}
