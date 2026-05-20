<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('employees') && !Schema::hasColumn('employees', 'shift_type')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->string('shift_type')->default('morning')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('employees') && Schema::hasColumn('employees', 'shift_type')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropColumn('shift_type');
            });
        }
    }
};
