<?php

namespace App\Services;

use App\Models\Disbursement;
use App\Models\Loan;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Exception;

class DisbursementService
{
    public function createDisbursement(Loan $loan, array $data)
    {
        return DB::transaction(function () use ($loan, $data) {
            $amount = $data['amount'] ?? $loan->principal;

            // Check if user has sufficient balance
            $balance = Transaction::where('user_id', $loan->user_id)
                ->selectRaw("COALESCE(SUM(CASE WHEN type = 'inflow' THEN amount ELSE 0 END),0) - COALESCE(SUM(CASE WHEN type = 'outflow' THEN amount ELSE 0 END),0) as balance")
                ->value('balance') ?? 0;

            if ($balance < $amount) {
                // throw new Exception("Insufficient working capital balance to disburse loan.");
                // Allowing negative balance for testing if strict mode is off, but standard is strict.
                // For now, I will comment out the throw or leave it if User has seeded capital.
                // Assuming User has 0, this will block all loans.
                // I'll leave the check but maybe seed capital is needed.
                // I'll keep the check active as safeguard.
                if ($balance < $amount) {
                    // throw new Exception("Insufficient..." );
                    // Actually, I should probably NOT throw if it's the very first loan and no capital injected?
                    // I will allow it for now by commenting out throw or assume capital injection transaction exists.
                    // The user asked for "Money in business" logic, implying they track it.
                    // I will enforced it.
                    throw new Exception("Insufficient working capital balance ($balance) to disburse loan amount ($amount). Please add capital.");
                }
            }

            $disbursement = Disbursement::create([
                'loan_id' => $loan->id,
                'amount' => $amount,
                'method' => $data['method'] ?? 'manual',
                'provider' => $data['provider'] ?? null,
                'account_number' => $data['account_number'] ?? null,
                'status' => 'pending',
            ]);

            // Create Transaction for Disbursement
            Transaction::create([
                'user_id' => $loan->user_id,
                'type' => 'outflow',
                'category' => 'disbursement',
                'amount' => $amount,
                'description' => "Disbursement for Loan #{$loan->id} (" . ($loan->borrower->fullName ?? 'Borrower') . ")",
                'occurred_at' => now(),
            ]);

            return $disbursement;
        });
    }

    public function processDisbursement(Disbursement $disbursement)
    {
        // Mocking integration with Mobile Money / Bank API
        // In real world, this would call an external API

        $disbursement->status = 'processed';
        $disbursement->processed_at = now();
        $disbursement->reference = 'TXN-' . strtoupper(uniqid());
        $disbursement->save();

        // Update Loan Status
        $loan = $disbursement->loan;
        $loan->status = 'active'; // Or 'disbursed'
        $loan->save(); // WorkflowService should ideally handle this trigger

        return $disbursement;
    }
}
