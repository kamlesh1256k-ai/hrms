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
        Schema::table('attendance_employees', function (Blueprint $table) {
            if (!Schema::hasColumn('attendance_employees', 'facial_verification_photo')) {
                $table->string('facial_verification_photo')->nullable()->after('total_rest');
            }
            if (!Schema::hasColumn('attendance_employees', 'facial_verification_status')) {
                $table->enum('facial_verification_status', ['pending', 'passed', 'failed'])->default('pending')->after('facial_verification_photo');
            }
            if (!Schema::hasColumn('attendance_employees', 'facial_verification_confidence')) {
                $table->decimal('facial_verification_confidence', 5, 2)->default(0)->after('facial_verification_status');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('attendance_employees', function (Blueprint $table) {
            if (Schema::hasColumn('attendance_employees', 'facial_verification_photo')) {
                $table->dropColumn('facial_verification_photo');
            }
            if (Schema::hasColumn('attendance_employees', 'facial_verification_status')) {
                $table->dropColumn('facial_verification_status');
            }
            if (Schema::hasColumn('attendance_employees', 'facial_verification_confidence')) {
                $table->dropColumn('facial_verification_confidence');
            }
        });
    }
};