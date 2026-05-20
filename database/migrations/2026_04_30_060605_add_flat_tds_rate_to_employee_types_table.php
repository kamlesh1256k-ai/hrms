<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_types', function (Blueprint $table) {
            // Flat TDS rate as % (e.g. 10.00 for Consultant 194J).
            // Null/0 = use slab-based TDS pipeline.
            $table->decimal('flat_tds_rate', 5, 2)->default(0)->after('tds_applicable');
        });

        // Update Consultant: attendance-prorata + no CTC structure + flat 10% TDS
        DB::table('employee_types')->where('code', 'consultant')->update([
            'ctc_applicable'     => 0,
            'attendance_prorata' => 1,
            'tds_applicable'     => 1,
            'flat_tds_rate'      => 10.00,
            'description'        => 'Contract professional. Attendance-based monthly retainer with flat 10% TDS (Sec 194J). No PF/ESIC/PT/LWF.',
        ]);
    }

    public function down(): void
    {
        Schema::table('employee_types', function (Blueprint $table) {
            $table->dropColumn('flat_tds_rate');
        });

        DB::table('employee_types')->where('code', 'consultant')->update([
            'ctc_applicable'     => 1,
            'attendance_prorata' => 0,
        ]);
    }
};
