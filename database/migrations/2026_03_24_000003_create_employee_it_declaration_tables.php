<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeItDeclarationTables extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('tax_declarations')) {
            Schema::create('tax_declarations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('employee_id');
                $table->string('financial_year', 9);
                $table->enum('tax_regime', ['old', 'new'])->default('old');
                $table->enum('declaration_status', ['draft', 'submitted', 'approved', 'rejected'])->default('draft');
                $table->boolean('is_rented_house')->default(false);
                $table->boolean('is_home_loan')->default(false);
                $table->boolean('is_rental_income')->default(false);
                $table->decimal('rent_paid', 15, 2)->default(0);
                $table->string('landlord_name', 120)->nullable();
                $table->string('landlord_pan', 20)->nullable();
                $table->decimal('home_loan_interest', 15, 2)->default(0);
                $table->decimal('rental_income_amount', 15, 2)->default(0);
                $table->json('compare_json')->nullable();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->text('remarks')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->index(['employee_id', 'financial_year']);
                $table->unique(['employee_id', 'financial_year']);
            });
        }

        if (!Schema::hasTable('investment_details')) {
            Schema::create('investment_details', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tax_declaration_id');
                $table->string('section_code', 40)->default('80C');
                $table->string('investment_type', 120);
                $table->decimal('amount', 15, 2)->default(0);
                $table->string('proof_file')->nullable();
                $table->timestamps();

                $table->foreign('tax_declaration_id')->references('id')->on('tax_declarations')->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('exemption_details')) {
            Schema::create('exemption_details', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tax_declaration_id');
                $table->string('section_code', 40)->default('80D');
                $table->string('exemption_type', 120);
                $table->decimal('amount', 15, 2)->default(0);
                $table->string('proof_file')->nullable();
                $table->timestamps();

                $table->foreign('tax_declaration_id')->references('id')->on('tax_declarations')->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('income_sources')) {
            Schema::create('income_sources', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tax_declaration_id');
                $table->string('income_type', 120);
                $table->decimal('amount', 15, 2)->default(0);
                $table->timestamps();

                $table->foreign('tax_declaration_id')->references('id')->on('tax_declarations')->onDelete('cascade');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('income_sources');
        Schema::dropIfExists('exemption_details');
        Schema::dropIfExists('investment_details');
        Schema::dropIfExists('tax_declarations');
    }
}

