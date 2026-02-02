<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Payment Receipt</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .details {
            margin-bottom: 20px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .table th,
        .table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        .table th {
            background-color: #f2f2f2;
        }

        .footer {
            text-align: center;
            margin-top: 50px;
            font-size: 12px;
            color: #777;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>UNIVERSITY PORTAL</h1>
        <h2>Payment Receipt</h2>
        <p>Reference: {{ $payment->gateway_reference }}</p>
    </div>

    <div class="details">
        <p><strong>Student Name:</strong> {{ $user->name }}</p>
        <p><strong>Email:</strong> {{ $user->email }}</p>
        <p><strong>Date:</strong>
            {{ $payment->paid_at ? $payment->paid_at->format('d M, Y H:i A') : now()->format('d M, Y H:i A') }}</p>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Description</th>
                <th>Amount (₦)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td>{{ number_format($item->amount, 2) }}</td>
                </tr>
            @endforeach
            <tr>
                <td><strong>Total</strong></td>
                <td><strong>{{ number_format($payment->amount, 2) }}</strong></td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <p>This is an electronically generated receipt.</p>
    </div>
</body>

</html>