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
            border-bottom: 2px solid #dc2626;
            /* Red for Exam */
            padding-bottom: 10px;
            margin-bottom: 15px;
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
            width: 90px;
            height: 100px;
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
            color: #dc2626;
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
            color: #dc2626;
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
            color: rgba(220, 38, 38, 0.03);
            font-weight: bold;
            z-index: -1;
            white-space: nowrap;
        }
    </style>
</head>

<body>
    <div class="watermark">EXAM ADMITTED</div>

    <div class="header">
        <div class="logo-box">
            <div
                style="width: 35px; height: 35px; background: #dc2626; color: white; border-radius: 50%; line-height: 35px; text-align: center; font-weight: bold; font-size: 16px;">
                U</div>
        </div>

        <h1 class="uni-name">{{ env('APP_NAME') }}</h1>
        <div class="form-title">Examination Admission Card</div>
        <div class="session-info">
            {{ $session->name }} Session - {{ $semester->name }} Semester
        </div>

        <div class="passport-box">
            @if($student->passport_photo_path)
                <img src="{{ public_path('storage/' . $student->passport_photo_path) }}" class="passport-photo">
            @else
                <div style="text-align: center; padding-top: 35px; color: #ccc;">No Photo</div>
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
                    <th width="5%">S/N</th>
                    <th width="15%">Code</th>
                    <th width="55%">Course Title</th>
                    <th width="10%">Units</th>
                    <th width="15%">Invigilator</th>
                </tr>
            </thead>
            <tbody>
                @foreach($registrations as $index => $reg)
                    <tr>
                        <td align="center">{{ $loop->iteration }}</td>
                        <td class="course-code">{{ $reg->course->code }}</td>
                        <td>{{ $reg->course->title }}</td>
                        <td align="center">{{ $reg->course->units }}</td>
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

    <div class="verification-row">
        <table class="signature-grid">
            <tr>
                <td class="signature-cell">
                    <div class="signature-line">Candidate's Signature</div>
                </td>
                <td class="signature-cell">
                    <div class="signature-line">Registrar / Exams Officer</div>
                </td>
                <td class="signature-cell">
                    <div class="signature-line">Faculty Officer's Stamp</div>
                </td>
            </tr>
            <tr>
                <td colspan="3" style="padding-top: 20px; text-align: center; font-size: 8px; color: #999;">
                    Generated on {{ now()->format('d/m/Y H:i:s') }} | Secure Verification Code:
                    {{ strtoupper(substr(md5($student->id . now()), 0, 10)) }}
                </td>
            </tr>
        </table>
    </div>
</body>

</html>