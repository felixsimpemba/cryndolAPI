<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Customers
        Schema::create('customers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignUuid('branch_id')->nullable(); // Will be constrained in next migration if needed, but let's keep it clean
            
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('id_number')->nullable();
            $table->enum('id_type', ['NATIONAL_ID', 'PASSPORT', 'DRIVER_LICENSE'])->nullable();
            $table->string('tpin')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            
            $table->string('occupation')->nullable();
            $table->decimal('annual_income', 15, 2)->nullable();
            $table->integer('credit_score')->nullable();
            $table->enum('status', ['ACTIVE', 'INACTIVE', 'BLACKLISTED'])->default('ACTIVE');
            
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            $table->unique(['email', 'business_id']);
        });

        // 2. Loan Templates (Moved here for FK consistency)
        Schema::create('loan_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
            
            $table->enum('template_type', ['flat_rate', 'smart_loan', 'legacy'])->default('legacy');
            $table->string('name');
            $table->text('description')->nullable();
            
            // Interest Rates
            $table->decimal('rate_per_day', 8, 4)->nullable();
            $table->decimal('rate_per_week', 8, 4)->nullable();
            $table->decimal('rate_per_2weeks', 8, 4)->nullable();
            $table->decimal('rate_per_3weeks', 8, 4)->nullable();
            $table->decimal('rate_per_month', 8, 4)->nullable();
            $table->decimal('interest_rate', 5, 2); // Also used for Smart Loan annual rate
            $table->enum('interest_type', ['FLAT', 'REDUCING'])->default('FLAT');
            $table->enum('repayment_strategy', ['INSTALLMENTS', 'BULLET'])->default('INSTALLMENTS');
            
            // Limits & Terms
            $table->decimal('min_amount', 15, 2)->default(0);
            $table->decimal('max_amount', 15, 2)->default(0);
            $table->enum('term_unit', ['days', 'weeks', 'months', 'years'])->default('months');
            $table->integer('default_term')->default(1);
            $table->boolean('allow_custom_term')->default(false);
            
            // Fees & Penalties
            $table->enum('processing_fee_type', ['fixed', 'percentage'])->default('fixed');
            $table->decimal('processing_fee_value', 15, 2)->default(0);
            $table->decimal('penalty_rate', 5, 2)->default(0);
            $table->integer('grace_period_days')->default(0);
            $table->enum('late_penalty_type', ['fixed', 'percentage'])->default('fixed');
            $table->decimal('late_penalty_value', 15, 2)->default(0);
            $table->enum('late_penalty_frequency', ['once', 'daily', 'weekly', 'monthly'])->default('once');
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 3. Loans
        Schema::create('loans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignUuid('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignUuid('loan_template_id')->nullable()->constrained('loan_templates')->nullOnDelete();
            $table->foreignUuid('loan_officer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('branch_id')->nullable();
            
            $table->string('loan_number')->unique();
            $table->decimal('principal_amount', 15, 2);
            $table->decimal('interest_rate', 5, 2);
            $table->string('interest_type')->default('FLAT');
            $table->enum('repayment_strategy', ['INSTALLMENTS', 'BULLET'])->default('INSTALLMENTS');
            $table->string('repayment_frequency')->default('MONTHLY');
            $table->enum('rate_period', ['day', 'week', 'biweekly', 'triweekly', 'month'])->nullable();
            
            $table->integer('loan_term_months');
            $table->string('term_unit')->default('months');
            $table->date('start_date');
            $table->date('maturity_date');
            
            $table->enum('status', ['PENDING', 'APPROVED', 'ACTIVE', 'PAID', 'DEFAULTED', 'CANCELLED'])->default('PENDING');
            $table->string('purpose')->nullable();
            
            $table->string('collateral_name')->nullable();
            $table->text('collateral_description')->nullable();
            
            $table->timestamp('approved_at')->nullable();
            $table->foreignUuid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
        });

        // 4. Loan Payments
        Schema::create('loan_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignUuid('loan_id')->constrained('loans')->cascadeOnDelete();
            
            $table->date('payment_date');
            $table->decimal('amount_paid', 15, 2);
            $table->decimal('principal_paid', 15, 2)->default(0);
            $table->decimal('interest_paid', 15, 2)->default(0);
            $table->decimal('fee_paid', 15, 2)->default(0);
            $table->decimal('penalty_paid', 15, 2)->default(0);
            $table->decimal('balance_remaining', 15, 2)->default(0);
            
            $table->enum('payment_method', ['BANK_TRANSFER', 'CASH', 'CHEQUE', 'MOBILE_MONEY'])->nullable();
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            
            $table->foreignUuid('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 5. Collaterals
        Schema::create('collaterals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
            
            $table->enum('collateral_type', ['PROPERTY', 'VEHICLE', 'EQUIPMENT', 'SECURITIES', 'OTHER']);
            $table->text('description')->nullable();
            $table->decimal('estimated_value', 15, 2)->nullable();
            $table->timestamps();
        });

        // 6. Loan Collaterals
        Schema::create('loan_collaterals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('loan_id')->constrained('loans')->cascadeOnDelete();
            $table->foreignUuid('collateral_id')->constrained('collaterals')->cascadeOnDelete();
            
            $table->enum('collateral_status', ['HELD', 'RELEASED', 'VALUED', 'APPRAISED'])->default('HELD');
            $table->date('valuation_date')->nullable();
            $table->string('valuator_name')->nullable();
            $table->decimal('appraised_value', 15, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('attached_at')->useCurrent();
        });

        // 7. Documents
        Schema::create('documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
            
            $table->enum('entity_type', ['CUSTOMER', 'LOAN', 'COLLATERAL']);
            $table->uuid('entity_id');
            
            $table->enum('document_type', ['ID_PROOF', 'INCOME_PROOF', 'PROPERTY_DEED', 'AGREEMENT', 'OTHER']);
            $table->string('file_name');
            $table->string('file_url');
            $table->integer('file_size')->nullable();
            
            $table->foreignUuid('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            
            $table->date('expiry_date')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->foreignUuid('verified_by')->nullable()->constrained('users')->nullOnDelete();
        });

        // 8. Activity Logs (Consolidated Audit + Activity)
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            
            $table->string('loggable_type')->nullable(); // Polymorphic support
            $table->uuid('loggable_id')->nullable();
            
            $table->string('action'); // e.g., 'CREATE', 'UPDATE', 'DELETE', 'LOGIN'
            $table->text('description')->nullable();
            
            $table->json('properties')->nullable(); // For old/new values, extra data
            $table->string('ip_address')->nullable();
            
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('loan_collaterals');
        Schema::dropIfExists('collaterals');
        Schema::dropIfExists('loan_payments');
        Schema::dropIfExists('loans');
        Schema::dropIfExists('loan_templates');
        Schema::dropIfExists('customers');
    }
};
