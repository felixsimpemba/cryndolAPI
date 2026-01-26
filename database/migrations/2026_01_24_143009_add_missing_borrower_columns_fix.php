<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('borrowers', function (Blueprint $table) {
            // Check if columns don't exist before adding
            if (!Schema::hasColumn('borrowers', 'nrc_number')) {
                $table->string('nrc_number', 50)->nullable()->after('phoneNumber');
            }
            if (!Schema::hasColumn('borrowers', 'tpin_number')) {
                $table->string('tpin_number', 50)->nullable()->after('nrc_number');
            }
            if (!Schema::hasColumn('borrowers', 'passport_number')) {
                $table->string('passport_number', 50)->nullable()->after('tpin_number');
            }
            if (!Schema::hasColumn('borrowers', 'address')) {
                $table->text('address')->nullable()->after('passport_number');
            }
            if (!Schema::hasColumn('borrowers', 'date_of_birth')) {
                $table->date('date_of_birth')->nullable()->after('address');
            }
            if (!Schema::hasColumn('borrowers', 'gender')) {
                $table->enum('gender', ['male', 'female', 'other'])->nullable()->after('date_of_birth');
            }
            if (!Schema::hasColumn('borrowers', 'marital_status')) {
                $table->enum('marital_status', ['single', 'married', 'divorced', 'widowed'])->nullable()->after('gender');
            }
            if (!Schema::hasColumn('borrowers', 'employment_status')) {
                $table->enum('employment_status', ['employed', 'self_employed', 'unemployed', 'student'])->nullable()->after('marital_status');
            }
            if (!Schema::hasColumn('borrowers', 'employer_name')) {
                $table->string('employer_name')->nullable()->after('employment_status');
            }
            if (!Schema::hasColumn('borrowers', 'monthly_income')) {
                $table->decimal('monthly_income', 12, 2)->nullable()->after('employer_name');
            }
            if (!Schema::hasColumn('borrowers', 'risk_segment')) {
                $table->enum('risk_segment', ['low', 'medium', 'high'])->nullable()->after('monthly_income');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('borrowers', function (Blueprint $table) {
            $columns = [
                'nrc_number',
                'tpin_number',
                'passport_number',
                'address',
                'date_of_birth',
                'gender',
                'marital_status',
                'employment_status',
                'employer_name',
                'monthly_income',
                'risk_segment'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('borrowers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
