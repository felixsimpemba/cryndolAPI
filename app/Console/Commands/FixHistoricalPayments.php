<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\LoanPayment;

class FixHistoricalPayments extends Command
{
    protected $signature = 'fix:historical-payments';
    protected $description = 'Recalculate interest/principal portions for historical payments';

    public function handle()
    {
        $this->info("Scanning for payments with 0 interest portion...");

        $payments = LoanPayment::where('interest_portion', 0)
            ->where('amountPaid', '>', 0)
            ->with('loan')
            ->get();

        if ($payments->isEmpty()) {
            $this->info("No payments found requiring fix.");
            return;
        }

        $this->info("Found {$payments->count()} payments to fix.");

        foreach ($payments as $payment) {
            $loan = $payment->loan;
            if (!$loan) {
                $this->error("Payment #{$payment->id} has no loan associated. Skipping.");
                continue;
            }

            $currentPrincipal = (float) $payment->principal_portion;
            $currentInterest = (float) $payment->interest_portion;

            // Double check if it needs fixing (sometimes 0 interest is valid if rate is 0)
            if ($loan->interestRate <= 0) {
                $this->info("Payment #{$payment->id}: Loan interest rate is 0. Skipping.");
                continue;
            }

            $amountPaid = (float) $payment->amountPaid;
            $totalPrincipal = (float) $loan->principal;
            $totalInterest = (float) $loan->principal * (float) $loan->interestRate / 100.0;
            $totalDue = $totalPrincipal + $totalInterest;

            if ($totalDue <= 0) {
                continue;
            }

            // Calculate Proportional Split
            $interestRatio = $totalInterest / $totalDue;
            $newInterest = round($amountPaid * $interestRatio, 2);
            $newPrincipal = $amountPaid - $newInterest;

            $this->line("Fixing Payment #{$payment->id} (Amount: $amountPaid):");
            $this->line("   - Old: Prin=$currentPrincipal, Int=$currentInterest");
            $this->line("   - New: Prin=$newPrincipal, Int=$newInterest");

            $payment->update([
                'principal_portion' => $newPrincipal,
                'interest_portion' => $newInterest
            ]);
        }

        $this->info("All payments processed.");
    }
}
