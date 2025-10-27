<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BlmsLoanPayment extends Model
{
    use HasFactory;

    protected $table = 'blms_loan_payments';
    public $incrementing = false;
    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id','loan_id','payment_date','amount_paid','principal_paid','interest_paid','balance_remaining','payment_method','reference_number','notes','recorded_by','created_at'
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount_paid' => 'decimal:2',
        'principal_paid' => 'decimal:2',
        'interest_paid' => 'decimal:2',
        'balance_remaining' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($model) { if (!$model->id) { $model->id = (string) Str::uuid(); } });
    }

    public function loan() { return $this->belongsTo(BlmsLoan::class, 'loan_id'); }
}
