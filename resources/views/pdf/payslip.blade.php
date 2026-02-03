<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Staff Payslip</title>
    <style>
        body {
            font-family: 'Helvetica', sans-serif;
            color: #1e293b;
            line-height: 1.5;
            margin: 0;
            padding: 40px;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #1e3a8a;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .university-name {
            font-size: 18px;
            color: #475569;
            margin-bottom: 5px;
        }

        .document-title {
            font-size: 14px;
            font-weight: bold;
            color: #3b82f6;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .info-section {
            width: 100%;
            margin-bottom: 30px;
            border-collapse: collapse;
        }

        .info-box {
            width: 50%;
            vertical-align: top;
            padding: 10px;
            border: 1px solid #e2e8f0;
            background-color: #f8fafc;
        }

        .info-label {
            font-size: 10px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .info-value {
            font-size: 14px;
            color: #0f172a;
            font-weight: bold;
        }

        .salary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .salary-table th {
            background-color: #f1f5f9;
            text-align: left;
            padding: 12px;
            font-size: 12px;
            color: #475569;
            border: 1px solid #e2e8f0;
            text-transform: uppercase;
        }

        .salary-table td {
            padding: 12px;
            border: 1px solid #e2e8f0;
            font-size: 13px;
        }

        .section-header {
            background-color: #f8fafc;
            font-weight: bold;
            color: #1e40af;
        }

        .amount {
            text-align: right;
            font-family: 'Courier', monospace;
            font-weight: bold;
        }

        .total-row {
            background-color: #eff6ff;
            font-weight: bold;
            font-size: 15px;
        }

        .footer {
            margin-top: 50px;
            border-top: 1px solid #e2e8f0;
            padding-top: 20px;
            text-align: center;
            font-size: 10px;
            color: #94a3b8;
        }

        .qr-placeholder {
            float: right;
            width: 60px;
            height: 60px;
            border: 1px solid #e2e8f0;
            padding: 5px;
            margin-top: -10px;
        }

        .verification-text {
            font-size: 9px;
            color: #cbd5e1;
            margin-top: 5px;
        }

        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 9999px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .badge-paid {
            background-color: #dcfce7;
            color: #166534;
        }

        .badge-pending {
            background-color: #fef9c3;
            color: #854d0e;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="logo">Uniwize Portal</div>
        <div class="university-name">Apex State University</div>
        <div class="document-title">Official Staff Payslip</div>
    </div>

    <table class="info-section">
        <tr>
            <td class="info-box">
                <div class="info-label">Staff Name</div>
                <div class="info-value">{{ $item->staff->user->name }}</div>
                <div style="margin-top: 10px;"></div>
                <div class="info-label">Staff Number</div>
                <div class="info-value">{{ $item->staff->staff_number }}</div>
            </td>
            <td class="info-box">
                <div class="info-label">Period</div>
                <div class="info-value">{{ date('F', mktime(0, 0, 0, $item->payroll->month, 10)) }}
                    {{ $item->payroll->year }}</div>
                <div style="margin-top: 10px;"></div>
                <div class="info-label">Department</div>
                <div class="info-value">{{ $item->staff->department->name ?? 'N/A' }}</div>
            </td>
        </tr>
    </table>

    <table class="salary-table">
        <thead>
            <tr>
                <th>Description</th>
                <th style="text-align: right;">Amount (NGN)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Basic Salary</td>
                <td class="amount">{{ number_format($item->basic_salary, 2) }}</td>
            </tr>
            <tr class="section-header">
                <td colspan="2">Earnings / Allowances</td>
            </tr>
            @if($item->allowance_breakdown)
                @foreach($item->allowance_breakdown as $label => $val)
                    <tr>
                        <td>{{ str_replace('_', ' ', ucfirst($label)) }}</td>
                        <td class="amount">{{ number_format($val, 2) }}</td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td>Regular Allowances</td>
                    <td class="amount">{{ number_format($item->total_allowances, 2) }}</td>
                </tr>
            @endif

            <tr class="section-header">
                <td colspan="2">Deductions</td>
            </tr>
            @if($item->deduction_breakdown)
                @foreach($item->deduction_breakdown as $label => $val)
                    <tr>
                        <td>{{ str_replace('_', ' ', ucfirst($label)) }}</td>
                        <td class="amount" style="color: #b91c1c;">({{ number_format($val, 2) }})</td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td>Standard Deductions</td>
                    <td class="amount" style="color: #b91c1c;">({{ number_format($item->total_deductions, 2) }})</td>
                </tr>
            @endif

            <tr class="total-row">
                <td>NET SALARY PAYABLE</td>
                <td class="amount" style="color: #1e3a8a;">NGN {{ number_format($item->net_salary, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div style="margin-bottom: 40px;">
        <div class="info-label" style="display:inline-block">Status:</div>
        <div class="badge {{ $item->payroll->paid_at ? 'badge-paid' : 'badge-pending' }}" style="display:inline-block">
            {{ $item->payroll->paid_at ? 'Electronic Funds Transferred' : 'Payment Processing' }}
        </div>

        <div class="qr-placeholder">
            <!-- Simulated QR code for verification -->
            <div style="width:100%; height:100%; background-color:#f1f5f9; border:1px dashed #cbd5e1;"></div>
            <div class="verification-text">E-Verify #{{ substr($item->id, 0, 8) }}</div>
        </div>
    </div>

    <div class="footer">
        This is a computer-generated payslip and does not require a physical signature.<br>
        Apex State University &copy; {{ date('Y') }} - Human Resources Department
    </div>
</body>

</html>