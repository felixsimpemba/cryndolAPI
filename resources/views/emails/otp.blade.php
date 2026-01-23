<!DOCTYPE html>
<html>

<head>
    <title>Verify Your Account</title>
</head>

<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #0F172A;">Verify Your Cryndol Account</h2>
        <p>Hello,</p>
        <p>Thank you for registering with Cryndol. Please use the following One-Time Password (OTP) to verify your email
            address and complete your registration:</p>

        <div style="background-color: #F1F5F9; padding: 20px; text-align: center; border-radius: 8px; margin: 20px 0;">
            <h1 style="color: #10B981; margin: 0; letter-spacing: 5px; font-size: 32px;">{{ $otp }}</h1>
        </div>

        <p>This code will expire in 10 minutes.</p>
        <p>If you did not create an account, no further action is required.</p>

        <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">

        <p style="font-size: 12px; color: #666;">
            Regards,<br>
            Cryndol Lite
        </p>
    </div>
</body>

</html>