<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('policies', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            // Free-form string so future categories don't require a migration.
            // The form/UI will offer the standard list (HR / Leave / IT / Conduct / Other).
            $table->string('category', 50)->default('hr');
            $table->text('description')->nullable();

            // File metadata
            $table->string('file_path');                           // relative to storage/app/public
            $table->string('file_name', 255);                      // original upload name
            $table->string('file_mime', 100)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();   // bytes

            $table->string('version', 20)->default('1.0');
            // When true, employees must acknowledge to proceed (used by the
            // optional "must acknowledge before next action" gate).
            $table->boolean('is_mandatory')->default(false);
            $table->enum('status', ['active', 'archived'])->default('active');

            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->index(['created_by', 'status']);
            $table->index(['category', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('policies');
    }
};
