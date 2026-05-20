<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gr_increments', function (Blueprint $t) {
            if (!Schema::hasColumn('gr_increments', 'proposed_by')) {
                $t->unsignedBigInteger('proposed_by')->nullable()->after('approved_by');
            }
            if (!Schema::hasColumn('gr_increments', 'proposed_at')) {
                $t->timestamp('proposed_at')->nullable()->after('proposed_by');
            }
        });

        DB::statement("ALTER TABLE gr_increments MODIFY status ENUM('proposed','manager_proposed','approved','applied','rejected') NOT NULL DEFAULT 'proposed'");
    }

    public function down(): void
    {
        DB::statement("UPDATE gr_increments SET status = 'proposed' WHERE status = 'manager_proposed'");
        DB::statement("ALTER TABLE gr_increments MODIFY status ENUM('proposed','approved','applied','rejected') NOT NULL DEFAULT 'proposed'");

        Schema::table('gr_increments', function (Blueprint $t) {
            $t->dropColumn(['proposed_by', 'proposed_at']);
        });
    }
};
