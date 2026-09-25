<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Official Examination Docket & Permit</title>
    <style>
        @page {
            margin: 0.8cm;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #1e293b;
            line-height: 1.4;
            margin: 0;
            padding: 0;
            background: #fff;
            font-size: 10.5px;
        }

        .header {
            border-bottom: 3px solid #f59e0b;
            padding-bottom: 12px;
            margin-bottom: 15px;
            position: relative;
            min-height: 85px;
        }

        .logo-box {
            position: absolute;
            left: 0;
            top: 0;
        }

        .passport-box {
            position: absolute;
            right: 0;
            top: 0;
            width: 80px;
            height: 95px;
            border: 2px solid #000a29;
            border-radius: 4px;
            padding: 2px;
            background: #fff;
            text-align: center;
        }

        .passport-photo {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .header-content {
            margin-left: 70px;
            margin-right: 95px;
        }

        .uni-name {
            font-size: 16px;
            font-weight: 900;
            text-transform: uppercase;
            color: #000a29;
            margin: 0;
            letter-spacing: 0.5px;
        }

        .form-title {
            font-size: 13px;
            font-weight: 800;
            margin: 3px 0;
            color: #f59e0b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .session-info {
            font-size: 10px;
            color: #475569;
            font-weight: bold;
        }

        .student-info-section {
            margin-bottom: 15px;
            width: calc(100% - 100px);
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table td {
            padding: 4px 0;
            vertical-align: top;
        }

        .info-label {
            font-weight: bold;
            color: #64748b;
            width: 110px;
            font-size: 10px;
            text-transform: uppercase;
        }

        .info-value {
            color: #0f172a;
            font-weight: 700;
            font-size: 11px;
        }

        .course-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            border: 1px solid #cbd5e1;
        }

        .course-table th {
            background: #000a29;
            color: #ffffff;
            padding: 6px 8px;
            text-align: left;
            font-weight: bold;
            font-size: 9.5px;
            text-transform: uppercase;
            border-right: 1px solid #1e293b;
        }

        .course-table td {
            padding: 5px 8px;
            border-bottom: 1px solid #e2e8f0;
            border-right: 1px solid #e2e8f0;
            font-size: 10px;
        }

        .course-code {
            font-family: monospace;
            font-weight: bold;
            color: #000a29;
        }

        .rules-section {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 8px 12px;
            border-radius: 4px;
            margin-top: 10px;
        }

        .rules-title {
            font-weight: bold;
            color: #000a29;
            margin-bottom: 4px;
            text-transform: uppercase;
            font-size: 9.5px;
        }

        .rules-list {
            margin: 0;
            padding-left: 15px;
            font-size: 9px;
            color: #334155;
        }

        .rules-list li {
            margin-bottom: 2px;
        }

        .verification-row {
            margin-top: 12px;
            border-top: 2px solid #cbd5e1;
            padding-top: 10px;
        }

        .signature-cell {
            width: 33%;
            text-align: center;
            vertical-align: bottom;
        }

        .signature-line {
            border-top: 1px solid #334155;
            margin-top: 25px;
            padding-top: 3px;
            font-size: 8.5px;
            font-weight: bold;
            color: #475569;
            text-transform: uppercase;
        }

        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 65px;
            color: rgba(0, 10, 41, 0.03);
            font-weight: bold;
            z-index: -1;
            white-space: nowrap;
        }
    </style>
</head>

<body>
    <div class="watermark">OFFICIAL DOCKET CLEARED</div>

    @php
        $logoPath = public_path('miu-logo.png');
        if (!file_exists($logoPath)) {
            $logoPath = public_path('miu-logo.jpeg');
        }
        $logoBase64 = file_exists($logoPath) ? base64_encode(file_get_contents($logoPath)) : null;

        $passportBase64 = null;
        if (!empty($student->passport_photo_path)) {
            $pPath = public_path('storage/' . $student->passport_photo_path);
            if (!file_exists($pPath)) {
                $pPath = storage_path('app/public/' . $student->passport_photo_path);
            }
            if (file_exists($pPath)) {
                $passportBase64 = base64_encode(file_get_contents($pPath));
            }
        }
    @endphp

    <div class="header">
        <div class="logo-box">
            @if($logoBase64)
                <img src="data:image/png;base64,{{ $logoBase64 }}" alt="Logo"
                    style="height: 50px; width: auto; max-width: 140px;">
            @else
                <div style="font-weight: bold; color: #000a29; font-size: 16px;">MIU NIGERIA</div>
            @endif
        </div>

        <div class="header-content">
            <h1 class="uni-name">Mewar International University Nigeria</h1>
            <div class="form-title">OFFICIAL EXAMINATION DOCKET & ADMISSION PERMIT</div>
            <div class="session-info">
                Academic Session: <strong>{{ $session->name }}</strong> &nbsp;|&nbsp; Semester: <strong>{{ $semester->name }}</strong>
            </div>
        </div>

        <div class="passport-box">
            @if($passportBase64)
                <img src="data:image/jpeg;base64,{{ $passportBase64 }}" class="passport-photo">
            @else
                <div style="text-align: center; padding-top: 35px; color: #94a3b8; font-size: 8px; font-weight: bold;">CANDIDATE PHOTO</div>
            @endif
        </div>
    </div>

    <div class="student-info-section">
        <table class="info-table">
            <tr>
                <td class="info-label">Candidate Name:</td>
                <td class="info-value">{{ strtoupper($student->user->name . ' ' . $student->user->last_name) }}</td>
                <td class="info-label">Matric Number:</td>
                <td class="info-value" style="font-family: monospace;">{{ $student->matriculation_number ?? $student->matric_number }}</td>
            </tr>
            <tr>
                <td class="info-label">Faculty / Dept:</td>
                <td class="info-value">{{ $student->department->name ?? 'N/A' }}</td>
                <td class="info-label">Programme:</td>
                <td class="info-value">{{ $student->program->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="info-label">Level & Gender:</td>
                <td class="info-value">{{ $student->current_level }} Level ({{ $student->gender ?? 'N/A' }})</td>
                <td class="info-label">Clearance Token:</td>
                <td class="info-value" style="font-family: monospace; color: #000a29;">{{ $verificationToken ?? strtoupper(substr(md5($student->id . now()), 0, 10)) }}</td>
            </tr>
        </table>
    </div>

    <div class="course-section">
        <table class="course-table">
            <thead>
                <tr>
                    <th width="4%" style="text-align: center;">S/N</th>
                    <th width="12%">Course Code</th>
                    <th width="34%">Course Title</th>
                    <th width="6%" style="text-align: center;">Units</th>
                    <th width="22%">Date & Time</th>
                    <th width="10%">Venue</th>
                    <th width="12%" style="text-align: right;">Invigilator Sign</th>
                </tr>
            </thead>
            <tbody>
                @foreach($registrations as $index => $reg)
                    @php
                        $sch = isset($examSchedules[$reg->course_id]) ? $examSchedules[$reg->course_id] : null;
                    @endphp
                    <tr>
                        <td align="center">{{ $loop->iteration }}</td>
                        <td class="course-code">{{ $reg->course->code }}</td>
                        <td>{{ $reg->course->title }}</td>
                        <td align="center"><strong>{{ $reg->course->units }}</strong></td>
                        <td>
                            @if($sch)
                                <strong>{{ \Carbon\Carbon::parse($sch->exam_date)->format('D, d M Y') }}</strong><br>
                                <span style="font-size: 8px; color: #64748b;">{{ \Carbon\Carbon::parse($sch->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($sch->end_time)->format('g:i A') }}</span>
                            @else
                                <span style="color: #94a3b8; font-style: italic;">Schedule Pending</span>
                            @endif
                        </td>
                        <td>
                            @if($sch)
                                <strong>{{ $sch->venue }}</strong>
                            @else
                                <span style="color: #94a3b8;">TBA</span>
                            @endif
                        </td>
                        <td align="right">
                            <div style="border-bottom: 1px dashed #94a3b8; width: 60px; height: 14px; margin-left: auto;"></div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="rules-section">
        <div class="rules-title">Candidate Rules & Examination Regulations</div>
        <ol class="rules-list">
            <li>Candidates must present this official docket along with a valid Student ID Card at the examination hall entrance.</li>
            <li>Possession of mobile phones, smartwatches, electronic devices, or unauthorized materials is strictly prohibited.</li>
            <li>Invigilator signature must be obtained for each course paper attended. Any unauthorized alteration voids this permit.</li>
        </ol>
    </div>

    <div class="verification-row">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 75%; vertical-align: bottom; padding-right: 15px;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td class="signature-cell" style="padding-right: 10px;">
                                <div class="signature-line">Candidate Signature</div>
                            </td>
                            <td class="signature-cell" style="padding-right: 10px;">
                                <div class="signature-line">Registrar / Exams Officer</div>
                            </td>
                            <td class="signature-cell">
                                <div class="signature-line">Faculty Officer Stamp</div>
                            </td>
                        </tr>
                    </table>
                </td>

                <td style="width: 25%; text-align: center; vertical-align: middle;">
                    <div class="qr-box" style="text-align: center;">
                        <img src="{{ $qrCodeUrl ?? ('https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' . urlencode(url('/admin/exams/verify-pass/' . ($verificationToken ?? 'PERMIT')))) }}" alt="Verification QR" style="width: 75px; height: 75px; display: block; margin: 0 auto;">
                        <span class="scan-text" style="display: block; font-size: 7.5px; font-weight: bold; color: #000a29; margin-top: 3px; text-transform: uppercase;">Scan to Verify Student</span>
                    </div>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="padding-top: 10px; text-align: center; font-size: 8px; color: #64748b;">
                    Official Clearance Permit • Mewar International University Nigeria • Generated on {{ now()->format('d/m/Y H:i:s') }}
                </td>
            </tr>
        </table>
    </div>
</body>

</html>