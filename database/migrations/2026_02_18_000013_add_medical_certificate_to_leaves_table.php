<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMedicalCertificateToLeavesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('leaves')) {
            Schema::table('leaves', function (Blueprint $table) {
                if (!Schema::hasColumn('leaves', 'medical_certificate')) {
                    $table->string('medical_certificate')->nullable()->after('leave_reason');
                }
                if (!Schema::hasColumn('leaves', 'certificate_verified')) {
                    $table->boolean('certificate_verified')->default(false)->after('medical_certificate');
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
                if (Schema::hasColumn('leaves', 'medical_certificate')) {
                    $table->dropColumn('medical_certificate');
                }
                if (Schema::hasColumn('leaves', 'certificate_verified')) {
                    $table->dropColumn('certificate_verified');
                }
            });
        }
    }
}
