<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class LoanTemplate extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $casts = [
        'is_active'           => 'boolean',
        'allow_custom_term'   => 'boolean',
        'rate_per_day'        => 'float',
        'rate_per_week'       => 'float',
        'rate_per_2weeks'     => 'float',
        'rate_per_3weeks'     => 'float',
        'rate_per_month'      => 'float',
        'interest_rate'       => 'float',
        'min_amount'          => 'float',
        'max_amount'          => 'float',
        'processing_fee_value'=> 'float',
        'late_penalty_value'  => 'float',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function loans()
    {
        return $this->hasMany(Loan::class);
    }

    /**
     * Get the configured period rates for a flat_rate template.
     */
    public function getPeriodRates(): array
    {
        return [
            'day'        => $this->rate_per_day,
            'week'       => $this->rate_per_week,
            'biweekly'   => $this->rate_per_2weeks,
            'triweekly'  => $this->rate_per_3weeks,
            'month'      => $this->rate_per_month,
        ];
    }

    /**
     * Get the rate for a specific period.
     */
    public function getRateForPeriod(string $period): ?float
    {
        return $this->getPeriodRates()[$period] ?? null;
    }
}
