<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('attendance_modification_requests')) {
            return;
        }

        Schema::create('attendance_modification_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('attendance_employee_id');
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('manager_employee_id');
            $table->string('requested_status')->nullable();
            $table->time('requested_clock_in')->nullable();
            $table->time('requested_clock_out')->nullable();
            $table->text('reason')->nullable();
            $table->string('status')->default('Pending');
            $table->text('manager_comment')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index('attendance_employee_id', 'amr_attendance_idx');
            $table->index('employee_id', 'amr_employee_idx');
            $table->index('manager_employee_id', 'amr_manager_idx');
            $table->index('status', 'amr_status_idx');
            $table->index(['manager_employee_id', 'status'], 'amr_manager_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_modification_requests');
    }
};
