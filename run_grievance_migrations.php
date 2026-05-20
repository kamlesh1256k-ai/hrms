<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Running Grievance Module Migrations...\n\n";

try {
    // Create grievances table
    if (!Schema::hasTable('grievances')) {
        echo "Creating grievances table...\n";
        Schema::create('grievances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->comment('Nullable for anonymous complaints');
            $table->string('category');
            $table->string('title');
            $table->text('description');
            $table->enum('status', ['open', 'in_progress', 'resolved'])->default('open');
            $table->boolean('is_anonymous')->default(false);
            $table->text('anonymous_token')->nullable()->unique()->comment('Token for tracking anonymous complaints');
            $table->unsignedBigInteger('assigned_to')->nullable()->comment('HR/Admin assigned to handle');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
            
            $table->index(['status', 'created_at']);
            $table->index(['category', 'status']);
        });
        echo "✅ grievances table created successfully!\n";
    } else {
        echo "ℹ️ grievances table already exists\n";
    }

    // Create grievance_responses table
    if (!Schema::hasTable('grievance_responses')) {
        echo "Creating grievance_responses table...\n";
        Schema::create('grievance_responses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('grievance_id');
            $table->unsignedBigInteger('responder_id');
            $table->text('message');
            $table->enum('response_type', ['hr_response', 'employee_reply', 'system_note'])->default('hr_response');
            $table->boolean('is_internal_note')->default(false)->comment('Visible only to HR staff');
            $table->timestamps();
            
            $table->foreign('grievance_id')->references('id')->on('grievances')->onDelete('cascade');
            $table->foreign('responder_id')->references('id')->on('users')->onDelete('cascade');
            
            $table->index(['grievance_id', 'created_at']);
            $table->index(['responder_id', 'created_at']);
        });
        echo "✅ grievance_responses table created successfully!\n";
    } else {
        echo "ℹ️ grievance_responses table already exists\n";
    }

    echo "\n🎉 All grievance module migrations completed successfully!\n";
    echo "\n📝 Next Steps:\n";
    echo "1. Visit: http://localhost/hrms/grievances\n";
    echo "2. Test raising a grievance: http://localhost/hrms/grievances/create\n";
    echo "3. Check test page: http://localhost/hrms/test-grievances\n";

} catch (Exception $e) {
    echo "❌ Error during migration: " . $e->getMessage() . "\n";
    echo "\n🔧 Troubleshooting:\n";
    echo "1. Make sure database connection is working\n";
    echo "2. Check if users table exists\n";
    echo "3. Verify database permissions\n";
}

echo "\n";
