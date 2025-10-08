<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanPayment extends Model
{
	use HasFactory;

	protected $fillable = [
		'loan_id',
		'scheduledDate',
		'paidDate',
		'amountScheduled',
		'amountPaid',
		'status',
	];

	protected function casts(): array
	{
		return [
			'scheduledDate' => 'date',
			'paidDate' => 'date',
			'amountScheduled' => 'decimal:2',
			'amountPaid' => 'decimal:2',
		];
	}

	public function loan()
	{
		return $this->belongsTo(Loan::class);
	}
}


