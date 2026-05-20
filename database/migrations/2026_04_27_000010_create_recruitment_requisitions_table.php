<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment_requisitions', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('designation_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->text('skills');
            $table->string('experience', 100)->nullable();
            $table->unsignedSmallInteger('positions')->default(1);
            $table->enum('priority', ['high', 'medium', 'low'])->default('medium');
            $table->enum('reason', ['replacement', 'new_hire', 'expansion'])->default('new_hire');
            $table->string('replacement_for', 200)->nullable();
            $table->string('salary_range', 100)->nullable();
            $table->string('location', 200)->nullable();
            $table->string('job_type', 50)->nullable();
            $table->text('description')->nullable();
            $table->longText('generated_jd')->nullable();
            $table->enum('status', ['draft', 'pending', 'approved', 'rejected', 'fulfilled'])->default('pending');
            $table->date('needed_by')->nullable();
            $table->unsignedBigInteger('job_id')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('raised_by_user_id');
            $table->timestamps();

            $table->index(['created_by', 'status']);
            $table->index('raised_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment_requisitions');
    }
};
