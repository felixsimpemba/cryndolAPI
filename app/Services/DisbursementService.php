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
            $disbursement = Disbursement::create([
                'loan_id' => $loan->id,
                'amount' => $data['amount'] ?? $loan->principal,
                'method' => $data['method'] ?? 'manual',
                'provider' => $data['provider'] ?? null,
                'account_number' => $data['account_number'] ?? null,
                'status' => 'pending',
            ]);

            $amount = $data['amount'] ?? $loan->principal;

            // Check if user has sufficient balance
            $balance = Transaction::where('user_id', $loan->user_id)
                ->selectRaw("COALESCE(SUM(CASE WHEN type = 'inflow' THEN amount ELSE 0 END),0) - COALESCE(SUM(CASE WHEN type = 'outflow' THEN amount ELSE 0 END),0) as balance")
                ->value('balance') ?? 0;

            if ($balance < $amount) {
                throw new Exception("Insufficient working capital balance to disburse loan.");
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
