<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exit_checklist_items', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('resignation_id');
            // Denormalised so "this user's items" lookups don't need the join
            $table->unsignedBigInteger('user_id');

            $table->string('item_name', 200);
            $table->enum('status', ['pending', 'completed'])->default('pending');
            $table->text('notes')->nullable();

            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('completed_by')->nullable();

            $table->timestamps();

            $table->foreign('resignation_id')
                ->references('id')->on('exit_resignations')
                ->onDelete('cascade');
            $table->index(['resignation_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exit_checklist_items');
    }
};
