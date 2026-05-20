<?php
// Seeds the Growth Review module with realistic dummy data for testing.
// Run:  php scripts/seed_growth_review_dummy.php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

$createdBy = 6;
$now = Carbon::now();

echo "=== Growth Review Dummy Seeder ===\n";

$employees = DB::table('employees')->where('created_by', $createdBy)->get();
if ($employees->isEmpty()) { echo "No employees found.\n"; exit(1); }
echo "Employees: " . $employees->count() . "\n";

$manager   = $employees->first();                      // Nitesh (salary 83333)
$head      = $employees->firstWhere('salary', '>=', 80000) ?? $manager;
$teamIds   = $employees->pluck('id')->all();

DB::beginTransaction();
try {
    // ── 1. Performance Cycles ───────────────────────────────────────
    DB::table('performance_cycles')->where('created_by', $createdBy)->delete();

    $activeCycleId = DB::table('performance_cycles')->insertGetId([
        'name'                  => 'FY 2026-27 H1',
        'start_date'            => '2026-04-01',
        'end_date'              => '2026-09-30',
        'goal_deadline'         => '2026-04-30',
        'self_review_start'     => '2026-09-01',
        'self_review_end'       => '2026-09-10',
        'manager_review_start'  => '2026-09-11',
        'manager_review_end'    => '2026-09-20',
        'head_review_start'     => '2026-09-21',
        'head_review_end'       => '2026-09-25',
        'calibration_start'     => '2026-09-26',
        'calibration_end'       => '2026-09-30',
        'status'                => 'active',
        'rating_scale'          => '1-5',
        'created_by'            => $createdBy,
        'created_at'            => $now, 'updated_at' => $now,
    ]);

    $pastCycleId = DB::table('performance_cycles')->insertGetId([
        'name'         => 'FY 2025-26 H2',
        'start_date'   => '2025-10-01',
        'end_date'     => '2026-03-31',
        'goal_deadline'=> '2025-10-31',
        'status'       => 'completed',
        'rating_scale' => '1-5',
        'created_by'   => $createdBy,
        'created_at'   => $now->copy()->subMonths(7), 'updated_at' => $now,
    ]);
    echo "Cycles: active=$activeCycleId, past=$pastCycleId\n";

    // ── 2. Missions (Goals) ─────────────────────────────────────────
    DB::table('gr_missions')->where('created_by', $createdBy)->delete();

    $missionTemplates = [
        ['Achieve Q2 revenue target of ₹50L',         'Revenue',      30, 'Hit assigned revenue targets consistently.'],
        ['Reduce customer complaints by 20%',         'CSAT Score',   20, 'Improve customer satisfaction through faster resolution.'],
        ['Complete React certification',              'Completion',   15, 'Upskill in modern frontend frameworks.'],
        ['Onboard 3 new team members',                'Count',        15, 'Mentor and ramp up new joiners to productivity.'],
        ['Improve ticket TAT by 30%',                 'Avg TAT (hrs)',20, 'Faster ticket turnaround across all priority levels.'],
    ];
    $missionCount = 0;
    foreach ($employees as $emp) {
        $pick = array_slice($missionTemplates, 0, rand(3, 5));
        foreach ($pick as $i => $m) {
            $progress = rand(20, 95);
            $status = $progress >= 100 ? 'completed' : ($progress >= 40 ? 'in_progress' : 'pending');
            DB::table('gr_missions')->insert([
                'cycle_id'     => $activeCycleId,
                'employee_id'  => $emp->id,
                'title'        => $m[0],
                'description'  => $m[3],
                'kpi'          => $m[1],
                'weightage'    => $m[2],
                'deadline'     => '2026-09-15',
                'status'       => $status,
                'approval'     => $i < 2 ? 'approved' : (rand(0,1) ? 'approved' : 'pending'),
                'approved_by'  => $i < 2 ? $manager->id : null,
                'approved_at'  => $i < 2 ? $now : null,
                'manager_remarks' => $i === 0 ? 'Aligned with team OKRs.' : null,
                'progress'     => $progress,
                'created_by'   => $createdBy,
                'created_at'   => $now, 'updated_at' => $now,
            ]);
            $missionCount++;
        }
    }
    echo "Missions: $missionCount\n";

    // ── 3. Shoutouts ───────────────────────────────────────────────
    DB::table('gr_shoutouts')->where('created_by', $createdBy)->delete();
    $badges = ['star','teamwork','innovation','customer-hero','mentor'];
    $messages = [
        'Great job handling the client escalation last week!',
        'Thanks for staying late to help debug the payroll issue.',
        'Your demo blew away the leadership team — well done!',
        'Always reliable and ready to jump in — appreciate you.',
        'Crushed the Q1 targets, truly inspiring work.',
    ];
    $shoutCount = 0;
    for ($i = 0; $i < 15; $i++) {
        $from = $teamIds[array_rand($teamIds)];
        do { $to = $teamIds[array_rand($teamIds)]; } while ($to === $from);
        DB::table('gr_shoutouts')->insert([
            'from_employee_id' => $from,
            'to_employee_id'   => $to,
            'message'          => $messages[array_rand($messages)],
            'badge'            => $badges[array_rand($badges)],
            'cycle_id'         => $activeCycleId,
            'created_by'       => $createdBy,
            'created_at'       => $now->copy()->subDays(rand(1, 60)),
            'updated_at'       => $now,
        ]);
        $shoutCount++;
    }
    echo "Shoutouts: $shoutCount\n";

    // ── 4. Sync Ups ────────────────────────────────────────────────
    DB::table('gr_sync_ups')->where('created_by', $createdBy)->delete();
    $syncCount = 0;
    foreach ($employees->take(6) as $emp) {
        if ($emp->id === $manager->id) continue;
        foreach ([45, 20] as $daysAgo) {
            DB::table('gr_sync_ups')->insert([
                'cycle_id'         => $activeCycleId,
                'employee_id'      => $emp->id,
                'manager_id'       => $manager->id,
                'meeting_date'     => $now->copy()->subDays($daysAgo)->toDateString(),
                'notes'            => 'Discussed progress on Q2 goals and upcoming deliverables.',
                'discussion_points'=> json_encode(['Q2 goal progress','Blockers','Training needs','Career growth']),
                'action_items'     => json_encode([
                    ['task'=>'Complete React course','owner'=>$emp->name,'due_date'=>'2026-05-30','status'=>'pending'],
                    ['task'=>'Pair with senior on backend API','owner'=>$manager->name,'due_date'=>'2026-05-15','status'=>'in_progress'],
                ]),
                'status'     => $daysAgo > 30 ? 'completed' : 'scheduled',
                'created_by' => $createdBy,
                'created_at' => $now, 'updated_at' => $now,
            ]);
            $syncCount++;
        }
    }
    echo "Sync Ups: $syncCount\n";

    // ── 5. Comeback Plans ──────────────────────────────────────────
    DB::table('gr_comeback_plans')->where('created_by', $createdBy)->delete();
    $pipEmp = $employees->last();
    DB::table('gr_comeback_plans')->insert([
        'employee_id'  => $pipEmp->id,
        'assigned_by'  => $manager->id,
        'cycle_id'     => $activeCycleId,
        'title'        => '90-Day Performance Improvement Plan',
        'issues'       => 'Missed Q1 targets; quality of deliverables needs improvement; communication gaps with team.',
        'action_steps' => json_encode([
            ['step'=>'Weekly 1:1 with manager','deadline'=>'2026-06-30','status'=>'in_progress','notes'=>'Every Monday 10am'],
            ['step'=>'Complete advanced training module','deadline'=>'2026-05-31','status'=>'pending','notes'=>''],
            ['step'=>'Shadow senior engineer on 2 projects','deadline'=>'2026-06-15','status'=>'pending','notes'=>''],
        ]),
        'start_date' => '2026-04-01',
        'end_date'   => '2026-06-30',
        'status'     => 'on_track',
        'created_by' => $createdBy,
        'created_at' => $now, 'updated_at' => $now,
    ]);
    echo "Comeback Plans: 1 (emp=$pipEmp->name)\n";

    // ── 6. Reviews (multi-level) ───────────────────────────────────
    DB::table('gr_reviews')->where('created_by', $createdBy)->delete();
    $reviewCount = 0;
    $pickForReview = $employees->take(6); // only first 6 have reviews
    foreach ($pickForReview as $idx => $emp) {
        // self review
        $selfRating = [3.5, 4.0, 4.5, 3.0, 4.2, 3.8][$idx] ?? 3.5;
        DB::table('gr_reviews')->insert([
            'cycle_id'    => $activeCycleId,
            'employee_id' => $emp->id,
            'review_type' => 'self',
            'reviewer_id' => $emp->id,
            'rating'      => $selfRating,
            'strengths'   => 'Strong ownership, good communication with stakeholders, reliable delivery.',
            'improvements'=> 'Need to improve on documentation and time management.',
            'comments'    => 'Overall a good cycle, learned a lot from the team.',
            'status'      => 'submitted',
            'submitted_at'=> $now->copy()->subDays(20),
            'created_by'  => $createdBy,
            'created_at'  => $now, 'updated_at' => $now,
        ]);
        $reviewCount++;

        // manager review (only first 5)
        if ($idx < 5) {
            $mgrRating = max(1, min(5, $selfRating + (rand(-10,10)/10)));
            DB::table('gr_reviews')->insert([
                'cycle_id'    => $activeCycleId,
                'employee_id' => $emp->id,
                'review_type' => 'manager',
                'reviewer_id' => $manager->id,
                'rating'      => $mgrRating,
                'strengths'   => 'Consistent performer, good team player, meets deadlines.',
                'improvements'=> 'Could take more initiative on cross-team projects.',
                'comments'    => 'Solid contribution this cycle. Keep pushing for more ownership.',
                'status'      => 'submitted',
                'submitted_at'=> $now->copy()->subDays(15),
                'created_by'  => $createdBy,
                'created_at'  => $now, 'updated_at' => $now,
            ]);
            $reviewCount++;
        }

        // head review (only first 3)
        if ($idx < 3) {
            DB::table('gr_reviews')->insert([
                'cycle_id'    => $activeCycleId,
                'employee_id' => $emp->id,
                'review_type' => 'head',
                'reviewer_id' => $head->id,
                'rating'      => $selfRating - 0.2,
                'strengths'   => 'Demonstrates strong technical skills.',
                'improvements'=> 'Should mentor juniors more actively.',
                'comments'    => 'Endorsing manager rating with minor adjustment.',
                'status'      => 'submitted',
                'submitted_at'=> $now->copy()->subDays(10),
                'created_by'  => $createdBy,
                'created_at'  => $now, 'updated_at' => $now,
            ]);
            $reviewCount++;
        }
    }
    echo "Reviews: $reviewCount\n";

    // ── 7. Ratings (calibrated) ────────────────────────────────────
    DB::table('gr_ratings')->where('created_by', $createdBy)->delete();
    $ratingCount = 0;
    $grades = ['A+','A','B+','B','C'];
    foreach ($pickForReview as $idx => $emp) {
        $self = [3.5,4.0,4.5,3.0,4.2,3.8][$idx] ?? 3.5;
        $mgr  = $idx < 5 ? max(1, min(5, $self + 0.1)) : null;
        $head = $idx < 3 ? $self - 0.2 : null;
        $final = $idx < 3 ? round(($self + $mgr + $head) / 3, 1) : ($mgr ?? $self);
        $grade = $final >= 4.5 ? 'A+' : ($final >= 4.0 ? 'A' : ($final >= 3.5 ? 'B+' : ($final >= 3.0 ? 'B' : 'C')));
        $frozen = $idx < 2; // first 2 are frozen
        DB::table('gr_ratings')->insert([
            'cycle_id'      => $activeCycleId,
            'employee_id'   => $emp->id,
            'self_rating'   => $self,
            'manager_rating'=> $mgr,
            'head_rating'   => $head,
            'final_rating'  => $final,
            'grade'         => $grade,
            'is_calibrated' => $idx < 4,
            'is_frozen'     => $frozen,
            'calibration_notes' => $idx < 4 ? 'Calibrated in HR meeting on 2026-09-28.' : null,
            'calibrated_by' => $idx < 4 ? $createdBy : null,
            'frozen_at'     => $frozen ? $now : null,
            'created_by'    => $createdBy,
            'created_at'    => $now, 'updated_at' => $now,
        ]);
        $ratingCount++;
    }
    echo "Ratings: $ratingCount\n";

    // ── 8. Increments (only for frozen ratings) ────────────────────
    DB::table('gr_increments')->where('created_by', $createdBy)->delete();
    $incCount = 0;
    foreach ($pickForReview->take(2) as $emp) {
        $ratingRow = DB::table('gr_ratings')
            ->where('cycle_id', $activeCycleId)->where('employee_id', $emp->id)->first();
        $pct = $ratingRow->final_rating >= 4.3 ? 12 : ($ratingRow->final_rating >= 3.8 ? 8 : 5);
        $old = (float) $emp->salary;
        $new = round($old * (1 + $pct/100), 2);
        DB::table('gr_increments')->insert([
            'cycle_id'        => $activeCycleId,
            'employee_id'     => $emp->id,
            'rating_id'       => $ratingRow->id ?? null,
            'old_ctc'         => $old,
            'new_ctc'         => $new,
            'increment_pct'   => $pct,
            'increment_amount'=> $new - $old,
            'effective_date'  => '2026-10-01',
            'status'          => 'proposed',
            'synced_to_payroll' => false,
            'letter_generated'  => false,
            'remarks'         => "Based on $ratingRow->grade grade in $activeCycleId cycle.",
            'created_by'      => $createdBy,
            'created_at'      => $now, 'updated_at' => $now,
        ]);
        $incCount++;
    }
    echo "Increments: $incCount\n";

    DB::commit();
    echo "\n✓ Dummy data seeded successfully for cycle ID $activeCycleId (FY 2026-27 H1)\n";
} catch (\Throwable $e) {
    DB::rollBack();
    echo "\n✗ ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
