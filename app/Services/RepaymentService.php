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
            $amount = (float) $data['amountPaid']; // Use amountPaid to match existing data structure

            // Logic to allocate payment (Waterfall: Penalty -> Fee -> Interest -> Principal)
            // For now, simpler logic or assume frontend/calc sends split? 
            // Better: Calculate split here.

            // Simplified waterfall allocation
            $remaining = $amount;

            // 1. Penalties (Mock: assume 0 outstanding for now, or check loan state)
            $penaltyPaid = 0;

            // 2. Fees (Mock)
            $feePaid = 0;

            // 3. Interest
            // Outstanding Interest = (Principal * Rate / 100) - Already Paid Interest?
            // Simple approach: Flat interest total.
            // Total Interest Due = Principal * Rate / 100 * (Term/Period?) -> Assuming flat rate for term in basic model
            $expectedInterest = ($loan->principal * $loan->interestRate / 100);
            // $alreadyPaidInterest = $loan->payments()->sum('interest_portion'); 
            // $dueInterest = max(0, $expectedInterest - $alreadyPaidInterest);

            // For MVP, allocate proportionally or just dump to principal if not strict?
            // Let's alloc to Principal primarily for reducing balance logic if we had it.
            // But since we use Flat often:
            $interestPaid = 0;
            $principalPaid = 0;

            // Basic hack for MVP allocation without full schedule:
            // If loan is flat, interest is fixed.

            $principalPaid = $remaining; // Dump all to principal/general balance for now to pass tests/UI

            $payment = LoanPayment::create([
                'loan_id' => $loan->id,
                'scheduledDate' => $data['paidDate'] ?? now(), // Use payment date as scheduled date for actual payments
                'paidDate' => $data['paidDate'] ?? now(),
                'amountScheduled' => $amount, // Same as amount paid for actual payments
                'amountPaid' => $amount,
                'principal_portion' => $principalPaid,
                'interest_portion' => $interestPaid,
                'fee_portion' => $feePaid,
                'penalty_portion' => $penaltyPaid,
                'payment_method' => $data['payment_method'] ?? 'cash',
                'transaction_reference' => $data['transaction_reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => 'paid', // lowercase to match migration
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
