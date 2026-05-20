<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('background_screenshots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('screenshot_path');
            $table->string('page_url', 500)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('captured_at');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'captured_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('background_screenshots');
    }
};
