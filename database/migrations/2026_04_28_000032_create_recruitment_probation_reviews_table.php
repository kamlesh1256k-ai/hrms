<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment_probation_reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id'); // employees.id (post-conversion)
            $table->date('joined_on');
            $table->unsignedSmallInteger('day_milestone'); // 30 / 60 / 90
            $table->date('review_date');
            $table->enum('outcome', ['pending', 'on_track', 'needs_improvement', 'extend', 'confirm', 'terminate'])->default('pending');
            $table->unsignedTinyInteger('rating')->nullable(); // 1-5
            $table->text('strengths')->nullable();
            $table->text('improvements')->nullable();
            $table->text('manager_comments')->nullable();
            $table->unsignedBigInteger('reviewer_user_id')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->index(['employee_id', 'day_milestone'], 'prob_emp_day_idx');
            $table->index(['created_by'], 'prob_creator_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment_probation_reviews');
    }
};
