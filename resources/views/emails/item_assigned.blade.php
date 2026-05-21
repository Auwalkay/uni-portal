<!DOCTYPE html>
<html>
<head>
    <title>New Item Assigned</title>
</head>
<body>
    <h1>Hello,</h1>
    <p>A new inventory item has been assigned to you.</p>
    <p><strong>Item:</strong> {{ $assignment->item->name }}</p>
    <p><strong>SKU:</strong> {{ $assignment->item->sku }}</p>
    <p><strong>Date Assigned:</strong> {{ $assignment->assigned_at }}</p>
    <p><strong>Expected Return Date:</strong> {{ $assignment->expected_return_date ?? 'N/A' }}</p>
    <p>Please log in to your dashboard to view more details.</p>
    <p>Thank you.</p>
</body>
</html>
