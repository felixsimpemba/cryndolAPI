<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create('loans', function (Blueprint $table) {
			$table->id();
			$table->foreignId('user_id')->constrained()->onDelete('cascade');
			$table->foreignId('borrower_id')->constrained('borrowers')->onDelete('cascade');
			$table->decimal('principal', 12, 2);
			$table->decimal('interestRate', 5, 2); // percent APR
			$table->integer('termMonths');
			$table->date('startDate');
			$table->enum('status', ['pending', 'active', 'completed', 'defaulted', 'cancelled'])->default('active');
			$table->decimal('totalPaid', 12, 2)->default(0);
			$table->timestamps();
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('loans');
	}
};


