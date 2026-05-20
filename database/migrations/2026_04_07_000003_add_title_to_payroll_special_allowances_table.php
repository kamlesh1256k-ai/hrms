<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payroll_special_allowances') && !Schema::hasColumn('payroll_special_allowances', 'title')) {
            Schema::table('payroll_special_allowances', function (Blueprint $table) {
                $table->string('title', 120)->default('Bonus')->after('month');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('payroll_special_allowances') && Schema::hasColumn('payroll_special_allowances', 'title')) {
            Schema::table('payroll_special_allowances', function (Blueprint $table) {
                $table->dropColumn('title');
            });
        }
    }
};

