<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Branches
        Schema::create('branches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
            
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('city')->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Add branch_id constraints to existing tables
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
        });
        Schema::table('customers', function (Blueprint $table) {
            $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
        });
        Schema::table('loans', function (Blueprint $table) {
            $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
        });

        // 3. Business Configurations
        Schema::create('business_configurations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
            
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        // 4. Guarantors
        Schema::create('guarantors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignUuid('loan_id')->constrained('loans')->cascadeOnDelete();
            
            $table->string('first_name');
            $table->string('last_name');
            $table->string('id_number')->nullable();
            $table->string('phone')->nullable();
            $table->string('relationship')->nullable();
            $table->text('address')->nullable();
            
            $table->timestamps();
        });

        // 5. Tasks
        Schema::create('tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignUuid('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('loan_id')->nullable()->constrained('loans')->cascadeOnDelete();
            
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', ['PENDING', 'IN_PROGRESS', 'COMPLETED', 'CANCELLED'])->default('PENDING');
            $table->date('due_date')->nullable();
            
            $table->timestamps();
        });

        // 6. Communications Log
        Schema::create('communications_log', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
            
            $table->string('recipient_phone');
            $table->text('message');
            $table->enum('status', ['PENDING', 'SENT', 'FAILED'])->default('PENDING');
            $table->json('gateway_response')->nullable();
            
            $table->timestamps();
        });

        // 7. Disbursements
        Schema::create('disbursements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignUuid('loan_id')->constrained('loans')->cascadeOnDelete();
            
            $table->decimal('amount', 15, 2);
            $table->string('destination_account');
            $table->string('provider');
            $table->string('transaction_reference')->unique()->nullable();
            $table->enum('status', ['PENDING', 'SUCCESS', 'FAILED'])->default('PENDING');
            
            $table->timestamps();
        });

        // 8. Webhook Logs
        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->nullable()->constrained('businesses')->cascadeOnDelete();
            
            $table->string('provider');
            $table->string('event_type')->nullable();
            $table->json('payload');
            $table->enum('status', ['UNPROCESSED', 'PROCESSED', 'FAILED'])->default('UNPROCESSED');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_logs');
        Schema::dropIfExists('disbursements');
        Schema::dropIfExists('communications_log');
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('guarantors');
        Schema::dropIfExists('business_configurations');

        Schema::table('loans', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
        });
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
        });

        Schema::dropIfExists('branches');
    }
};
