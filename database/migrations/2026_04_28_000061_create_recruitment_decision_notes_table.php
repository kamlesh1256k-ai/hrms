<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment_decision_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('candidate_id');
            $table->unsignedBigInteger('user_id');
            $table->text('note');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->index(['candidate_id', 'created_at'], 'rdn_cand_created_idx');
            $table->index('created_by', 'rdn_creator_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment_decision_notes');
    }
};
