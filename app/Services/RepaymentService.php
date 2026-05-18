<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\LoanSchedule;
use App\Models\Account;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Services\AccountingService;

class RepaymentService
{
    protected $accounting;

    public function __construct(AccountingService $accounting)
    {
        $this->accounting = $accounting;
    }

    public function recordPayment(Loan $loan, array $data)
    {
        return DB::transaction(function () use ($loan, $data) {
            $amountToDistribute = (float) $data['amount_paid'];
            $originalAmount = $amountToDistribute;

            // Load pending and partial schedules
            $schedules = LoanSchedule::where('loan_id', $loan->id)
                ->whereIn('status', ['PENDING', 'PARTIAL', 'OVERDUE'])
                ->orderBy('due_date', 'asc')
                ->lockForUpdate()
                ->get();

            $totalPrincipalPaid = 0;
            $totalInterestPaid = 0;
            $totalFeePaid = 0;
            $totalPenaltyPaid = 0;

            foreach ($schedules as $schedule) {
                if ($amountToDistribute <= 0) break;

                $outstandingPenalty = max(0, $schedule->penalty_amount - $schedule->penalty_paid);
                $outstandingFee = max(0, $schedule->fee_amount - $schedule->fee_paid);
                $outstandingInterest = max(0, $schedule->interest_amount - $schedule->interest_paid);
                $outstandingPrincipal = max(0, $schedule->principal_amount - $schedule->principal_paid);

                // 1. Penalty
                $payPenalty = min($amountToDistribute, $outstandingPenalty);
                $schedule->penalty_paid += $payPenalty;
                $totalPenaltyPaid += $payPenalty;
                $amountToDistribute -= $payPenalty;

                // 2. Fee
                $payFee = min($amountToDistribute, $outstandingFee);
                $schedule->fee_paid += $payFee;
                $totalFeePaid += $payFee;
                $amountToDistribute -= $payFee;

                // 3. Interest
                $payInterest = min($amountToDistribute, $outstandingInterest);
                $schedule->interest_paid += $payInterest;
                $totalInterestPaid += $payInterest;
                $amountToDistribute -= $payInterest;

                // 4. Principal
                $payPrincipal = min($amountToDistribute, $outstandingPrincipal);
                $schedule->principal_paid += $payPrincipal;
                $totalPrincipalPaid += $payPrincipal;
                $amountToDistribute -= $payPrincipal;

                // Update Status
                $totalExpected = $schedule->principal_amount + $schedule->interest_amount + $schedule->fee_amount + $schedule->penalty_amount;
                $totalPaidOnSchedule = $schedule->principal_paid + $schedule->interest_paid + $schedule->fee_paid + $schedule->penalty_paid;

                $schedule->status = ($totalPaidOnSchedule >= $totalExpected - 0.01) ? 'PAID' : 'PARTIAL';
                $schedule->save();
            }

            // Create Payment Record
            $payment = LoanPayment::create([
                'loan_id' => $loan->id,
                'business_id' => $loan->business_id,
                'payment_date' => $data['payment_date'] ?? now()->toDateString(),
                'amount_paid' => $originalAmount,
                'principal_paid' => $totalPrincipalPaid,
                'interest_paid' => $totalInterestPaid,
                'fee_paid' => $totalFeePaid,
                'penalty_paid' => $totalPenaltyPaid,
                'balance_remaining' => 0, // Should calculate real remaining principal
                'payment_method' => $data['payment_method'] ?? 'CASH',
                'reference_number' => $data['reference_number'] ?? null,
                'notes' => $data['notes'] ?? null,
                'recorded_by' => auth()->id(),
            ]);

            // ACCOUNTING TRIGGER
            $this->processAccounting($loan, $originalAmount, $totalPrincipalPaid, $totalInterestPaid, $totalFeePaid, $totalPenaltyPaid, $data);

            // Check if entirely paid off
            $allPaid = LoanSchedule::where('loan_id', $loan->id)->get()->every(fn($s) => $s->status === 'PAID');
            if ($allPaid) {
                $loan->status = 'PAID';
                $loan->save();
            }

            return $payment;
        });
    }

    protected function processAccounting(Loan $loan, $total, $principal, $interest, $fee, $penalty, $data)
    {
        // For a true enterprise system, we should have a 'Chart of Accounts' pre-configured for each business.
        // For now, we'll try to find them by name or create defaults if missing (though in prod they should be fixed).
        
        $businessId = $loan->business_id;

        // 1. Find or create the standard accounts for this business
        $cashAcc = Account::firstOrCreate(
            ['business_id' => $businessId, 'code' => '1010'],
            ['name' => 'Cash/Bank', 'type' => 'ASSET']
        );
        $receivableAcc = Account::firstOrCreate(
            ['business_id' => $businessId, 'code' => '1200'],
            ['name' => 'Loan Receivables', 'type' => 'ASSET']
        );
        $interestIncAcc = Account::firstOrCreate(
            ['business_id' => $businessId, 'code' => '4010'],
            ['name' => 'Interest Income', 'type' => 'REVENUE']
        );
        $feeIncAcc = Account::firstOrCreate(
            ['business_id' => $businessId, 'code' => '4020'],
            ['name' => 'Fee Income', 'type' => 'REVENUE']
        );
        $penaltyIncAcc = Account::firstOrCreate(
            ['business_id' => $businessId, 'code' => '4030'],
            ['name' => 'Penalty Income', 'type' => 'REVENUE']
        );

        $lines = [
            ['account_id' => $cashAcc->id, 'debit' => $total, 'credit' => 0], // Debit Cash (Increase)
        ];

        if ($principal > 0) {
            $lines[] = ['account_id' => $receivableAcc->id, 'debit' => 0, 'credit' => $principal]; // Credit Receivables (Decrease)
        }
        if ($interest > 0) {
            $lines[] = ['account_id' => $interestIncAcc->id, 'debit' => 0, 'credit' => $interest]; // Credit Interest Income (Increase)
        }
        if ($fee > 0) {
            $lines[] = ['account_id' => $feeIncAcc->id, 'debit' => 0, 'credit' => $fee]; // Credit Fee Income (Increase)
        }
        if ($penalty > 0) {
            $lines[] = ['account_id' => $penaltyIncAcc->id, 'debit' => 0, 'credit' => $penalty]; // Credit Penalty Income (Increase)
        }

        $this->accounting->createEntry(
            $businessId,
            "Repayment for Loan {$loan->loan_number}",
            $data['reference_number'] ?? null,
            $data['payment_date'] ?? now()->toDateString(),
            $lines,
            auth()->id()
        );
    }
}
