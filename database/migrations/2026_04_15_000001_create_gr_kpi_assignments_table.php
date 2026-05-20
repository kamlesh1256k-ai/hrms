<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gr_kpi_assignments', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('generation_id');
            $t->unsignedBigInteger('employee_id');
            $t->text('remarks')->nullable();
            $t->unsignedBigInteger('assigned_by');
            $t->timestamp('assigned_at')->nullable();
            $t->unsignedBigInteger('created_by');
            $t->timestamps();
            $t->unique(['generation_id', 'employee_id']);
            $t->index(['employee_id']);
            $t->index(['created_by']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gr_kpi_assignments');
    }
};
