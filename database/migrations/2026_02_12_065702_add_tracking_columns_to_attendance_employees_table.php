<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('attendance_employees', function (Blueprint $table) {
            $table->string('device_type')->nullable()->after('created_by'); // desktop, mobile, web
            $table->string('latitude')->nullable()->after('device_type');
            $table->string('longitude')->nullable()->after('latitude');
            $table->text('address')->nullable()->after('longitude');
            $table->string('photo')->nullable()->after('address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_employees', function (Blueprint $table) {
            $table->dropColumn(['device_type', 'latitude', 'longitude', 'address', 'photo']);
        });
    }
};
