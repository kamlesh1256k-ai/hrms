<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_answers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('response_id');
            $table->unsignedBigInteger('question_id');
            // answer_value: free-form string (yes/no, choice label, etc.)
            $table->string('answer_value', 500)->nullable();
            // rating_value: numeric for rating_5 / rating_10
            $table->decimal('rating_value', 5, 2)->nullable();
            // text_value: long text answers (analyzed for sentiment)
            $table->text('text_value')->nullable();
            $table->timestamps();
            $table->index('response_id');
            $table->index('question_id');
            $table->foreign('response_id')->references('id')->on('survey_responses')->onDelete('cascade');
            $table->foreign('question_id')->references('id')->on('survey_questions')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_answers');
    }
};
