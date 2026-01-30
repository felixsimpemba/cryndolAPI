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
            background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);
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
            border-left: 4px solid #F59E0B;
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

        .alert-box {
            background: #FEF3C7;
            padding: 15px;
            border-radius: 6px;
            border-left: 4px solid #F59E0B;
            margin: 20px 0;
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
        <h1 style="margin: 0; font-size: 28px;">⏰ Payment Reminder</h1>
        <p style="margin: 5px 0 0 0; opacity: 0.9;">{{ $business }}</p>
    </div>

    <div class="content">
        <p>Dear <strong>{{ $borrower->fullName }}</strong>,</p>

        <p>This is a friendly reminder about your loan payment with <strong>{{ $business }}</strong>.</p>

        <div class="detail-box">
            <h3 style="margin-top: 0; color: #F59E0B;">Loan Summary</h3>

            <div class="detail-row">
                <span class="label">Loan ID:</span>
                <span class="value">#{{ $loan->id }}</span>
            </div>

            <div class="detail-row">
                <span class="label">Outstanding Balance:</span>
                <span class="value" style="color: #DC2626; font-size: 18px;">K {{ number_format($balance, 2) }}</span>
            </div>

            <div class="detail-row">
                <span class="label">Due Date:</span>
                <span class="value">{{ $loan->dueDate ? $loan->dueDate->format('F d, Y') : 'Please contact us' }}</span>
            </div>
        </div>

        <div class="alert-box">
            <h4 style="margin: 0 0 10px 0; color: #92400E;">📌 Important</h4>
            <p style="margin: 0;">Please make your payment at your earliest convenience to avoid any late fees or
                penalties.</p>
        </div>

        <p>If you have already made a payment, please disregard this reminder. If you have any questions or need to
            discuss payment arrangements, please don't hesitate to contact us.</p>

        <p>Thank you for your prompt attention to this matter.</p>

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