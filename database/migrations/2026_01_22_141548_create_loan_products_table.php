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
        Schema::create('loan_products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();

            // Interest Settings
            $table->enum('interest_type', ['flat', 'reducing_balance', 'tiered'])->default('flat');
            $table->decimal('interest_rate', 8, 2); // Annual or Flat rate depending on type

            // Loan Limits
            $table->decimal('min_amount', 15, 2);
            $table->decimal('max_amount', 15, 2);

            // Term Limits
            $table->integer('min_term');
            $table->integer('max_term');
            $table->enum('term_unit', ['days', 'weeks', 'months'])->default('months');

            // Repayment
            $table->enum('repayment_frequency', ['weekly', 'bi_weekly', 'monthly'])->default('monthly');
            $table->integer('grace_period')->default(0); // In days

            // Fees & Penalties
            $table->enum('processing_fee_type', ['fixed', 'percentage'])->default('fixed');
            $table->decimal('processing_fee_value', 10, 2)->default(0);

            $table->enum('late_penalty_type', ['fixed', 'percentage'])->default('fixed');
            $table->decimal('late_penalty_value', 10, 2)->default(0);

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_products');
    }
};
