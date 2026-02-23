<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Loan;

class Collateral extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_id',
        'name',
        'description',
        'photos',
    ];

    protected $casts = [
        'photos' => 'array',
    ];

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }
}
