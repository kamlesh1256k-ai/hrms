<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * audience_rules — JSON object holding eligibility filters that
     * mySurveys() applies on top of the existing department audience.
     * Supported keys (all optional):
     *   tenure_max_days : int  — only employees with DOJ within N days
     *   tenure_min_days : int  — only employees with DOJ ≥ N days ago
     *   include_inactive: bool — include is_active=0 employees (default false)
     */
    public function up(): void
    {
        Schema::table('employee_surveys', function (Blueprint $table) {
            $table->json('audience_rules')->nullable()->after('department_ids');
        });
    }

    public function down(): void
    {
        Schema::table('employee_surveys', function (Blueprint $table) {
            $table->dropColumn('audience_rules');
        });
    }
};
