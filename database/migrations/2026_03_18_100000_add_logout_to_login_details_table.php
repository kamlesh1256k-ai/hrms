<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('login_details', function (Blueprint $table) {
            $table->dateTime('logout_at')->nullable()->after('date');
            $table->string('logout_selfie', 500)->nullable()->after('selfie_image');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('login_details', function (Blueprint $table) {
            $table->dropColumn(['logout_at', 'logout_selfie']);
        });
    }
};
