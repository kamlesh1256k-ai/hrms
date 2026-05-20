<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $t) {
            if (!Schema::hasColumn('employees', 'fingerprint_template')) {
                $t->text('fingerprint_template')->nullable();
            }
            if (!Schema::hasColumn('employees', 'fingerprint_enrolled_at')) {
                $t->timestamp('fingerprint_enrolled_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $t) {
            $t->dropColumn(['fingerprint_template', 'fingerprint_enrolled_at']);
        });
    }
};
