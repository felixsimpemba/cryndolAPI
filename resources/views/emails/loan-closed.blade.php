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
            background: linear-gradient(135deg, #8B5CF6 0%, #7C3AED 100%);
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

        .success-box {
            background: linear-gradient(135deg, #D1FAE5 0%, #A7F3D0 100%);
            padding: 30px;
            border-radius: 12px;
            text-align: center;
            margin: 20px 0;
            border: 3px solid #10B981;
        }

        .detail-box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
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

        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
            text-align: center;
            color: #6B7280;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1 style="margin: 0; font-size: 32px;">🎊 Congratulations!</h1>
        <p style="margin: 5px 0 0 0; opacity: 0.9; font-size: 18px;">Loan Fully Paid</p>
    </div>

    <div class="content">
        <p>Dear <strong>{{ $borrower->fullName }}</strong>,</p>

        <div class="success-box">
            <h2 style="margin: 0 0 10px 0; color: #065F46; font-size: 28px;">🏆 LOAN CLOSED</h2>
            <p style="margin: 0; color: #047857; font-size: 16px;">Your loan has been successfully paid off!</p>
        </div>

        <p>We are delighted to inform you that your loan (ID: <strong>#{{ $loan->id }}</strong>) with
            <strong>{{ $business }}</strong> has been fully paid and closed.</p>

        <div class="detail-box">
            <h3 style="margin-top: 0; color: #8B5CF6;">Final Summary</h3>

            <div class="detail-row">
                <span class="label">Loan ID:</span>
                <span class="value">#{{ $loan->id }}</span>
            </div>

            <div class="detail-row">
                <span class="label">Total Amount Paid:</span>
                <span class="value" style="color: #10B981;">K {{ number_format($totalPaid, 2) }}</span>
            </div>

            <div class="detail-row">
                <span class="label">Loan Duration:</span>
                <span class="value">{{ $loanDuration }} {{ $loan->term_unit ?? 'months' }}</span>
            </div>

            <div class="detail-row">
                <span class="label">Status:</span>
                <span class="value" style="color: #10B981;">✓ CLOSED</span>
            </div>
        </div>

        <div
            style="background: #FEF3C7; padding: 15px; border-radius: 6px; border-left: 4px solid #F59E0B; margin: 20px 0;">
            <h4 style="margin: 0 0 10px 0; color: #92400E;">💡 What's Next?</h4>
            <p style="margin: 0;">Thank you for being a valued customer. Should you need financial assistance in the
                future, we would be honored to serve you again. We offer competitive rates and flexible terms tailored
                to your needs.</p>
        </div>

        <p style="font-size: 16px; font-weight: 600; color: #1F2937;">
            Thank you for your business and your trust in {{ $business }}. We wish you continued success!
        </p>

        <div style="text-align: center; margin: 30px 0;">
            <p style="font-size: 24px; margin: 0;">⭐⭐⭐⭐⭐</p>
            <p style="color: #6B7280; font-size: 14px; margin: 10px 0 0 0;">Excellent Customer - Loan Completed
                Successfully</p>
        </div>

        <div class="footer">
            <p><strong>{{ $business }}</strong></p>
            <p>Representative: {{ $lender->fullName }}</p>
            @if($lender->email)
                <p>Email: {{ $lender->email }}</p>
            @endif
            @if($lender->phoneNumber)
                <p>Phone: {{ $lender->phoneNumber }}</p>
            @endif
            <p style="margin-top: 15px; font-style: italic;">We look forward to serving you again!</p>
        </div>
    </div>
</body>

</html>