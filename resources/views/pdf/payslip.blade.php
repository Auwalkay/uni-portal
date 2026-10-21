<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Staff Payslip - {{ $item->staff->staff_number }} - {{ date('F Y', mktime(0, 0, 0, $item->payroll->month, 10)) }}</title>
    <style>
        @page {
            margin: 25px 32px 25px 32px;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #1e293b;
            line-height: 1.4;
            margin: 0;
            padding: 0;
            background: #ffffff;
            font-size: 10px;
        }

        .accent-bar {
            width: 100%;
            height: 5px;
            background-color: #1e3a8a;
            margin-bottom: 2px;
        }

        .accent-subbar {
            width: 100%;
            height: 2px;
            background-color: #d97706;
            margin-bottom: 14px;
        }

        .table-reset {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
        }

        /* Header */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 10px;
        }

        .logo-cell {
            width: 65px;
            vertical-align: middle;
        }

        .logo-img {
            width: 60px;
            height: auto;
        }

        .uni-details {
            vertical-align: middle;
            padding-left: 10px;
        }

        .uni-name {
            font-size: 16px;
            font-weight: bold;
            color: #1e3a8a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0;
            line-height: 1.2;
        }

        .uni-sub {
            font-size: 8.5px;
            color: #64748b;
            text-transform: uppercase;
            margin-top: 3px;
            font-weight: 500;
        }

        .doc-badge-cell {
            vertical-align: middle;
            text-align: right;
            width: 180px;
        }

        .doc-badge {
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1e40af;
            font-size: 10.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 5px 10px;
            border-radius: 4px;
            display: inline-block;
        }

        .doc-meta {
            font-size: 8px;
            color: #64748b;
            margin-top: 4px;
            line-height: 1.3;
        }

        /* Employee Information Box */
        .card-container {
            border: 1px solid #cbd5e1;
            background-color: #f8fafc;
            border-radius: 4px;
            margin-bottom: 12px;
        }

        .card-header {
            background-color: #f1f5f9;
            border-bottom: 1px solid #e2e8f0;
            padding: 5px 10px;
            font-size: 9px;
            font-weight: bold;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table td {
            padding: 6px 10px;
            vertical-align: top;
            width: 33.33%;
            border-bottom: 1px solid #f1f5f9;
        }

        .info-label {
            font-size: 8px;
            font-weight: bold;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 2px;
        }

        .info-value {
            font-size: 11px;
            font-weight: bold;
            color: #0f172a;
        }

        .info-value-mono {
            font-size: 11px;
            font-weight: bold;
            color: #1e40af;
            font-family: 'Courier', monospace;
        }

        /* Attendance Strip */
        .attendance-container {
            border: 1px solid #e2e8f0;
            background-color: #ffffff;
            border-radius: 4px;
            margin-bottom: 12px;
        }

        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            text-align: center;
        }

        .attendance-table td {
            padding: 6px 10px;
            width: 25%;
            border-right: 1px solid #f1f5f9;
        }

        .attendance-table td:last-child {
            border-right: none;
        }

        .att-num {
            font-size: 13px;
            font-weight: bold;
            display: block;
        }

        .att-label {
            font-size: 7.5px;
            font-weight: bold;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-top: 1px;
        }

        /* Earnings & Deductions Tables */
        .breakdown-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .breakdown-col {
            vertical-align: top;
            width: 49%;
        }

        .col-spacer {
            width: 2%;
        }

        .item-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
        }

        .item-table th {
            padding: 6px 8px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            border-bottom: 1px solid #cbd5e1;
        }

        .th-earnings {
            background-color: #eff6ff;
            color: #1e40af;
            text-align: left;
        }

        .th-deductions {
            background-color: #fff1f2;
            color: #9f1239;
            text-align: left;
        }

        .item-table td {
            padding: 6px 8px;
            font-size: 9.5px;
            border-bottom: 1px solid #f1f5f9;
        }

        .item-amt {
            text-align: right;
            font-weight: bold;
            font-family: 'Courier', monospace;
            font-size: 10px;
        }

        .earnings-total-row {
            background-color: #f0fdf4;
            font-weight: bold;
            border-top: 2px solid #bbf7d0;
            color: #166534;
        }

        .deductions-total-row {
            background-color: #fef2f2;
            font-weight: bold;
            border-top: 2px solid #fecdd3;
            color: #991b1b;
        }

        /* Net Pay Hero Box */
        .net-pay-banner {
            width: 100%;
            border: 1.5px solid #1e3a8a;
            background-color: #f8fafc;
            border-radius: 4px;
            margin-bottom: 12px;
        }

        .net-pay-table {
            width: 100%;
            border-collapse: collapse;
        }

        .net-pay-table td {
            padding: 10px 14px;
            vertical-align: middle;
        }

        .net-title {
            font-size: 10px;
            font-weight: bold;
            color: #1e3a8a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .net-words {
            font-size: 9px;
            color: #475569;
            font-style: italic;
            margin-top: 2px;
        }

        .net-amount-cell {
            text-align: right;
            width: 220px;
        }

        .net-amount-val {
            font-size: 18px;
            font-weight: bold;
            color: #1e3a8a;
            font-family: 'Courier', monospace;
        }

        .net-amount-badge {
            display: inline-block;
            margin-top: 3px;
            padding: 2px 7px;
            border-radius: 9999px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .badge-paid {
            background-color: #dcfce7;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }

        .badge-pending {
            background-color: #fef9c3;
            color: #854d0e;
            border: 1px solid #fef08a;
        }

        .badge-excluded {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        /* Remarks */
        .remarks-box {
            border: 1px solid #fde68a;
            background-color: #fffbeb;
            padding: 6px 10px;
            border-radius: 4px;
            margin-bottom: 12px;
        }

        .remarks-label {
            font-size: 8px;
            font-weight: bold;
            color: #92400e;
            text-transform: uppercase;
        }

        .remarks-text {
            font-size: 9px;
            color: #78350f;
            margin-top: 2px;
        }

        /* Signatures and Verification */
        .signatures-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            margin-bottom: 15px;
        }

        .sig-col {
            vertical-align: bottom;
            width: 32%;
            text-align: center;
        }

        .sig-spacer {
            width: 2%;
        }

        .sig-line {
            border-bottom: 1px solid #94a3b8;
            height: 28px;
            margin-bottom: 4px;
        }

        .sig-title {
            font-size: 8.5px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
        }

        .sig-sub {
            font-size: 7.5px;
            color: #64748b;
        }

        .seal-box {
            border: 1px solid #cbd5e1;
            background-color: #f8fafc;
            border-radius: 4px;
            padding: 8px 10px;
            text-align: center;
        }

        .seal-title {
            font-size: 8px;
            font-weight: bold;
            color: #1e3a8a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .seal-ref {
            font-family: 'Courier', monospace;
            font-size: 11px;
            font-weight: bold;
            color: #0f172a;
            margin: 2px 0;
        }

        .seal-check {
            font-size: 7.5px;
            color: #16a34a;
            font-weight: bold;
        }

        /* Footer */
        .footer {
            border-top: 1px solid #e2e8f0;
            padding-top: 8px;
            text-align: center;
            font-size: 7.5px;
            color: #94a3b8;
            line-height: 1.4;
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

        $monthName = date('F', mktime(0, 0, 0, $item->payroll->month, 10));
        $periodLabel = "{$monthName} {$item->payroll->year}";

        $grossEarnings = (float) $item->basic_salary + (float) $item->total_allowances;
        $totalDeductions = (float) $item->total_deductions;
        $netSalary = (float) $item->net_salary;

        if (!function_exists('miuNumberToWords')) {
            function miuNumberToWords($number) {
                $dictionary = [
                    0 => 'Zero', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four',
                    5 => 'Five', 6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine',
                    10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen',
                    15 => 'Fifteen', 16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen',
                    20 => 'Twenty', 30 => 'Thirty', 40 => 'Forty', 50 => 'Fifty',
                    60 => 'Sixty', 70 => 'Seventy', 80 => 'Eighty', 90 => 'Ninety',
                    100 => 'Hundred', 1000 => 'Thousand', 1000000 => 'Million', 1000000000 => 'Billion'
                ];
                if (!is_numeric($number)) return '';
                $num = round((float) $number, 2);
                $integer = (int) floor($num);
                $fraction = (int) round(($num - $integer) * 100);

                $convertInteger = function($n) use (&$convertInteger, $dictionary) {
                    if ($n == 0) return 'Zero';
                    $str = '';
                    if ($n >= 1000000000) {
                        $str .= $convertInteger((int) floor($n / 1000000000)) . ' Billion ';
                        $n %= 1000000000;
                    }
                    if ($n >= 1000000) {
                        $str .= $convertInteger((int) floor($n / 1000000)) . ' Million ';
                        $n %= 1000000;
                    }
                    if ($n >= 1000) {
                        $str .= $convertInteger((int) floor($n / 1000)) . ' Thousand ';
                        $n %= 1000;
                    }
                    if ($n >= 100) {
                        $str .= $dictionary[(int) floor($n / 100)] . ' Hundred ';
                        $n %= 100;
                    }
                    if ($n > 0) {
                        if ($n < 20) {
                            $str .= $dictionary[$n] . ' ';
                        } else {
                            $tens = (int) floor($n / 10) * 10;
                            $units = $n % 10;
                            $str .= $dictionary[$tens] . ($units ? '-' . $dictionary[$units] : '') . ' ';
                        }
                    }
                    return trim($str);
                };

                $words = $convertInteger($integer) . ' Naira';
                if ($fraction > 0) {
                    $words .= ' and ' . $convertInteger($fraction) . ' Kobo';
                } else {
                    $words .= ' Only';
                }
                return $words;
            }
        }
    @endphp

    <div class="accent-bar"></div>
    <div class="accent-subbar"></div>

    <!-- Header -->
    <table class="header-table">
        <tr>
            @if($logoData)
                <td class="logo-cell">
                    <img src="{{ $logoData }}" class="logo-img" alt="Logo" />
                </td>
            @endif
            <td class="uni-details">
                <div class="uni-name">Mewar International University</div>
                <div class="uni-sub">KM 20, Abuja-Keffi Expressway, Masaka, Nasarawa State, Nigeria</div>
                <div class="uni-sub" style="color: #94a3b8; margin-top: 1px;">Human Resources Management System • Bursary Division</div>
            </td>
            <td class="doc-badge-cell">
                <div class="doc-badge">Official Staff Payslip</div>
                <div class="doc-meta">
                    <strong>Ref:</strong> PAY-{{ strtoupper(substr($item->id, 0, 8)) }}<br>
                    <strong>Issued:</strong> {{ date('d M, Y') }}
                </div>
            </td>
        </tr>
    </table>

    <!-- Employee & Employment Information Box -->
    <div class="card-container">
        <div class="card-header">Staff & Employment Particulars</div>
        <table class="info-table">
            <tr>
                <td>
                    <div class="info-label">Staff Member Name</div>
                    <div class="info-value">{{ strtoupper($item->staff?->user?->name ?? 'N/A') }}</div>
                </td>
                <td>
                    <div class="info-label">Staff ID Number</div>
                    <div class="info-value-mono">{{ $item->staff?->staff_number ?? 'N/A' }}</div>
                </td>
                <td>
                    <div class="info-label">Pay Period</div>
                    <div class="info-value">{{ $periodLabel }}</div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="info-label">Department / Unit</div>
                    <div class="info-value">{{ $item->staff?->department?->name ?? 'General Administration' }}</div>
                </td>
                <td>
                    <div class="info-label">Faculty / Division</div>
                    <div class="info-value">{{ $item->staff?->department?->faculty?->name ?? ($item->staff?->is_academic ? 'Academic Affairs' : 'Registry / Support') }}</div>
                </td>
                <td>
                    <div class="info-label">Designation / Rank</div>
                    <div class="info-value">{{ $item->staff?->designation ?? ($item->staff?->is_academic ? 'Faculty Academic Staff' : 'Senior Staff') }}</div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="info-label">Official Email</div>
                    <div class="info-value" style="font-size: 10px; font-weight: normal; color: #334155;">{{ $item->staff?->user?->email ?? 'N/A' }}</div>
                </td>
                <td>
                    <div class="info-label">Disbursement Account</div>
                    <div class="info-value" style="font-size: 10px; font-weight: normal; color: #334155;">
                        @if($item->staff?->bank_name)
                            {{ $item->staff->bank_name }} (••••{{ substr($item->staff->account_number ?? '0000', -4) }})
                        @else
                            Direct Institutional Bank Transfer
                        @endif
                    </div>
                </td>
                <td>
                    <div class="info-label">Employment Classification</div>
                    <div class="info-value">{{ $item->staff?->is_academic ? 'Full-Time Academic' : 'Non-Academic Regular' }}</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Monthly Attendance Summary Strip -->
    @if(isset($attendance))
        <div class="attendance-container">
            <table class="attendance-table">
                <tr>
                    <td>
                        <span class="att-num" style="color: #15803d;">{{ (int)($attendance->present_days ?? 0) }}</span>
                        <span class="att-label">Days Present</span>
                    </td>
                    <td>
                        <span class="att-num" style="color: #b45309;">{{ (int)($attendance->late_days ?? 0) }}</span>
                        <span class="att-label">Late Arrivals</span>
                    </td>
                    <td>
                        <span class="att-num" style="color: #1d4ed8;">{{ (int)($attendance->leave_days ?? 0) }}</span>
                        <span class="att-label">Approved Leave</span>
                    </td>
                    <td>
                        <span class="att-num" style="color: #b91c1c;">{{ (int)($attendance->absent_days ?? 0) }}</span>
                        <span class="att-label">Unexcused Absences</span>
                    </td>
                </tr>
            </table>
        </div>
    @endif

    <!-- Side-by-Side Breakdown: Earnings vs Deductions -->
    <table class="breakdown-table">
        <tr>
            <!-- Earnings (Left Column) -->
            <td class="breakdown-col">
                <table class="item-table">
                    <thead>
                        <tr>
                            <th class="th-earnings">Earnings & Allowances</th>
                            <th class="th-earnings" style="text-align: right; width: 95px;">Amount (NGN)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Basic Salary</td>
                            <td class="item-amt">{{ number_format($item->basic_salary, 2) }}</td>
                        </tr>
                        @if(!empty($item->allowance_breakdown) && is_array($item->allowance_breakdown))
                            @foreach($item->allowance_breakdown as $label => $val)
                                <tr>
                                    <td>{{ str_replace('_', ' ', ucfirst($label)) }}</td>
                                    <td class="item-amt">{{ number_format((float) $val, 2) }}</td>
                                </tr>
                            @endforeach
                        @elseif($item->total_allowances > 0)
                            <tr>
                                <td>Regular Allowances</td>
                                <td class="item-amt">{{ number_format($item->total_allowances, 2) }}</td>
                            </tr>
                        @else
                            <tr>
                                <td style="color: #94a3b8; font-style: italic;">No additional allowances</td>
                                <td class="item-amt" style="color: #94a3b8;">0.00</td>
                            </tr>
                        @endif
                        <tr class="earnings-total-row">
                            <td style="font-weight: bold; text-transform: uppercase;">Total Gross Earnings</td>
                            <td class="item-amt">{{ number_format($grossEarnings, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </td>

            <td class="col-spacer"></td>

            <!-- Deductions (Right Column) -->
            <td class="breakdown-col">
                <table class="item-table">
                    <thead>
                        <tr>
                            <th class="th-deductions">Deductions & Penalties</th>
                            <th class="th-deductions" style="text-align: right; width: 95px;">Amount (NGN)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(!empty($item->deduction_breakdown) && is_array($item->deduction_breakdown))
                            @foreach($item->deduction_breakdown as $label => $val)
                                <tr>
                                    <td>{{ str_replace('_', ' ', ucfirst($label)) }}</td>
                                    <td class="item-amt" style="color: #b91c1c;">({{ number_format((float) $val, 2) }})</td>
                                </tr>
                            @endforeach
                        @elseif($item->total_deductions > 0)
                            <tr>
                                <td>Standard Deductions / Penalties</td>
                                <td class="item-amt" style="color: #b91c1c;">({{ number_format($item->total_deductions, 2) }})</td>
                            </tr>
                        @else
                            <tr>
                                <td style="color: #94a3b8; font-style: italic;">No deductions applied</td>
                                <td class="item-amt" style="color: #94a3b8;">(0.00)</td>
                            </tr>
                        @endif
                        <tr class="deductions-total-row">
                            <td style="font-weight: bold; text-transform: uppercase;">Total Deductions</td>
                            <td class="item-amt" style="color: #991b1b;">({{ number_format($totalDeductions, 2) }})</td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </table>

    <!-- Grand Net Payable Hero Card -->
    <div class="net-pay-banner">
        <table class="net-pay-table">
            <tr>
                <td>
                    <div class="net-title">Net Salary Payable</div>
                    <div class="net-words">Amount in words: {{ miuNumberToWords($item->net_salary) }}</div>
                    <div style="margin-top: 4px;">
                        @if($item->status === 'excluded')
                            <span class="net-amount-badge badge-excluded">&#10006; Excluded / Unpaid</span>
                        @elseif($item->status === 'paid' || $item->payroll->status === 'paid')
                            <span class="net-amount-badge badge-paid">&#10004; Electronic Funds Transferred</span>
                        @else
                            <span class="net-amount-badge badge-pending">&#9679; Payment Processing</span>
                        @endif
                    </div>
                </td>
                <td class="net-amount-cell">
                    <div style="font-size: 8px; font-weight: bold; color: #64748b; text-transform: uppercase; margin-bottom: 2px;">Take Home Pay</div>
                    <div class="net-amount-val">
                        @if($item->status === 'excluded')
                            <span style="text-decoration: line-through; color: #991b1b;">NGN {{ number_format($netSalary, 2) }}</span>
                        @else
                            NGN {{ number_format($netSalary, 2) }}
                        @endif
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Remarks / Notes -->
    @if($item->remarks)
        <div class="remarks-box">
            <div class="remarks-label">Payroll Adjustment Remarks / Explanation</div>
            <div class="remarks-text">{{ $item->remarks }}</div>
        </div>
    @endif

    <!-- Security & Signatures Section -->
    <table class="signatures-table">
        <tr>
            <td class="sig-col" style="width: 32%;">
                <div class="seal-box">
                    <div class="seal-title">Official E-Verification</div>
                    <div class="seal-ref">#{{ strtoupper(substr(md5($item->id . $item->payroll_id), 0, 10)) }}</div>
                    <div class="seal-check">&#10004; Digitally Authenticated</div>
                    <div style="font-size: 7px; color: #64748b; margin-top: 2px;">MIU Human Resources Directorate</div>
                </div>
            </td>
            <td class="sig-spacer" style="width: 6%;"></td>
            <td class="sig-col" style="width: 31%;">
                <div class="sig-line"></div>
                <div class="sig-title">Bursary / Payroll Officer</div>
                <div class="sig-sub">Prepared & Reconciled</div>
            </td>
            <td class="sig-spacer" style="width: 4%;"></td>
            <td class="sig-col" style="width: 31%;">
                <div class="sig-line"></div>
                <div class="sig-title">Director of Human Resources</div>
                <div class="sig-sub">Authorized Institutional Signatory</div>
            </td>
        </tr>
    </table>

    <!-- Footer -->
    <div class="footer">
        <strong>CONFIDENTIAL DOCUMENT:</strong> This payslip is generated automatically from the Mewar International University HRMS portal and constitutes an official record of earnings and statutory withholdings.<br>
        Mewar International University &copy; {{ date('Y') }} • KM 20 Abuja-Keffi Expressway, Masaka, Nasarawa State, Nigeria • support@miu.edu.ng
    </div>
</body>

</html>