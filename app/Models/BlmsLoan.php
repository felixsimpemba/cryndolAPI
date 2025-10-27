<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BlmsLoan extends Model
{
    use HasFactory;

    protected $table = 'blms_loans';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id','business_id','customer_id','loan_officer_id','loan_number','principal_amount','interest_rate','loan_term_months','start_date','maturity_date','status','purpose','approved_at','approved_by'
    ];

    protected $casts = [
        'principal_amount' => 'decimal:2',
        'interest_rate' => 'decimal:2',
        'start_date' => 'date',
        'maturity_date' => 'date',
        'approved_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($model) { if (!$model->id) { $model->id = (string) Str::uuid(); } });
    }

    public function customer() { return $this->belongsTo(BlmsCustomer::class, 'customer_id'); }
    public function payments() { return $this->hasMany(BlmsLoanPayment::class, 'loan_id'); }
}
