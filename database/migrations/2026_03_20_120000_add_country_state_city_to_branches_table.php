<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCountryStateCityToBranchesTable extends Migration
{
    public function up()
    {
        Schema::table('branches', function (Blueprint $table) {
            if (!Schema::hasColumn('branches', 'country')) {
                $table->string('country', 50)->nullable()->after('name');
            }
            if (!Schema::hasColumn('branches', 'state')) {
                $table->string('state', 50)->nullable()->after('country');
            }
            if (!Schema::hasColumn('branches', 'city')) {
                $table->string('city', 100)->nullable()->after('state');
            }
        });
    }

    public function down()
    {
        Schema::table('branches', function (Blueprint $table) {
            if (Schema::hasColumn('branches', 'country')) {
                $table->dropColumn('country');
            }
            if (Schema::hasColumn('branches', 'state')) {
                $table->dropColumn('state');
            }
            if (Schema::hasColumn('branches', 'city')) {
                $table->dropColumn('city');
            }
        });
    }
}
