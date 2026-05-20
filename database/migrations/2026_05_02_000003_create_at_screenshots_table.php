<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Activity Tracker — screenshot archive.
 * image_path is relative to storage/app/public (so Storage::url() works).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('at_screenshots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('device_id');
            $table->string('image_path', 300);
            $table->string('active_app', 200)->nullable();
            $table->string('active_window_title', 500)->nullable();
            $table->unsignedInteger('size_bytes')->default(0);
            $table->timestamp('captured_at')->index();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'captured_at']);
            $table->index(['device_id', 'captured_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('at_screenshots');
    }
};
