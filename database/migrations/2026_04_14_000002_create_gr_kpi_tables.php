<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $masters = [
            'gr_kpi_industries',
            'gr_kpi_company_sizes',
            'gr_kpi_seniority_levels',
            'gr_kpi_work_models',
            'gr_kpi_company_types',
            'gr_kpi_timeframes',
        ];
        foreach ($masters as $table) {
            Schema::create($table, function (Blueprint $t) {
                $t->id();
                $t->string('name', 150);
                $t->integer('sort_order')->default(0);
                $t->boolean('is_active')->default(true);
                $t->unsignedBigInteger('created_by');
                $t->timestamps();
                $t->index(['created_by', 'is_active']);
            });
        }

        Schema::create('gr_kpi_generations', function (Blueprint $t) {
            $t->id();
            $t->string('job_role');
            $t->string('department')->nullable();
            $t->string('company_size', 50)->nullable();
            $t->string('industry', 100)->nullable();
            $t->string('city', 100)->nullable();
            $t->string('country', 100)->nullable();
            $t->string('seniority_level', 50)->nullable();
            $t->string('work_model', 50)->nullable();
            $t->string('company_type', 100)->nullable();
            $t->string('target_timeframe', 50)->nullable();
            $t->unsignedInteger('no_of_items')->default(5);
            $t->longText('content_json')->nullable();
            $t->string('pdf_path')->nullable();
            $t->enum('ai_mode', ['basic', 'advanced'])->default('basic');
            $t->unsignedBigInteger('created_by');
            $t->timestamps();
            $t->index(['created_by', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gr_kpi_generations');
        Schema::dropIfExists('gr_kpi_timeframes');
        Schema::dropIfExists('gr_kpi_company_types');
        Schema::dropIfExists('gr_kpi_work_models');
        Schema::dropIfExists('gr_kpi_seniority_levels');
        Schema::dropIfExists('gr_kpi_company_sizes');
        Schema::dropIfExists('gr_kpi_industries');
    }
};
