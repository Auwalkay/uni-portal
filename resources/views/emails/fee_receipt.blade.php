<!DOCTYPE html>
<html>

<head>
    <title>Payment Receipt</title>
</head>

<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 5px;">
        <h2 style="color: #2c3e50;">Payment Receipt</h2>

        <p>Dear {{ $user->name }},</p>

        <p>Thank you for your payment. We have received the sum of
            <strong>₦{{ number_format($payment->amount, 2) }}</strong>.</p>

        <p><strong>Payment Reference:</strong> {{ $payment->gateway_reference }}</p>
        <p><strong>Date:</strong>
            {{ $payment->paid_at ? $payment->paid_at->format('d M, Y') : now()->format('d M, Y') }}</p>

        <p>Please find your payment receipt attached to this email.</p>

        <p>Best regards,<br>Bursary Department</p>
    </div>
</body>

</html>