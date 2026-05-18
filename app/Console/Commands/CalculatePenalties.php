<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\LoanSchedule;
use App\Models\Loan;
use Carbon\Carbon;
use App\Services\AccountingService;
use App\Models\Account;

class CalculatePenalties extends Command
{
    protected $signature = 'calculate:penalties';
    protected $description = 'Daily task to calculate and apply penalties to overdue loans';

    protected $accounting;

    public function __construct(AccountingService $accounting)
    {
        parent::__construct();
        $this->accounting = $accounting;
    }

    public function handle()
    {
        $today = Carbon::today()->toDateString();
        $overdueSchedules = LoanSchedule::where('due_date', '<', $today)
            ->whereIn('status', ['PENDING', 'PARTIAL', 'OVERDUE'])
            ->with('loan.loanTemplate')
            ->get();

        $this->info("Processing " . $overdueSchedules->count() . " overdue schedules...");

        foreach ($overdueSchedules as $schedule) {
            $loan = $schedule->loan;
            $template = $loan->loanTemplate;

            if ($template && $template->penalty_rate > 0) {
                // Check grace period
                $dueDate = Carbon::parse($schedule->due_date);
                $graceDays = $template->grace_period_days ?? 0;
                
                if (now()->diffInDays($dueDate) > $graceDays) {
                    // Update status to OVERDUE if not already
                    if ($schedule->status !== 'OVERDUE') {
                        $schedule->status = 'OVERDUE';
                        $loan->status = 'OVERDUE';
                        $loan->save();
                    }

                    // Calculation logic: Penalty = Outstanding Balance * Penalty Rate
                    $outstanding = ($schedule->principal_amount + $schedule->interest_amount + $schedule->fee_amount + $schedule->penalty_amount) 
                                 - ($schedule->principal_paid + $schedule->interest_paid + $schedule->fee_paid + $schedule->penalty_paid);
                    
                    if ($outstanding > 0) {
                        $penaltyAmount = round($outstanding * ($template->penalty_rate / 100), 2);
                        
                        if ($penaltyAmount > 0) {
                            $schedule->penalty_amount += $penaltyAmount;
                            $schedule->save();

                            // ACCOUNTING: Penalty Revenue Recognized
                            $this->recognizePenaltyRevenue($loan, $penaltyAmount);
                            
                            $this->line("Applied K$penaltyAmount penalty to Loan {$loan->loan_number} (Installment #{$schedule->installment_number})");
                        }
                    }
                }
            }
        }

        $this->info("Penalty calculation complete.");
    }

    protected function recognizePenaltyRevenue($loan, $amount)
    {
        $businessId = $loan->business_id;

        $receivableAcc = Account::firstOrCreate(
            ['business_id' => $businessId, 'code' => '1200'],
            ['name' => 'Loan Receivables', 'type' => 'ASSET']
        );
        $penaltyIncAcc = Account::firstOrCreate(
            ['business_id' => $businessId, 'code' => '4030'],
            ['name' => 'Penalty Income', 'type' => 'REVENUE']
        );

        $this->accounting->createEntry(
            $businessId,
            "Auto-penalty for Loan {$loan->loan_number}",
            null,
            now()->toDateString(),
            [
                ['account_id' => $receivableAcc->id, 'debit' => $amount, 'credit' => 0],
                ['account_id' => $penaltyIncAcc->id, 'debit' => 0, 'credit' => $amount],
            ]
        );
    }
}
