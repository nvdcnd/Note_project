<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password change</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #1f2937;">
    <h2 style="color: #2563eb;">Password change request</h2>
    <p>Hello,</p>
    <p>Use the secure code below to continue with your password change request.</p>
    <p><strong>Code:</strong> {{ $passkey ?? 'n/a' }}</p>
    <p>Thanks,<br>Team</p>
</body>
</html>
