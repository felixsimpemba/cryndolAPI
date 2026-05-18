<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\LoanSchedule;
use App\Models\Loan;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Mail\PaymentReminderMail;

class SendEmailReminders extends Command
{
    protected $signature = 'send:reminders';
    protected $description = 'Send automated email reminders for upcoming loan payments';

    public function handle()
    {
        // Find schedules due in exactly 3 days
        $targetDate = Carbon::today()->addDays(3)->toDateString();
        $upcomingSchedules = LoanSchedule::where('due_date', $targetDate)
            ->whereIn('status', ['PENDING', 'PARTIAL'])
            ->with(['loan.customer', 'loan.business'])
            ->get();

        $this->info("Found " . $upcomingSchedules->count() . " reminders to send.");

        foreach ($upcomingSchedules as $schedule) {
            $loan = $schedule->loan;
            $customer = $loan->customer;

            if ($customer && $customer->email) {
                // Calculate balance for the reminder
                $totalDue = ($schedule->principal_amount + $schedule->interest_amount + $schedule->fee_amount + $schedule->penalty_amount);
                $totalPaid = ($schedule->principal_paid + $schedule->interest_paid + $schedule->fee_paid + $schedule->penalty_paid);
                $balance = max(0, $totalDue - $totalPaid);

                if ($balance > 0) {
                    try {
                        Mail::to($customer->email)->send(new PaymentReminderMail($loan, $balance));
                        $this->line("Sent reminder to {$customer->email} for Loan {$loan->loan_number}");
                        
                        // We could log this in CommunicationsLog if needed
                    } catch (\Exception $e) {
                        $this->error("Failed to send to {$customer->email}: " . $e->getMessage());
                    }
                }
            }
        }

        $this->info("Email reminder process complete.");
    }
}
