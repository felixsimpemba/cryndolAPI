<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // businesses
        Schema::create('blms_businesses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('business_name');
            $table->string('registration_number')->unique();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->string('industry')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // extend users
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users','business_id')) {
                $table->uuid('business_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('users','phone')) {
                $table->string('phone')->nullable()->after('email');
            }
            if (!Schema::hasColumn('users','role')) {
                $table->enum('role', ['SUPER_ADMIN','ADMIN','LOAN_OFFICER','VIEWER'])->default('VIEWER')->after('password');
            }
            if (!Schema::hasColumn('users','status')) {
                $table->enum('status', ['ACTIVE','INACTIVE','SUSPENDED'])->default('ACTIVE')->after('role');
            }
            if (!Schema::hasColumn('users','last_login')) {
                $table->timestamp('last_login')->nullable()->after('updated_at');
            }
            if (!Schema::hasColumn('users','is_super_user')) {
                $table->boolean('is_super_user')->default(false)->after('status');
            }
            if (!Schema::hasColumn('users','created_at')) {
                $table->timestamps();
            }
        });
        // FK for users.business_id
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users','business_id')) return; // safety
            $table->foreign('business_id')->references('id')->on('blms_businesses')->nullOnDelete();
        });

        // customers
        Schema::create('blms_customers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('id_number')->nullable();
            $table->enum('id_type', ['NATIONAL_ID','PASSPORT','DRIVER_LICENSE'])->nullable();
            $table->date('date_of_birth')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->string('occupation')->nullable();
            $table->decimal('annual_income', 15, 2)->nullable();
            $table->integer('credit_score')->nullable();
            $table->enum('status', ['ACTIVE','INACTIVE','BLACKLISTED'])->default('ACTIVE');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->foreign('business_id')->references('id')->on('blms_businesses')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->unique(['email','business_id']);
        });

        // loans
        Schema::create('blms_loans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('customer_id');
            $table->unsignedBigInteger('loan_officer_id')->nullable();
            $table->string('loan_number')->unique();
            $table->decimal('principal_amount', 15, 2);
            $table->decimal('interest_rate', 5, 2);
            $table->integer('loan_term_months');
            $table->date('start_date');
            $table->date('maturity_date');
            $table->enum('status', ['PENDING','APPROVED','ACTIVE','PAID','DEFAULTED','CANCELLED'])->default('PENDING');
            $table->string('purpose')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('blms_businesses')->cascadeOnDelete();
            $table->foreign('customer_id')->references('id')->on('blms_customers')->cascadeOnDelete();
            $table->foreign('loan_officer_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();

        });

        // loan_payments
        Schema::create('blms_loan_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('loan_id');
            $table->date('payment_date');
            $table->decimal('amount_paid', 15, 2);
            $table->decimal('principal_paid', 15, 2)->default(0);
            $table->decimal('interest_paid', 15, 2)->default(0);
            $table->decimal('balance_remaining', 15, 2)->default(0);
            $table->enum('payment_method', ['BANK_TRANSFER','CASH','CHEQUE','MOBILE_MONEY'])->nullable();
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('recorded_by')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('loan_id')->references('id')->on('blms_loans')->cascadeOnDelete();
            $table->foreign('recorded_by')->references('id')->on('users')->nullOnDelete();
        });

        // collaterals
        Schema::create('blms_collaterals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->enum('collateral_type', ['PROPERTY','VEHICLE','EQUIPMENT','SECURITIES','OTHER']);
            $table->text('description')->nullable();
            $table->decimal('estimated_value', 15, 2)->nullable();
            $table->timestamps();
            $table->foreign('business_id')->references('id')->on('blms_businesses')->cascadeOnDelete();
        });

        // loan_collaterals
        Schema::create('blms_loan_collaterals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('loan_id');
            $table->uuid('collateral_id');
            $table->enum('collateral_status', ['HELD','RELEASED','VALUED','APPRAISED'])->default('HELD');
            $table->date('valuation_date')->nullable();
            $table->string('valuator_name')->nullable();
            $table->decimal('appraised_value', 15, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('attached_at')->useCurrent();

            $table->foreign('loan_id')->references('id')->on('blms_loans')->cascadeOnDelete();
            $table->foreign('collateral_id')->references('id')->on('blms_collaterals')->cascadeOnDelete();
        });

        // documents
        Schema::create('blms_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->enum('entity_type', ['CUSTOMER','LOAN','COLLATERAL']);
            $table->uuid('entity_id');
            $table->enum('document_type', ['ID_PROOF','INCOME_PROOF','PROPERTY_DEED','AGREEMENT','OTHER']);
            $table->string('file_name');
            $table->string('file_url');
            $table->integer('file_size')->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->date('expiry_date')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->unsignedBigInteger('verified_by')->nullable();

            $table->foreign('business_id')->references('id')->on('blms_businesses')->cascadeOnDelete();
            $table->foreign('uploaded_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('verified_by')->references('id')->on('users')->nullOnDelete();
        });

        // audit_logs
        Schema::create('blms_audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('entity_type');
            $table->uuid('entity_id');
            $table->enum('action', ['CREATE','UPDATE','DELETE','VIEW','APPROVE']);
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->text('reason')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('business_id')->references('id')->on('blms_businesses')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        // activity_logs
        Schema::create('blms_activity_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->uuid('business_id');
            $table->string('action_type');
            $table->text('description')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamp('timestamp')->useCurrent();

            $table->foreign('business_id')->references('id')->on('blms_businesses')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        // Indexes
        Schema::table('users', function (Blueprint $table) {
            $table->index('business_id', 'idx_users_business_id');
            $table->index('email', 'idx_users_email');
        });
        Schema::table('blms_customers', function (Blueprint $table) {
            $table->index('business_id', 'idx_customers_business_id');
            $table->index('email', 'idx_customers_email');
        });
        Schema::table('blms_loans', function (Blueprint $table) {
            $table->index('business_id', 'idx_loans_business_id');
            $table->index('customer_id', 'idx_loans_customer_id');
            $table->index('status', 'idx_loans_status');
            $table->index('created_at', 'idx_loans_created_at');
        });
        Schema::table('blms_loan_payments', function (Blueprint $table) {
            $table->index('loan_id', 'idx_loan_payments_loan_id');
            $table->index('payment_date', 'idx_loan_payments_payment_date');
        });
        Schema::table('blms_audit_logs', function (Blueprint $table) {
            $table->index(['entity_type','entity_id'], 'idx_audit_logs_entity');
        });
        Schema::table('blms_activity_logs', function (Blueprint $table) {
            $table->index('user_id', 'idx_activity_logs_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('blms_activity_logs', function (Blueprint $table) { $table->dropIndex('idx_activity_logs_user_id'); });
        Schema::table('blms_audit_logs', function (Blueprint $table) { $table->dropIndex('idx_audit_logs_entity'); });
        Schema::table('blms_loan_payments', function (Blueprint $table) { $table->dropIndex('idx_loan_payments_loan_id'); $table->dropIndex('idx_loan_payments_payment_date'); });
        Schema::table('blms_loans', function (Blueprint $table) { $table->dropIndex('idx_loans_business_id'); $table->dropIndex('idx_loans_customer_id'); $table->dropIndex('idx_loans_status'); $table->dropIndex('idx_loans_created_at'); });
        Schema::table('blms_customers', function (Blueprint $table) { $table->dropIndex('idx_customers_business_id'); $table->dropIndex('idx_customers_email'); });
        Schema::table('users', function (Blueprint $table) { $table->dropIndex('idx_users_business_id'); $table->dropIndex('idx_users_email'); });

        Schema::dropIfExists('blms_activity_logs');
        Schema::dropIfExists('blms_audit_logs');
        Schema::dropIfExists('blms_documents');
        Schema::dropIfExists('blms_loan_collaterals');
        Schema::dropIfExists('blms_collaterals');
        Schema::dropIfExists('blms_loan_payments');
        Schema::dropIfExists('blms_loans');
        Schema::dropIfExists('blms_customers');
        Schema::dropIfExists('blms_businesses');

        // Note: not rolling back added columns on users for safety in existing app
    }
};
