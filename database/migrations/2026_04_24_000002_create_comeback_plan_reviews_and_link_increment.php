<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Link a plan back to the 0% increment that triggered it (for auto-init
        // traceability and to prevent duplicate auto-creations).
        Schema::table('gr_comeback_plans', function (Blueprint $t) {
            if (!Schema::hasColumn('gr_comeback_plans', 'increment_id')) {
                $t->unsignedBigInteger('increment_id')->nullable()->after('cycle_id');
                $t->index('increment_id');
            }
            if (!Schema::hasColumn('gr_comeback_plans', 'auto_initiated')) {
                $t->boolean('auto_initiated')->default(false)->after('status');
            }
            if (!Schema::hasColumn('gr_comeback_plans', 'final_outcome')) {
                // final_remarks already exists — this is the structured outcome
                $t->enum('final_outcome', ['pending', 'success', 'failed', 'extended'])
                  ->default('pending')->after('final_remarks');
            }
            if (!Schema::hasColumn('gr_comeback_plans', 'outcome_decided_at')) {
                $t->timestamp('outcome_decided_at')->nullable()->after('final_outcome');
            }
        });

        // Periodic reviews attached to a plan (weekly/monthly check-ins).
        if (!Schema::hasTable('gr_comeback_plan_reviews')) {
            Schema::create('gr_comeback_plan_reviews', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('plan_id');
                $t->unsignedBigInteger('reviewer_id'); // employee id of reviewer
                $t->date('review_date');
                $t->enum('progress', ['on_track', 'at_risk', 'off_track'])->default('on_track');
                $t->unsignedTinyInteger('rating')->nullable(); // 1-5
                $t->text('strengths')->nullable();
                $t->text('improvements')->nullable();
                $t->text('comments')->nullable();
                $t->unsignedBigInteger('created_by');
                $t->timestamps();
                $t->index(['plan_id', 'review_date']);
                $t->foreign('plan_id')->references('id')->on('gr_comeback_plans')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('gr_comeback_plan_reviews');
        Schema::table('gr_comeback_plans', function (Blueprint $t) {
            $t->dropColumn(['increment_id', 'auto_initiated', 'final_outcome', 'outcome_decided_at']);
        });
    }
};
