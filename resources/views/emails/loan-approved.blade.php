<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: linear-gradient(135deg, #10B981 0%, #059669 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }

        .content {
            background: #f9f9f9;
            padding: 30px;
            border-radius: 0 0 10px 10px;
        }

        .detail-box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #10B981;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .label {
            font-weight: 600;
            color: #6B7280;
        }

        .value {
            font-weight: 700;
            color: #1F2937;
        }

        .success-badge {
            background: #D1FAE5;
            color: #065F46;
            padding: 8px 16px;
            border-radius: 20px;
            display: inline-block;
            font-weight: 600;
            margin: 10px 0;
        }

        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
            text-align: center;
            color: #6B7280;
            font-size: 14px;
        }

        .button {
            display: inline-block;
            padding: 12px 30px;
            background: #10B981;
            color: white !important;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin: 15px 0;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1 style="margin: 0; font-size: 28px;">🎉 Loan Approved!</h1>
        <p style="margin: 5px 0 0 0; opacity: 0.9;">{{ $business }}</p>
    </div>

    <div class="content">
        <p>Dear <strong>{{ $borrower->fullName }}</strong>,</p>

        <p>Congratulations! We are pleased to inform you that your loan application has been <span
                class="success-badge">APPROVED</span></p>

        <div class="detail-box">
            <h3 style="margin-top: 0; color: #10B981;">Loan Details</h3>

            <div class="detail-row">
                <span class="label">Loan ID:</span>
                <span class="value">#{{ $loan->id }}</span>
            </div>

            <div class="detail-row">
                <span class="label">Principal Amount:</span>
                <span class="value">K {{ number_format($loan->principal, 2) }}</span>
            </div>

            <div class="detail-row">
                <span class="label">Interest Rate:</span>
                <span class="value">{{ number_format($loan->interestRate, 2) }}% per month</span>
            </div>

            <div class="detail-row">
                <span class="label">Loan Term:</span>
                <span class="value">{{ $loan->termMonths }} {{ $loan->term_unit ?? 'months' }}</span>
            </div>

            <div class="detail-row">
                <span class="label">Estimated Monthly Payment:</span>
                <span class="value">K {{ number_format($monthlyPayment, 2) }}</span>
            </div>

            <div class="detail-row">
                <span class="label">Start Date:</span>
                <span
                    class="value">{{ $loan->startDate ? $loan->startDate->format('F d, Y') : 'To be determined' }}</span>
            </div>
        </div>

        <div
            style="background: #EFF6FF; padding: 15px; border-radius: 6px; border-left: 4px solid #3B82F6; margin: 20px 0;">
            <h4 style="margin: 0 0 10px 0; color: #1E40AF;">📋 Next Steps</h4>
            <ul style="margin: 0; padding-left: 20px;">
                <li>Your loan will be disbursed shortly</li>
                <li>You will receive payment schedule details soon</li>
                <li>Please ensure your contact information is up to date</li>
            </ul>
        </div>

        <p>Thank you for choosing <strong>{{ $business }}</strong>. We look forward to serving you!</p>

        <div class="footer">
            <p><strong>{{ $business }}</strong></p>
            <p>Representative: {{ $lender->fullName }}</p>
            @if($lender->email)
                <p>Email: {{ $lender->email }}</p>
            @endif
            @if($lender->phoneNumber)
                <p>Phone: {{ $lender->phoneNumber }}</p>
            @endif
        </div>
    </div>
</body>

</html>