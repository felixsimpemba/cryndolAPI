<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\LoanSchedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AmortizationService
{
    /**
     * Generate an amortization schedule for a loan.
     * 
     * @param Loan $loan
     * @return array
     */
    public function generateSchedule(Loan $loan)
    {
        return DB::transaction(function () use ($loan) {
            // Clear existing
            LoanSchedule::where('loan_id', $loan->id)->delete();

            $template = $loan->loanTemplate;
            $principal = (float) $loan->principal_amount;
            
            // If loan has specific rate, use it, otherwise use template
            $interestRate = (float) ($loan->interest_rate ?? $template->interest_rate) / 100.0;
            $termMonths = (int) $loan->loan_term_months;
            
            $startDate = Carbon::parse($loan->start_date ?? now());
            
            $graceDays = $template->grace_period_days ?? 0;
            if ($graceDays > 0) {
                $startDate->addDays($graceDays);
            }

            $interestType = $template->interest_type ?? 'FLAT';
            $installmentsCount = $termMonths;

            if ($interestType === 'FLAT') {
                return $this->generateFlatSchedule($loan, $principal, $interestRate, $installmentsCount, $startDate);
            } else {
                return $this->generateReducingBalanceSchedule($loan, $principal, $interestRate, $installmentsCount, $startDate);
            }
        });
    }

    protected function generateFlatSchedule(Loan $loan, $principal, $rate, $count, Carbon $startDate)
    {
        // Total Interest = Principal * Rate
        $totalInterest = $principal * $rate;
        
        $principalPerInstallment = round($principal / $count, 2);
        $interestPerInstallment = round($totalInterest / $count, 2);

        $schedules = [];
        $runningPrincipal = 0;
        $runningInterest = 0;

        for ($i = 1; $i <= $count; $i++) {
            $dueDate = $startDate->copy()->addMonths($i);

            $thisPrincipal = $principalPerInstallment;
            $thisInterest = $interestPerInstallment;
            
            if ($i === $count) {
                $thisPrincipal = $principal - $runningPrincipal;
                $thisInterest = $totalInterest - $runningInterest;
            }

            $schedule = LoanSchedule::create([
                'loan_id' => $loan->id,
                'business_id' => $loan->business_id,
                'installment_number' => $i,
                'due_date' => $dueDate->toDateString(),
                'principal_amount' => max(0, $thisPrincipal),
                'interest_amount' => max(0, $thisInterest),
                'fee_amount' => 0,
                'penalty_amount' => 0,
                'status' => 'PENDING',
            ]);

            $schedules[] = $schedule;
            
            $runningPrincipal += $thisPrincipal;
            $runningInterest += $thisInterest;
        }

        return $schedules;
    }

    protected function generateReducingBalanceSchedule(Loan $loan, $principal, $rate, $count, Carbon $startDate)
    {
        // Monthly rate
        $periodicRate = $rate / 12; // Assuming annual rate

        // PMT formula: P * (r * (1 + r)^n) / ((1 + r)^n - 1)
        if ($periodicRate > 0) {
            $emi = $principal * ($periodicRate * pow(1 + $periodicRate, $count)) / (pow(1 + $periodicRate, $count) - 1);
        } else {
            $emi = $principal / $count;
        }

        $schedules = [];
        $balance = $principal;

        for ($i = 1; $i <= $count; $i++) {
            $dueDate = $startDate->copy()->addMonths($i);

            $thisInterest = round($balance * $periodicRate, 2);
            $thisPrincipal = round($emi - $thisInterest, 2);

            if ($i === $count) {
                $thisPrincipal = $balance;
            }

            $schedule = LoanSchedule::create([
                'loan_id' => $loan->id,
                'business_id' => $loan->business_id,
                'installment_number' => $i,
                'due_date' => $dueDate->toDateString(),
                'principal_amount' => max(0, $thisPrincipal),
                'interest_amount' => max(0, $thisInterest),
                'fee_amount' => 0,
                'penalty_amount' => 0,
                'status' => 'PENDING',
            ]);

            $balance -= $thisPrincipal;
            $schedules[] = $schedule;
        }

        return $schedules;
    }
}
