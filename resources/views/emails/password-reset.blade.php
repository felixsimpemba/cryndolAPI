<!DOCTYPE html>
<html>

<head>
    <title>Reset Your Password</title>
</head>

<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #0F172A;">Reset Your Cryndol Password</h2>
        <p>Hello,</p>
        <p>You have requested to reset your password. Please use the following One-Time Password (OTP) to proceed:</p>

        <div style="background-color: #F1F5F9; padding: 20px; text-align: center; border-radius: 8px; margin: 20px 0;">
            <h1 style="color: #10B981; margin: 0; letter-spacing: 5px; font-size: 32px;">{{ $otp }}</h1>
        </div>

        <p>This code will expire in 10 minutes.</p>
        <p>If you did not request a password reset, please ignore this email or contact support if you have concerns.
        </p>

        <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">

        <p style="font-size: 12px; color: #666;">
            Regards,<br>
            Cryndol Security Team
        </p>
    </div>
</body>

</html>