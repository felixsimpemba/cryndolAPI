<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Accounts (Chart of Accounts)
        Schema::create('accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
            
            $table->string('name');
            $table->string('code')->nullable(); // e.g., 1000 for Assets
            $table->enum('type', ['ASSET', 'LIABILITY', 'EQUITY', 'REVENUE', 'EXPENSE']);
            $table->decimal('balance', 15, 2)->default(0);
            
            $table->timestamps();
            
            $table->unique(['business_id', 'code']);
        });

        // 2. Wallets
        Schema::create('wallets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignUuid('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            
            $table->string('name');
            $table->enum('type', ['CASH', 'BANK', 'MOBILE_MONEY', 'SAVINGS'])->default('CASH');
            $table->decimal('balance', 15, 2)->default(0);
            $table->string('currency', 3)->default('ZMW');
            
            $table->timestamps();
        });

        // 3. Journal Entries
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
            
            $table->string('description');
            $table->string('reference')->nullable(); // External ref or internal link
            $table->date('date');
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
        });

        // 4. Journal Entry Lines (The actual debits/credits)
        Schema::create('journal_entry_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('journal_entry_id')->constrained('journal_entries')->cascadeOnDelete();
            $table->foreignUuid('account_id')->constrained('accounts')->cascadeOnDelete();
            
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entry_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('wallets');
        Schema::dropIfExists('accounts');
    }
};
