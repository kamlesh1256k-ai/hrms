<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('payroll_special_deductions')) {
            Schema::create('payroll_special_deductions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('employee_id');
                $table->string('month', 7);
                $table->string('title', 120)->default('Penalty');
                $table->decimal('amount', 12, 2)->default(0);
                $table->string('remarks', 255)->nullable();
                $table->unsignedBigInteger('created_by');
                $table->timestamps();

                $table->index(['employee_id', 'month']);
                $table->unique(['employee_id', 'month', 'title', 'created_by'], 'payroll_special_deduction_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_special_deductions');
    }
};

