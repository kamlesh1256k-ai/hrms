<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment_assessments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('candidate_id');
            $table->string('assessment_type', 60); // aptitude / technical / case_study / coding / personality
            $table->string('title', 200);
            $table->date('scheduled_on')->nullable();
            $table->date('completed_on')->nullable();
            $table->unsignedSmallInteger('max_score')->default(100);
            $table->unsignedSmallInteger('score')->nullable();
            $table->unsignedSmallInteger('passing_score')->default(60);
            $table->enum('outcome', ['pending', 'completed', 'cleared', 'rejected', 'no_show'])->default('pending');
            $table->text('feedback')->nullable();
            $table->string('document_path', 500)->nullable();
            $table->unsignedBigInteger('evaluator_user_id')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->index(['candidate_id', 'outcome'], 'asmt_cand_outcome_idx');
            $table->index(['created_by'], 'asmt_creator_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment_assessments');
    }
};
