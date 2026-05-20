<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('policy_acknowledgements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('policy_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamp('acknowledged_at')->useCurrent();

            // Audit context — useful for compliance/legal proof
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();

            $table->timestamps();

            // One acknowledgement per (policy, user) — DB-level guard against
            // duplicates from double-clicks or replayed POSTs.
            $table->unique(['policy_id', 'user_id'], 'uniq_policy_user');
            $table->index('user_id');

            $table->foreign('policy_id')->references('id')->on('policies')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('policy_acknowledgements');
    }
};
