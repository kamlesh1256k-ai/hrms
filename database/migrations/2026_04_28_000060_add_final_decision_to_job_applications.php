<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            if (!Schema::hasColumn('job_applications', 'final_status'))      $table->string('final_status', 20)->default('pending')->after('rating');
            if (!Schema::hasColumn('job_applications', 'final_rank'))        $table->unsignedTinyInteger('final_rank')->nullable()->after('final_status');
            if (!Schema::hasColumn('job_applications', 'final_notes'))       $table->text('final_notes')->nullable()->after('final_rank');
            if (!Schema::hasColumn('job_applications', 'final_decided_by'))  $table->unsignedBigInteger('final_decided_by')->nullable()->after('final_notes');
            if (!Schema::hasColumn('job_applications', 'final_decided_at'))  $table->timestamp('final_decided_at')->nullable()->after('final_decided_by');
        });
    }

    public function down(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            foreach (['final_status', 'final_rank', 'final_notes', 'final_decided_by', 'final_decided_at'] as $c) {
                if (Schema::hasColumn('job_applications', $c)) $table->dropColumn($c);
            }
        });
    }
};
