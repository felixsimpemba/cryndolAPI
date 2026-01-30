<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\Transaction;
use App\Models\User;

class DebugFinance extends Command
{
    protected $signature = 'debug:finance';
    protected $description = 'Debug financial calculations';

    public function handle()
    {
        $users = User::withCount('loans')->get();
        if ($users->isEmpty()) {
            $this->error("No users found.");
            return;
        }

        foreach ($users as $user) {
            $this->info("DEBUGGING FINANCIALS FOR USER: {$user->email} (ID: {$user->id}) [Loans: {$user->loans_count}]");
            if ($user->loans_count == 0 && $user->working_capital == 0) {
                $this->line("   (Skipping empty user)");
                continue;
            }
            $this->line("---------------------------------------------------");

            // 1. Working Capital
            $this->info("1. Working Capital: " . $user->working_capital);

            // 2. Loans Disbursed
            $loansDisbursedFromLoans = Loan::where('user_id', $user->id)->sum('principal');
            $loansDisbursedFromTxn = Transaction::where('user_id', $user->id)->where('category', 'disbursement')->sum('amount');
            $this->info("2. Loans Disbursed (Loan Model): $loansDisbursedFromLoans");
            $this->info("   Loans Disbursed (Transactions): $loansDisbursedFromTxn");

            // 3. Payments (Collected)
            $totalPaymentAmount = LoanPayment::whereHas('loan', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })->sum('amountPaid');
            $totalPrincipalPortion = LoanPayment::whereHas('loan', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })->sum('principal_portion');
            $totalInterestPortion = LoanPayment::whereHas('loan', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })->sum('interest_portion');

            $this->info("3. Payments:");
            $this->info("   Total AmountPaid: $totalPaymentAmount");
            $this->info("   Sum Principal Portion: $totalPrincipalPortion");
            $this->info("   Sum Interest Portion: $totalInterestPortion");
            $this->info("   Combined Portions: " . ($totalPrincipalPortion + $totalInterestPortion));

            if ($totalPaymentAmount > 0 && $totalInterestPortion == 0) {
                $this->error("   WARNING: Payments exist but Interest Portion is 0. Historical data migration required.");
            }

            // 4. Expenses
            $expenses = Transaction::where('user_id', $user->id)
                ->where('type', 'outflow')
                ->whereNotIn('category', ['disbursement', 'capital_withdrawal'])
                ->sum('amount');
            $this->info("4. Expenses (Transactions): $expenses");

            // 5. Losses
            $defaultedLoans = Loan::where('user_id', $user->id)->where('status', 'defaulted')->get();
            $losses = 0;
            foreach ($defaultedLoans as $loan) {
                $prin = $loan->principal;
                // $expInt = $prin * ($loan->interestRate / 100);
                // Re-calculate expected interest based on logic
                $expInt = $prin * ($loan->interestRate / 100);

                $paidPrin = $loan->payments->sum('principal_portion');
                $paidInt = $loan->payments->sum('interest_portion');

                $loss = ($prin - $paidPrin) + ($expInt - $paidInt);
                $losses += $loss;
            }
            $this->info("5. Calculated Losses: $losses");

            // 6. Profit
            $profit = $totalInterestPortion - $expenses - $losses;
            $this->info("6. Calculated Profit Made: $profit");

            // 7. Estimated Profit
            $estProfit = 0;
            $activeLoans = Loan::where('user_id', $user->id)->where('status', 'active')->get();
            foreach ($activeLoans as $loan) {
                $expInt = $loan->principal * ($loan->interestRate / 100);
                $paidInt = $loan->payments->sum('interest_portion');
                $estProfit += max(0, $expInt - $paidInt);
            }
            $this->info("7. Calculated Estimated Profit: $estProfit");

            // 8. Business Value
            $bv = $user->working_capital + $profit;
            $this->info("8. Business Value: $bv");

            $this->line("---------------------------------------------------");
        }
    }
}