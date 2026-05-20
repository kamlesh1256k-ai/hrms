<?php

namespace App\Console\Commands;

use App\Models\EmployeeSurvey;
use App\Services\PulseNotifier;
use Illuminate\Console\Command;

/**
 * Dispatches pulse-survey invites/reminders on a recurring cadence.
 *
 *   php artisan surveys:pulse-dispatch
 *
 * Logic:
 *   - For each ACTIVE pulse survey:
 *       weekly  → if last_sent_at was ≥ 7 days ago, send reminder
 *       monthly → if last_sent_at was ≥ 30 days ago, send reminder
 *       custom/once → skip (not recurring)
 *
 * Recommended cron: daily at 09:00.
 *   0 9 * * *  cd /path/to/hrms && php artisan surveys:pulse-dispatch
 */
class PulseDispatchCommand extends Command
{
    protected $signature   = 'surveys:pulse-dispatch
                              {--force : Send to all active pulse surveys regardless of last_sent_at}
                              {--dry   : Show what would be sent without sending}';

    protected $description = 'Send recurring pulse survey reminders to assigned employees';

    public function handle(PulseNotifier $notifier): int
    {
        $force = (bool) $this->option('force');
        $dry   = (bool) $this->option('dry');

        $surveys = EmployeeSurvey::where('type', 'pulse')
            ->where('status', 'active')
            ->whereIn('frequency', ['weekly', 'monthly'])
            ->get();

        if ($surveys->isEmpty()) {
            $this->info('No active recurring pulse surveys found.');
            return self::SUCCESS;
        }

        $totalSent = 0;
        foreach ($surveys as $s) {
            $intervalDays = $s->frequency === 'weekly' ? 7 : 30;
            $due = $force
                || !$s->last_sent_at
                || $s->last_sent_at->lt(now()->subDays($intervalDays));

            if (!$due) {
                $this->line("  ↳ Skipped (not yet due): #{$s->id} {$s->title}");
                continue;
            }

            if ($dry) {
                $aud = $notifier->audience($s)->count();
                $this->line("  ↳ Would send to {$aud} employee(s): #{$s->id} {$s->title}");
                continue;
            }

            $sent = $notifier->dispatch($s, true /* reminder */);
            $totalSent += $sent;
            $this->line("  ↳ Sent {$sent} invite(s): #{$s->id} {$s->title}");

            $s->update(['last_sent_at' => now()]);
        }

        $this->info("Done. Total emails sent: {$totalSent}");
        return self::SUCCESS;
    }
}
