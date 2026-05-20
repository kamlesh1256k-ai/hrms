<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            if (!Schema::hasColumn('jobs', 'requisition_id')) {
                $table->unsignedBigInteger('requisition_id')->nullable()->after('id');
                $table->index('requisition_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            if (Schema::hasColumn('jobs', 'requisition_id')) {
                $table->dropIndex(['requisition_id']);
                $table->dropColumn('requisition_id');
            }
        });
    }
};
