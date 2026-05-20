<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('employees') && !Schema::hasColumn('employees', 'shift_id')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->unsignedBigInteger('shift_id')->nullable();
                $table->index('shift_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('employees') && Schema::hasColumn('employees', 'shift_id')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropIndex(['shift_id']);
                $table->dropColumn('shift_id');
            });
        }
    }
};
