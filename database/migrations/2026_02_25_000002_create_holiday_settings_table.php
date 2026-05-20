<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('holiday_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->enum('holiday_scope', ['company', 'location', 'shift', 'location_shift'])->default('company');
            $table->boolean('allow_multiple_holidays_same_date')->default(false);
            $table->enum('weekend_holiday_rule', ['ignore', 'carry_forward', 'comp_off'])->default('ignore');
            $table->enum('leave_on_holiday_rule', ['block', 'exclude', 'deduct'])->default('exclude');
            $table->boolean('exclude_holidays_from_leave_balance')->default(true);
            $table->enum('attendance_on_holiday', ['holiday', 'present', 'none'])->default('holiday');
            $table->boolean('ignore_late_entry')->default(true);
            $table->boolean('ignore_early_exit')->default(true);
            $table->boolean('ignore_monthly_late_counter')->default(true);
            $table->boolean('enable_optional_holidays')->default(false);
            $table->integer('max_optional_holidays_per_year')->default(0);
            $table->boolean('require_optional_holiday_approval')->default(false);
            $table->boolean('enable_recurring_holidays')->default(false);
            $table->enum('recurring_type', ['same_date', 'custom'])->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('holiday_settings');
    }
};