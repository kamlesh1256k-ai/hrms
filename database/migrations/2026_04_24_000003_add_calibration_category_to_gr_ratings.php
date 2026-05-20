<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gr_ratings', function (Blueprint $t) {
            if (!Schema::hasColumn('gr_ratings', 'calibration_category')) {
                $t->enum('calibration_category', ['Outstanding', 'Exceeds', 'Meets', 'Low'])
                  ->nullable()->after('grade');
                $t->index('calibration_category');
            }
        });
    }

    public function down(): void
    {
        Schema::table('gr_ratings', function (Blueprint $t) {
            $t->dropColumn('calibration_category');
        });
    }
};
