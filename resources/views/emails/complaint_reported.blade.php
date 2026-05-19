<!DOCTYPE html>
<html>
<head>
    <title>New Complaint Reported</title>
</head>
<body>
    <h1>Hello Admin,</h1>
    <p>A new inventory complaint has been reported.</p>
    <p><strong>Subject:</strong> {{ $complaint->subject }}</p>
    <p><strong>Item:</strong> {{ $complaint->item->name }}</p>
    <p><strong>Reported By:</strong> {{ $complaint->user->name }}</p>
    <p><strong>Description:</strong></p>
    <p>{{ $complaint->description }}</p>
    <p>Please log in to the admin dashboard to review and handle this complaint.</p>
    <p>Thank you.</p>
</body>
</html>
