<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStatutoryComplianceTables extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('statutory_components')) {
            Schema::create('statutory_components', function (Blueprint $table) {
                $table->id();
                $table->string('name', 80);
                $table->string('code', 30)->unique();
                $table->boolean('status')->default(true);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('states')) {
            Schema::create('states', function (Blueprint $table) {
                $table->id();
                $table->string('state_name', 120);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('statutory_rules')) {
            Schema::create('statutory_rules', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('component_id');
                $table->unsignedBigInteger('state_id')->nullable();
                $table->decimal('min_salary', 15, 2)->nullable();
                $table->decimal('max_salary', 15, 2)->nullable();
                $table->enum('employee_contribution_type', ['percentage', 'fixed'])->default('percentage');
                $table->decimal('employee_value', 12, 4)->default(0);
                $table->enum('employer_contribution_type', ['percentage', 'fixed'])->default('percentage');
                $table->decimal('employer_value', 12, 4)->default(0);
                $table->decimal('max_limit', 15, 2)->nullable();
                $table->enum('frequency', ['monthly', 'yearly', 'half-yearly'])->default('monthly');
                $table->enum('applicable_gender', ['male', 'female', 'other'])->nullable();
                $table->date('effective_from');
                $table->boolean('status')->default(true);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->foreign('component_id')->references('id')->on('statutory_components')->onDelete('cascade');
                $table->foreign('state_id')->references('id')->on('states')->onDelete('cascade');
                $table->index(['component_id', 'state_id', 'effective_from']);
            });
        }

        if (!Schema::hasTable('employee_statutory_config')) {
            Schema::create('employee_statutory_config', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('employee_id')->unique();
                $table->unsignedBigInteger('state_id')->nullable();
                $table->boolean('pf_enabled')->default(true);
                $table->boolean('esic_enabled')->default(true);
                $table->boolean('pt_enabled')->default(true);
                $table->boolean('lwf_enabled')->default(true);
                $table->string('uan_number', 50)->nullable();
                $table->string('esic_number', 50)->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->foreign('state_id')->references('id')->on('states')->onDelete('set null');
            });
        }

        if (Schema::hasTable('payroll') && !Schema::hasColumn('payroll', 'statutory_json')) {
            Schema::table('payroll', function (Blueprint $table) {
                $table->json('statutory_json')->nullable()->after('reimbursements_json');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('payroll') && Schema::hasColumn('payroll', 'statutory_json')) {
            Schema::table('payroll', function (Blueprint $table) {
                $table->dropColumn('statutory_json');
            });
        }

        Schema::dropIfExists('employee_statutory_config');
        Schema::dropIfExists('statutory_rules');
        Schema::dropIfExists('states');
        Schema::dropIfExists('statutory_components');
    }
}

