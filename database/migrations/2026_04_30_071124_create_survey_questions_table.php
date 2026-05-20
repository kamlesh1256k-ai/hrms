<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('survey_id');
            $table->text('question_text');
            // Question types: rating_5 (1-5), rating_10 (0-10), yes_no, multiple_choice, text
            $table->enum('question_type', ['rating_5', 'rating_10', 'yes_no', 'multiple_choice', 'text'])->default('rating_5');
            $table->json('options')->nullable();         // array of choices for multiple_choice
            $table->boolean('is_required')->default(true);
            $table->boolean('is_enps')->default(false);  // marks the eNPS question (0-10 recommend question)
            $table->integer('order_no')->default(0);
            $table->timestamps();
            $table->index('survey_id');
            $table->foreign('survey_id')->references('id')->on('employee_surveys')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_questions');
    }
};
