<?php

namespace App\Console\Commands;

use App\Models\AtDevice;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Har 5 minute mein chalti hai.
 * Agar kisi device ka last_seen_at 10 minute se zyada purana ho —
 * matlab user ne tracker band kar diya — admin ko email bhejo.
 *
 * Duplicate alert avoid karne ke liye ek simple flag file / cache key use karte hain.
 * Jab device wapas online aaye to flag reset ho jaata hai (next heartbeat pe last_seen_at update hota hai).
 */
class NotifyTrackerOffline extends Command
{
    protected $signature   = 'tracker:notify-offline';
    protected $description = 'Admin ko notify karo jab kisi employee ka tracker offline ho jaye';

    // Kitne minute baad "offline" maana jaye (heartbeat 1 min mein aata hai)
    public const OFFLINE_AFTER_MINUTES = 5;

    public function handle(): int
    {
        // Seedha .env parse karo — env()/config() cached values return karte hain
        $envVars = $this->parseEnvFile(base_path('.env'));
        config([
            'mail.default'                 => 'smtp',
            'mail.mailers.smtp.host'       => $envVars['MAIL_HOST']        ?? 'smtp.gmail.com',
            'mail.mailers.smtp.port'       => (int)($envVars['MAIL_PORT']  ?? 587),
            'mail.mailers.smtp.username'   => $envVars['MAIL_USERNAME']    ?? '',
            'mail.mailers.smtp.password'   => $envVars['MAIL_PASSWORD']    ?? '',
            'mail.mailers.smtp.encryption' => $envVars['MAIL_ENCRYPTION']  ?? 'tls',
            'mail.from.address'            => $envVars['MAIL_FROM_ADDRESS'] ?? '',
            'mail.from.name'               => $envVars['MAIL_FROM_NAME']    ?? 'HRMS Tracker',
        ]);

        $cutoff = now()->subMinutes(self::OFFLINE_AFTER_MINUTES);

        // Sirf woh devices jo recently active thi (aaj ke din) aur ab offline hain
        $offlineDevices = AtDevice::with('user:id,name,email,created_by')
            ->where('last_seen_at', '<', $cutoff)
            ->whereNotNull('last_seen_at')
            ->get();

        foreach ($offlineDevices as $device) {
            $user = $device->user;
            if (!$user) continue;

            $cacheKey = 'tracker_offline_notified_' . $device->id;

            // Agar pehle se notify kar chuke hain to skip karo
            if (cache()->get($cacheKey)) continue;

            // Admin dhundo
            $admin = User::find($user->created_by);
            if (!$admin || !$admin->email) continue;

            try {
                $lastSeen = $device->last_seen_at
                    ? $device->last_seen_at->format('d M Y, h:i A')
                    : 'Unknown';

                $subject = '[Tracker Offline] ' . $user->name . ' ka tracker band ho gaya';
                $body    = "Employee:   {$user->name} ({$user->email})\n"
                         . "Device:     {$device->device_name}\n"
                         . "Last Seen:  {$lastSeen}\n\n"
                         . "Yeh employee ka tracker " . self::OFFLINE_AFTER_MINUTES . " minute se zyada se band hai.\n"
                         . "Activity Tracker dashboard mein check karein.";

                Mail::raw($body, function ($msg) use ($admin, $subject) {
                    $msg->to($admin->email, $admin->name)->subject($subject);
                });

                // Flag set karo — 2 ghante tak dobara notify mat karo
                cache()->put($cacheKey, true, now()->addHours(2));

                $this->info("Notified admin ({$admin->email}) about offline device: {$device->device_name} ({$user->name})");
                Log::info('tracker:notify-offline sent', [
                    'device_id' => $device->id,
                    'user'      => $user->name,
                    'admin'     => $admin->email,
                ]);
            } catch (\Throwable $e) {
                Log::warning('tracker:notify-offline mail failed', [
                    'device_id' => $device->id,
                    'error'     => $e->getMessage(),
                ]);
            }
        }

        // Jab device wapas online aaye to flag clear karo
        // (last_seen_at recent hai — cutoff se newer)
        $onlineDevices = AtDevice::where('last_seen_at', '>=', $cutoff)->get();
        foreach ($onlineDevices as $device) {
            cache()->forget('tracker_offline_notified_' . $device->id);
        }

        return 0;
    }

    private function parseEnvFile(string $path): array
    {
        $env = [];
        if (!file_exists($path)) return $env;
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
            [$key, $val] = explode('=', $line, 2);
            $env[trim($key)] = trim($val, " \t\n\r\0\x0B\"");
        }
        return $env;
    }
}
