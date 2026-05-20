<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('chat_group_messages', function (Blueprint $table) {
            if (!Schema::hasColumn('chat_group_messages', 'message_type')) {
                $table->string('message_type', 20)->default('text')->after('message');
            }
            if (!Schema::hasColumn('chat_group_messages', 'voice_path')) {
                $table->string('voice_path')->nullable()->after('message_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('chat_group_messages', function (Blueprint $table) {
            if (Schema::hasColumn('chat_group_messages', 'voice_path')) {
                $table->dropColumn('voice_path');
            }
            if (Schema::hasColumn('chat_group_messages', 'message_type')) {
                $table->dropColumn('message_type');
            }
        });
    }
};
