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
        // Create system_settings table if it doesn't exist
        if (!Schema::hasTable('system_settings')) {
            Schema::create('system_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->string('group')->index(); // workflow, security, notification
                $table->string('type')->default('string'); // string, boolean, integer, json
                $table->text('description')->nullable();
                $table->boolean('is_public')->default(false); // exposed to frontend?
                $table->timestamps();
            });
        }

        // Create approval_logs table if it doesn't exist
        if (!Schema::hasTable('approval_logs')) {
            Schema::create('approval_logs', function (Blueprint $table) {
                $table->id();
                $table->morphs('approvable'); // loan, disbursement, etc
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->enum('action', ['approved', 'rejected']);
                $table->text('comment')->nullable();
                $table->timestamps();
            });
        }

        // Create disbursements table if it doesn't exist
        if (!Schema::hasTable('disbursements')) {
            Schema::create('disbursements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('loan_id')->constrained()->cascadeOnDelete();
                $table->decimal('amount', 15, 2);
                $table->enum('method', ['bank_transfer', 'mobile_money', 'cash', 'cheque']);
                $table->string('reference_number')->nullable();
                $table->date('disbursement_date');
                $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
                $table->text('notes')->nullable();
                $table->foreignId('disbursed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // Create documents table if it doesn't exist
        if (!Schema::hasTable('documents')) {
            Schema::create('documents', function (Blueprint $table) {
                $table->id();
                $table->morphs('documentable'); // borrower, loan, etc
                $table->string('type'); // nrc, payslip, bank_statement
                $table->string('file_path');
                $table->string('file_name');
                $table->integer('file_size')->nullable();
                $table->string('mime_type')->nullable();
                $table->enum('verification_status', ['pending', 'verified', 'rejected'])->default('pending');
                $table->text('verification_notes')->nullable();
                $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('verified_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
        Schema::dropIfExists('disbursements');
        Schema::dropIfExists('approval_logs');
        Schema::dropIfExists('system_settings');
    }
};
