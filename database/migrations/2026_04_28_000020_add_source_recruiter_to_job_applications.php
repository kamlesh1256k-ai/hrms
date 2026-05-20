<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            if (!Schema::hasColumn('job_applications', 'source'))         $table->string('source', 60)->nullable()->after('phone');
            if (!Schema::hasColumn('job_applications', 'source_detail'))  $table->string('source_detail', 200)->nullable()->after('source');
            if (!Schema::hasColumn('job_applications', 'recruiter_id'))   $table->unsignedBigInteger('recruiter_id')->nullable()->after('source_detail');
        });
    }

    public function down(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            foreach (['source', 'source_detail', 'recruiter_id'] as $col) {
                if (Schema::hasColumn('job_applications', $col)) $table->dropColumn($col);
            }
        });
    }
};
