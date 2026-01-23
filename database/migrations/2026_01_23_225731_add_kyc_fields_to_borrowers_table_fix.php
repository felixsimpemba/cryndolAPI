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
            $table->string('nrc_number', 50)->nullable()->after('phoneNumber');
            $table->string('tpin_number', 50)->nullable()->after('nrc_number');
            $table->string('passport_number', 50)->nullable()->after('tpin_number');
            $table->text('address')->nullable()->after('passport_number');
            $table->date('date_of_birth')->nullable()->after('address');
            $table->enum('gender', ['male', 'female', 'other'])->nullable()->after('date_of_birth');
            $table->enum('marital_status', ['single', 'married', 'divorced', 'widowed'])->nullable()->after('gender');
            $table->enum('employment_status', ['employed', 'self_employed', 'unemployed', 'student'])->nullable()->after('marital_status');
            $table->string('employer_name')->nullable()->after('employment_status');
            $table->decimal('monthly_income', 12, 2)->nullable()->after('employer_name');
            $table->enum('risk_segment', ['low', 'medium', 'high'])->nullable()->after('monthly_income');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('borrowers', function (Blueprint $table) {
            $table->dropColumn([
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
            ]);
        });
    }
};
