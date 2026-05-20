<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $t) {
            if (!Schema::hasColumn('employees', 'hod_id')) {
                $t->unsignedBigInteger('hod_id')->nullable()->after('reporting_manager_id');
            }
            if (!Schema::hasColumn('employees', 'management_id')) {
                $t->unsignedBigInteger('management_id')->nullable()->after('hod_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $t) {
            $t->dropColumn(['hod_id', 'management_id']);
        });
    }
};
