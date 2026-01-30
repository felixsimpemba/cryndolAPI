<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\LoanPayment;
use Illuminate\Support\Facades\DB;
use Exception;

class RepaymentService
{
    public function recordPayment(Loan $loan, array $data)
    {
        return DB::transaction(function () use ($loan, $data) {
            $amount = (float) $data['amountPaid'];

            // Calculate Totals for Proportional Split
            $totalPrincipal = (float) $loan->principal;
            $totalInterest = (float) $loan->principal * (float) $loan->interestRate / 100.0;
            $totalDue = $totalPrincipal + $totalInterest;

            // Avoid division by zero
            if ($totalDue <= 0) {
                $principalPaid = $amount;
                $interestPaid = 0;
            } else {
                // Proportional Split
                $interestRatio = $totalInterest / $totalDue;
                $interestPaid = round($amount * $interestRatio, 2);
                $principalPaid = $amount - $interestPaid;
            }

            // 1. Penalties (Mock: assume 0 outstanding for now)
            $penaltyPaid = 0;
            // 2. Fees (Mock)
            $feePaid = 0;

            $payment = LoanPayment::create([
                'loan_id' => $loan->id,
                'scheduledDate' => $data['paidDate'] ?? now(),
                'paidDate' => $data['paidDate'] ?? now(),
                'amountScheduled' => $amount,
                'amountPaid' => $amount,
                'principal_portion' => $principalPaid,
                'interest_portion' => $interestPaid,
                'fee_portion' => $feePaid,
                'penalty_portion' => $penaltyPaid,
                'payment_method' => $data['payment_method'] ?? 'cash',
                'transaction_reference' => $data['transaction_reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => 'paid',
            ]);

            // Create Transaction for Repayment (Cash In)
            \App\Models\Transaction::create([
                'user_id' => $loan->user_id,
                'type' => 'inflow',
                'category' => 'repayment',
                'amount' => $amount,
                'description' => "Repayment for Loan #{$loan->id} (" . ($loan->borrower->fullName ?? 'Borrower') . ")",
                'occurred_at' => now(),
            ]);

            // Update Loan Totals
            // $loan->totalPaid is cached in controller usually, let's update it here
            $totalPaid = $loan->payments()->sum('amountPaid');

            // Check formatted total due
            $totalDue = (float) $loan->principal + ((float) $loan->principal * (float) $loan->interestRate / 100.0);

            if ($totalPaid >= $totalDue - 0.1) { // Tolerance
                $loan->status = 'closed'; // or paid
            }

            $loan->save();

            return $payment;
        });
    }
}
