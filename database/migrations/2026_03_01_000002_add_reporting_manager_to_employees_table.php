<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReportingManagerToEmployeesTable extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('employees', 'reporting_manager_id')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->unsignedBigInteger('reporting_manager_id')->nullable()->after('department_hierarchy');
                $table->index('reporting_manager_id');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('employees', 'reporting_manager_id')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropIndex(['reporting_manager_id']);
                $table->dropColumn('reporting_manager_id');
            });
        }
    }
}
