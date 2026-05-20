<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_increment_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->decimal('old_ctc', 15, 2);
            $table->decimal('new_ctc', 15, 2);
            $table->decimal('increment_amount', 15, 2);       // new_ctc - old_ctc
            $table->decimal('increment_percentage', 8, 2);     // % increase
            $table->date('effective_date');                     // when increment takes effect (e.g. 2025-04-01)
            $table->string('arrears_month', 7)->nullable();    // month in which arrears should be paid (e.g. 2025-08) NULL = no arrears
            $table->boolean('arrears_paid')->default(false);   // has arrears been included in payroll
            $table->decimal('arrears_amount', 15, 2)->default(0); // calculated arrears amount
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->index('employee_id');
            $table->index('arrears_month');
            $table->index('effective_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_increment_history');
    }
};
