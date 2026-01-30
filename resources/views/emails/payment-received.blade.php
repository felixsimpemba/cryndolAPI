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
        .receipt-box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border: 2px dashed #10B981;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
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
    </style>
</head>
<body>
    <div class="header">
        <h1 style="margin: 0; font-size: 28px;">✅ Payment Received!</h1>
        <p style="margin: 5px 0 0 0; opacity: 0.9;">{{ $business }}</p>
    </div>
    
    <div class="content">
        <p>Dear <strong>{{ $borrower->fullName }}</strong>,</p>
        
        <p>We have received your payment. <span class="success-badge">THANK YOU!</span></p>
        
        <div class="receipt-box">
            <h3 style="margin-top: 0; color: #10B981; text-align: center;">Payment Receipt</h3>
            <p style="text-align: center; color: #6B7280; font-size: 14px; margin: 0 0 15px 0;">Loan #{{ $loan->id }}</p>
            
            <div class="detail-row">
                <span class="label">Payment Amount:</span>
                <span class="value" style="color: #10B981; font-size: 20px;">K {{ number_format($paymentAmount, 2) }}</span>
            </div>
            
            <div class="detail-row">
                <span class="label">Payment Date:</span>
                <span class="value">{{ \Carbon\Carbon::parse($paymentDate)->format('F d, Y') }}</span>
            </div>
            
            <div class="detail-row">
                <span class="label">Remaining Balance:</span>
                <span class="value">K {{ number_format($balance, 2) }}</span>
            </div>
        </div>
        
        @if($balance > 0)
        <div style="background: #EFF6FF; padding: 15px; border-radius: 6px; margin: 20px 0;">
            <p style="margin: 0; color: #1E40AF;">
                <strong>Next Payment:</strong> Please continue making payments until your loan is fully paid off.
            </p>
        </div>
        @else
        <div style="background: #D1FAE5; padding: 15px; border-radius: 6px; margin: 20px 0;">
            <p style="margin: 0; color: #065F46;">
                <strong>🎉 Congratulations!</strong> Your loan has been fully paid off!
            </p>
        </div>
        @endif
        
        <p>We appreciate your prompt payment and your trust in <strong>{{ $business }}</strong>.</p>
        
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
