<?php

namespace App\Services;

use App\Models\Disbursement;
use App\Models\Loan;
use App\Models\Account;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Services\AccountingService;

class DisbursementService
{
    protected $accounting;

    public function __construct(AccountingService $accounting)
    {
        $this->accounting = $accounting;
    }

    public function createDisbursement(Loan $loan, array $data)
    {
        return DB::transaction(function () use ($loan, $data) {
            $amount = $data['amount'] ?? $loan->principal_amount;

            // Simplified balance check for now (using cash account balance)
            $cashAcc = Account::firstOrCreate(
                ['business_id' => $loan->business_id, 'code' => '1010'],
                ['name' => 'Cash/Bank', 'type' => 'ASSET']
            );

            $disbursement = Disbursement::create([
                'business_id' => $loan->business_id,
                'loan_id' => $loan->id,
                'amount' => $amount,
                'destination_account' => $data['destination_account'] ?? 'MANUAL',
                'provider' => $data['provider'] ?? 'MANUAL',
                'status' => 'PENDING',
            ]);

            return $disbursement;
        });
    }

    public function processDisbursement(Disbursement $disbursement, array $data = [])
    {
        return DB::transaction(function () use ($disbursement, $data) {
            $disbursement->update([
                'status' => 'SUCCESS',
                'provider' => $data['method'] ?? $disbursement->provider ?? 'CASH',
                'transaction_reference' => $data['reference'] ?? 'TXN-' . strtoupper(uniqid()),
            ]);

            $loan = $disbursement->loan;
            $loan->update(['status' => 'ACTIVE']);

            // ACCOUNTING: Disbursement (Money leaving business)
            // Debit: Loan Receivables (Asset increase)
            // Credit: Cash/Bank (Asset decrease)
            
            $receivableAcc = Account::firstOrCreate(
                ['business_id' => $loan->business_id, 'code' => '1200'],
                ['name' => 'Loan Receivables', 'type' => 'ASSET']
            );
            $cashAcc = Account::firstOrCreate(
                ['business_id' => $loan->business_id, 'code' => '1010'],
                ['name' => 'Cash/Bank', 'type' => 'ASSET']
            );

            $this->accounting->createEntry(
                $loan->business_id,
                "Disbursement for Loan {$loan->loan_number}",
                $disbursement->transaction_reference,
                now()->toDateString(),
                [
                    ['account_id' => $receivableAcc->id, 'debit' => $disbursement->amount, 'credit' => 0],
                    ['account_id' => $cashAcc->id, 'debit' => 0, 'credit' => $disbursement->amount],
                ],
                auth()->id()
            );

            return $disbursement;
        });
    }
}
