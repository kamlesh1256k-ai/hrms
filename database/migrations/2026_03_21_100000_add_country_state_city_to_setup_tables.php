<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCountryStateCityToSetupTables extends Migration
{
    protected $tables = [
        'departments',
        'designations',
        'leave_types',
        'documents',
        'payslip_types',
        'allowance_options',
        'loan_options',
        'deduction_options',
        'goal_types',
        'trainers',
    ];

    public function up()
    {
        foreach ($this->tables as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (!Schema::hasColumn($tableName, 'country')) {
                    $table->string('country', 50)->nullable();
                }
                if (!Schema::hasColumn($tableName, 'state')) {
                    $table->string('state', 50)->nullable();
                }
                if (!Schema::hasColumn($tableName, 'city')) {
                    $table->string('city', 100)->nullable();
                }
            });
        }
    }

    public function down()
    {
        foreach ($this->tables as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (Schema::hasColumn($tableName, 'country')) {
                    $table->dropColumn('country');
                }
                if (Schema::hasColumn($tableName, 'state')) {
                    $table->dropColumn('state');
                }
                if (Schema::hasColumn($tableName, 'city')) {
                    $table->dropColumn('city');
                }
            });
        }
    }
}
