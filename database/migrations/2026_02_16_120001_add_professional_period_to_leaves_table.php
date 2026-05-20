<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddProfessionalPeriodToLeavesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('leaves', function (Blueprint $table) {
            // Add professional period columns if they don't exist
            if (!Schema::hasColumn('leaves', 'professional_days')) {
                $table->integer('professional_days')->default(0)->comment('Total days employee has been with company');
            }
            if (!Schema::hasColumn('leaves', 'professional_months')) {
                $table->integer('professional_months')->default(0)->comment('Total months employee has been with company');
            }
            if (!Schema::hasColumn('leaves', 'professional_years')) {
                $table->integer('professional_years')->default(0)->comment('Total years employee has been with company');
            }
            if (!Schema::hasColumn('leaves', 'calculated_at')) {
                $table->timestamp('calculated_at')->nullable()->comment('When professional period was calculated');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('leaves', function (Blueprint $table) {
            $table->dropColumn(['professional_days', 'professional_months', 'professional_years', 'calculated_at']);
        });
    }
}
