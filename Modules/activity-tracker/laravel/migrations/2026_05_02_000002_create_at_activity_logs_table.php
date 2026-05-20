<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Activity Tracker — sampled activity ticks (default every 30s from agent).
 * Stores the active app/window + idle seconds + keyboard/mouse counts since
 * the last sample. captured_at is the agent's wall-clock time.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('at_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('device_id');
            $table->string('active_app', 200)->nullable();
            $table->string('active_window_title', 500)->nullable();
            $table->unsignedInteger('idle_seconds')->default(0);
            $table->unsignedInteger('keyboard_count')->default(0);
            $table->unsignedInteger('mouse_count')->default(0);
            $table->timestamp('captured_at')->index();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'captured_at']);
            $table->index(['device_id', 'captured_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('at_activity_logs');
    }
};
