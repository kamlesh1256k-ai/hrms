<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_employees', function (Blueprint $table) {
            if (!Schema::hasColumn('attendance_employees', 'photo_verified')) {
                $table->boolean('photo_verified')->nullable()->after('photo');
            }
            if (!Schema::hasColumn('attendance_employees', 'photo_out_verified')) {
                $table->boolean('photo_out_verified')->nullable()->after('photo_out');
            }
        });
    }

    public function down(): void
    {
        Schema::table('attendance_employees', function (Blueprint $table) {
            if (Schema::hasColumn('attendance_employees', 'photo_verified')) {
                $table->dropColumn('photo_verified');
            }
            if (Schema::hasColumn('attendance_employees', 'photo_out_verified')) {
                $table->dropColumn('photo_out_verified');
            }
        });
    }
};
