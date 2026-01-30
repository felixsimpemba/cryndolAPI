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
    public $borrower;
    public $business;
    public $lender;
    public $monthlyPayment;

    /**
     * Create a new message instance.
     */
    public function __construct(Loan $loan)
    {
        $this->loan = $loan;
        $this->borrower = $loan->borrower;
        $this->lender = $loan->user;
        $this->business = $loan->user->businessProfile->businessName ?? 'Cryndol';

        // Calculate monthly payment
        $totalInterest = ($loan->principal * $loan->interestRate * $loan->termMonths) / 100;
        $totalAmount = $loan->principal + $totalInterest;
        $this->monthlyPayment = $totalAmount / $loan->termMonths;
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
