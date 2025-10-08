<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create('loan_payments', function (Blueprint $table) {
			$table->id();
			$table->foreignId('loan_id')->constrained('loans')->onDelete('cascade');
			$table->date('scheduledDate');
			$table->date('paidDate')->nullable();
			$table->decimal('amountScheduled', 12, 2);
			$table->decimal('amountPaid', 12, 2)->default(0);
			$table->enum('status', ['scheduled', 'paid', 'overdue'])->default('scheduled');
			$table->timestamps();
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('loan_payments');
	}
};


