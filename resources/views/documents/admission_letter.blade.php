<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Admission Letter</title>
    <style>
        @page {
            margin: 0cm 0cm;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            color: #333;
            line-height: 1.35;
            margin: 0;
            padding: 0;
            background: #fff;
            font-size: 11px;
        }
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.05;
            z-index: -1000;
            width: 400px;
        }
        .container {
            padding: 0.8cm 1.2cm;
            position: relative;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #E31E24;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .logo {
            width: 60px;
            height: auto;
            margin-bottom: 5px;
        }
        .uni-name {
            font-size: 20px;
            font-weight: 700;
            text-transform: uppercase;
            color: #E31E24;
            margin: 0;
            letter-spacing: 1px;
        }
        .uni-address {
            font-size: 9px;
            color: #666;
            margin-top: 3px;
            text-transform: uppercase;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 12px;
        }
        .meta-table td {
            vertical-align: top;
        }
        .ref-box {
            text-align: right;
            font-size: 10px;
        }
        .ref-label {
            font-weight: bold;
            color: #555;
        }
        .recipient-box {
            background: #f8f9fa;
            border-left: 3px solid #E31E24;
            padding: 6px 10px;
            margin-bottom: 8px;
        }
        .recipient-name {
            font-weight: bold;
            font-size: 13px;
            margin-bottom: 2px;
        }
        .title-banner {
            text-align: center;
            margin-bottom: 12px;
        }
        .title-text {
            display: inline-block;
            background: #eff6ff;
            color: #E31E24;
            padding: 4px 15px;
            font-weight: bold;
            font-size: 13px;
            border-radius: 50px;
            text-transform: uppercase;
        }
        .content-body {
            font-size: 11px;
            text-align: justify;
            margin-bottom: 8px;
        }
        .content-body p {
            margin-bottom: 6px;
            margin-top: 0;
        }
        .admission-details {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0;
            font-size: 10px;
        }
        .admission-details th {
            text-align: left;
            padding: 5px 8px;
            background: #f1f5f9;
            color: #475569;
            border-bottom: 1px solid #e2e8f0;
            width: 30%;
        }
        .admission-details td {
            padding: 5px 8px;
            border-bottom: 1px solid #e2e8f0;
            font-weight: 600;
        }
        .instructions {
            background: #fffbeb;
            border: 1px solid #fcd34d;
            color: #92400e;
            padding: 6px 10px;
            font-size: 9px;
            border-radius: 6px;
            margin-top: 8px;
        }
        .footer {
            margin-top: 15px;
            display: table;
            width: 100%;
        }
        .signature-box {
            display: table-cell;
            width: 50%;
            vertical-align: bottom;
        }
        .signature-img {
            width: 100px;
            height: auto;
            margin-bottom: -10px;
        }
        .signature-line {
            border-bottom: 1px solid #000;
            width: 150px;
            margin-bottom: 5px;
        }
        .registrar-name {
            font-weight: bold;
            font-size: 11px;
        }
        .registrar-title {
            font-size: 9px;
            color: #666;
        }
        .qr-box {
            display: table-cell;
            width: 50%;
            text-align: right;
            vertical-align: bottom;
        }
        .qr-box img {
            width: 80px;
            height: 80px;
            display: block;
            margin-left: auto;
        }
        .qr-box .scan-text {
            display: block;
            font-size: 8px;
            font-weight: bold;
            color: #64748b;
            margin-top: 4px;
            text-transform: uppercase;
        }
        .naira {
            font-family: 'DejaVu Sans', sans-serif !important;
        }
    </style>
</head>

<body>
    @php
        $logoPath = public_path('miu-logo.png');
        if (!file_exists($logoPath)) {
            $logoPath = public_path('miu-logo.jpeg');
        }
        $logoBase64 = base64_encode(file_get_contents($logoPath));
        $sigBase64 = base64_encode(file_get_contents(public_path('images/admission_signature.jpeg')));
    @endphp

    <img src="data:image/jpeg;base64,{{ $logoBase64 }}" class="watermark">

    <div class="container">
        <!-- Header -->
        <div class="header">
            <img src="data:image/jpeg;base64,{{ $logoBase64 }}" class="logo" alt="Logo">
            <h1 class="uni-name">Mewar International University Nigeria</h1>
            <p class="uni-address">Km21, Kuchikau I, Abuja - Keffi Expy, Nasarawa</p>
            <p class="uni-address">www.miu.edu.ng | admission@miu.edu.ng | +2348183012911| +2348108040392 </p>
        </div>

        <!-- Meta Info -->
        <table class="meta-table">
            <tr>
                <td>
                    <div class="recipient-box">
                        <div class="recipient-name">{{ strtoupper($applicant->user->name) }}</div>
                        <div>{{ $applicant->address ?? 'No Address Provided' }}</div>
                        <div>{{ $applicant->state->name ?? '' }}, {{ $applicant->lga->name ?? '' }}</div>
                    </div>
                </td>
                <td>
                    <div class="ref-box">
                        <div><span class="ref-label">Registration Number:</span>
                            {{ strtoupper($applicant->application_number)}}</div>
                        <div><span class="ref-label">Date:</span> {{ now()->format('F d, Y') }}</div>
                        <div><span class="ref-label">Session:</span> {{ $session_name }}</div>
                    </div>
                </td>
            </tr>
        </table>

        <!-- Title -->
        <div class="title-banner">
            <span class="title-text">Provisional Offer of Admission</span>
        </div>

        <!-- Body -->
        <div class="content-body">
            <p>Dear <strong>{{ ucwords($applicant->first_name) }}</strong>,</p>

            <p>The Management of Mewar International University (MIU) Nigeria is pleased to congratulate you on your selection for admission into our esteemed institution. Strategically located within the Federal Capital Territory corridor, MIU was established by the Federal Executive Council on February 3, 2021, and formally licensed by the National Universities Commission (NUC) on April 8, 2021. As the first Indian-established university in Africa, we are committed to providing a world-class educational experience that blends heritage with innovation.</p>

            <table class="admission-details">
                <tr>
                    <th>Programme</th>
                    <td>{{ $programme_name }}</td>
                </tr>
                <tr>
                    <th>Faculty</th>
                    <td>{{ $faculty_name }}</td>
                </tr>
                <tr>
                    <th>Department</th>
                    <td>{{ $applicant->programme?->department->name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Mode of Study</th>
                    <td>{{ ucwords($applicant->application_mode) }} (Full-Time)</td>
                </tr>
                <tr>
                    <th>Duration</th>
                    <td>{{ $applicant->programme?->duration ?? '4' }} Years</td>
                </tr>
            </table>

            @if(isset($fees))
                <div style="margin-top: 8px; padding: 8px; border: 1px solid #e2e8f0; border-radius: 6px; background: #f8fafc;">
                    <h3 style="margin-top: 0; color: #1e293b; font-size: 11px; text-transform: uppercase; border-bottom: 1px solid #cbd5e1; padding-bottom: 2px; margin-bottom: 4px;">
                        Financial Information</h3>
                    <table style="width: 100%; font-size: 10px; border-collapse: collapse;">
                        <tr>
                            <td style="padding: 1px 0;">Tuition & Core Fees:</td>
                            <td style="text-align: right; font-weight: bold;"><span class="naira">&#x20A6;</span>{{ number_format($fees['tuition']) }}</td>
                        </tr>
                        @if(isset($fees['one_time_fees_list']) && count($fees['one_time_fees_list']) > 0)
                            @foreach($fees['one_time_fees_list'] as $otFee)
                                <tr>
                                    <td style="padding: 1px 0;">{{ $otFee['name'] }} (One-Time):</td>
                                    <td style="text-align: right; font-weight: bold;"><span class="naira">&#x20A6;</span>{{ number_format($otFee['amount']) }}</td>
                                </tr>
                            @endforeach
                        @endif
                        <tr>
                            <td style="padding: 1px 0;">Administrative Charges:</td>
                            <td style="text-align: right; font-weight: bold;"><span class="naira">&#x20A6;</span>{{ number_format($fees['admin_charge']) }}</td>
                        </tr>
                        @if($fees['discount'] > 0)
                            <tr style="color: #059669;">
                                <td style="padding: 1px 0;">Scholarship ({{ $fees['scholarship_name'] }}):</td>
                                <td style="text-align: right; font-weight: bold;"><span class="naira">&#x20A6;</span>{{ number_format($fees['discount']) }}</td>
                            </tr>
                        @endif
                        <tr style="font-size: 12px; border-top: 1px solid #cbd5e1; color: #1e40af;">
                            <td style="padding: 4px 0; font-weight: bold;">Total Net Payable:</td>
                            <td style="text-align: right; font-weight: bold;">
                                <span class="naira">&#x20A6;</span>{{ number_format($fees['total']) }}</td>
                        </tr>
                    </table>
                </div>
            @endif

            <p style="margin-top: 10px;">This offer is subject to the ratification of your credentials by the Admissions
                Office. You are required
                to accept this offer by paying the non-refundable acceptance fee within two (2) weeks of this letter.
            </p>
        </div>

        <!-- Instructions -->
        <div class="instructions">
            <strong>IMPORTANT NOTICE:</strong><br>
            1. This offer will be withdrawn if it is discovered that you do not possess the qualification(s) which you
            claimed to have obtained, or if there is any falsification of your credentials.<br>
            2. You must present original copies of your credentials during the physical clearance for verification.<br>
            3. Inconsistency in names across different documents may lead to the withdrawal of this offer.<br>
            4. This admission is subject to your being certified medically fit for study by a recognized medical officer.<br>
            5. Involvement in social vices, cultism, or any form of criminal activity will lead to immediate withdrawal of this offer.<br>
            6. Failure to complete all registration formalities within the stipulated period will result in the forfeiture of this admission.<br>
            7. All payments made to the university are strictly non-refundable.
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="signature-box">
                <img src="data:image/jpeg;base64,{{ $sigBase64 }}" class="signature-img">
                <div class="signature-line"></div>
                <div class="registrar-name">{{ env('ADMIN_DIRECTOR_NAME', 'Ahmad Habibu Shehu') }}</div>
                <div class="registrar-title">Director of Admissions</div>
            </div>
            <div class="qr-box">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={{ urlencode(route('verify.admission', encrypt($applicant->id ?? 'unknown'))) }}" alt="Verification QR">
                <span class="scan-text">Scan to Verify Admission</span>
            </div>
        </div>
    </div>

</body>
</html>