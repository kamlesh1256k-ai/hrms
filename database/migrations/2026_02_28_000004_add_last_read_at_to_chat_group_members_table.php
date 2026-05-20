<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('chat_group_members', function (Blueprint $table) {
            if (!Schema::hasColumn('chat_group_members', 'last_read_at')) {
                $table->timestamp('last_read_at')->nullable()->after('added_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('chat_group_members', function (Blueprint $table) {
            if (Schema::hasColumn('chat_group_members', 'last_read_at')) {
                $table->dropColumn('last_read_at');
            }
        });
    }
};
