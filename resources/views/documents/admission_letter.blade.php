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
            line-height: 1.45; /* Reduced line-height */
            margin: 0;
            padding: 0;
            background: #fff;
            font-size: 13px; /* Slightly smaller base font */
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
            padding: 1.5cm 2cm 1.5cm 2cm; /* Reduced top/bottom padding */
            position: relative;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #1a56db;
            padding-bottom: 15px;
            margin-bottom: 25px; /* Reduced from 40px */
        }

        .logo {
            width: 70px; /* Smaller */
            height: auto;
            margin-bottom: 10px;
        }

        .uni-name {
            font-size: 22px; /* Smaller */
            font-weight: 700;
            text-transform: uppercase;
            color: #1a56db;
            margin: 0;
            letter-spacing: 1px;
        }

        .uni-address {
            font-size: 10px;
            color: #666;
            margin-top: 5px;
            text-transform: uppercase;
        }

        .meta-table {
            width: 100%;
            margin-bottom: 20px; /* Reduced */
        }

        .meta-table td {
            vertical-align: top;
        }

        .ref-box {
            text-align: right;
            font-size: 11px;
        }

        .ref-label {
            font-weight: bold;
            color: #555;
        }

        .recipient-box {
            background: #f8f9fa;
            border-left: 4px solid #1a56db;
            padding: 10px 15px; /* Reduced */
            margin-bottom: 20px;
        }

        .recipient-name {
            font-weight: bold;
            font-size: 15px;
            margin-bottom: 3px;
        }

        .title-banner {
            text-align: center;
            margin-bottom: 20px;
        }

        .title-text {
            display: inline-block;
            background: #eff6ff;
            color: #1a56db;
            padding: 8px 25px; /* Reduced */
            font-weight: bold;
            font-size: 15px;
            border-radius: 50px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .content-body {
            font-size: 13px;
            text-align: justify;
            margin-bottom: 20px;
        }

        .admission-details {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0; /* Reduced */
            font-size: 12px;
        }

        .admission-details th {
            text-align: left;
            padding: 8px 12px; /* Reduced */
            background: #f1f5f9;
            color: #475569;
            border-bottom: 1px solid #e2e8f0;
            width: 35%;
        }

        .admission-details td {
            padding: 8px 12px; /* Reduced */
            border-bottom: 1px solid #e2e8f0;
            font-weight: 600;
        }

        .instructions {
            background: #fffbeb;
            border: 1px solid #fcd34d;
            color: #92400e;
            padding: 10px 15px; /* Reduced */
            font-size: 11px;
            border-radius: 6px;
            margin-top: 15px;
        }

        .footer {
            margin-top: 40px; /* Reduced significantly from 60px */
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
            width: 180px;
            margin-bottom: 5px;
        }

        .registrar-name {
            font-weight: bold;
            font-size: 13px;
        }

        .registrar-title {
            font-size: 11px;
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
            width: 70px;
            height: 70px;
            background: #f1f5f9;
            border: 1px dashed #cbd5e1;
            padding: 5px;
            text-align: center;
            font-size: 8px;
            color: #94a3b8;
        }
    </style>
</head>

<body>

    <div class="watermark">OFFICIAL COPY</div>

    <div class="container">
        <!-- Header -->
        <div class="header">
            <!-- <img src="{{ public_path('images/logo.png') }}" class="logo" alt="Logo"> -->
            <!-- Placeholder Circle for Logo -->
            <div
                style="width: 60px; height: 60px; background: #1a56db; color: white; border-radius: 50%; line-height: 60px; margin: 0 auto 10px auto; font-weight: bold; font-size: 24px;">
                U</div>

            <h1 class="uni-name">University Portal</h1>
            <p class="uni-address">PVT Mail Bag 1234, Innovation Campus, Tech City, State</p>
            <p class="uni-address">www.university-portal.edu.ng | admissions@university-portal.edu.ng</p>
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
                        <div><span class="ref-label">Reference No:</span>
                            {{ $applicant->application_number ?? 'REF-' . rand(1000, 9999) }}</div>
                        <div><span class="ref-label">Date:</span> {{ now()->format('F d, Y') }}</div>
                        <div><span class="ref-label">Session:</span> 2025/2026</div>
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
            <p>Dear <strong>{{ $applicant->first_name }}</strong>,</p>

            <p>Following your success in the recent screening exercise, I am pleased to inform you that you have been
                offered provisional admission into the <strong>University Portal</strong> to pursue a course of study
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

            <p>This offer is subject to the ratification of your credentials by the Admissions Office. You are required
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