<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAddressCountryStateCityPincodeToEmployeesTable extends Migration
{
    public function up()
    {
        Schema::table('employees', function (Blueprint $table) {
            if (!Schema::hasColumn('employees', 'present_country')) {
                $table->string('present_country', 100)->nullable()->after('permanent_address');
            }
            if (!Schema::hasColumn('employees', 'present_state')) {
                $table->string('present_state', 100)->nullable()->after('present_country');
            }
            if (!Schema::hasColumn('employees', 'present_city')) {
                $table->string('present_city', 100)->nullable()->after('present_state');
            }
            if (!Schema::hasColumn('employees', 'present_pincode')) {
                $table->string('present_pincode', 20)->nullable()->after('present_city');
            }
            if (!Schema::hasColumn('employees', 'permanent_country')) {
                $table->string('permanent_country', 100)->nullable()->after('present_pincode');
            }
            if (!Schema::hasColumn('employees', 'permanent_state')) {
                $table->string('permanent_state', 100)->nullable()->after('permanent_country');
            }
            if (!Schema::hasColumn('employees', 'permanent_city')) {
                $table->string('permanent_city', 100)->nullable()->after('permanent_state');
            }
            if (!Schema::hasColumn('employees', 'permanent_pincode')) {
                $table->string('permanent_pincode', 20)->nullable()->after('permanent_city');
            }
        });
    }

    public function down()
    {
        Schema::table('employees', function (Blueprint $table) {
            $cols = [
                'present_country', 'present_state', 'present_city', 'present_pincode',
                'permanent_country', 'permanent_state', 'permanent_city', 'permanent_pincode',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('employees', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
}
