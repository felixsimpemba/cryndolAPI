<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Loan extends Model { 
    use HasUuids; 
    protected $guarded = []; 
    protected $appends = ['total_paid', 'borrower_name'];

    public function getTotalPaidAttribute()
    {
        return $this->payments()->sum('amount_paid');
    }

    public function getBorrowerNameAttribute()
    {
        try {
            // Must check if relationship is loaded, otherwise it returns null
            if (!$this->relationLoaded('customer')) {
                return 'N/A';
            }
            $customer = $this->customer;
            return $customer ? trim($customer->first_name . ' ' . $customer->last_name) : 'N/A';
        } catch (\Exception $e) {
            return 'N/A';
        }
    }

    public function customer() { return $this->belongsTo(Customer::class); }
    public function borrower() { return $this->customer(); }
    public function payments() { return $this->hasMany(LoanPayment::class); } 
    public function schedules() { return $this->hasMany(LoanSchedule::class); }
    public function loanTemplate() { return $this->belongsTo(LoanTemplate::class); }
    public function business() { return $this->belongsTo(Business::class); }
    public function collaterals() { return $this->belongsToMany(Collateral::class, 'loan_collaterals')->withPivot('collateral_status', 'valuation_date', 'appraised_value', 'notes'); } 
}
