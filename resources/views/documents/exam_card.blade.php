<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Examination Admission Card</title>
    <style>
        @page {
            margin: 1cm;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #333;
            line-height: 1.4;
            margin: 0;
            padding: 0;
            background: #fff;
            font-size: 11px;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #E31E24;
            /* Red for Exam */
            padding-bottom: 10px;
            margin-bottom: 15px;
            min-height: 100px;
            position: relative;
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
            width: 85px;
            height: 95px;
            border: 1px solid #ddd;
            padding: 2px;
            background: #fff;
        }

        .passport-photo {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .uni-name {
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            color: #E31E24;
            margin: 0;
            letter-spacing: 1px;
        }

        .form-title {
            font-size: 15px;
            font-weight: bold;
            margin: 5px 0;
            color: #000;
            text-transform: uppercase;
        }

        .session-info {
            font-size: 11px;
            color: #444;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .student-info-section {
            margin-bottom: 15px;
            width: calc(100% - 110px);
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table td {
            padding: 3px 0;
            vertical-align: top;
        }

        .info-label {
            font-weight: bold;
            color: #555;
            width: 110px;
        }

        .info-value {
            color: #000;
            font-weight: 600;
        }

        .course-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            border: 1px solid #e2e8f0;
        }

        .course-table th {
            background: #f8fafc;
            color: #475569;
            padding: 6px 8px;
            text-align: left;
            font-weight: bold;
            border-bottom: 1px solid #e2e8f0;
            border-right: 1px solid #e2e8f0;
            font-size: 10px;
            text-transform: uppercase;
        }

        .course-table td {
            padding: 6px 8px;
            border-bottom: 1px solid #e2e8f0;
            border-right: 1px solid #e2e8f0;
            font-size: 10px;
        }

        .course-code {
            font-family: monospace;
            font-weight: bold;
            color: #E31E24;
        }

        .rules-section {
            background: #fef2f2;
            border: 1px solid #fee2e2;
            padding: 10px;
            border-radius: 4px;
            margin-top: 10px;
        }

        .rules-title {
            font-weight: bold;
            color: #991b1b;
            margin-bottom: 5px;
            text-transform: uppercase;
            font-size: 10px;
        }

        .rules-list {
            margin: 0;
            padding-left: 15px;
            font-size: 9px;
            color: #7f1d1d;
        }

        .rules-list li {
            margin-bottom: 2px;
        }

        .verification-row {
            margin-top: 15px;
            border-top: 1px dashed #cbd5e1;
            padding-top: 10px;
        }

        .signature-grid {
            width: 100%;
            border-collapse: collapse;
        }

        .signature-cell {
            width: 33%;
            text-align: center;
            vertical-align: bottom;
        }

        .signature-line {
            border-top: 1px solid #000;
            margin-top: 30px;
            padding-top: 3px;
            font-size: 9px;
            color: #666;
        }

        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 70px;
            color: rgba(227, 30, 36, 0.03);
            font-weight: bold;
            z-index: -1;
            white-space: nowrap;
        }
    </style>
</head>

<body>
    <div class="watermark">EXAM ADMITTED</div>

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
                    style="height: 45px; width: auto; max-width: 150px; margin-top: -5px;">
            @else
                <div style="font-weight: bold; color: #E31E24; font-size: 14px;">MIU</div>
            @endif
        </div>

        <h1 class="uni-name">Mewar International University Nigeria</h1>
        <div class="form-title">Examination Admission Card</div>
        <div class="session-info">
            {{ $session->name }} Session - {{ $semester->name }} Semester
        </div>

        <div class="passport-box">
            @if($passportBase64)
                <img src="data:image/jpeg;base64,{{ $passportBase64 }}" class="passport-photo">
            @else
                <div style="text-align: center; padding-top: 35px; color: #999; font-size: 9px; font-weight: bold;">NO PHOTO</div>
            @endif
        </div>
    </div>

    <div class="student-info-section">
        <table class="info-table">
            <tr>
                <td class="info-label">Student Name:</td>
                <td class="info-value">{{ strtoupper($student->user->name . ' ' . $student->user->last_name) }}</td>
            </tr>
            <tr>
                <td class="info-label">Matric Number:</td>
                <td class="info-value">{{ $student->matriculation_number }}</td>
            </tr>
            <tr>
                <td class="info-label">Department:</td>
                <td class="info-value">{{ $student->department->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="info-label">Programme:</td>
                <td class="info-value">{{ $student->program->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="info-label">Level / Gender:</td>
                <td class="info-value">{{ $student->current_level }} / {{ $student->gender ?? 'N/A' }}</td>
            </tr>
        </table>
    </div>

    <div class="course-section">
        <table class="course-table">
            <thead>
                <tr>
                    <th width="4%">S/N</th>
                    <th width="12%">Code</th>
                    <th width="34%">Course Title</th>
                    <th width="6%">Units</th>
                    <th width="20%">Date & Time</th>
                    <th width="12%">Venue</th>
                    <th width="12%">Invigilator Sign</th>
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
                        <td align="center">{{ $reg->course->units }}</td>
                        <td>
                            @if($sch)
                                <strong>{{ \Carbon\Carbon::parse($sch->exam_date)->format('D, d M Y') }}</strong><br>
                                <span style="font-size: 8.5px; color: #555;">{{ \Carbon\Carbon::parse($sch->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($sch->end_time)->format('g:i A') }}</span>
                            @else
                                <span style="color: #888; font-style: italic;">Schedule Pending</span>
                            @endif
                        </td>
                        <td>
                            @if($sch)
                                <strong>{{ $sch->venue }}</strong>
                            @else
                                <span style="color: #888;">TBA</span>
                            @endif
                        </td>
                        <td></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="rules-section">
        <div class="rules-title">Instructions to Candidates</div>
        <ul class="rules-list">
            <li>Candidates must be at the examination hall 30 minutes before the start of the examination.</li>
            <li>This card must be presented for every internal examination and signed by the invigilator.</li>
            <li>No candidate will be allowed into the examination hall without a valid identity card and this exam card.
            </li>
            <li>Possession of mobile phones, electronic gadgets, or unauthorized materials in the exam hall is strictly
                prohibited.</li>
            <li>Any form of examination malpractice will lead to immediate expulsion from the University.</li>
        </ul>
    </div>

    <div class="verification-row" style="margin-top: 15px; border-top: 1px dashed #cbd5e1; padding-top: 10px;">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 75%; vertical-align: bottom; padding-right: 15px;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td class="signature-cell" style="padding-right: 10px;">
                                <div class="signature-line">Candidate's Signature</div>
                            </td>
                            <td class="signature-cell" style="padding-right: 10px;">
                                <div class="signature-line">Registrar / Exams Officer</div>
                            </td>
                            <td class="signature-cell">
                                <div class="signature-line">Faculty Officer's Stamp</div>
                            </td>
                        </tr>
                    </table>
                </td>

                <td style="width: 25%; text-align: center; vertical-align: middle;">
                    <div class="qr-box" style="text-align: center;">
                        <img src="{{ $qrCodeUrl ?? ('https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' . urlencode(url('/admin/exams/verify-pass/' . ($verificationToken ?? 'PERMIT')))) }}" alt="Verification QR" style="width: 80px; height: 80px; display: block; margin: 0 auto;">
                        <span class="scan-text" style="display: block; font-size: 8px; font-weight: bold; color: #E31E24; margin-top: 4px; text-transform: uppercase;">Scan to Verify Student</span>
                    </div>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="padding-top: 12px; text-align: center; font-size: 8px; color: #777;">
                    Generated on {{ now()->format('d/m/Y H:i:s') }} | Official Invigilator Verification Token:
                    <strong style="color: #333; font-family: monospace; font-size: 9px;">{{ $verificationToken ?? strtoupper(substr(md5($student->id . now()), 0, 10)) }}</strong>
                </td>
            </tr>
        </table>
    </div>
</body>

</html>