<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedInteger('active_seconds')->default(0);
            $table->unsignedInteger('idle_seconds')->default(0);
            $table->unsignedInteger('keystrokes')->default(0);
            $table->unsignedInteger('mouse_events')->default(0);
            $table->string('active_window', 500)->nullable();
            $table->string('active_app', 200)->nullable();
            $table->string('active_url', 500)->nullable();
            $table->unsignedTinyInteger('productivity_score')->nullable();
            $table->string('hostname', 200)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('captured_at');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'captured_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_activities');
    }
};
