<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Payment Receipt - MIU Nigeria</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #1e293b;
            margin: 0;
            padding: 0;
            background-color: #f8fafc;
        }

        .container {
            max-width: 600px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            border: 1px solid #e2e8f0;
        }

        .header {
            background-color: #E31E24;
            color: #ffffff;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .content {
            padding: 40px;
        }

        .greeting {
            font-size: 18px;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 20px;
        }

        .success-box {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            text-align: center;
        }

        .amount {
            font-size: 32px;
            font-weight: 900;
            color: #166534;
            margin: 10px 0;
        }

        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .details-table td {
            padding: 12px 0;
            border-bottom: 1px solid #f1f5f9;
            font-size: 14px;
        }

        .label {
            color: #64748b;
            font-weight: 500;
        }

        .value {
            text-align: right;
            color: #0f172a;
            font-weight: 600;
        }

        .footer {
            background-color: #f1f5f9;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
        }

        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #E31E24;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            @php
                $logoPath = public_path('miu-logo.png');
                if (!file_exists($logoPath)) {
                    $logoPath = public_path('miu-logo.jpeg');
                }
                $logoBase64 = base64_encode(file_get_contents($logoPath));
            @endphp
            <img src="data:image/png;base64,{{ $logoBase64 }}" style="width: 80px; height: auto; margin-bottom: 10px;">
            <h1>Mewar International University</h1>
        </div>
        <div class="content">
            <div class="greeting">Payment Successful!</div>
            <p>Dear {{ $user->name }},</p>
            <p>We are pleased to confirm that your payment has been successfully processed. Below are the transaction
                details for your records.</p>

            <div class="success-box">
                <div class="label">Amount Paid</div>
                <div class="amount">₦{{ number_format($payment->amount, 2) }}</div>
                <div style="font-size: 12px; color: #166534; font-weight: 600;">Transaction Reference:
                    {{ $payment->gateway_reference }}</div>
            </div>

            <table class="details-table">
                <tr>
                    <td class="label">Payment Type</td>
                    <td class="value">{{ strtoupper(str_replace('_', ' ', $invoice->type)) }}</td>
                </tr>
                <tr>
                    <td class="label">Date & Time</td>
                    <td class="value">
                        {{ $payment->paid_at ? $payment->paid_at->format('d M, Y H:i') : now()->format('d M, Y H:i') }}
                    </td>
                </tr>
                <tr>
                    <td class="label">Payment Method</td>
                    <td class="value">{{ strtoupper($payment->channel ?? 'Online Gateway') }}</td>
                </tr>
            </table>

            <p>An official receipt has been attached to this email as a PDF document. You can also view and download all
                your receipts at any time from your student portal.</p>
        </div>
        <div class="footer">
            <p>This is an automated notification from the MIU Bursary Department.<br>
                Please do not reply directly to this email.</p>
            <p>&copy; {{ date('Y') }} Mewar International University Nigeria. All rights reserved.</p>
        </div>
    </div>
</body>

</html>