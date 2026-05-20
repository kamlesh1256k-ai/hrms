<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Convert all growth-review ratings from 0-10 scale to 1-5 scale by halving.
 *
 * Usage:
 *   php artisan gr:rescale-ratings              # dry-run, all cycles
 *   php artisan gr:rescale-ratings --apply      # actually update
 *   php artisan gr:rescale-ratings --cycle=3 --apply
 */
class RescaleGrowthReviewRatings extends Command
{
    protected $signature = 'gr:rescale-ratings
                            {--cycle= : Limit to a single cycle id}
                            {--apply : Actually write changes (default is dry-run)}';

    protected $description = 'Rescale growth-review ratings from 0-10 to 1-5 (halve all values >5).';

    public function handle(): int
    {
        $cycleId = $this->option('cycle');
        $apply   = (bool) $this->option('apply');

        $this->info($apply ? 'APPLY mode — changes will be written.' : 'DRY-RUN — no changes will be written. Pass --apply to commit.');
        if ($cycleId) $this->info("Scoped to cycle_id={$cycleId}");

        // ── gr_ratings: self_rating, manager_rating, head_rating, final_rating
        $rq = DB::table('gr_ratings');
        if ($cycleId) $rq->where('cycle_id', $cycleId);
        $ratings = $rq->get();

        $ratingsTouched = 0;
        foreach ($ratings as $r) {
            $patch = [];
            foreach (['self_rating','manager_rating','head_rating','final_rating'] as $col) {
                if ($r->$col !== null && (float)$r->$col > 5.0) {
                    $patch[$col] = round(((float)$r->$col) / 2, 1);
                }
            }
            if (!empty($patch)) {
                $ratingsTouched++;
                if ($apply) {
                    DB::table('gr_ratings')->where('id', $r->id)->update($patch);
                }
            }
        }
        $this->line("gr_ratings rows needing rescale: {$ratingsTouched}");

        // ── gr_reviews.rating
        $vq = DB::table('gr_reviews')->whereNotNull('rating')->where('rating', '>', 5.0);
        if ($cycleId) $vq->where('cycle_id', $cycleId);
        $reviewRows = $vq->get(['id','rating']);
        $reviewCount = $reviewRows->count();
        if ($apply && $reviewCount) {
            foreach ($reviewRows as $row) {
                DB::table('gr_reviews')->where('id', $row->id)->update([
                    'rating' => round(((float)$row->rating) / 2, 1),
                ]);
            }
        }
        $this->line("gr_reviews rows needing rescale: {$reviewCount}");

        // ── performance_cycles.rating_scale → force 1-5
        $cq = DB::table('performance_cycles');
        if ($cycleId) $cq->where('id', $cycleId);
        $cycleCount = (clone $cq)->where('rating_scale', '!=', '1-5')->count();
        if ($apply && $cycleCount) {
            (clone $cq)->where('rating_scale', '!=', '1-5')->update(['rating_scale' => '1-5']);
        }
        $this->line("performance_cycles updated to rating_scale=1-5: {$cycleCount}");

        $this->info($apply ? 'Done.' : 'Dry-run complete. Re-run with --apply to commit.');
        return self::SUCCESS;
    }
}
