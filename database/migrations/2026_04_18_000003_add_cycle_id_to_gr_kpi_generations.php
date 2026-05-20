<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gr_kpi_generations', function (Blueprint $t) {
            if (!Schema::hasColumn('gr_kpi_generations', 'cycle_id')) {
                $t->unsignedBigInteger('cycle_id')->nullable()->after('id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('gr_kpi_generations', function (Blueprint $t) {
            $t->dropColumn('cycle_id');
        });
    }
};
