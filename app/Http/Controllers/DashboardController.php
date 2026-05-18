<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Customer;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\LoanSchedule;
use App\Models\Account;
use App\Models\JournalEntry;
use OpenApi\Attributes as OA;

class DashboardController extends Controller
{
	public function summary(Request $request, $businessId)
	{
		try {
			$user = $request->user();
			$bId = $user->business_id;

			if (!$bId) {
				return response()->json([
					'coreMetrics' => [
						'totalCapitalInvested' => 0,
						'currentBalance' => 0,
						'realizedProfit' => 0,
						'totalActiveLoanAmount' => 0,
						'numberOfCustomers' => 0,
						'customersWithActiveDebt' => 0,
						'loansDueNext7Days' => ['count' => 0, 'amount' => 0],
						'loanStatusCounts' => ['active' => 0, 'closed' => 0, 'defaulted' => 0]
					],
					'growthMetrics' => [
						'monthlyRevenue' => 0,
						'monthlyProfit' => 0,
						'revenueGrowthRate' => 0,
						'customerGrowthRate' => 0,
						'revenueTrend' => []
					]
				]);
			}

			// 1. Core Accounting Metrics
			$totalCapitalInvested = (float) Account::where('business_id', $bId)->where('type', 'EQUITY')->sum('balance');
			$currentBalance = (float) Account::where('business_id', $bId)->where('code', '1010')->value('balance');
			
			$totalRevenue = (float) Account::where('business_id', $bId)->where('type', 'REVENUE')->sum('balance');
			$totalExpenses = (float) Account::where('business_id', $bId)->where('type', 'EXPENSE')->sum('balance');
			$realizedProfit = $totalRevenue - $totalExpenses;

			$totalActiveLoanAmount = (float) Loan::where('business_id', $bId)
				->where('status', 'ACTIVE')
				->sum('principal_amount');

			$numberOfCustomers = Customer::where('business_id', $bId)->count();
			$customersWithActiveDebt = Loan::where('business_id', $bId)->where('status', 'ACTIVE')->distinct('customer_id')->count();

			// Upcoming receivables (7 days)
			$next7Days = Carbon::now()->addDays(7)->toDateString();
			$dueNext7Count = LoanSchedule::where('business_id', $bId)
				->whereBetween('due_date', [Carbon::now()->toDateString(), $next7Days])
				->whereIn('status', ['PENDING', 'PARTIAL', 'OVERDUE'])
				->count();
			
			$dueNext7Amount = (float) LoanSchedule::where('business_id', $bId)
				->whereBetween('due_date', [Carbon::now()->toDateString(), $next7Days])
				->whereIn('status', ['PENDING', 'PARTIAL', 'OVERDUE'])
				->selectRaw('SUM(principal_amount + interest_amount + fee_amount + penalty_amount) as total')
				->value('total');

			return response()->json([
				'coreMetrics' => [
					'totalCapitalInvested' => round($totalCapitalInvested, 2),
					'currentBalance' => round($currentBalance, 2),
					'realizedProfit' => round($realizedProfit, 2),
					'totalActiveLoanAmount' => round($totalActiveLoanAmount, 2),
					'numberOfCustomers' => $numberOfCustomers,
					'customersWithActiveDebt' => $customersWithActiveDebt,
					'loansDueNext7Days' => [
						'count' => (int) $dueNext7Count,
						'amount' => round($dueNext7Amount, 2)
					],
					'loanStatusCounts' => [
						'active' => Loan::where('business_id', $bId)->where('status', 'ACTIVE')->count(),
						'closed' => Loan::where('business_id', $bId)->where('status', 'PAID')->count(),
						'defaulted' => Loan::where('business_id', $bId)->where('status', 'DEFAULTED')->count()
					]
				],
				'growthMetrics' => [
					'monthlyRevenue' => 0,
					'monthlyProfit' => 0,
					'revenueGrowthRate' => 0,
					'customerGrowthRate' => 0,
					'revenueTrend' => []
				]
			]);
		} catch (\Exception $e) {
			return $this->logAndResponseError($e, 'Failed to retrieve dashboard data');
		}
	}
}
