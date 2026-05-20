<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_salaries', function (Blueprint $table) {
            if (!Schema::hasColumn('employee_salaries', 'overtime_enabled')) {
                $table->boolean('overtime_enabled')->default(false)->after('is_esic_enabled');
            }
            if (!Schema::hasColumn('employee_salaries', 'overtime_formula')) {
                $table->string('overtime_formula', 30)->default('basic')->after('overtime_enabled');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employee_salaries', function (Blueprint $table) {
            if (Schema::hasColumn('employee_salaries', 'overtime_formula')) {
                $table->dropColumn('overtime_formula');
            }
            if (Schema::hasColumn('employee_salaries', 'overtime_enabled')) {
                $table->dropColumn('overtime_enabled');
            }
        });
    }
};
