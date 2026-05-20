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
            $cols = [
                'device_type' => 'string', 'latitude' => 'string', 'longitude' => 'string',
                'address' => 'text', 'photo' => 'string',
                'device_type_out' => 'string', 'latitude_out' => 'string', 'longitude_out' => 'string',
                'address_out' => 'text', 'photo_out' => 'string',
            ];
            foreach ($cols as $col => $type) {
                if (!Schema::hasColumn('attendance_employees', $col)) {
                    $table->{$type}($col)->nullable();
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_employees', function (Blueprint $table) {
            $columns = ['device_type', 'latitude', 'longitude', 'address', 'photo', 
                       'device_type_out', 'latitude_out', 'longitude_out', 'address_out', 'photo_out'];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('attendance_employees', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
