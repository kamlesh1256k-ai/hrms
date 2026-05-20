<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('chat_group_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chat_group_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('added_by')->nullable();
            $table->timestamps();

            $table->foreign('chat_group_id')->references('id')->on('chat_groups')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('added_by')->references('id')->on('users')->onDelete('set null');
            $table->unique(['chat_group_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_group_members');
    }
};
