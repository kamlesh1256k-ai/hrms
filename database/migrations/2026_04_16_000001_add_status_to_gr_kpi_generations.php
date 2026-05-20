<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gr_kpi_generations', function (Blueprint $t) {
            $t->enum('status', ['draft', 'submitted'])->default('draft')->after('ai_mode');
            $t->timestamp('submitted_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('gr_kpi_generations', function (Blueprint $t) {
            $t->dropColumn(['status', 'submitted_at']);
        });
    }
};
