<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Activity Tracker — devices registered by the Node.js agent.
 *
 * One row per machine the user installs the agent on. device_uuid is generated
 * by node-machine-id on first run and is what the agent uses to identify itself.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('at_devices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('created_by');             // tenant scope (company)
            $table->string('device_uuid', 80)->unique();
            $table->string('device_name', 200);
            $table->string('os', 80)->nullable();
            $table->string('ip_address', 64)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('at_devices');
    }
};
