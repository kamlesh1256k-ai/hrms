<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->unsignedBigInteger('employee_type_id')->nullable()->after('salary_type');
            $table->decimal('monthly_stipend', 12, 2)->nullable()->after('employee_type_id');
            $table->index('employee_type_id');
        });

        // Backfill: every existing employee → Full-time
        $fullTimeId = DB::table('employee_types')->where('code', 'full_time')->value('id');
        if ($fullTimeId) {
            DB::table('employees')->whereNull('employee_type_id')->update(['employee_type_id' => $fullTimeId]);
        }
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex(['employee_type_id']);
            $table->dropColumn(['employee_type_id', 'monthly_stipend']);
        });
    }
};
