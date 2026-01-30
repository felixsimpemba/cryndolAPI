<?php

namespace App\Mail;

use App\Models\Loan;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PaymentReceivedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $loan;
    public $borrower;
    public $business;
    public $lender;
    public $paymentAmount;
    public $paymentDate;
    public $balance;

    /**
     * Create a new message instance.
     */
    public function __construct(Loan $loan, $paymentAmount, $paymentDate, $balance)
    {
        $this->loan = $loan;
        $this->borrower = $loan->borrower;
        $this->lender = $loan->user;
        $this->business = $loan->user->businessProfile->businessName ?? 'Cryndol';
        $this->paymentAmount = $paymentAmount;
        $this->paymentDate = $paymentDate;
        $this->balance = $balance;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject("Payment Received - Thank You!")
            ->view('emails.payment-received');
    }
}
