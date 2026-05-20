<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment_preonboarding_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('candidate_id');
            $table->string('category', 60); // document / asset / access / training / other
            $table->string('item_label', 200);
            $table->enum('status', ['pending', 'received', 'completed', 'waived'])->default('pending');
            $table->text('notes')->nullable();
            $table->string('document_path', 500)->nullable();
            $table->date('due_by')->nullable();
            $table->date('completed_on')->nullable();
            $table->unsignedBigInteger('owner_user_id')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->index(['candidate_id', 'status'], 'preon_cand_status_idx');
            $table->index(['created_by'], 'preon_creator_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment_preonboarding_items');
    }
};
