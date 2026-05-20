<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gr_missions', function (Blueprint $t) {
            if (!Schema::hasColumn('gr_missions', 'self_rating')) {
                $t->decimal('self_rating', 3, 1)->nullable()->after('progress');
                $t->text('self_remarks')->nullable()->after('self_rating');
                $t->decimal('manager_rating', 3, 1)->nullable()->after('self_remarks');
                $t->text('manager_rating_remarks')->nullable()->after('manager_rating');
                $t->decimal('hod_rating', 3, 1)->nullable()->after('manager_rating_remarks');
                $t->text('hod_rating_remarks')->nullable()->after('hod_rating');
            }
        });
    }

    public function down(): void
    {
        Schema::table('gr_missions', function (Blueprint $t) {
            $t->dropColumn(['self_rating', 'self_remarks', 'manager_rating', 'manager_rating_remarks', 'hod_rating', 'hod_rating_remarks']);
        });
    }
};
