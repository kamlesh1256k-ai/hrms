<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Activity Tracker — pre-aggregated per-user-per-device per-day rollup.
 * Re-computed by ActivityTrackerSummaryService whenever new data lands for a
 * day; the dashboard reads from this table for fast trend charts.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('at_daily_summaries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('device_id');
            $table->date('work_date');
            $table->unsignedInteger('active_seconds')->default(0);
            $table->unsignedInteger('idle_seconds')->default(0);
            $table->unsignedInteger('total_screenshots')->default(0);
            $table->string('most_used_app', 200)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'device_id', 'work_date'], 'uniq_at_summary');
            $table->index('work_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('at_daily_summaries');
    }
};
