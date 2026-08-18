<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Hostel Payment Receipt - {{ $payment->gateway_reference }}</title>
    <style>
        @page {
            margin: 0;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            color: #1e293b;
            line-height: 1.5;
            margin: 0;
            padding: 0;
            background: #fff;
            font-size: 11px;
        }

        .container {
            padding: 40px 50px;
        }

        .top-accent {
            height: 6px;
            background: #E31E24;
            width: 100%;
        }

        .header {
            margin-bottom: 35px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 20px;
        }

        .header-table {
            width: 100%;
        }

        .logo {
            width: 65px;
            height: auto;
        }

        .uni-info h1 {
            font-size: 18px;
            font-weight: 800;
            color: #E31E24;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .uni-info p {
            font-size: 9px;
            color: #64748b;
            margin: 2px 0;
            text-transform: uppercase;
        }

        .receipt-badge {
            background: #fef2f2;
            color: #E31E24;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 800;
            font-size: 13px;
            text-transform: uppercase;
            text-align: right;
            border: 1px solid #fee2e2;
        }

        .info-grid {
            width: 100%;
            margin-bottom: 30px;
            border-collapse: collapse;
        }

        .info-section {
            vertical-align: top;
            width: 50%;
            padding: 10px 15px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
        }

        .label {
            font-size: 8px;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            margin-bottom: 3px;
        }

        .value {
            font-size: 11px;
            font-weight: 600;
            color: #0f172a;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 35px;
            margin-top: 10px;
        }

        .items-table th {
            text-align: left;
            padding: 12px 14px;
            background: #f1f5f9;
            border-bottom: 2px solid #e2e8f0;
            color: #475569;
            font-size: 10px;
            text-transform: uppercase;
            font-weight: 700;
        }

        .items-table td {
            padding: 12px 14px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 11px;
            color: #334155;
        }

        .summary-wrapper {
            float: right;
            width: 260px;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }

        .summary-table td {
            padding: 8px 10px;
            font-size: 11px;
        }

        .total-row {
            border-top: 2px solid #e2e8f0;
            font-weight: 800;
            font-size: 15px;
            color: #E31E24;
            background: #fff5f5;
        }

        .status-stamp-wrapper {
            float: left;
            margin-top: 10px;
        }

        .status-stamp {
            border: 2px solid #10b981;
            color: #10b981;
            font-weight: 800;
            font-size: 16px;
            padding: 6px 14px;
            border-radius: 4px;
            text-transform: uppercase;
            display: inline-block;
            transform: rotate(-8deg);
            background: #ecfdf5;
        }

        .footer {
            clear: both;
            margin-top: 60px;
            text-align: center;
            font-size: 10px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 20px;
        }
    </style>
</head>

<body>
    @php
        $logoPath = public_path('miu-logo.png');
        if (!file_exists($logoPath)) {
            $logoPath = public_path('miu-logo.jpeg');
        }
        $logoData = '';
        if (file_exists($logoPath)) {
            $logoData = 'data:image/' . pathinfo($logoPath, PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents($logoPath));
        }
    @endphp

    <div class="top-accent"></div>

    <div class="container">
        <div class="header">
            <table class="header-table">
                <tr>
                    <td style="width: 70px;">
                        @if($logoData)
                            <img src="{{ $logoData }}" class="logo" alt="MIU Logo" />
                        @endif
                    </td>
                    <td class="uni-info" style="vertical-align: middle; padding-left: 10px;">
                        <h1>Mewar International University</h1>
                        <p>KM 20, Abuja-Keffi Expressway, Masaka, Nasarawa State, Nigeria</p>
                        <p>Support: bursary@mewaruniversity.edu.ng | www.mewaruniversity.edu.ng</p>
                    </td>
                    <td style="text-align: right; vertical-align: middle;">
                        <div class="receipt-badge">
                            Hostel Receipt
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <table class="info-grid">
            <tr>
                <td class="info-section" style="margin-right: 10px;">
                    <div style="margin-bottom: 8px;">
                        <span class="label">Billed To:</span>
                        <div class="value" style="font-size: 13px; font-weight: 700;">{{ $user->name }}</div>
                    </div>
                    <div style="margin-bottom: 8px;">
                        <span class="label">Matric Number:</span>
                        <div class="value">{{ $user->student->matriculation_number ?? 'Pending' }}</div>
                    </div>
                    <div>
                        <span class="label">Email Address:</span>
                        <div class="value">{{ $user->email }}</div>
                    </div>
                </td>
                <td style="width: 20px;"></td>
                <td class="info-section">
                    <div style="margin-bottom: 8px;">
                        <span class="label">Receipt Reference:</span>
                        <div class="value" style="font-family: monospace; font-size: 12px; color: #E31E24;">{{ $payment->gateway_reference }}</div>
                    </div>
                    <div style="margin-bottom: 8px;">
                        <span class="label">Payment Date & Time:</span>
                        <div class="value">{{ $payment->paid_at ? $payment->paid_at->format('d M, Y \a\t h:i A') : now()->format('d M, Y \a\t h:i A') }}</div>
                    </div>
                    <div>
                        <span class="label">Payment Method:</span>
                        <div class="value" style="text-transform: uppercase;">{{ $payment->channel ?? 'Online Gateway' }}</div>
                    </div>
                </td>
            </tr>
        </table>

        <h3 style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #475569; margin-bottom: 5px;">Payment Breakdown</h3>
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 70%;">Description</th>
                    <th style="text-align: right; width: 30%;">Amount (₦)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $item)
                    <tr>
                        <td style="font-weight: 600;">{{ $item->description }}</td>
                        <td style="text-align: right; font-weight: 700;">{{ number_format($item->amount, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div style="width: 100%;">
            <div class="status-stamp-wrapper">
                <div class="status-stamp">
                    Paid Successfully
                </div>
            </div>

            <div class="summary-wrapper">
                <table class="summary-table">
                    <tr>
                        <td style="color: #64748b; font-weight: 600;">Total Invoiced:</td>
                        <td style="text-align: right; font-weight: 700; color: #334155;">₦{{ number_format($invoice->amount, 2) }}</td>
                    </tr>
                    <tr class="total-row">
                        <td style="font-weight: 800; padding: 10px;">Amount Paid:</td>
                        <td style="text-align: right; font-weight: 800; padding: 10px;">₦{{ number_format($payment->amount, 2) }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="footer">
            <p>Mewar International University — Official Hostel Payment Receipt.</p>
            <p>This is an electronically generated and verified receipt. No physical signature is required.</p>
            <p style="margin-top: 5px; font-size: 8px;">Generated on {{ now()->format('Y-m-d H:i:s') }}</p>
        </div>
    </div>
</body>

</html>