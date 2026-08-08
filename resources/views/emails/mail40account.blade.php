<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Note shared with you</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #1f2937;">
    <h2 style="color: #2563eb;">A note was shared with you</h2>
    <p>Hello,</p>
    <p>A note has been shared with you. Please sign in to review it.</p>

    @if($notes)
        <p><strong>Note title:</strong> {{ $notes->title ?? 'Untitled note' }}</p>
    @endif

    <p>
        @if($recipientEmail)
            This invitation was sent to: {{ $recipientEmail }}
        @endif
    </p>

    <p>Thanks,<br>Team</p>
</body>
</html>
