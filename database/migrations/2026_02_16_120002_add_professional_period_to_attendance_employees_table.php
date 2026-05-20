<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddProfessionalPeriodToAttendanceEmployeesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('attendance_employees', function (Blueprint $table) {
            // Add professional period reference columns if they don't exist
            if (!Schema::hasColumn('attendance_employees', 'professional_days_at_attendance')) {
                $table->integer('professional_days_at_attendance')->default(0)->comment('Days employed at time of attendance');
            }
            if (!Schema::hasColumn('attendance_employees', 'professional_months_at_attendance')) {
                $table->integer('professional_months_at_attendance')->default(0)->comment('Months employed at time of attendance');
            }
            if (!Schema::hasColumn('attendance_employees', 'professional_years_at_attendance')) {
                $table->integer('professional_years_at_attendance')->default(0)->comment('Years employed at time of attendance');
            }
            if (!Schema::hasColumn('attendance_employees', 'in_probation_at_attendance')) {
                $table->boolean('in_probation_at_attendance')->default(false)->comment('Was employee in probation at time of attendance');
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
        Schema::table('attendance_employees', function (Blueprint $table) {
            $table->dropColumn([
                'professional_days_at_attendance',
                'professional_months_at_attendance',
                'professional_years_at_attendance',
                'in_probation_at_attendance'
            ]);
        });
    }
}
