<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'interest_type',
        'interest_rate',
        'min_amount',
        'max_amount',
        'min_term',
        'max_term',
        'term_unit',
        'repayment_frequency',
        'grace_period',
        'processing_fee_type',
        'processing_fee_value',
        'late_penalty_type',
        'late_penalty_value',
        'user_id',
        'is_active',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected $casts = [
        'interest_rate' => 'decimal:2',
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'processing_fee_value' => 'decimal:2',
        'late_penalty_value' => 'decimal:2',
        'is_active' => 'boolean',
    ];
}
