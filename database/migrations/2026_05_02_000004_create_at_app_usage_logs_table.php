<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Activity Tracker — aggregated app-usage spans.
 * The agent collapses consecutive same-app samples into one span and ships it
 * with started_at / ended_at. duration_seconds is denormalised for fast reports.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('at_app_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('device_id');
            $table->string('app_name', 200);
            $table->string('window_title', 500)->nullable();
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->timestamp('started_at')->index();
            $table->timestamp('ended_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'started_at']);
            $table->index(['device_id', 'started_at']);
            $table->index('app_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('at_app_usage_logs');
    }
};
