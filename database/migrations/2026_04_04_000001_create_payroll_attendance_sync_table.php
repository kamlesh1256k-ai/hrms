<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_attendance_sync', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->string('month', 7); // YYYY-MM
            $table->integer('working_days')->default(0);
            $table->integer('present')->default(0);
            $table->integer('half_day')->default(0);
            $table->integer('absent')->default(0);
            $table->integer('leave')->default(0);
            $table->integer('late_marks')->default(0);
            $table->integer('early_marks')->default(0);
            $table->float('deduction_units')->default(0);
            $table->integer('early_half_day')->default(0);
            $table->json('details_json')->nullable(); // full breakdown per date
            $table->unsignedBigInteger('synced_by');
            $table->timestamp('synced_at')->useCurrent();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->unique(['employee_id', 'month', 'created_by']);
            $table->index(['month', 'created_by']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_attendance_sync');
    }
};
