<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Grievance & GrievanceResponse models use the SoftDeletes trait but the
 * original migrations didn't add deleted_at — every list query exploded with
 * "Unknown column 'grievances.deleted_at'". This migration adds it.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('grievances') && !Schema::hasColumn('grievances', 'deleted_at')) {
            Schema::table('grievances', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        if (Schema::hasTable('grievance_responses') && !Schema::hasColumn('grievance_responses', 'deleted_at')) {
            Schema::table('grievance_responses', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('grievances', 'deleted_at')) {
            Schema::table('grievances', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
        if (Schema::hasColumn('grievance_responses', 'deleted_at')) {
            Schema::table('grievance_responses', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
