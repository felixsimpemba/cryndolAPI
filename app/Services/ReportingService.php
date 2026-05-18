<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\LoanSchedule;
use App\Models\Account;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportingService
{
    /**
     * Get core business intelligence metrics for a business.
     */
    public function getBusinessOverview(string $businessId)
    {
        $totalPrincipalDisbursed = (float) Loan::where('business_id', $businessId)
            ->whereIn('status', ['ACTIVE', 'PAID', 'DEFAULTED'])
            ->sum('principal_amount');

        $activePortfolio = (float) Loan::where('business_id', $businessId)
            ->where('status', 'ACTIVE')
            ->sum('principal_amount');

        $totalInterestEarned = (float) Account::where('business_id', $businessId)
            ->where('code', '4010')
            ->value('balance');

        $totalCustomers = Customer::where('business_id', $businessId)->count();
        
        // Portfolio at Risk (PAR) - Loans with schedules overdue by > 30 days
        $overdue30Amount = LoanSchedule::where('business_id', $businessId)
            ->where('due_date', '<', Carbon::now()->subDays(30))
            ->whereIn('status', ['PENDING', 'PARTIAL', 'OVERDUE'])
            ->distinct('loan_id')
            ->count();
            
        $activeLoansCount = Loan::where('business_id', $businessId)->where('status', 'ACTIVE')->count();
        $parRate = $activeLoansCount > 0 ? ($overdue30Amount / $activeLoansCount) * 100 : 0;

        return [
            'total_principal_disbursed' => $totalPrincipalDisbursed,
            'active_portfolio_value' => $activePortfolio,
            'total_interest_earned' => $totalInterestEarned,
            'total_customers' => $totalCustomers,
            'par_rate_30' => round($parRate, 2),
            'active_loans_count' => $activeLoansCount,
        ];
    }

    /**
     * Get performance metrics grouped by branch.
     */
    public function getBranchPerformance(string $businessId)
    {
        return DB::table('branches')
            ->where('branches.business_id', $businessId)
            ->leftJoin('loans', 'branches.id', '=', 'loans.branch_id')
            ->select(
                'branches.name as branch_name',
                DB::raw('count(loans.id) as total_loans'),
                DB::raw("sum(case when loans.status = 'ACTIVE' then loans.principal_amount else 0 end) as active_portfolio"),
                DB::raw('sum(loans.principal_amount) as principal_disbursed')
            )
            ->groupBy('branches.id', 'branches.name')
            ->get();
    }
}
