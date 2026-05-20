<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_sentiment_analysis', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('answer_id');
            $table->enum('sentiment', ['positive', 'neutral', 'negative'])->default('neutral');
            // Topics: salary, manager, workload, culture, growth, policy, benefits (CSV/JSON)
            $table->json('topic')->nullable();
            $table->enum('emotion', ['happy', 'frustrated', 'stressed', 'motivated', 'neutral'])->default('neutral');
            $table->enum('risk_level', ['low', 'medium', 'high'])->default('low');
            $table->boolean('hr_alert')->default(false);
            $table->text('ai_summary')->nullable();
            $table->timestamps();
            $table->index('answer_id');
            $table->index(['sentiment', 'risk_level']);
            $table->foreign('answer_id')->references('id')->on('survey_answers')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_sentiment_analysis');
    }
};
