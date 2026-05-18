<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ApplyLatePenalties extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:apply-late-penalties';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Applies late penalties to overdue loan installments';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to apply late penalties...');

        // Find schedules that are due before today and not fully paid
        $overdueSchedules = \App\Models\LoanSchedule::where('due_date', '<', now()->startOfDay())
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->with(['loan.loanTemplate'])
            ->get();

        $count = 0;

        foreach ($overdueSchedules as $schedule) {
            /** @var \App\Models\LoanSchedule $schedule */
            $loan = $schedule->loan;
            $product = $loan->loanTemplate;

            if ($product && $product->late_penalty_value > 0) {
                $dueDate = \Illuminate\Support\Carbon::parse($schedule->due_date);
                $daysLate = $dueDate->diffInDays(now());
                $frequency = $product->late_penalty_frequency ?? 'once';
                
                $penaltyTimes = 0;
                if ($frequency === 'once') {
                    $penaltyTimes = 1;
                } elseif ($frequency === 'daily') {
                    $penaltyTimes = $daysLate;
                } elseif ($frequency === 'weekly') {
                    $penaltyTimes = floor($daysLate / 7);
                } elseif ($frequency === 'monthly') {
                    $penaltyTimes = floor($daysLate / 30);
                }

                if ($penaltyTimes > 0) {
                    $basePenalty = 0;
                    if ($product->late_penalty_type === 'fixed') {
                        $basePenalty = (float) $product->late_penalty_value;
                    } elseif ($product->late_penalty_type === 'percentage') {
                        // Percentage of principal amount per the user request: "10% of the principle amount"
                        $basePenalty = (float) $loan->principal * ((float) $product->late_penalty_value / 100.0);
                    }

                    $totalPenalty = $basePenalty * $penaltyTimes;

                    if ($totalPenalty > $schedule->penalty_amount) {
                        $schedule->penalty_amount = $totalPenalty;
                        $schedule->status = 'overdue';
                        $schedule->save();
                        $count++;
                        $this->line("Updated penalty to {$totalPenalty} for Loan #{$loan->id} Schedule #{$schedule->installment_number} ({$penaltyTimes} times late)");
                    }
                }
            }

            // Always mark as overdue if past due date
            if ($schedule->status !== 'overdue' && $schedule->status !== 'paid') {
                $schedule->status = 'overdue';
                $schedule->save();
            }
        }

        $this->info("Finished applying late penalties. Total applied: {$count}");
    }
}
