<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Payment Receipt - {{ $payment->gateway_reference }}</title>
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
            margin-bottom: 40px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 20px;
        }

        .header-table {
            width: 100%;
        }

        .logo {
            width: 70px;
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
            padding: 6px 15px;
            border-radius: 4px;
            font-weight: 800;
            font-size: 14px;
            text-transform: uppercase;
            display: inline-block;
        }

        .info-grid {
            width: 100%;
            margin-bottom: 30px;
        }

        .info-section {
            vertical-align: top;
            width: 50%;
        }

        .label {
            font-size: 9px;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            margin-bottom: 4px;
            display: block;
        }

        .value {
            font-size: 12px;
            font-weight: 600;
            color: #0f172a;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .items-table th {
            text-align: left;
            padding: 10px;
            background: #f8fafc;
            border-bottom: 2px solid #e2e8f0;
            color: #64748b;
            font-size: 10px;
            text-transform: uppercase;
        }

        .items-table td {
            padding: 12px 10px;
            border-bottom: 1px solid #f1f5f9;
        }

        .summary-wrapper {
            float: right;
            width: 250px;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }

        .summary-table td {
            padding: 8px 0;
        }

        .total-row {
            border-top: 2px solid #e2e8f0;
            font-weight: 800;
            font-size: 16px;
            color: #E31E24;
        }

        .status-stamp {
            border: 3px solid #10b981;
            color: #10b981;
            font-weight: 900;
            font-size: 24px;
            padding: 10px 20px;
            display: inline-block;
            transform: rotate(-10deg);
            opacity: 0.2;
            position: absolute;
            top: 150px;
            right: 50px;
            text-transform: uppercase;
            border-radius: 8px;
        }

        .footer {
            clear: both;
            margin-top: 60px;
            padding-top: 20px;
            border-top: 1px solid #f1f5f9;
            text-align: center;
            font-size: 9px;
            color: #94a3b8;
        }

        .signature-section {
            margin-top: 40px;
        }

        .signature-box {
            width: 200px;
            text-align: center;
        }

        .sig-line {
            border-bottom: 1px solid #334155;
            margin-bottom: 5px;
        }

        .naira {
            font-family: 'DejaVu Sans', sans-serif !important;
        }
    </style>
</head>

<body>
    <div class="top-accent"></div>
    <div class="status-stamp">Official Receipt</div>

    <div class="container">
        <div class="header">
            <table class="header-table">
                <tr>
                    <td width="80">
                        @php
                            $logoPath = public_path('miu-logo.png');
                            if (!file_exists($logoPath)) {
                                $logoPath = public_path('miu-logo.jpeg');
                            }
                        @endphp
                        <img src="data:image/jpeg;base64,{{ base64_encode(file_get_contents($logoPath)) }}"
                            class="logo">
                    </td>
                    <td class="uni-info">
                        <h1>Mewar International University</h1>
                        <p>Nigeria Campus</p>
                        <p>KM 21 Abuja Keffi Rd, Masaka, Nasarawa State</p>
                        <p>www.miu.edu.ng | admission@miu.edu.ng</p>
                    </td>
                    <td align="right" valign="top">
                        <div class="receipt-badge">Payment Receipt</div>
                        <p style="margin-top: 10px; font-weight: 700; color: #64748b;">
                            REF: {{ $payment->gateway_reference }}
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        <table class="info-grid">
            <tr>
                <td class="info-section">
                    <span class="label">Billed To:</span>
                    <div class="value" style="font-size: 14px; color: #E31E24;">{{ strtoupper($payment->user->name) }}
                    </div>
                    <div class="value" style="font-weight: 400; color: #64748b;">{{ $payment->user->email }}</div>
                    <div class="value" style="margin-top: 5px;">
                        Matric No: {{ $student->matriculation_number ?? 'N/A' }}<br>
                        {{ $student->program->name ?? 'Student' }}
                    </div>
                </td>
                <td class="info-section" align="right">
                    <span class="label">Payment Details:</span>
                    <div class="value">Date: {{ $payment->paid_at->format('d M, Y') }}</div>
                    <div class="value">Gateway: {{ strtoupper($payment->gateway ?? 'SQUADCO') }}</div>
                    <div class="value">Method: {{ strtoupper($payment->channel ?? 'N/A') }}</div>
                    <div class="value">Session: {{ $invoice->session->name ?? 'N/A' }}</div>
                    <div class="value">Status: <span style="color: #10b981;">SUCCESSFUL</span></div>
                </td>
            </tr>
        </table>

        <table class="items-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th align="right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="font-weight: 600;">
                        {{ strtoupper(str_replace('_', ' ', $invoice->type)) }} PAYMENT
                        <div style="font-weight: 400; font-size: 10px; color: #64748b; margin-top: 2px;">
                            Payment for academic year requirements
                        </div>
                    </td>
                    <td align="right" class="value"><span
                            class="naira">&#x20A6;</span>{{ number_format($payment->amount, 2) }}</td>
                </tr>
            </tbody>
        </table>

        <div class="summary-wrapper">
            <table class="summary-table">
                <tr>
                    <td class="label">Invoice Total</td>
                    <td align="right" class="value"><span
                            class="naira">&#x20A6;</span>{{ number_format($invoice->amount, 2) }}</td>
                </tr>
                <tr>
                    <td class="label">Total Paid to Date</td>
                    <td align="right" class="value"><span
                            class="naira">&#x20A6;</span>{{ number_format($invoice->paid_amount, 2) }}</td>
                </tr>
                <tr class="total-row">
                    <td style="padding: 15px 0;">Balance Remaining</td>
                    <td align="right" style="padding: 15px 0;"><span
                            class="naira">&#x20A6;</span>{{ number_format($invoice->amount - $invoice->paid_amount, 2) }}
                    </td>
                </tr>
            </table>
        </div>

        <div class="footer" style="clear: both; margin-top: 40px;">
            <p><strong>Note:</strong> This is a valid electronic receipt generated from the Mewar International
                University ERP.
                No physical signature is required for its validity.</p>
            <p>&copy; {{ date('Y') }} Mewar International University. All rights reserved.</p>
        </div>
    </div>
</body>

</html>