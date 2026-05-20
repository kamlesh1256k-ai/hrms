<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interview_schedules', function (Blueprint $table) {
            if (!Schema::hasColumn('interview_schedules', 'round_type'))      $table->string('round_type', 40)->default('technical')->after('time');
            if (!Schema::hasColumn('interview_schedules', 'round_label'))     $table->string('round_label', 200)->nullable()->after('round_type');
            if (!Schema::hasColumn('interview_schedules', 'mode'))            $table->string('mode', 30)->default('online')->after('round_label');
            if (!Schema::hasColumn('interview_schedules', 'meeting_link'))    $table->string('meeting_link', 500)->nullable()->after('mode');
            if (!Schema::hasColumn('interview_schedules', 'status'))          $table->string('status', 30)->default('scheduled')->after('meeting_link');
            if (!Schema::hasColumn('interview_schedules', 'rating'))          $table->unsignedTinyInteger('rating')->nullable()->after('status');
            if (!Schema::hasColumn('interview_schedules', 'feedback'))        $table->text('feedback')->nullable()->after('rating');
            if (!Schema::hasColumn('interview_schedules', 'recommendation')) $table->string('recommendation', 30)->nullable()->after('feedback');
        });
    }

    public function down(): void
    {
        Schema::table('interview_schedules', function (Blueprint $table) {
            foreach (['round_type','round_label','mode','meeting_link','status','rating','feedback','recommendation'] as $c) {
                if (Schema::hasColumn('interview_schedules', $c)) $table->dropColumn($c);
            }
        });
    }
};
