<!DOCTYPE html>
<html>
<head>
    <title>Team Invitation for Cryndol</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background: #ffffff; padding: 20px; border-radius: 8px;">
        <h2 style="color: #333333;">Welcome to Cryndol, {{ $user->fullName }}</h2>
        <p style="color: #555555;">
            You have been invited by <strong>{{ $inviter->fullName }}</strong> to join their team on Cryndol.
        </p>
        <p style="color: #555555;">
            To accept this invitation and set up your account password, click the button below:
        </p>
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $inviteUrl }}" 
               style="background-color: #10b981; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;">
               Accept Invitation
            </a>
        </div>
        <p style="color: #777777; font-size: 14px;">
            If you did not expect this invitation, you can safely ignore this email.
        </p>
        <hr style="border: 0; border-top: 1px solid #eeeeee; margin: 20px 0;">
        <p style="color: #999999; font-size: 12px; text-align: center;">
            &copy; {{ date('Y') }} Cryndol. All rights reserved.
        </p>
    </div>
</body>
</html>
