<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Transaction notification</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #1f2937;">
    <h2 style="color: #2563eb;">Transaction update</h2>
    <p>Hello,</p>
    <p>Your transaction request has been received and is being processed.</p>
    <p><strong>Reference:</strong> {{ $transaction->id ?? 'n/a' }}</p>
    <p>Thanks,<br>Team</p>
</body>
</html>
