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
		'term_unit', // Added
		'startDate',
		'status',
		'totalPaid',
	];

	protected $appends = ['dueDate'];

	protected function casts(): array
	{
		return [
			'principal' => 'decimal:2',
			'interestRate' => 'decimal:2',
			'startDate' => 'date',
			'totalPaid' => 'decimal:2',
		];
	}

	public function getDueDateAttribute()
	{
		if (!$this->startDate)
			return null;

		$date = \Illuminate\Support\Carbon::parse($this->startDate);
		$term = (int) $this->termMonths; // This column represents the duration value
		$unit = $this->term_unit ?? 'months'; // Default to months

		switch ($unit) {
			case 'days':
				return $date->addDays($term);
			case 'weeks':
				return $date->addWeeks($term);
			case 'years':
				return $date->addYears($term);
			case 'months':
			default:
				return $date->addMonths($term);
		}
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

	public function collateral()
	{
		return $this->hasOne(Collateral::class);
	}
}


