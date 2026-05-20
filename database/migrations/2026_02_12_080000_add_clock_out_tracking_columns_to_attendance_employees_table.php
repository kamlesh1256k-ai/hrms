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
            $table->string('device_type_out')->nullable()->after('photo'); // desktop, mobile, web for clock-out
            $table->string('latitude_out')->nullable()->after('device_type_out');
            $table->string('longitude_out')->nullable()->after('latitude_out');
            $table->text('address_out')->nullable()->after('longitude_out');
            $table->string('photo_out')->nullable()->after('address_out');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_employees', function (Blueprint $table) {
            $table->dropColumn(['device_type_out', 'latitude_out', 'longitude_out', 'address_out', 'photo_out']);
        });
    }
};
