<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Exit Management module — main resignation record.
 *
 * Named "exit_resignations" (not "resignations") because the legacy
 * resignations table is still in use by the older ResignationController.
 * This module is a fresh, workflow-driven replacement.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exit_resignations', function (Blueprint $table) {
            $table->id();

            // The resigning employee (User row) and tenant scope
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('created_by');

            $table->text('reason');
            $table->date('resignation_date');
            $table->date('last_working_day');
            $table->unsignedSmallInteger('notice_period_days')->nullable();

            // Workflow status
            $table->enum('status', [
                'pending',
                'manager_approved',
                'manager_rejected',
                'hr_approved',
                'hr_rejected',
                'completed',
            ])->default('pending');

            // Manager review
            $table->unsignedBigInteger('manager_id')->nullable();
            $table->timestamp('manager_action_at')->nullable();
            $table->text('manager_note')->nullable();

            // HR review
            $table->unsignedBigInteger('hr_id')->nullable();
            $table->timestamp('hr_action_at')->nullable();
            $table->text('hr_note')->nullable();

            // Set when checklist + FNF are done and HR clicks "Mark Complete"
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->index(['created_by', 'status']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exit_resignations');
    }
};
