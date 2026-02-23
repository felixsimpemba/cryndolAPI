<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Borrower;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\Transaction;
use OpenApi\Attributes as OA;

class DashboardController extends Controller
{
	/**
	 * @OA\Get(
	 *     path="/dashboard/summary/{businessId}",
	 *     operationId="getDashboardSummary",
	 *     summary="Get dashboard summary metrics",
	 *     description="Returns aggregated stats and profit trend for the authenticated business",
	 *     tags={"Dashboard"},
	 *     security={{"bearerAuth": {}}},
	 *     @OA\Parameter(
	 *         name="businessId",
	 *         in="path",
	 *         required=true,
	 *         description="Business identifier",
	 *         @OA\Schema(type="string")
	 *     ),
	 *     @OA\Response(
	 *         response=200,
	 *         description="Dashboard summary"
	 *     ),
	 *     @OA\Response(
	 *         response=401,
	 *         description="Unauthorized",
	 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
	 *     )
	 * )
	 */
	public function summary(Request $request, $businessId)
	{
		$user = $request->user();

		// 1. Working Capital
		$workingCapital = (float) $user->working_capital;

		// 2. Expenses (Operational costs only - no disbursements)
		$expenses = Transaction::where('user_id', $user->id)
			->where('type', 'outflow')
			->whereNotIn('category', ['disbursement', 'capital_withdrawal'])
			->sum('amount');

		// 3. Money Collected (Principal + Interest)
		// We need to split this for the formula.
		$collectionStats = LoanPayment::whereHas('loan', function ($q) use ($user) {
			$q->where('user_id', $user->id);
		})->where('status', 'paid')
			->selectRaw('sum(principal_portion) as total_principal, sum(interest_portion) as total_interest')
			->first();

		$principalCollected = (float) ($collectionStats->total_principal ?? 0);
		$interestCollected = (float) ($collectionStats->total_interest ?? 0);
		$moneyCollected = $principalCollected + $interestCollected;

		// 4. Losses from Defaulted Loans (Unpaid Principal + Unpaid Interest)
		$defaultedLoans = Loan::where('user_id', $user->id)
			->where('status', 'defaulted')
			->with(['payments']) // Eager load payments to sum portions
			->get();

		$lossesFromDefaults = 0;
		foreach ($defaultedLoans as $loan) {
			$loanPrincipal = (float) $loan->principal;
			$loanExpectedInterest = $loanPrincipal * ((float) $loan->interestRate / 100.0);

			// Re-calculate what was paid on this specific loan
			// Note: we can't just use loan->totalPaid if it's not split, but our RepaymentService now splits it.
			// Using logic from RepaymentService to be safe or summing relation.
			$paidPrincipal = $loan->payments->sum('principal_portion');
			$paidInterest = $loan->payments->sum('interest_portion');

			$outstandingPrincipal = max(0, $loanPrincipal - $paidPrincipal);
			$outstandingInterest = max(0, $loanExpectedInterest - $paidInterest);

			$lossesFromDefaults += ($outstandingPrincipal + $outstandingInterest);
		}

		// 5. Profit Made (REALIZED PROFIT)
		// Formula: Total Interest Collected - Total Expenses - Losses
		$profitMade = $interestCollected - $expenses - $lossesFromDefaults;

		// 6. Estimated Profit (UNREALIZED PROFIT)
		// Formula: Expected Interest from Active Loans - Expected Defaults - Expected Expenses
		// "must NOT include already collected interest."
		$activeLoans = Loan::where('user_id', $user->id)
			->where('status', 'active')
			->with(['payments'])
			->get();

		$unrealizedProfit = 0;
		foreach ($activeLoans as $loan) {
			$totalExpectedInterest = (float) $loan->principal * ((float) $loan->interestRate / 100.0);
			$paidInterest = $loan->payments->sum('interest_portion');
			$remainingInterest = max(0, $totalExpectedInterest - $paidInterest);
			$unrealizedProfit += $remainingInterest;
		}
		// Subtract Expected Defaults/Expenses if we had a prediction model. For now, 0.
		$estimatedProfit = $unrealizedProfit;

		// 7. Current Balance (Available Cash)
		// Formula: Working Capital - Total Loans Disbursed + Principal Collected + Interest Collected - Expenses
		$totalLoansDisbursed = Transaction::where('user_id', $user->id)
			->where('category', 'disbursement')
			->sum('amount');

		$currentBalance = $workingCapital - $totalLoansDisbursed + $principalCollected + $interestCollected - $expenses;


		// 8. Business Value (Money in Business)
		// Formula: Working Capital + Profit Made
		$moneyInBusiness = $workingCapital + $profitMade;

		// Extra Metrics for UI
		$totalBorrowers = Borrower::where('user_id', $user->id)->count();
		$totalLoans = Loan::where('user_id', $user->id)->count();
		$totalOutstandingAmount = LoanPayment::whereHas('loan', function ($q) use ($user) {
			$q->where('user_id', $user->id)->where('status', 'active');
		})
			->whereIn('status', ['scheduled', 'overdue'])
			->sum(DB::raw('(amountScheduled - amountPaid)'));

		$loansDueInNext7Days = LoanPayment::whereHas('loan', function ($q) use ($user) {
			$q->where('user_id', $user->id)->where('status', 'active');
		})
			->whereBetween('scheduledDate', [Carbon::today(), Carbon::today()->addDays(7)])
			->whereIn('status', ['scheduled', 'overdue'])
			->count();

		$overdueAmount = LoanPayment::whereHas('loan', function ($q) use ($user) {
			$q->where('user_id', $user->id)->where('status', 'active');
		})
			->where('status', 'overdue')
			->sum(DB::raw('(amountScheduled - amountPaid)'));

		$dueThisWeekAmount = LoanPayment::whereHas('loan', function ($q) use ($user) {
			$q->where('user_id', $user->id)->where('status', 'active');
		})
			->whereBetween('scheduledDate', [Carbon::today(), Carbon::today()->addDays(7)])
			->sum(DB::raw('(amountScheduled - amountPaid)'));

		$collectedToday = LoanPayment::whereHas('loan', function ($q) use ($user) {
			$q->where('user_id', $user->id);
		})
			->whereDate('paidDate', Carbon::today())
			->where('status', 'paid')
			->sum('amountPaid');

		$profitTrend = $this->calculateProfitTrend($user);

		return response()->json([
			'totalBorrowers' => $totalBorrowers,
			'totalLoans' => $totalLoans,
			'totalOutstandingAmount' => round((float) $totalOutstandingAmount, 2),
			'totalPaidAmount' => round((float) $moneyCollected, 2),
			'currentBalance' => round((float) $currentBalance, 2),
			'loansDueInNext7Days' => $loansDueInNext7Days,
			'overdueAmount' => round((float) $overdueAmount, 2),
			'dueThisWeekAmount' => round((float) $dueThisWeekAmount, 2),
			'collectedToday' => round((float) $collectedToday, 2),
			'profitTrend' => $profitTrend,
			'workingCapital' => round((float) $workingCapital, 2),
			'estimatedProfit' => round((float) $estimatedProfit, 2), // Now purely Unrealized
			'profitMade' => round((float) $profitMade, 2),
			'moneyInBusiness' => round((float) $moneyInBusiness, 2),
			'expenses' => round((float) $expenses, 2),
			'losses' => round((float) $lossesFromDefaults, 2),
		]);
	}

	private function calculateProfitTrend($user)
	{
		// Profit trend based on Interest Collected over time
		$fromDate = Carbon::today()->subDays(29);
		$payments = LoanPayment::whereHas('loan', function ($q) use ($user) {
			$q->where('user_id', $user->id);
		})
			->where('status', 'paid')
			->whereBetween('paidDate', [$fromDate->startOfDay(), Carbon::today()->endOfDay()])
			->get(['paidDate', 'interest_portion']);

		$daily = [];
		for ($i = 0; $i < 30; $i++) {
			$daily[Carbon::today()->subDays($i)->format('Y-m-d')] = 0.0;
		}

		foreach ($payments as $payment) {
			$date = Carbon::parse($payment->paidDate)->format('Y-m-d');
			$daily[$date] = ($daily[$date] ?? 0) + (float) $payment->interest_portion;
		}

		$profitTrend = [];
		foreach (collect($daily)->sortKeys() as $date => $amount) {
			$profitTrend[] = [
				'date' => $date,
				'amount' => round($amount, 2),
			];
		}
		return $profitTrend;
	}
}
