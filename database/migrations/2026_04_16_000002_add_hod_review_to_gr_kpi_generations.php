<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gr_kpi_generations', function (Blueprint $t) {
            if (!Schema::hasColumn('gr_kpi_generations', 'manager_reviewed_at')) {
                $t->timestamp('manager_reviewed_at')->nullable()->after('submitted_at');
            }
            if (!Schema::hasColumn('gr_kpi_generations', 'hod_reviewed_at')) {
                $t->timestamp('hod_reviewed_at')->nullable()->after('manager_reviewed_at');
            }
        });

        // Extend the status enum to include manager_reviewed and hod_reviewed.
        DB::statement("ALTER TABLE gr_kpi_generations MODIFY status ENUM('draft','submitted','manager_reviewed','hod_reviewed') NOT NULL DEFAULT 'draft'");
    }

    public function down(): void
    {
        DB::statement("UPDATE gr_kpi_generations SET status = 'submitted' WHERE status IN ('manager_reviewed','hod_reviewed')");
        DB::statement("ALTER TABLE gr_kpi_generations MODIFY status ENUM('draft','submitted') NOT NULL DEFAULT 'draft'");

        Schema::table('gr_kpi_generations', function (Blueprint $t) {
            $t->dropColumn(['manager_reviewed_at', 'hod_reviewed_at']);
        });
    }
};
