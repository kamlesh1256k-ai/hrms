<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Performance Cycles ──────────────────────────────────────
        Schema::create('performance_cycles', function (Blueprint $table) {
            $table->id();
            $table->string('name');                          // e.g. "FY 2025-26 H1"
            $table->date('start_date');
            $table->date('end_date');
            $table->date('goal_deadline')->nullable();       // last date to set missions
            $table->date('self_review_start')->nullable();
            $table->date('self_review_end')->nullable();
            $table->date('manager_review_start')->nullable();
            $table->date('manager_review_end')->nullable();
            $table->date('head_review_start')->nullable();
            $table->date('head_review_end')->nullable();
            $table->date('calibration_start')->nullable();
            $table->date('calibration_end')->nullable();
            $table->enum('status', ['draft', 'active', 'review', 'calibration', 'completed'])->default('draft');
            $table->string('rating_scale', 20)->default('1-5');  // e.g. "1-5" or "1-10"
            $table->json('settings_json')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            $table->index(['created_by', 'status']);
        });

        // ── Missions (Goals) ────────────────────────────────────────
        Schema::create('gr_missions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cycle_id');
            $table->unsignedBigInteger('employee_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('kpi')->nullable();               // measurable KPI
            $table->decimal('weightage', 5, 2)->default(0);  // % weightage in overall rating
            $table->date('deadline')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->enum('approval', ['pending', 'approved', 'rejected'])->default('pending');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('manager_remarks')->nullable();
            $table->integer('progress')->default(0);          // 0-100%
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            $table->index(['cycle_id', 'employee_id']);
            $table->index(['employee_id', 'status']);
        });

        // ── Shoutouts (Peer Recognition) ────────────────────────────
        Schema::create('gr_shoutouts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('from_employee_id');
            $table->unsignedBigInteger('to_employee_id');
            $table->text('message');
            $table->string('badge', 50)->nullable();          // e.g. "star", "teamwork", "innovation"
            $table->unsignedBigInteger('cycle_id')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            $table->index(['to_employee_id']);
            $table->index(['from_employee_id']);
        });

        // ── Sync Ups (Check-in Meetings) ────────────────────────────
        Schema::create('gr_sync_ups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cycle_id')->nullable();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('manager_id');
            $table->date('meeting_date');
            $table->text('notes')->nullable();
            $table->json('discussion_points')->nullable();    // array of points
            $table->json('action_items')->nullable();          // [{task, owner, due_date, status}]
            $table->enum('status', ['scheduled', 'completed', 'cancelled'])->default('scheduled');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            $table->index(['employee_id', 'cycle_id']);
        });

        // ── Comeback Plans (PIPs) ───────────────────────────────────
        Schema::create('gr_comeback_plans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('assigned_by');        // HR or Manager
            $table->unsignedBigInteger('cycle_id')->nullable();
            $table->string('title');
            $table->text('issues')->nullable();                // identified problems
            $table->json('action_steps')->nullable();          // [{step, deadline, status, notes}]
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['active', 'on_track', 'at_risk', 'completed', 'failed'])->default('active');
            $table->text('final_remarks')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            $table->index(['employee_id', 'status']);
        });

        // ── Reviews (Multi-level) ───────────────────────────────────
        Schema::create('gr_reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cycle_id');
            $table->unsignedBigInteger('employee_id');
            $table->enum('review_type', ['self', 'manager', 'head', 'management']);
            $table->unsignedBigInteger('reviewer_id');        // who submitted this review
            $table->decimal('rating', 3, 1)->nullable();      // overall rating
            $table->json('ratings_json')->nullable();          // per-mission ratings [{mission_id, rating, comment}]
            $table->text('strengths')->nullable();
            $table->text('improvements')->nullable();
            $table->text('comments')->nullable();
            $table->enum('status', ['draft', 'submitted'])->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            $table->unique(['cycle_id', 'employee_id', 'review_type']);
            $table->index(['cycle_id', 'employee_id']);
        });

        // ── Ratings (Final calibrated) ──────────────────────────────
        Schema::create('gr_ratings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cycle_id');
            $table->unsignedBigInteger('employee_id');
            $table->decimal('self_rating', 3, 1)->nullable();
            $table->decimal('manager_rating', 3, 1)->nullable();
            $table->decimal('head_rating', 3, 1)->nullable();
            $table->decimal('final_rating', 3, 1)->nullable();
            $table->string('grade', 20)->nullable();          // A+, A, B+, B, C, etc.
            $table->boolean('is_calibrated')->default(false);
            $table->boolean('is_frozen')->default(false);
            $table->text('calibration_notes')->nullable();
            $table->unsignedBigInteger('calibrated_by')->nullable();
            $table->timestamp('frozen_at')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            $table->unique(['cycle_id', 'employee_id']);
        });

        // ── Increments (linked to cycle) ────────────────────────────
        Schema::create('gr_increments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cycle_id');
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('rating_id')->nullable();
            $table->decimal('old_ctc', 12, 2);
            $table->decimal('new_ctc', 12, 2);
            $table->decimal('increment_pct', 5, 2)->default(0);
            $table->decimal('increment_amount', 12, 2)->default(0);
            $table->date('effective_date');
            $table->enum('status', ['proposed', 'approved', 'applied', 'rejected'])->default('proposed');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->boolean('synced_to_payroll')->default(false);
            $table->boolean('letter_generated')->default(false);
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            $table->unique(['cycle_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gr_increments');
        Schema::dropIfExists('gr_ratings');
        Schema::dropIfExists('gr_reviews');
        Schema::dropIfExists('gr_comeback_plans');
        Schema::dropIfExists('gr_sync_ups');
        Schema::dropIfExists('gr_shoutouts');
        Schema::dropIfExists('gr_missions');
        Schema::dropIfExists('performance_cycles');
    }
};
