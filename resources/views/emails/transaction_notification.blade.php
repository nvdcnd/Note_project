<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Transaction notification</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #1f2937; background-color: #f9fafb; margin: 0; padding: 24px;">
    <div style="max-width: 480px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 16px rgba(0,0,0,0.08);">
        <div style="background-color: #FACC15; padding: 16px 24px;">
            <h2 style="margin: 0; color: #000;">Noteket - Transaction update</h2>
        </div>
        <div style="padding: 24px;">
            <p>Hello,</p>
            <p>Your transaction request has been received and is being processed.</p>
            <p><strong>Reference:</strong> #{{ $transaction->id ?? 'n/a' }}</p>
            @if(!empty($otp))
                <p>Use the secure code below to confirm this transaction:</p>
                <div style="background: #FFE86E; border: 2px dashed #000; border-radius: 8px; padding: 16px; text-align: center; font-size: 1.6rem; font-weight: 700; letter-spacing: 6px;">
                    {{ $otp }}
                </div>
                <p style="font-size: 0.85rem; color: #6b7280;">This code expires in 10 minutes. Never share it with anyone.</p>
            @endif
            <p>Thanks,<br>Noteket Team</p>
        </div>
    </div>
</body>
</html>
