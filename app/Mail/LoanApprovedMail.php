<?php

namespace App\Mail;

use App\Models\Loan;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LoanApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $loan;
    public $customer;
    public $business;
    public $monthlyPayment;

    /**
     * Create a new message instance.
     */
    public function __construct(Loan $loan)
    {
        $this->loan = $loan;
        $this->customer = $loan->customer;
        $this->business = $loan->business->name ?? 'Cryndol';

        // Simplified calculation for the mail
        $totalInterest = ($loan->principal_amount * ($loan->interest_rate / 100));
        $totalAmount = $loan->principal_amount + $totalInterest;
        $this->monthlyPayment = ($loan->loan_term_months > 0) ? ($totalAmount / $loan->loan_term_months) : $totalAmount;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject("Loan Approved - {$this->business}")
            ->view('emails.loan-approved');
    }
}
