<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Admission Letter</title>
    <style>
        @page {
            margin: 0cm 0cm;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #333;
            line-height: 1.35;
            /* Even tighter */
            margin: 0;
            padding: 0;
            background: #fff;
            font-size: 12px;
            /* Reduced from 13px */
        }

        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 100px;
            color: rgba(0, 0, 0, 0.03);
            font-weight: bold;
            z-index: -1;
            white-space: nowrap;
        }

        .container {
            padding: 1.0cm 1.5cm;
            /* Reduced padding */
            position: relative;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #E31E24;
            padding-bottom: 8px;
            /* Reduced */
            margin-bottom: 15px;
            /* Reduced */
        }

        .logo {
            width: 60px;
            /* Smaller */
            height: auto;
            margin-bottom: 5px;
        }

        .uni-name {
            font-size: 20px;
            /* Smaller */
            font-weight: 700;
            text-transform: uppercase;
            color: #E31E24;
            margin: 0;
            letter-spacing: 1px;
        }

        .uni-address {
            font-size: 9px;
            /* Smaller */
            color: #666;
            margin-top: 3px;
            text-transform: uppercase;
        }

        .meta-table {
            width: 100%;
            margin-bottom: 15px;
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
            padding: 8px 12px;
            margin-bottom: 10px;
        }

        .recipient-name {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 2px;
        }

        .title-banner {
            text-align: center;
            margin-bottom: 15px;
        }

        .title-text {
            display: inline-block;
            background: #eff6ff;
            color: #E31E24;
            padding: 5px 20px;
            font-weight: bold;
            font-size: 14px;
            border-radius: 50px;
            text-transform: uppercase;
        }

        .content-body {
            font-size: 12px;
            text-align: justify;
            margin-bottom: 10px;
        }

        .content-body p {
            margin-bottom: 8px;
            /* Tighten paragraph spacing */
            margin-top: 0;
        }

        .admission-details {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 11px;
        }

        .admission-details th {
            text-align: left;
            padding: 6px 10px;
            background: #f1f5f9;
            color: #475569;
            border-bottom: 1px solid #e2e8f0;
            width: 30%;
        }

        .admission-details td {
            padding: 6px 10px;
            border-bottom: 1px solid #e2e8f0;
            font-weight: 600;
        }

        .instructions {
            background: #fffbeb;
            border: 1px solid #fcd34d;
            color: #92400e;
            padding: 8px 12px;
            font-size: 10px;
            border-radius: 6px;
            margin-top: 10px;
        }

        .footer {
            margin-top: 25px;
            /* Reduced from 40px */
            display: table;
            width: 100%;
        }

        .signature-box {
            display: table-cell;
            width: 50%;
            vertical-align: bottom;
        }

        .signature-line {
            border-bottom: 1px solid #000;
            width: 150px;
            margin-bottom: 5px;
        }

        .registrar-name {
            font-weight: bold;
            font-size: 12px;
        }

        .registrar-title {
            font-size: 10px;
            color: #666;
        }

        .qr-box {
            display: table-cell;
            width: 50%;
            text-align: right;
            vertical-align: bottom;
        }

        .qr-placeholder {
            display: inline-block;
            width: 60px;
            height: 60px;
            background: #f1f5f9;
            border: 1px dashed #cbd5e1;
            padding: 5px;
            text-align: center;
            font-size: 7px;
            color: #94a3b8;
        }
    </style>
</head>

<body>

    <div class="watermark">OFFICIAL COPY</div>

    <div class="container">
        <!-- Header -->
        <div class="header">
            <img src="{{ public_path('miu-logo.jpeg') }}" class="logo" alt="Logo">

            <h1 class="uni-name">Mewar International University Nigeria</h1>
            <p class="uni-address">Km21, Kuchikau I, Abuja - Keffi Expy, New Karu, Nasarawa</p>
            <p class="uni-address">www.miu.edu.ng | admissions@miu.edu.ng</p>
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
                            {{ $applicant->application_number ?? 'REF-' . rand(1000, 9999) }}</div>
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

            <p>Following your success in the recent screening exercise, I am pleased to inform you that you have been
                offered provisional admission into <strong>Mewar International University Nigeria</strong> to pursue a
                course of study
                leading to the award of:</p>

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

            @if(isset($fees) && $fees['total'] > 0)
                <div
                    style="margin-top: 10px; padding: 10px; border: 1px solid #e2e8f0; border-radius: 6px; background: #f8fafc;">
                    <h3
                        style="margin-top: 0; color: #1e293b; font-size: 12px; text-transform: uppercase; border-bottom: 1px solid #cbd5e1; padding-bottom: 3px; margin-bottom: 5px;">
                        Financial Information</h3>
                    <table style="width: 100%; font-size: 11px; border-collapse: collapse;">
                        <tr>
                            <td style="padding: 2px 0;">Tuition & Core Fees:</td>
                            <td style="text-align: right; font-weight: bold;">N{{ number_format($fees['tuition']) }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 2px 0;">Administrative Charges:</td>
                            <td style="text-align: right; font-weight: bold;">N{{ number_format($fees['admin_charge']) }}
                            </td>
                        </tr>
                        @if($fees['discount'] > 0)
                            <tr style="color: #059669;">
                                <td style="padding: 2px 0;">Scholarship ({{ $fees['scholarship_name'] }}):</td>
                                <td style="text-align: right; font-weight: bold;">N{{ number_format($fees['discount']) }}</td>
                            </tr>
                        @endif
                        <tr style="font-size: 13px; border-top: 1px solid #cbd5e1;">
                            <td style="padding: 5px 0; font-weight: bold; color: #1e40af;">Total Net Payable:</td>
                            <td style="text-align: right; font-weight: bold; color: #1e40af;">
                                N{{ number_format($fees['total']) }}</td>
                        </tr>
                    </table>
                </div>
            @endif

            <p style="margin-top: 15px;">This offer is subject to the ratification of your credentials by the Admissions
                Office. You are required
                to accept this offer by paying the non-refundable acceptance fee within two (2) weeks of this letter.
            </p>
        </div>

        <!-- Instructions -->
        <div class="instructions">
            <strong>IMPORTANT NOTICE:</strong><br>
            1. This offer will be withdrawn if it is discovered that you do not possess the qualification which you
            claimed to have obtained.<br>
            2. You must present original copies of your credentials during the physical clearance.
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="signature-box">
                <div class="signature-line"></div>
                <!-- <img src="{{ public_path('images/signature.png') }}" style="width: 150px; margin-bottom: -10px;"> -->
                <div class="registrar-name">Dr. Sarah Admin</div>
                <div class="registrar-title">Registrar</div>
            </div>
            <div class="qr-box">
                <div class="qr-placeholder">
                    <br>Scan to Verify<br>Admission
                </div>
            </div>
        </div>
    </div>

</body>

</html>