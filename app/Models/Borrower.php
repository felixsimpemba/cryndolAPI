<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Borrower extends Model
{
	use HasFactory;

	protected $fillable = [
		'user_id',
		'fullName',
		'email',
		'phoneNumber',
		'nrc_number',
		'tpin_number',
		'passport_number',
		'address',
		'date_of_birth',
		'gender',
		'marital_status',
		'employment_status',
		'employer_name',
		'monthly_income',
		'credit_score',
		'risk_segment',
	];

	protected $casts = [
		'date_of_birth' => 'date',
		'monthly_income' => 'decimal:2',
	];

	public function user()
	{
		return $this->belongsTo(User::class);
	}

	public function loans()
	{
		return $this->hasMany(Loan::class);
	}

	public function documents()
	{
		return $this->hasMany(Document::class);
	}
}
