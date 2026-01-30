<?php

namespace App\Mail;

use App\Models\Loan;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LoanClosedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $loan;
    public $borrower;
    public $business;
    public $lender;
    public $totalPaid;
    public $loanDuration;

    /**
     * Create a new message instance.
     */
    public function __construct(Loan $loan)
    {
        $this->loan = $loan;
        $this->borrower = $loan->borrower;
        $this->lender = $loan->user;
        $this->business = $loan->user->businessProfile->businessName ?? 'Cryndol';
        $this->totalPaid = $loan->totalPaid ?? 0;

        // Calculate loan duration
        if ($loan->startDate && $loan->dueDate) {
            $this->loanDuration = $loan->startDate->diffInMonths($loan->dueDate);
        } else {
            $this->loanDuration = $loan->termMonths;
        }
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject("Loan Closed - Congratulations!")
            ->view('emails.loan-closed');
    }
}
