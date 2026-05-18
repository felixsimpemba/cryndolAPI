<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Collateral extends Model { 
    use HasUuids; 
    protected $guarded = []; 
    public function loans() { return $this->belongsToMany(Loan::class, 'loan_collaterals'); } 
}
