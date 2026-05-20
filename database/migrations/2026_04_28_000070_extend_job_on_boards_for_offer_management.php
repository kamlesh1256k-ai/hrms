<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_on_boards', function (Blueprint $table) {
            if (!Schema::hasColumn('job_on_boards', 'compensation_breakup'))   $table->json('compensation_breakup')->nullable()->after('salary_duration');
            if (!Schema::hasColumn('job_on_boards', 'total_ctc'))              $table->decimal('total_ctc', 14, 2)->nullable()->after('compensation_breakup');
            if (!Schema::hasColumn('job_on_boards', 'currency'))               $table->string('currency', 8)->default('INR')->after('total_ctc');
            if (!Schema::hasColumn('job_on_boards', 'offer_letter_path'))      $table->string('offer_letter_path', 500)->nullable()->after('currency');
            if (!Schema::hasColumn('job_on_boards', 'offer_expiry_date'))      $table->date('offer_expiry_date')->nullable()->after('offer_letter_path');
            if (!Schema::hasColumn('job_on_boards', 'offer_released_at'))      $table->timestamp('offer_released_at')->nullable()->after('offer_expiry_date');
            if (!Schema::hasColumn('job_on_boards', 'accepted_declined_at'))   $table->timestamp('accepted_declined_at')->nullable()->after('offer_released_at');
            if (!Schema::hasColumn('job_on_boards', 'decline_reason'))         $table->text('decline_reason')->nullable()->after('accepted_declined_at');
            if (!Schema::hasColumn('job_on_boards', 'negotiation_notes'))      $table->text('negotiation_notes')->nullable()->after('decline_reason');
            if (!Schema::hasColumn('job_on_boards', 'requires_approval'))      $table->boolean('requires_approval')->default(false)->after('negotiation_notes');
            if (!Schema::hasColumn('job_on_boards', 'approved_by_user_id'))    $table->unsignedBigInteger('approved_by_user_id')->nullable()->after('requires_approval');
            if (!Schema::hasColumn('job_on_boards', 'approved_at'))            $table->timestamp('approved_at')->nullable()->after('approved_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('job_on_boards', function (Blueprint $table) {
            foreach ([
                'compensation_breakup', 'total_ctc', 'currency',
                'offer_letter_path', 'offer_expiry_date',
                'offer_released_at', 'accepted_declined_at',
                'decline_reason', 'negotiation_notes',
                'requires_approval', 'approved_by_user_id', 'approved_at',
            ] as $c) {
                if (Schema::hasColumn('job_on_boards', $c)) $table->dropColumn($c);
            }
        });
    }
};
