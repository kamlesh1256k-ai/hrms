<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();          // e.g. 'full_time', 'consultant'
            $table->string('name');                         // human label
            $table->text('description')->nullable();

            // Rule flags — drive payroll behavior
            $table->boolean('ctc_applicable')->default(true);      // false = use monthly_stipend instead of CTC
            $table->boolean('pf_applicable')->default(true);
            $table->boolean('esic_applicable')->default(true);
            $table->boolean('pt_applicable')->default(true);
            $table->boolean('lwf_applicable')->default(true);
            $table->boolean('tds_applicable')->default(true);
            $table->boolean('attendance_prorata')->default(false); // true = stipend × (present/total)

            $table->boolean('is_system')->default(false);   // seeded defaults — cannot be deleted
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        // Seed the 5 default types
        DB::table('employee_types')->insert([
            [
                'code' => 'full_time', 'name' => 'Full-time',
                'description' => 'Permanent employee with full statutory compliance and CTC structure.',
                'ctc_applicable' => 1, 'pf_applicable' => 1, 'esic_applicable' => 1, 'pt_applicable' => 1, 'lwf_applicable' => 1, 'tds_applicable' => 1, 'attendance_prorata' => 0,
                'is_system' => 1, 'is_active' => 1, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'code' => 'part_time', 'name' => 'Part-time',
                'description' => 'Part-time employee with statutory compliance and prorated CTC.',
                'ctc_applicable' => 1, 'pf_applicable' => 1, 'esic_applicable' => 1, 'pt_applicable' => 1, 'lwf_applicable' => 1, 'tds_applicable' => 1, 'attendance_prorata' => 0,
                'is_system' => 1, 'is_active' => 1, 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'code' => 'consultant', 'name' => 'Consultant / Retainer / Professional',
                'description' => 'Contract professional. Only TDS deduction, no PF/ESIC/PT/LWF.',
                'ctc_applicable' => 1, 'pf_applicable' => 0, 'esic_applicable' => 0, 'pt_applicable' => 0, 'lwf_applicable' => 0, 'tds_applicable' => 1, 'attendance_prorata' => 0,
                'is_system' => 1, 'is_active' => 1, 'sort_order' => 3, 'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'code' => 'mgmt_trainee', 'name' => 'Management Trainee',
                'description' => 'Management trainee with full statutory compliance and CTC structure.',
                'ctc_applicable' => 1, 'pf_applicable' => 1, 'esic_applicable' => 1, 'pt_applicable' => 1, 'lwf_applicable' => 1, 'tds_applicable' => 1, 'attendance_prorata' => 0,
                'is_system' => 1, 'is_active' => 1, 'sort_order' => 4, 'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'code' => 'intern', 'name' => 'Intern / Stipend',
                'description' => 'Intern paid a fixed monthly stipend, prorated by attendance. No statutory deductions.',
                'ctc_applicable' => 0, 'pf_applicable' => 0, 'esic_applicable' => 0, 'pt_applicable' => 0, 'lwf_applicable' => 0, 'tds_applicable' => 0, 'attendance_prorata' => 1,
                'is_system' => 1, 'is_active' => 1, 'sort_order' => 5, 'created_at' => now(), 'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_types');
    }
};
