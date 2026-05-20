<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPolicyColumnsToAttendanceEmployeesTable extends Migration
{
    public function up()
    {
        Schema::table('attendance_employees', function (Blueprint $table) {
            $table->boolean('late_mark')->default(false)->after('late');
            $table->boolean('early_mark')->default(false)->after('early_leaving');
            $table->boolean('less_hours_mark')->default(false)->after('overtime');
            $table->decimal('deduction_units', 3, 1)->default(0)->after('total_rest');
        });
    }

    public function down()
    {
        Schema::table('attendance_employees', function (Blueprint $table) {
            $table->dropColumn(['late_mark', 'early_mark', 'less_hours_mark', 'deduction_units']);
        });
    }
}
