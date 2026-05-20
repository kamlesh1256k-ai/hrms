<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('leaves')) {
            Schema::table('leaves', function (Blueprint $table) {
                if (!Schema::hasColumn('leaves', 'is_compensatory')) {
                    $table->boolean('is_compensatory')->default(false)->after('leave_reason');
                }
                if (!Schema::hasColumn('leaves', 'compensatory_leave_id')) {
                    $table->bigInteger('compensatory_leave_id')->nullable()->after('is_compensatory');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('leaves')) {
            Schema::table('leaves', function (Blueprint $table) {
                if (Schema::hasColumn('leaves', 'is_compensatory')) {
                    $table->dropColumn('is_compensatory');
                }
                if (Schema::hasColumn('leaves', 'compensatory_leave_id')) {
                    $table->dropColumn('compensatory_leave_id');
                }
            });
        }
    }
};