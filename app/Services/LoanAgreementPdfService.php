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
        $loan->load(['customer', 'user.business', 'loanTemplate', 'collaterals']);

        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

        // Set document information
        $pdf->SetCreator('Cryndol Loan Management');
        $pdf->SetAuthor($loan->user->full_name ?? 'Cryndol');
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

        if (!\Illuminate\Support\Facades\Storage::exists('temp')) {
            \Illuminate\Support\Facades\Storage::makeDirectory('temp');
        }

        $filename = 'loan_agreement_' . $loan->id . '_' . now()->format('YmdHis') . '.pdf';
        $tempPath = storage_path('app/temp');
        
        if (!file_exists($tempPath)) {
            mkdir($tempPath, 0755, true);
        }

        $filepath = $tempPath . '/' . $filename;
        $pdf->Output($filepath, 'F');
        
        return $filepath;
    }

    /**
     * Get HTML content for loan agreement
     */
    private function getLoanAgreementHtml(Loan $loan): string
    {
        $principal_amount = number_format($loan->principal_amount, 2);
        $interest_rate = number_format($loan->interest_rate, 2);
        
        // Simple interest for agreement display
        $totalInterest = ($loan->principal_amount * $loan->interest_rate * $loan->loan_term_months) / 100;
        $totalAmount = $loan->principal_amount + $totalInterest;
        $monthlyPayment = $totalAmount / max(1, $loan->loan_term_months);

        $customer = $loan->customer;
        $customerName = trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''));
        $customerEmail = $customer->email ?? 'N/A';
        $customerPhone = $customer->phone ?? 'N/A';
        $customerNRC = $customer->id_number ?? 'N/A'; // matched to new column
        $customerAddress = $customer->address ?? 'N/A';

        $lenderName = $loan->user->full_name ?? 'Cryndol Loan Management';
        $businessName = $loan->user->business->name ?? 'Cryndol';

        $start_date = $loan->start_date ? $loan->start_date->format('F d, Y') : 'N/A';
        $maturity_date = $loan->maturity_date ? $loan->maturity_date->format('F d, Y') : 'N/A';
        $agreementDate = now()->format('F d, Y');

        $termUnit = 'months';

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
                        <strong>{$customerName}</strong><br>
                        NRC: {$customerNRC}<br>
                        Email: {$customerEmail}<br>
                        Phone: {$customerPhone}<br>
                        Address: {$customerAddress}
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="section">
            <h2>LOAN DETAILS</h2>
            <table>
                <tr>
                    <td class="detail-label">Principal Amount</td>
                    <td>K {$principal_amount}</td>
                </tr>
                <tr>
                    <td class="detail-label">Interest Rate</td>
                    <td>{$interest_rate}% per month</td>
                </tr>
                <tr>
                    <td class="detail-label">Loan Term</td>
                    <td>{$loan->loan_term_months} {$termUnit}</td>
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
                    <td>{$start_date}</td>
                </tr>
                <tr>
                    <td class="detail-label">Maturity Date</td>
                    <td>{$maturity_date}</td>
                </tr>
            </table>
        </div>
        HTML;

        if ($loan->collaterals && $loan->collaterals->count() > 0) {
            foreach($loan->collaterals as $collateral) {
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
        }

        $html .= <<<HTML
        <div class="section">
            <h2>TERMS AND CONDITIONS</h2>
            
            <h3>1. LOAN DISBURSEMENT</h3>
            <p>The Lender agrees to disburse the Principal Amount of K {$principal_amount} to the Customer upon execution of this agreement.</p>
            
            <h3>2. REPAYMENT OBLIGATION</h3>
            <p>The Customer agrees to repay the total sum of K {$totalAmount} (Principal plus Interest) over the loan term of {$loan->loan_term_months} {$termUnit}, commencing from {$start_date} and ending on {$maturity_date}.</p>
            
            <h3>3. INTEREST RATE</h3>
            <p>Interest shall accrue at the rate of {$interest_rate}% per month on the outstanding principal balance.</p>
            
            <h3>4. PAYMENT SCHEDULE</h3>
            <p>The Customer shall make payments according to the schedule agreed upon with the Lender. Late payments may incur additional charges.</p>
        HTML;

        $html .= <<<HTML
            <h3>5. DEFAULT</h3>
            <p>The Customer shall be considered in default if:</p>
            <ul>
                <li>Payment is not received within the agreed timeframe</li>
                <li>The Customer becomes insolvent or bankrupt</li>
                <li>Any representation made by the Customer is found to be false</li>
            </ul>
            
            <h3>6. CONSEQUENCES OF DEFAULT</h3>
            <p>In the event of default, the Lender may:</p>
            <ul>
                <li>Demand immediate repayment of the entire outstanding balance</li>
                <li>Pursue legal action to recover the debt</li>
                <li>Report the default to credit bureaus</li>
            </ul>
            
            <h3>7. PREPAYMENT</h3>
            <p>The Customer may prepay the loan in full or in part at any time without penalty.</p>
            
            <h3>8. GOVERNING LAW</h3>
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
                        <p>{$customerName}</p>
                        <p>Date: _____________________</p>
                    </td>
                </tr>
            </table>
        </div>
        HTML;

        return $html;
    }
}
