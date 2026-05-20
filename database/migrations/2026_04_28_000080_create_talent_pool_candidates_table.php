<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('talent_pool_candidates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200);
            $table->string('email', 200);
            $table->string('phone', 50)->nullable();

            // Background
            $table->string('current_company', 200)->nullable();
            $table->string('current_designation', 200)->nullable();
            $table->decimal('experience_years', 4, 1)->nullable();
            $table->string('skills', 1000)->nullable();        // CSV
            $table->string('preferred_locations', 500)->nullable();
            $table->string('linkedin_url', 500)->nullable();
            $table->string('portfolio_url', 500)->nullable();
            $table->string('resume_path', 500)->nullable();

            // Compensation
            $table->decimal('current_ctc', 14, 2)->nullable();
            $table->decimal('expected_ctc', 14, 2)->nullable();
            $table->unsignedSmallInteger('notice_period_days')->nullable();

            // Sourcing
            $table->string('source', 60)->nullable();          // referral / linkedin / naukri / outbound / job_application
            $table->string('source_detail', 200)->nullable();
            $table->unsignedBigInteger('linked_application_id')->nullable(); // if imported from a JobApplication
            $table->unsignedBigInteger('assigned_recruiter_id')->nullable();

            // Talent CRM
            $table->string('tags', 500)->nullable();           // CSV — Senior, Diversity, Returning, etc.
            $table->text('notes')->nullable();
            $table->timestamp('last_engaged_at')->nullable();
            $table->enum('status', ['active', 'contacted', 'interested', 'not_interested', 'placed', 'archived'])->default('active');

            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->index(['created_by', 'status'], 'tpc_creator_status_idx');
            $table->index('linked_application_id', 'tpc_linked_app_idx');
            $table->index('assigned_recruiter_id', 'tpc_recruiter_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('talent_pool_candidates');
    }
};
