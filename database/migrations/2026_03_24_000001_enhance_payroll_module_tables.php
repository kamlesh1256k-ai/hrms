<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EnhancePayrollModuleTables extends Migration
{
    public function up()
    {
        if (Schema::hasTable('salary_components')) {
            Schema::table('salary_components', function (Blueprint $table) {
                if (!Schema::hasColumn('salary_components', 'category')) {
                    $table->enum('category', ['earning', 'deduction', 'benefit', 'reimbursement'])
                        ->default('earning')
                        ->after('name');
                }
                if (!Schema::hasColumn('salary_components', 'max_limit')) {
                    $table->decimal('max_limit', 15, 2)->nullable()->after('formula');
                }
                if (!Schema::hasColumn('salary_components', 'is_taxable')) {
                    $table->boolean('is_taxable')->default(true)->after('max_limit');
                }
                if (!Schema::hasColumn('salary_components', 'is_pf_applicable')) {
                    $table->boolean('is_pf_applicable')->default(false)->after('is_taxable');
                }
                if (!Schema::hasColumn('salary_components', 'is_esic_applicable')) {
                    $table->boolean('is_esic_applicable')->default(false)->after('is_pf_applicable');
                }
                if (!Schema::hasColumn('salary_components', 'frequency')) {
                    $table->enum('frequency', ['monthly', 'yearly', 'one-time'])->default('monthly')->after('is_esic_applicable');
                }
            });
        }

        if (!Schema::hasTable('pay_schedule')) {
            Schema::create('pay_schedule', function (Blueprint $table) {
                $table->id();
                $table->enum('pay_frequency', ['monthly'])->default('monthly');
                $table->unsignedTinyInteger('pay_day')->default(27);
                $table->string('working_days', 30)->default('mon,tue,wed,thu,fri,sat');
                $table->string('start_month', 7)->nullable();
                $table->boolean('status')->default(true);
                $table->boolean('is_locked')->default(false);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('payroll')) {
            Schema::create('payroll', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('employee_id');
                $table->string('month', 7); // YYYY-MM
                $table->json('earnings_json')->nullable();
                $table->json('deductions_json')->nullable();
                $table->json('benefits_json')->nullable();
                $table->json('reimbursements_json')->nullable();
                $table->decimal('gross_salary', 15, 2)->default(0);
                $table->decimal('total_deductions', 15, 2)->default(0);
                $table->decimal('employer_contribution', 15, 2)->default(0);
                $table->decimal('net_salary', 15, 2)->default(0);
                $table->boolean('is_locked')->default(false);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->index(['employee_id', 'month']);
                $table->unique(['employee_id', 'month']);
            });
        }

        if (!Schema::hasTable('reimbursement_claims')) {
            Schema::create('reimbursement_claims', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('employee_id');
                $table->unsignedBigInteger('component_id');
                $table->string('claim_month', 7);
                $table->decimal('amount', 15, 2);
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
                $table->string('remarks')->nullable();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->index(['employee_id', 'claim_month', 'status']);
            });
        }

        if (!Schema::hasTable('payroll_audit_logs')) {
            Schema::create('payroll_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('action', 120);
                $table->string('entity_type', 120);
                $table->unsignedBigInteger('entity_id')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('payroll_audit_logs');
        Schema::dropIfExists('reimbursement_claims');
        Schema::dropIfExists('payroll');
        Schema::dropIfExists('pay_schedule');

        if (Schema::hasTable('salary_components')) {
            Schema::table('salary_components', function (Blueprint $table) {
                $columns = ['category', 'max_limit', 'is_taxable', 'is_pf_applicable', 'is_esic_applicable', 'frequency'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('salary_components', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
}

