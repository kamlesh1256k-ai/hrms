<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_alerts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('survey_id');
            $table->unsignedBigInteger('response_id');
            // Nullable when survey is anonymous — HR sees alert without identity
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->string('alert_type', 60)->default('negative_feedback');
            $table->enum('risk_level', ['low', 'medium', 'high'])->default('medium');
            $table->text('message');
            $table->enum('status', ['open', 'acknowledged', 'resolved'])->default('open');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            $table->index(['created_by', 'status']);
            $table->index('survey_id');
            $table->foreign('survey_id')->references('id')->on('employee_surveys')->onDelete('cascade');
            $table->foreign('response_id')->references('id')->on('survey_responses')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_alerts');
    }
};
