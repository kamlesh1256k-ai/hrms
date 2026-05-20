<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment_requisition_approvals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('requisition_id');
            $table->unsignedBigInteger('actor_user_id');
            $table->string('actor_role', 50);
            $table->enum('action', ['approved', 'rejected', 'returned']);
            $table->text('comments')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('requisition_id', 'rra_req_id_fk')->references('id')->on('recruitment_requisitions')->onDelete('cascade');
            $table->index(['requisition_id', 'created_at'], 'rra_req_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment_requisition_approvals');
    }
};
