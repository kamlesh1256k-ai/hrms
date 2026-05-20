<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_responses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('survey_id');
            // Nullable when survey is anonymous (we still record participation but blur identity)
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->boolean('is_anonymous')->default(false);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->index('survey_id');
            $table->index('employee_id');
            // Prevent duplicate non-anonymous submissions for the same survey
            $table->unique(['survey_id', 'employee_id'], 'uniq_survey_emp');
            $table->foreign('survey_id')->references('id')->on('employee_surveys')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_responses');
    }
};
