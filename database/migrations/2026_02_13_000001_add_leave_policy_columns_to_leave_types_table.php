<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLeavePolicyColumnsToLeaveTypesTable extends Migration
{
    public function up()
    {
        Schema::table('leave_types', function (Blueprint $table) {
            $table->decimal('monthly_credit', 5, 2)->nullable()->after('days');
            $table->decimal('annual_credit', 5, 2)->nullable()->after('monthly_credit');
            $table->string('approval_requirement')->default('na')->after('annual_credit');
        });
    }

    public function down()
    {
        Schema::table('leave_types', function (Blueprint $table) {
            $table->dropColumn(['monthly_credit', 'annual_credit', 'approval_requirement']);
        });
    }
}
