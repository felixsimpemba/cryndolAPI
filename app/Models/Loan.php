<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Loan extends Model
{
	use HasFactory;

	protected $fillable = [
		'loan_product_id',
		'user_id',
		'borrower_id',
		'principal',
		'interestRate',
		'termMonths',
		'startDate',
		'status',
		'totalPaid',
	];

	protected function casts(): array
	{
		return [
			'principal' => 'decimal:2',
			'interestRate' => 'decimal:2',
			'startDate' => 'date',
			'totalPaid' => 'decimal:2',
		];
	}

	public function user()
	{
		return $this->belongsTo(User::class);
	}

	public function borrower()
	{
		return $this->belongsTo(Borrower::class);
	}

	public function payments()
	{
		return $this->hasMany(LoanPayment::class);
	}

	public function loanProduct()
	{
		return $this->belongsTo(LoanProduct::class);
	}
}


