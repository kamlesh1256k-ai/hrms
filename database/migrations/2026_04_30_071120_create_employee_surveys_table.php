<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_surveys', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['employee', 'pulse', 'enps'])->default('employee');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->enum('status', ['draft', 'active', 'closed'])->default('draft');
            $table->boolean('is_anonymous')->default(false);
            // null department_ids = all departments; otherwise CSV/JSON of dept ids
            $table->json('department_ids')->nullable();
            // For pulse surveys: scheduling
            $table->enum('frequency', ['once', 'weekly', 'monthly', 'custom'])->default('once');
            $table->timestamp('last_sent_at')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            $table->index(['created_by', 'status']);
            $table->index(['type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_surveys');
    }
};
