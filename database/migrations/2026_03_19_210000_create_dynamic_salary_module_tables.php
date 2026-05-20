<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDynamicSalaryModuleTables extends Migration
{
    public function up()
    {
        Schema::create('salary_components', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->enum('type', ['earning', 'deduction', 'employer']);
            $table->enum('calculation_type', ['fixed', 'percentage', 'formula'])->default('fixed');
            $table->decimal('value', 15, 2)->nullable();
            $table->text('formula')->nullable();
            $table->text('condition_rule')->nullable();
            $table->boolean('status')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('salary_structures', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('country', 60)->default('India');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('structure_components', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('structure_id');
            $table->unsignedBigInteger('component_id');
            $table->integer('priority')->default(10);
            $table->timestamps();

            $table->foreign('structure_id')->references('id')->on('salary_structures')->onDelete('cascade');
            $table->foreign('component_id')->references('id')->on('salary_components')->onDelete('cascade');
            $table->unique(['structure_id', 'component_id']);
        });

        Schema::create('employee_salaries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->decimal('ctc', 15, 2);
            $table->decimal('basic_percentage', 5, 2)->default(50.00);
            $table->boolean('is_pf_enabled')->default(true);
            $table->boolean('is_esic_enabled')->default(true);
            $table->unsignedBigInteger('structure_id')->nullable();
            $table->timestamps();

            $table->index('employee_id');
            $table->foreign('structure_id')->references('id')->on('salary_structures')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('employee_salaries');
        Schema::dropIfExists('structure_components');
        Schema::dropIfExists('salary_structures');
        Schema::dropIfExists('salary_components');
    }
}

