<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('attendance_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->time('office_start_time');
            $table->time('office_end_time');
            $table->integer('break_duration'); // minutes
            $table->integer('minimum_working_hours'); // minutes
            $table->integer('late_entry_grace_time'); // minutes
            $table->integer('early_exit_grace_time'); // minutes
            $table->integer('monthly_allowed_late_count');
            $table->enum('late_rule_action', ['half_day', 'deduct_leave']);
            $table->integer('late_rule_leave_deduction_count')->default(3); // e.g., after every 3 late marks
            $table->integer('created_by');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('attendance_settings');
    }
};