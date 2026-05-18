<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class LoanPayment extends Model { 
    use HasUuids; 
    protected $guarded = []; 
    public function loan() { return $this->belongsTo(Loan::class); } 
}
