<?php

use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\Transaction;
use App\Models\User;

// Get the first user (assuming dev environment)
$user = User::first();
if (!$user) {
    echo "No user found.\n";
    exit;
}

echo "DEBUGGING FINANCIALS FOR USER: {$user->email} (ID: {$user->id})\n";
echo "---------------------------------------------------\n";

// 1. Working Capital
echo "Working Capital (User Model): " . $user->working_capital . "\n";

// 2. Loans Disbursed
$loansDisbursedFromLoans = Loan::where('user_id', $user->id)->sum('principal');
$loansDisbursedFromTxn = Transaction::where('user_id', $user->id)->where('category', 'disbursement')->sum('amount');
echo "Loans Disbursed (Loan Model): $loansDisbursedFromLoans\n";
echo "Loans Disbursed (Transactions): $loansDisbursedFromTxn\n";

// 3. Payments (Collected)
$totalPaymentAmount = LoanPayment::whereHas('loan', function ($q) use ($user) {
    $q->where('user_id', $user->id); })->sum('amountPaid');
$totalPrincipalPortion = LoanPayment::whereHas('loan', function ($q) use ($user) {
    $q->where('user_id', $user->id); })->sum('principal_portion');
$totalInterestPortion = LoanPayment::whereHas('loan', function ($q) use ($user) {
    $q->where('user_id', $user->id); })->sum('interest_portion');

echo "Total Payments (AmountPaid): $totalPaymentAmount\n";
echo "Total Principal Portion: $totalPrincipalPortion\n";
echo "Total Interest Portion: $totalInterestPortion\n";
echo "Sum of Portions: " . ($totalPrincipalPortion + $totalInterestPortion) . "\n";

if ($totalPaymentAmount > 0 && $totalInterestPortion == 0) {
    echo "WARNING: Payments exist but Interest Portion is 0. Old data detected.\n";
}

// 4. Expenses
$expenses = Transaction::where('user_id', $user->id)
    ->where('type', 'outflow')
    ->whereNotIn('category', ['disbursement', 'capital_withdrawal'])
    ->sum('amount');
echo "Expenses (Transactions): $expenses\n";

// 5. Losses
$defaultedLoans = Loan::where('user_id', $user->id)->where('status', 'defaulted')->get();
$losses = 0;
foreach ($defaultedLoans as $loan) {
    $prin = $loan->principal;
    $expInt = $prin * ($loan->interestRate / 100);
    $paidPrin = $loan->payments->sum('principal_portion');
    $paidInt = $loan->payments->sum('interest_portion');
    $loss = ($prin - $paidPrin) + ($expInt - $paidInt);
    $losses += $loss;
}
echo "Calculated Losses: $losses\n";

// 6. Profit
$profit = $totalInterestPortion - $expenses - $losses;
echo "Calculated Profit Made: $profit\n";

// 7. Estimated Profit
$estProfit = 0;
$activeLoans = Loan::where('user_id', $user->id)->where('status', 'active')->get();
foreach ($activeLoans as $loan) {
    $expInt = $loan->principal * ($loan->interestRate / 100);
    $paidInt = $loan->payments->sum('interest_portion');
    $estProfit += max(0, $expInt - $paidInt);
}
echo "Calculated Estimated Profit: $estProfit\n";

echo "---------------------------------------------------\n";
