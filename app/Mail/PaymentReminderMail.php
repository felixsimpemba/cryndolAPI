<?php

namespace App\Mail;

use App\Models\Loan;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PaymentReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public $loan;
    public $borrower;
    public $business;
    public $lender;
    public $balance;

    /**
     * Create a new message instance.
     */
    public function __construct(Loan $loan, $balance)
    {
        $this->loan = $loan;
        $this->borrower = $loan->borrower;
        $this->lender = $loan->user;
        $this->business = $loan->user->businessProfile->businessName ?? 'Cryndol';
        $this->balance = $balance;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject("Payment Reminder - Loan #{$this->loan->id}")
            ->view('emails.payment-reminder');
    }
}
