<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Course Results Broadsheet</title>
    <style>
        @page {
            margin: 30px 40px;
        }
        body {
            font-family: 'Helvetica Neue', 'Helvetica', 'Arial', sans-serif;
            color: #1e293b;
            line-height: 1.4;
            margin: 0;
            padding: 0;
            background: #fff;
            font-size: 11px;
        }
        .sheet-container {
            width: 100%;
        }
        .header {
            margin-bottom: 25px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 15px;
        }
        .header-table {
            width: 100%;
        }
        .logo {
            width: 65px;
            height: auto;
        }
        .uni-info h1 {
            font-size: 16px;
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
        .sheet-title {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 6px 12px;
            font-weight: 800;
            font-size: 12px;
            color: #475569;
            text-transform: uppercase;
            text-align: center;
            margin-bottom: 20px;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
        }
        .meta-table td {
            padding: 4px 0;
            font-size: 10px;
        }
        .meta-label {
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            width: 15%;
        }
        .meta-value {
            color: #0f172a;
            width: 35%;
            font-weight: 600;
        }
        .results-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        .results-table th {
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            padding: 8px 6px;
            font-weight: 700;
            font-size: 9px;
            text-transform: uppercase;
            color: #475569;
            text-align: left;
        }
        .results-table td {
            border: 1px solid #e2e8f0;
            padding: 7px 6px;
            font-size: 10px;
            color: #334155;
        }
        .results-table tr:nth-child(even) td {
            background: #f8fafc;
        }
        .text-center {
            text-align: center;
        }
        .font-mono {
            font-family: monospace;
            font-size: 10px;
            font-weight: 600;
        }
        .badge {
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 700;
            font-size: 9px;
            text-transform: uppercase;
        }
        .badge-a { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .badge-b { background: #dbeafe; color: #1d4ed8; border: 1px solid #bfdbfe; }
        .badge-c { background: #fef9c3; color: #a16207; border: 1px solid #fef08a; }
        .badge-d { background: #ffedd5; color: #c2410c; border: 1px solid #fed7aa; }
        .badge-e { background: #f3e8ff; color: #6b21a8; border: 1px solid #e9d5ff; }
        .badge-f { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
        .badge-abs { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
        
        .stats-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .stats-card {
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            border-radius: 6px;
            padding: 10px;
            text-align: center;
            width: 16%;
        }
        .stats-label {
            font-size: 8px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .stats-value {
            font-size: 16px;
            font-weight: 800;
            color: #0f172a;
        }
        .signature-table {
            width: 100%;
            margin-top: 40px;
            border-collapse: collapse;
        }
        .signature-box {
            width: 30%;
            vertical-align: top;
            text-align: center;
        }
        .sig-line {
            border-bottom: 1px solid #94a3b8;
            margin: 40px 20px 8px 20px;
        }
        .sig-label {
            font-size: 9px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
        }
        .sig-sub {
            font-size: 8px;
            color: #94a3b8;
            margin-top: 2px;
        }
        .footer {
            margin-top: 45px;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
            text-align: center;
            font-size: 8px;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    @php
        $logoPath = public_path('miu-logo.png');
        if (!file_exists($logoPath)) {
            $logoPath = public_path('miu-logo.jpeg');
        }
        $logoBase64 = null;
        if (file_exists($logoPath)) {
            $logoBase64 = base64_encode(file_get_contents($logoPath));
        }
    @endphp

    @foreach($coursesData as $cIdx => $data)
        @php
            $course = $data['course'];
            $registrations = $data['registrations'];
            $stats = $data['stats'];
        @endphp

        <div class="sheet-container" style="{{ !$loop->last ? 'page-break-after: always;' : '' }}">
            <!-- Top accent bar -->
            <div style="height: 4px; background: #E31E24; width: 100%; margin-bottom: 15px;"></div>

            <div class="header">
                <table class="header-table">
                    <tr>
                        <td width="70">
                            @if($logoBase64)
                                <img src="data:image/jpeg;base64,{{ $logoBase64 }}" class="logo">
                            @endif
                        </td>
                        <td class="uni-info">
                            <h1>Mewar International University</h1>
                            <p>Nigeria Campus</p>
                            <p>KM 21 Abuja Keffi Rd, Masaka, Nasarawa State</p>
                            <p>OFFICE OF THE REGISTRAR &bull; ACADEMIC AFFAIRS DIVISION</p>
                        </td>
                        <td align="right" valign="top">
                            <span style="font-size: 9px; font-weight: 700; color: #94a3b8; text-transform: uppercase;">Generated</span><br>
                            <span style="font-size: 10px; font-weight: 600; color: #334155;">{{ $date }}</span>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="sheet-title">Official Course Results Sheet (Broadsheet)</div>

            <table class="meta-table">
                <tr>
                    <td class="meta-label">Course Code:</td>
                    <td class="meta-value">{{ $course->code }}</td>
                    <td class="meta-label">Academic Year:</td>
                    <td class="meta-value">{{ $session->name }}</td>
                </tr>
                <tr>
                    <td class="meta-label">Course Title:</td>
                    <td class="meta-value">{{ $course->title }}</td>
                    <td class="meta-label">Semester:</td>
                    <td class="meta-value">{{ $course->semester == '1' ? 'First Semester' : 'Second Semester' }}</td>
                </tr>
                <tr>
                    <td class="meta-label">Course Units:</td>
                    <td class="meta-value">{{ $course->units }} Cr.</td>
                    <td class="meta-label">Department:</td>
                    <td class="meta-value">{{ $course->department->name ?? 'N/A' }}</td>
                </tr>
            </table>

            <table class="results-table">
                <thead>
                    <tr>
                        <th width="30" class="text-center">S/N</th>
                        <th width="120">Registration No.</th>
                        <th>Student Name</th>
                        <th width="60" class="text-center">CA (40)</th>
                        <th width="60" class="text-center">Exam (60)</th>
                        <th width="70" class="text-center">Total (100)</th>
                        <th width="65" class="text-center">Grade</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($registrations as $index => $reg)
                        <tr>
                            <td class="text-center text-muted-foreground">{{ $index + 1 }}</td>
                            <td class="font-mono">{{ $reg->student->matriculation_number ?? 'N/A' }}</td>
                            <td><strong>{{ strtoupper($reg->student->user->name ?? 'Unknown') }}</strong></td>
                            <td class="text-center">{{ $reg->is_absent ? '-' : ($reg->ca_score ?? '0') }}</td>
                            <td class="text-center">{{ $reg->is_absent ? '-' : ($reg->exam_score ?? '0') }}</td>
                            <td class="text-center" style="font-weight: 700;">
                                {{ $reg->is_absent ? 'ABS' : ($reg->score ?? '0') }}
                            </td>
                            <td class="text-center">
                                @php
                                    $g = $reg->is_absent ? 'ABS' : ($reg->grade ?? 'F');
                                    $badgeClass = 'badge-' . strtolower($g);
                                @endphp
                                <span class="badge {{ $badgeClass }}">{{ $g }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center" style="padding: 20px; font-style: italic; color: #64748b;">
                                No student registrations found for this course in the selected session.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <table class="stats-grid">
                <tr>
                    <td class="stats-card">
                        <div class="stats-label">Registered</div>
                        <div class="stats-value">{{ $stats['total'] }}</div>
                    </td>
                    <td width="20"></td>
                    <td class="stats-card">
                        <div class="stats-label">Graded</div>
                        <div class="stats-value" style="color: #10b981;">{{ $stats['graded'] }}</div>
                    </td>
                    <td width="20"></td>
                    <td class="stats-card">
                        <div class="stats-label">Passes (&ge;40)</div>
                        <div class="stats-value" style="color: #16a34a;">{{ $stats['passes'] }}</div>
                    </td>
                    <td width="20"></td>
                    <td class="stats-card">
                        <div class="stats-label">Fails (&lt;40)</div>
                        <div class="stats-value" style="color: #dc2626;">{{ $stats['fails'] }}</div>
                    </td>
                    <td width="20"></td>
                    <td class="stats-card">
                        <div class="stats-label">Absents</div>
                        <div class="stats-value" style="color: #475569;">{{ $stats['absents'] }}</div>
                    </td>
                    <td width="20"></td>
                    <td class="stats-card">
                        <div class="stats-label">Class Average</div>
                        <div class="stats-value" style="color: #2563eb;">{{ $stats['average'] }}%</div>
                    </td>
                </tr>
            </table>

            <table class="signature-table">
                <tr>
                    <td class="signature-box">
                        <div class="sig-line"></div>
                        <div class="sig-label">Course Lecturer / Examiner</div>
                        <div class="sig-sub">Signature & Date</div>
                    </td>
                    <td width="5%"></td>
                    <td class="signature-box">
                        <div class="sig-line"></div>
                        <div class="sig-label">Head of Department (HOD)</div>
                        <div class="sig-sub">Signature & Date</div>
                    </td>
                    <td width="5%"></td>
                    <td class="signature-box">
                        <div class="sig-line"></div>
                        <div class="sig-label">Dean of Faculty</div>
                        <div class="sig-sub">Signature & Date</div>
                    </td>
                </tr>
            </table>

            <div class="footer">
                <p><strong>Security Notice:</strong> This results sheet is an official academic record from the Mewar International University ERP. Any unauthorized alterations render this document null and void.</p>
                <p>&copy; {{ date('Y') }} Mewar International University, Nasarawa State, Nigeria. All rights reserved.</p>
            </div>
        </div>
    @endforeach
</body>
</html>
