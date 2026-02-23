<?php

namespace App\Services;

use App\Models\Loan;
use TCPDF;

class LoanAgreementPdfService
{
    /**
     * Generate loan agreement PDF
     */
    public function generateLoanAgreement(Loan $loan)
    {
        $loan->load(['borrower', 'user', 'loanProduct', 'collateral']);

        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

        // Set document information
        $pdf->SetCreator('Cryndol Loan Management');
        $pdf->SetAuthor($loan->user->fullName ?? 'Cryndol');
        $pdf->SetTitle('Loan Agreement - #' . $loan->id);

        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Set margins
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 15);

        // Add a page
        $pdf->AddPage();

        // Generate HTML content
        $html = $this->getLoanAgreementHtml($loan);

        // Output the HTML content
        $pdf->writeHTML($html, true, false, true, false, '');

        // Ensure temp directory exists using Storage
        if (!\Illuminate\Support\Facades\Storage::exists('temp')) {
            \Illuminate\Support\Facades\Storage::makeDirectory('temp');
        }

        $filename = 'loan_agreement_' . $loan->id . '_' . now()->format('YmdHis') . '.pdf';
        // Get absolute path to storage/app/temp
        $tempPath = storage_path('app/temp');
        
        // Ensure directory exists physically if not created by Storage (sometimes needed if using local driver but accessing via absolute path)
        if (!file_exists($tempPath)) {
            mkdir($tempPath, 0755, true);
        }

        $filepath = $tempPath . '/' . $filename;

        // Save file
        $pdf->Output($filepath, 'F');
        
        return $filepath;
    }

    /**
     * Get HTML content for loan agreement
     */
    private function getLoanAgreementHtml(Loan $loan): string
    {
        $principal = number_format($loan->principal, 2);
        $interestRate = number_format($loan->interestRate, 2);
        $totalInterest = ($loan->principal * $loan->interestRate * $loan->termMonths) / 100;
        $totalAmount = $loan->principal + $totalInterest;
        $monthlyPayment = $totalAmount / $loan->termMonths;

        $borrowerName = $loan->borrower->fullName ?? 'N/A';
        $borrowerEmail = $loan->borrower->email ?? 'N/A';
        $borrowerPhone = $loan->borrower->phoneNumber ?? 'N/A';
        $borrowerNRC = $loan->borrower->nrc_number ?? 'N/A';
        $borrowerAddress = $loan->borrower->address ?? 'N/A';

        $lenderName = $loan->user->fullName ?? 'Cryndol Loan Management';
        $businessName = $loan->user->businessProfile->businessName ?? 'Cryndol';

        $startDate = $loan->startDate ? $loan->startDate->format('F d, Y') : 'N/A';
        $dueDate = $loan->dueDate ? $loan->dueDate->format('F d, Y') : 'N/A';
        $agreementDate = now()->format('F d, Y');

        $termUnit = $loan->term_unit ?? 'months';

        $html = <<<HTML
        <style>
            body { font-family: Arial, sans-serif; }
            h1 { color: #10B981; text-align: center; margin-bottom: 5px; }
            h2 { color: #059669; margin-top: 20px; }
            h3 { color: #047857; }
            .header { text-align: center; margin-bottom: 30px; }
            .agreement-details { margin: 20px 0; }
            .detail-row { margin: 10px 0; padding: 8px; background-color: #f9f9f9; }
            .detail-label { font-weight: bold; color: #374151; }
            .section { margin: 25px 0; }
            .parties { display: table; width: 100%; margin: 20px 0; }
            .party { display: table-cell; width: 50%; padding: 10px; }
            .signature-section { margin-top: 50px; }
            .signature-line { border-top: 1px solid #000; width: 200px; margin-top: 50px; }
            table { width: 100%; border-collapse: collapse; margin: 15px 0; }
            th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
            th { background-color: #10B981; color: white; }
        </style>
        
        <div class="header">
            <h1>LOAN AGREEMENT</h1>
            <p style="color: #6B7280;">Agreement No: LA-{$loan->id}</p>
            <p style="color: #6B7280;">Date: {$agreementDate}</p>
        </div>
        
        <div class="section">
            <h2>PARTIES TO THE AGREEMENT</h2>
            <table>
                <tr>
                    <th>Role</th>
                    <th>Details</th>
                </tr>
                <tr>
                    <td><strong>LENDER</strong></td>
                    <td>
                        <strong>{$businessName}</strong><br>
                        Representative: {$lenderName}
                    </td>
                </tr>
                <tr>
                    <td><strong>BORROWER</strong></td>
                    <td>
                        <strong>{$borrowerName}</strong><br>
                        NRC: {$borrowerNRC}<br>
                        Email: {$borrowerEmail}<br>
                        Phone: {$borrowerPhone}<br>
                        Address: {$borrowerAddress}
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="section">
            <h2>LOAN DETAILS</h2>
            <table>
                <tr>
                    <td class="detail-label">Principal Amount</td>
                    <td>K {$principal}</td>
                </tr>
                <tr>
                    <td class="detail-label">Interest Rate</td>
                    <td>{$interestRate}% per month</td>
                </tr>
                <tr>
                    <td class="detail-label">Loan Term</td>
                    <td>{$loan->termMonths} {$termUnit}</td>
                </tr>
                <tr>
                    <td class="detail-label">Total Interest</td>
                    <td>K {$totalInterest}</td>
                </tr>
                <tr>
                    <td class="detail-label">Total Amount to Repay</td>
                    <td><strong>K {$totalAmount}</strong></td>
                </tr>
                <tr>
                    <td class="detail-label">Estimated Monthly Payment</td>
                    <td>K {$monthlyPayment}</td>
                </tr>
                <tr>
                    <td class="detail-label">Start Date</td>
                    <td>{$startDate}</td>
                </tr>
                <tr>
                    <td class="detail-label">Due Date</td>
                    <td>{$dueDate}</td>
                </tr>
            </table>
        </div>
        HTML;

        // Conditionally add collateral section
        if ($loan->collateral) {
            $collateral = $loan->collateral;
            $collateralName = htmlspecialchars($collateral->name ?? 'N/A');
            $collateralDesc = htmlspecialchars($collateral->description ?? 'N/A');

            $html .= <<<HTML
        <div class="section">
            <h2>COLLATERAL</h2>
            <table>
                <tr>
                    <td class="detail-label" style="width:30%;">Collateral Item</td>
                    <td>{$collateralName}</td>
                </tr>
                <tr>
                    <td class="detail-label">Description</td>
                    <td>{$collateralDesc}</td>
                </tr>
            </table>
        </div>
        HTML;
        }

        $html .= <<<HTML
        <div class="section">
            <h2>TERMS AND CONDITIONS</h2>
            
            <h3>1. LOAN DISBURSEMENT</h3>
            <p>The Lender agrees to disburse the Principal Amount of K {$principal} to the Borrower upon execution of this agreement.</p>
            
            <h3>2. REPAYMENT OBLIGATION</h3>
            <p>The Borrower agrees to repay the total sum of K {$totalAmount} (Principal plus Interest) over the loan term of {$loan->termMonths} {$termUnit}, commencing from {$startDate} and ending on {$dueDate}.</p>
            
            <h3>3. INTEREST RATE</h3>
            <p>Interest shall accrue at the rate of {$interestRate}% per month on the outstanding principal balance.</p>
            
            <h3>4. PAYMENT SCHEDULE</h3>
            <p>The Borrower shall make payments according to the schedule agreed upon with the Lender. Late payments may incur additional charges.</p>
        HTML;

        if ($loan->collateral) {
            $securityName = htmlspecialchars($loan->collateral->name ?? 'the described collateral item');
            $html .= <<<HTML
            <h3>5. COLLATERAL SECURITY</h3>
            <p>As security for this loan, the Borrower pledges the following collateral: <strong>{$securityName}</strong>.
            The Borrower warrants that they have clear title to this collateral and that it is free from any encumbrances.
            In the event of default, the Lender shall have the right to take possession of and liquidate the collateral to recover the outstanding debt.</p>
        HTML;
            $defaultSection = 6;
            $consequencesSection = 7;
            $prepaymentSection = 8;
            $governingSection = 9;
        } else {
            $defaultSection = 5;
            $consequencesSection = 6;
            $prepaymentSection = 7;
            $governingSection = 8;
        }

        $html .= <<<HTML
            <h3>{$defaultSection}. DEFAULT</h3>
            <p>The Borrower shall be considered in default if:</p>
            <ul>
                <li>Payment is not received within the agreed timeframe</li>
                <li>The Borrower becomes insolvent or bankrupt</li>
                <li>Any representation made by the Borrower is found to be false</li>
            </ul>
            
            <h3>{$consequencesSection}. CONSEQUENCES OF DEFAULT</h3>
            <p>In the event of default, the Lender may:</p>
            <ul>
                <li>Demand immediate repayment of the entire outstanding balance</li>
                <li>Pursue legal action to recover the debt</li>
                <li>Report the default to credit bureaus</li>
            </ul>
            
            <h3>{$prepaymentSection}. PREPAYMENT</h3>
            <p>The Borrower may prepay the loan in full or in part at any time without penalty.</p>
            
            <h3>{$governingSection}. GOVERNING LAW</h3>
            <p>This agreement shall be governed by and construed in accordance with the laws of Zambia.</p>
        </div>
        
        <div class="section signature-section">
            <h2>SIGNATURES</h2>
            <p>By signing below, both parties acknowledge that they have read, understood, and agree to be bound by the terms and conditions of this Loan Agreement.</p>
            
            <table style="border: none; margin-top: 40px;">
                <tr>
                    <td style="border: none; width: 50%;">
                        <p><strong>LENDER</strong></p>
                        <div class="signature-line"></div>
                        <p>{$lenderName}</p>
                        <p>Date: _____________________</p>
                    </td>
                    <td style="border: none; width: 50%;">
                        <p><strong>BORROWER</strong></p>
                        <div class="signature-line"></div>
                        <p>{$borrowerName}</p>
                        <p>Date: _____________________</p>
                    </td>
                </tr>
            </table>
        </div>
        HTML;

        return $html;
    }
}
