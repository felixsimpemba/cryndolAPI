<?php

namespace App\Services;

use App\Models\Loan;
use Carbon\Carbon;

class LoanCalculationService
{
    // ─── Period helpers ───────────────────────────────────────────────────────

    /**
     * Number of days in each period unit (used to advance due dates).
     */
    private const PERIOD_DAYS = [
        'day'        => 1,
        'week'       => 7,
        'biweekly'   => 14,
        'triweekly'  => 21,
        'month'      => 30, // handled via addMonths below
    ];

    // ─── Flat Rate ────────────────────────────────────────────────────────────

    /**
     * Calculate total interest for a FLAT RATE loan.
     * interest = principal × periodRate% × numberOfPeriods
     *
     * @param float  $principal      Loan principal
     * @param float  $periodRate     Rate % for the chosen period (e.g. 5 = 5%)
     * @param int    $numPeriods     How many periods (1 for week/biweekly/triweekly, n for day/month)
     */
    public function calculateFlatTotalInterest(float $principal, float $periodRate, int $numPeriods): float
    {
        return $principal * ($periodRate / 100) * $numPeriods;
    }

    /**
     * Calculate total repayment for a FLAT RATE loan.
     */
    public function calculateFlatTotalRepayment(float $principal, float $periodRate, int $numPeriods): float
    {
        return $principal + $this->calculateFlatTotalInterest($principal, $periodRate, $numPeriods);
    }

    /**
     * Installment for a FLAT RATE loan = totalRepayment / numPeriods.
     */
    public function calculateFlatInstallment(float $principal, float $periodRate, int $numPeriods): float
    {
        if ($numPeriods <= 0) return 0;
        return $this->calculateFlatTotalRepayment($principal, $periodRate, $numPeriods) / $numPeriods;
    }

    /**
     * Generate repayment schedule for a FLAT RATE loan.
     */
    public function generateFlatSchedule(Loan $loan, string $startDate): array
    {
        $principal   = (float) $loan->principal_amount;
        $periodRate  = (float) $loan->interest_rate; // stored as the resolved period rate %
        $numPeriods  = (int) $loan->loan_term_months; // "periods" count (1 for week-based)
        $ratePeriod  = strtolower($loan->rate_period ?? 'month');
        $start       = Carbon::parse($startDate);
        $schedule    = [];

        if ($principal <= 0 || $numPeriods <= 0) return [];

        $installment      = $this->calculateFlatInstallment($principal, $periodRate, $numPeriods);
        $totalInterest    = $this->calculateFlatTotalInterest($principal, $periodRate, $numPeriods);
        $principalPortion = $principal / $numPeriods;
        $interestPortion  = $totalInterest / $numPeriods;

        for ($i = 1; $i <= $numPeriods; $i++) {
            $schedule[] = [
                'installment_number' => $i,
                'due_date'           => $this->advanceDate($start->copy(), $i, $ratePeriod)->toDateString(),
                'principal_amount'   => round($principalPortion, 2),
                'interest_amount'    => round($interestPortion, 2),
                'fee_amount'         => 0,
                'status'             => 'PENDING',
            ];
        }

        return $schedule;
    }

    // ─── Smart Loan (Bank-style Reducing Balance) ─────────────────────────────

    /**
     * Monthly payment using standard PMT formula.
     * Monthly rate = annualRate / 12 / 100
     *
     * @param float $principal   Loan principal
     * @param float $annualRate  Annual interest rate %
     * @param int   $months      Loan term in months
     */
    public function calculateSmartLoanPMT(float $principal, float $annualRate, int $months): float
    {
        if ($principal <= 0 || $months <= 0) return 0;
        $r = $annualRate / 12 / 100;
        if ($r == 0) return $principal / $months;
        return $principal * ($r * pow(1 + $r, $months)) / (pow(1 + $r, $months) - 1);
    }

    /**
     * Total interest for a Smart Loan.
     */
    public function calculateSmartLoanTotalInterest(float $principal, float $annualRate, int $months): float
    {
        $pmt = $this->calculateSmartLoanPMT($principal, $annualRate, $months);
        return max(0, ($pmt * $months) - $principal);
    }

    /**
     * Total repayment for a Smart Loan.
     */
    public function calculateSmartLoanTotalRepayment(float $principal, float $annualRate, int $months): float
    {
        $pmt = $this->calculateSmartLoanPMT($principal, $annualRate, $months);
        return $pmt * $months;
    }

    /**
     * Full amortization schedule for a Smart Loan.
     */
    public function generateSmartLoanSchedule(Loan $loan, string $startDate): array
    {
        $principal  = (float) $loan->principal_amount;
        $annualRate = (float) $loan->interest_rate;
        $months     = (int) $loan->loan_term_months;
        $start      = Carbon::parse($startDate);
        $schedule   = [];

        if ($principal <= 0 || $months <= 0) return [];

        $r       = $annualRate / 12 / 100;
        $pmt     = $this->calculateSmartLoanPMT($principal, $annualRate, $months);
        $balance = $principal;

        for ($i = 1; $i <= $months; $i++) {
            $interestForPeriod  = $balance * $r;
            $principalForPeriod = $pmt - $interestForPeriod;

            // Absorb rounding on last payment
            if ($i === $months) {
                $principalForPeriod = $balance;
            }

            $balance -= $principalForPeriod;

            $schedule[] = [
                'installment_number' => $i,
                'due_date'           => $start->copy()->addMonths($i)->toDateString(),
                'principal_amount'   => round($principalForPeriod, 2),
                'interest_amount'    => round($interestForPeriod, 2),
                'fee_amount'         => 0,
                'status'             => 'PENDING',
            ];
        }

        return $schedule;
    }

    // ─── Unified entry points used by LoansController ─────────────────────────

    /**
     * Generate repayment schedule based on loan's template_type (or interest_type for legacy).
     */
    public function generateRepaymentSchedule(Loan $loan, string $startDate): array
    {
        $templateType = $loan->loanTemplate?->template_type ?? 'legacy';

        if ($templateType === 'flat_rate') {
            return $this->generateFlatSchedule($loan, $startDate);
        }

        if ($templateType === 'smart_loan') {
            return $this->generateSmartLoanSchedule($loan, $startDate);
        }

        // ── Legacy fallback (existing loans) ──────────────────────────────────
        return $this->generateLegacySchedule($loan, $startDate);
    }

    /**
     * Unified total repayment calculation for a loan.
     */
    public function calculateTotalRepayment(Loan $loan): float
    {
        $principal    = (float) $loan->principal_amount;
        $interestRate = (float) $loan->interest_rate;
        $term         = (int) $loan->loan_term_months;
        $interestType = strtoupper($loan->interest_type ?? 'FLAT');
        $templateType = $loan->loanTemplate?->template_type ?? 'legacy';

        if ($interestType === 'REDUCING' || $templateType === 'smart_loan') {
            return $this->calculateSmartLoanTotalRepayment($principal, $interestRate, $term);
        }

        if ($templateType === 'flat_rate') {
            return $this->calculateFlatTotalRepayment($principal, $interestRate, $term);
        }

        // ── Legacy FLAT fallback ─────────────────────────────────────────────
        return $principal + ($principal * ($interestRate / 100));
    }

    // ─── Legacy schedule (unchanged logic for old loans) ─────────────────────

    private function generateLegacySchedule(Loan $loan, string $startDate): array
    {
        $principal   = (float) $loan->principal_amount;
        $interestRate = (float) $loan->interest_rate;
        $term        = (int) $loan->loan_term_months;
        $termUnit    = strtolower($loan->term_unit ?? 'months');
        $interestType = strtoupper($loan->interest_type ?? 'FLAT');
        $strategy    = strtoupper($loan->repayment_strategy ?? 'INSTALLMENTS');
        $start       = Carbon::parse($startDate);
        $schedule    = [];

        if ($principal <= 0 || $term <= 0) return [];

        if ($strategy === 'BULLET') {
            $dueDate = $this->getDueDate($start->copy(), $term, $termUnit);
            $totalInt = $principal * ($interestRate / 100);
            $schedule[] = [
                'installment_number' => 1,
                'due_date'           => $dueDate->toDateString(),
                'principal_amount'   => $principal,
                'interest_amount'    => $totalInt,
                'fee_amount'         => 0,
                'status'             => 'PENDING',
            ];
            return $schedule;
        }

        if ($interestType === 'REDUCING') {
            $r       = $interestRate / 100;
            $pmt     = ($r == 0) ? $principal / $term : $principal * ($r * pow(1 + $r, $term)) / (pow(1 + $r, $term) - 1);
            $balance = $principal;

            for ($i = 1; $i <= $term; $i++) {
                $intPeriod  = $balance * $r;
                $prinPeriod = $pmt - $intPeriod;
                if ($i === $term) $prinPeriod = $balance;
                $balance -= $prinPeriod;

                $schedule[] = [
                    'installment_number' => $i,
                    'due_date'           => $this->getDueDate($start->copy(), $i, $termUnit)->toDateString(),
                    'principal_amount'   => round($prinPeriod, 2),
                    'interest_amount'    => round($intPeriod, 2),
                    'fee_amount'         => 0,
                    'status'             => 'PENDING',
                ];
            }
            return $schedule;
        }

        // FLAT legacy
        $totalInterest    = $principal * ($interestRate / 100);
        $principalPortion = $principal / $term;
        $interestPortion  = $totalInterest / $term;

        for ($i = 1; $i <= $term; $i++) {
            $schedule[] = [
                'installment_number' => $i,
                'due_date'           => $this->getDueDate($start->copy(), $i, $termUnit)->toDateString(),
                'principal_amount'   => round($principalPortion, 2),
                'interest_amount'    => round($interestPortion, 2),
                'fee_amount'         => 0,
                'status'             => 'PENDING',
            ];
        }
        return $schedule;
    }

    // ─── Date helpers ─────────────────────────────────────────────────────────

    /**
     * Advance a Carbon date by $step period units.
     */
    private function advanceDate(Carbon $base, int $step, string $period): Carbon
    {
        switch ($period) {
            case 'day':        return $base->addDays($step);
            case 'week':       return $base->addWeeks($step);
            case 'biweekly':   return $base->addDays($step * 14);
            case 'triweekly':  return $base->addDays($step * 21);
            case 'month':
            default:           return $base->addMonths($step);
        }
    }

    /** Legacy term-unit date helper */
    private function getDueDate(Carbon $start, int $step, string $unit): Carbon
    {
        switch ($unit) {
            case 'days':   return $start->addDays($step);
            case 'weeks':  return $start->addWeeks($step);
            case 'years':  return $start->addYears($step);
            case 'months':
            default:       return $start->addMonths($step);
        }
    }
}
