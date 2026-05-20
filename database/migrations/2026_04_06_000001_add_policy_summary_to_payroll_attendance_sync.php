<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_attendance_sync', function (Blueprint $table) {
            $table->json('policy_summary_json')->nullable()->after('early_half_day');
            // Effective counts (policy-adjusted)
            $table->decimal('present_effective', 5, 1)->default(0)->after('policy_summary_json');
            $table->decimal('leave_effective', 5, 1)->default(0)->after('present_effective');
            $table->decimal('absent_effective', 5, 1)->default(0)->after('leave_effective');
            $table->decimal('hd_deduction', 5, 1)->default(0)->after('absent_effective');
            $table->integer('weekly_offs')->default(0)->after('hd_deduction');
            $table->integer('month_total_days')->default(0)->after('weekly_offs');
        });
    }

    public function down(): void
    {
        Schema::table('payroll_attendance_sync', function (Blueprint $table) {
            $table->dropColumn([
                'policy_summary_json', 'present_effective', 'leave_effective',
                'absent_effective', 'hd_deduction', 'weekly_offs', 'month_total_days',
            ]);
        });
    }
};
