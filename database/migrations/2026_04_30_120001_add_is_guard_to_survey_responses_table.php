<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('survey_responses', 'is_guard')) {
            Schema::table('survey_responses', function (Blueprint $table) {
                $table->boolean('is_guard')->default(false)->after('is_anonymous');
                $table->index('is_guard');
            });
        }

        // Backfill: anonymous "guard" rows are the ones that (a) are anonymous,
        // (b) have an employee_id, and (c) have no answers.
        try {
            DB::statement("
                UPDATE survey_responses sr
                LEFT JOIN survey_answers sa ON sa.response_id = sr.id
                SET sr.is_guard = 1
                WHERE sr.is_anonymous = 1
                  AND sr.employee_id IS NOT NULL
                  AND sa.id IS NULL
            ");
        } catch (\Throwable $e) {
            // best-effort (SQLite / older MySQL might not support this exact SQL)
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('survey_responses', 'is_guard')) {
            Schema::table('survey_responses', function (Blueprint $table) {
                $table->dropIndex(['is_guard']);
                $table->dropColumn('is_guard');
            });
        }
    }
};
