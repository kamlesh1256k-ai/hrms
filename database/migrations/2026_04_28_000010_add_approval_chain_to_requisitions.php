<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recruitment_requisitions', function (Blueprint $table) {
            if (!Schema::hasColumn('recruitment_requisitions', 'approval_chain')) {
                // Comma-separated roles in approval order: e.g. "hr,finance"
                $table->string('approval_chain', 200)->nullable()->after('status');
            }
            if (!Schema::hasColumn('recruitment_requisitions', 'current_approval_step')) {
                // 0 = waiting for first approver, 1 = waiting for next, etc.
                $table->unsignedTinyInteger('current_approval_step')->default(0)->after('approval_chain');
            }
        });

        // Drop the old enum so we can extend it (MySQL enum modify is awkward)
        Schema::table('recruitment_requisition_approvals', function (Blueprint $table) {
            $table->string('action_v2', 20)->nullable()->after('action');
        });
        \DB::statement("UPDATE recruitment_requisition_approvals SET action_v2 = action");
        Schema::table('recruitment_requisition_approvals', function (Blueprint $table) {
            $table->dropColumn('action');
        });
        Schema::table('recruitment_requisition_approvals', function (Blueprint $table) {
            $table->renameColumn('action_v2', 'action');
        });
    }

    public function down(): void
    {
        Schema::table('recruitment_requisitions', function (Blueprint $table) {
            if (Schema::hasColumn('recruitment_requisitions', 'approval_chain')) $table->dropColumn('approval_chain');
            if (Schema::hasColumn('recruitment_requisitions', 'current_approval_step')) $table->dropColumn('current_approval_step');
        });
    }
};
