<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            $table->string('day_type')->default('full_day')->after('end_date');
            $table->integer('substitute_employee_id')->nullable()->after('day_type');
            $table->string('substitute_status')->default('Pending')->after('substitute_employee_id');
            $table->string('substitute_token', 64)->nullable()->after('substitute_status');
            $table->timestamp('substitute_responded_at')->nullable()->after('substitute_token');
        });
    }

    public function down(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            $table->dropColumn([
                'day_type',
                'substitute_employee_id',
                'substitute_status',
                'substitute_token',
                'substitute_responded_at',
            ]);
        });
    }
};
