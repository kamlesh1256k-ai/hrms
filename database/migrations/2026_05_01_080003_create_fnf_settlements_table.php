<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fnf_settlements', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('resignation_id')->unique();
            $table->unsignedBigInteger('user_id');

            // Earnings
            $table->decimal('pending_salary',     14, 2)->default(0);
            $table->decimal('leave_encashment',   14, 2)->default(0);
            $table->decimal('gratuity',           14, 2)->default(0);
            $table->decimal('bonus',              14, 2)->default(0);
            $table->decimal('other_earnings',     14, 2)->default(0);
            $table->decimal('total_amount',       14, 2)->default(0);

            // Deductions
            $table->decimal('notice_recovery',    14, 2)->default(0);
            $table->decimal('asset_recovery',     14, 2)->default(0);
            $table->decimal('tax_deduction',      14, 2)->default(0);
            $table->decimal('other_deductions',   14, 2)->default(0);
            $table->decimal('deductions',         14, 2)->default(0);

            // Net
            $table->decimal('final_amount',       14, 2)->default(0);

            $table->enum('status', ['draft', 'finalised', 'paid'])->default('draft');

            $table->text('remarks')->nullable();
            $table->date('paid_on')->nullable();
            $table->unsignedBigInteger('processed_by')->nullable();

            $table->timestamps();

            $table->foreign('resignation_id')
                ->references('id')->on('exit_resignations')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fnf_settlements');
    }
};
