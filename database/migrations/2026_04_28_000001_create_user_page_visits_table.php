<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_page_visits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('tab_id', 64)->nullable();
            $table->string('url', 500);
            $table->string('page_title', 300)->nullable();
            $table->timestamp('started_at');
            $table->timestamp('last_seen_at');
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->unsignedInteger('focus_seconds')->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->foreign('user_id', 'upv_user_fk')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'started_at'], 'upv_user_started_idx');
            $table->index(['user_id', 'is_active'],  'upv_user_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_page_visits');
    }
};
