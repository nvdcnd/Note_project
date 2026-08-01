<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Note Shared</title>
</head>
<body>
    <h1>Ding dong!</h1>
    <h3>
        {{ $user->name }} shared a note with you
    </h3>
    <p>Click here to view note</p>
    <a href="{{ route('note', ['id' => $notes->id]) }}">View Note</a>
    <p>Thank you for using our service</p>
    <p>Your sincerely, </p>
    <p>Note App</p>
</body>
</html>