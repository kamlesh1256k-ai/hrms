<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('attendance_modification_request_logs')) {
            return;
        }

        Schema::create('attendance_modification_request_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('attendance_modification_request_id');
            $table->unsignedBigInteger('attendance_employee_id');
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->unsignedBigInteger('manager_employee_id')->nullable();
            $table->string('action', 30);
            $table->json('old_snapshot')->nullable();
            $table->json('new_snapshot')->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index('attendance_modification_request_id', 'amrl_request_idx');
            $table->index('attendance_employee_id', 'amrl_attendance_idx');
            $table->index('manager_employee_id', 'amrl_manager_idx');
            $table->index('action', 'amrl_action_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_modification_request_logs');
    }
};
