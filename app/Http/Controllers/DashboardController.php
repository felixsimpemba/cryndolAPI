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

		$totalBorrowers = Borrower::where('user_id', $user->id)->count();
		$totalLoans = Loan::where('user_id', $user->id)->count();

		$totalOutstandingAmount = LoanPayment::whereHas('loan', function ($q) use ($user) {
			$q->where('user_id', $user->id)->where('status', 'active');
		})
			->whereIn('status', ['scheduled', 'overdue'])
			->sum(DB::raw('(amountScheduled - amountPaid)'));

		$totalPaidAmount = LoanPayment::whereHas('loan', function ($q) use ($user) {
			$q->where('user_id', $user->id);
		})
			->where('status', 'paid')
			->sum('amountPaid');

		$currentBalance = Transaction::where('user_id', $user->id)
			->selectRaw("COALESCE(SUM(CASE WHEN type = 'inflow' THEN amount ELSE 0 END),0) - COALESCE(SUM(CASE WHEN type = 'outflow' THEN amount ELSE 0 END),0) as balance")
			->value('balance') ?? 0;

		$loansDueInNext7Days = LoanPayment::whereHas('loan', function ($q) use ($user) {
			$q->where('user_id', $user->id)->where('status', 'active');
		})
			->whereBetween('scheduledDate', [Carbon::today(), Carbon::today()->addDays(7)])
			->whereIn('status', ['scheduled', 'overdue'])
			->count();

		// Profit trend: realized profit as inflow category 'profit' minus outflow 'loss' (extend as needed)
		$fromDate = Carbon::today()->subDays(29);
		$profitRows = Transaction::where('user_id', $user->id)
			->whereBetween('occurred_at', [$fromDate->startOfDay(), Carbon::today()->endOfDay()])
			->get(['occurred_at', 'type', 'amount', 'category']);

		$daily = [];
		for ($i = 0; $i < 30; $i++) {
			$daily[Carbon::today()->subDays($i)->format('Y-m-d')] = 0.0;
		}

		foreach ($profitRows as $row) {
			$date = Carbon::parse($row->occurred_at)->format('Y-m-d');
			$delta = ($row->type === 'inflow') ? (float)$row->amount : -(float)$row->amount;
			$daily[$date] = ($daily[$date] ?? 0) + $delta;
		}

		$profitTrend = [];
		foreach (collect($daily)->sortKeys() as $date => $amount) {
			$profitTrend[] = [
				'date' => $date,
				'amount' => round($amount, 2),
			];
		}

		return response()->json([
			'totalBorrowers' => $totalBorrowers,
			'totalLoans' => $totalLoans,
			'totalOutstandingAmount' => round((float)$totalOutstandingAmount, 2),
			'totalPaidAmount' => round((float)$totalPaidAmount, 2),
			'currentBalance' => round((float)$currentBalance, 2),
			'loansDueInNext7Days' => $loansDueInNext7Days,
			'profitTrend' => $profitTrend,
		]);
	}
}


