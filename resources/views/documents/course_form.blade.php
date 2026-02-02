<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Course Registration Form</title>
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
            font-size: 12px;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #1a56db;
            padding-bottom: 10px;
            margin-bottom: 20px;
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
            width: 100px;
            height: 110px;
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
            font-size: 20px;
            font-weight: bold;
            text-transform: uppercase;
            color: #1a56db;
            margin: 0;
            letter-spacing: 1px;
        }

        .form-title {
            font-size: 16px;
            font-weight: bold;
            margin: 5px 0;
            color: #444;
            text-transform: uppercase;
        }

        .session-info {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }

        .student-info-section {
            margin-bottom: 20px;
            width: calc(100% - 120px);
            /* Leave space for passport */
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
            color: #555;
            width: 120px;
        }

        .info-value {
            color: #000;
            font-weight: 600;
        }

        .course-section {
            margin-top: 10px;
        }

        .semester-title {
            background: #eff6ff;
            color: #1a56db;
            padding: 6px 12px;
            font-weight: bold;
            font-size: 13px;
            border-radius: 4px;
            margin-bottom: 8px;
            border-left: 4px solid #1a56db;
        }

        .course-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .course-table th {
            background: #f1f5f9;
            color: #475569;
            padding: 8px;
            text-align: left;
            font-weight: bold;
            border-bottom: 1px solid #e2e8f0;
            font-size: 11px;
            text-transform: uppercase;
        }

        .course-table td {
            padding: 8px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 11px;
        }

        .course-code {
            font-family: monospace;
            font-weight: bold;
            color: #1a56db;
        }

        .total-row {
            background: #f8fafc;
            font-weight: bold;
        }

        .footer {
            margin-top: 30px;
            width: 100%;
            border-top: 1px dashed #cbd5e1;
            padding-top: 20px;
        }

        .signature-grid {
            width: 100%;
            border-collapse: collapse;
        }

        .signature-cell {
            width: 45%;
            text-align: center;
            vertical-align: bottom;
        }

        .signature-line {
            border-top: 1px solid #000;
            margin-top: 40px;
            padding-top: 5px;
            font-size: 11px;
            color: #666;
        }

        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 80px;
            color: rgba(0, 0, 0, 0.02);
            font-weight: bold;
            z-index: -1;
            white-space: nowrap;
        }
    </style>
</head>

<body>
    <div class="watermark">OFFICIAL COPY</div>

    <div class="header">
        <div class="logo-box">
            <div
                style="width: 40px; height: 40px; background: #1a56db; color: white; border-radius: 50%; line-height: 40px; text-align: center; font-weight: bold; font-size: 18px;">
                U</div>
        </div>

        <h1 class="uni-name">{{env('APP_NAME')}}</h1>
        <div class="form-title">Course Registration Form</div>
        <div class="session-info">
            {{ $session->name }} Academic Session
            @if($semester)
                - {{ $semester->name }}
            @endif
        </div>

        <div class="passport-box">
            @if($student->passport_photo_path)
                <img src="{{ public_path('storage/' . $student->passport_photo_path) }}" class="passport-photo">
            @else
                <div style="text-align: center; pt-10; color: #ccc;">Passport<br>Photo</div>
            @endif
        </div>
    </div>

    <div class="student-info-section">
        <table class="info-table">
            <tr>
                <td class="info-label">Full Name:</td>
                <td class="info-value">{{ strtoupper($student->user->name . ' ' . $student->user->last_name) }}</td>
                <td class="info-label">Matric Number:</td>
                <td class="info-value">{{ $student->matriculation_number }}</td>
            </tr>
            <tr>
                <td class="info-label">Faculty:</td>
                <td class="info-value">{{ $student->department->faculty->name ?? 'N/A' }}</td>
                <td class="info-label">Department:</td>
                <td class="info-value">{{ $student->department->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="info-label">Programme:</td>
                <td class="info-value">{{ $student->program->name ?? 'N/A' }}</td>
                <td class="info-label">Level:</td>
                <td class="info-value">{{ $student->current_level }}</td>
            </tr>
        </table>
    </div>

    <div class="course-section">
        @php $grandTotalUnits = 0; @endphp
        @foreach($registrations as $semesterName => $regs)
            <div class="semester-title">{{ strtoupper($semesterName) }}</div>
            <table class="course-table">
                <thead>
                    <tr>
                        <th width="15%">Code</th>
                        <th width="65%">Course Title</th>
                        <th width="10%">Units</th>
                        <th width="10%">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @php $semUnits = 0; @endphp
                    @foreach($regs as $reg)
                        <tr>
                            <td class="course-code">{{ $reg->course->code }}</td>
                            <td>{{ $reg->course->title }}</td>
                            <td align="center">{{ $reg->course->units }}</td>
                            <td align="center">CORE</td>
                        </tr>
                        @php $semUnits += $reg->course->units; @endphp
                    @endforeach
                    <tr class="total-row">
                        <td colspan="2" align="right">Semester Total Units:</td>
                        <td align="center">{{ $semUnits }}</td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
            @php $grandTotalUnits += $semUnits; @endphp
        @endforeach

        <div
            style="background: #1a56db; color: white; padding: 10px; border-radius: 4px; text-align: right; font-weight: bold;">
            Total Registered Units for Session: {{ $grandTotalUnits }}
        </div>
    </div>

    <div class="footer">
        <table class="signature-grid">
            <tr>
                <td class="signature-cell">
                    <div class="signature-line">Student's Signature</div>
                </td>
                <td width="10%"></td>
                <td class="signature-cell">
                    <div class="signature-line">Course Adviser's Signature</div>
                </td>
            </tr>
            <tr>
                <td colspan="3" style="padding-top: 30px; text-align: center; font-size: 10px; color: #999;">
                    Generated on {{ now()->format('d/m/Y H:i:s') }} | University Portal Course Management System
                </td>
            </tr>
        </table>
    </div>
</body>

</html>