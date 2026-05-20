<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEmployeeProfileFieldsToEmployeesTable extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('employees', 'family_details')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->text('family_details')->nullable()->after('address');
            });
        }
        if (!Schema::hasColumn('employees', 'emergency_contact_name')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->string('emergency_contact_name')->nullable()->after('family_details');
            });
        }
        if (!Schema::hasColumn('employees', 'emergency_contact_phone')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->string('emergency_contact_phone')->nullable()->after('emergency_contact_name');
            });
        }
        if (!Schema::hasColumn('employees', 'blood_group')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->string('blood_group')->nullable()->after('emergency_contact_phone');
            });
        }
        if (!Schema::hasColumn('employees', 'insurance_id')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->string('insurance_id')->nullable()->after('blood_group');
            });
        }
        if (!Schema::hasColumn('employees', 'insurer_name')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->string('insurer_name')->nullable()->after('insurance_id');
            });
        }
        if (!Schema::hasColumn('employees', 'insurance_contact_person')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->string('insurance_contact_person')->nullable()->after('insurer_name');
            });
        }
        if (!Schema::hasColumn('employees', 'hobbies')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->text('hobbies')->nullable()->after('insurance_contact_person');
            });
        }
        if (!Schema::hasColumn('employees', 'food_type')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->string('food_type')->nullable()->after('hobbies');
            });
        }
        if (!Schema::hasColumn('employees', 'education')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->text('education')->nullable()->after('food_type');
            });
        }
        if (!Schema::hasColumn('employees', 'present_address')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->text('present_address')->nullable()->after('education');
            });
        }
        if (!Schema::hasColumn('employees', 'permanent_address')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->text('permanent_address')->nullable()->after('present_address');
            });
        }
        if (!Schema::hasColumn('employees', 'department_hierarchy')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->string('department_hierarchy')->nullable()->after('designation_id');
            });
        }
    }

    public function down()
    {
        $columns = [
            'family_details',
            'emergency_contact_name',
            'emergency_contact_phone',
            'blood_group',
            'insurance_id',
            'insurer_name',
            'insurance_contact_person',
            'hobbies',
            'food_type',
            'education',
            'present_address',
            'permanent_address',
            'department_hierarchy',
        ];

        foreach ($columns as $column) {
            if (Schema::hasColumn('employees', $column)) {
                Schema::table('employees', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
}
