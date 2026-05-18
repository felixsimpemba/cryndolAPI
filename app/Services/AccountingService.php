<?php

namespace App\Services;

use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Account;
use Illuminate\Support\Facades\DB;
use Exception;

class AccountingService
{
    /**
     * Create a balanced journal entry.
     * 
     * @param string $businessId
     * @param string $description
     * @param string|null $reference
     * @param string $date
     * @param array $lines List of ['account_id' => ..., 'debit' => ..., 'credit' => ...]
     * @param string|null $userId
     * @return JournalEntry
     * @throws Exception
     */
    public function createEntry(string $businessId, string $description, ?string $reference, string $date, array $lines, ?string $userId = null)
    {
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($lines as $line) {
            $totalDebit += (float) ($line['debit'] ?? 0);
            $totalCredit += (float) ($line['credit'] ?? 0);
        }

        // Verify balance (with small epsilon for float precision)
        if (abs($totalDebit - $totalCredit) > 0.001) {
            throw new Exception("Journal entry is not balanced. Total Debit: $totalDebit, Total Credit: $totalCredit");
        }

        return DB::transaction(function () use ($businessId, $description, $reference, $date, $lines, $userId) {
            $entry = JournalEntry::create([
                'business_id' => $businessId,
                'description' => $description,
                'reference' => $reference,
                'date' => $date,
                'created_by' => $userId,
            ]);

            foreach ($lines as $lineData) {
                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $lineData['account_id'],
                    'debit' => $lineData['debit'] ?? 0,
                    'credit' => $lineData['credit'] ?? 0,
                ]);

                // Update account balance
                $account = Account::findOrFail($lineData['account_id']);
                
                // Asset/Expense: Debit +, Credit -
                // Liability/Equity/Revenue: Debit -, Credit +
                if (in_array($account->type, ['ASSET', 'EXPENSE'])) {
                    $account->balance += ($lineData['debit'] ?? 0);
                    $account->balance -= ($lineData['credit'] ?? 0);
                } else {
                    $account->balance -= ($lineData['debit'] ?? 0);
                    $account->balance += ($lineData['credit'] ?? 0);
                }
                
                $account->save();
            }

            return $entry;
        });
    }
}
