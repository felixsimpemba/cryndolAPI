<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class LoanSchedule extends Model
{
    use HasUuids;

    protected $guarded = [];

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    public function business()
    {
        return  $this->belongsTo(Business::class);
    }
}
