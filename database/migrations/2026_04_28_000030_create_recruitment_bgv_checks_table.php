<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment_bgv_checks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('candidate_id'); // job_applications.id
            $table->string('check_type', 60); // employment / education / id / address / criminal / reference / drug
            $table->string('item_label', 200);
            $table->enum('status', ['pending', 'in_progress', 'cleared', 'failed', 'na'])->default('pending');
            $table->text('notes')->nullable();
            $table->string('document_path', 500)->nullable();
            $table->date('initiated_on')->nullable();
            $table->date('completed_on')->nullable();
            $table->unsignedBigInteger('verified_by_user_id')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->index(['candidate_id', 'status'], 'bgv_cand_status_idx');
            $table->index(['created_by'], 'bgv_creator_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment_bgv_checks');
    }
};
