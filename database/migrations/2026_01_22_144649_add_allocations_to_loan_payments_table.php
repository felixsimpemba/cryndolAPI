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
        Schema::table('loan_payments', function (Blueprint $table) {
            $table->decimal('principal_portion', 15, 2)->default(0);
            $table->decimal('interest_portion', 15, 2)->default(0);
            $table->decimal('fee_portion', 15, 2)->default(0);
            $table->decimal('penalty_portion', 15, 2)->default(0);
            $table->string('payment_method')->nullable();
            $table->string('transaction_reference')->nullable();
            $table->text('notes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loan_payments', function (Blueprint $table) {
            //
        });
    }
};
