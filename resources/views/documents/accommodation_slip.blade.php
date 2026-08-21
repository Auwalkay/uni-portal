<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Accommodation Allocation Slip</title>
    <style>
        @page {
            margin: 0;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            color: #1e293b;
            line-height: 1.5;
            margin: 0;
            padding: 0;
            background: #fff;
            font-size: 11px;
        }

        .container {
            padding: 40px 50px;
        }

        .top-accent {
            height: 6px;
            background: #E31E24;
            width: 100%;
        }

        .header {
            margin-bottom: 30px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 18px;
        }

        .header-table {
            width: 100%;
        }

        .logo {
            width: 65px;
            height: auto;
        }

        .uni-info h1 {
            font-size: 18px;
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

        .slip-badge {
            background: #f0fdf4;
            color: #16a34a;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 800;
            font-size: 11px;
            text-transform: uppercase;
            text-align: right;
            border: 1px solid #dcfce7;
            display: inline-block;
        }

        .section-title {
            font-size: 11px;
            font-weight: 800;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 25px;
            margin-bottom: 10px;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 4px;
        }

        .info-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .info-grid td {
            padding: 6px 10px;
            vertical-align: top;
        }

        .info-grid .label {
            font-weight: 700;
            color: #64748b;
            width: 130px;
            font-size: 10px;
            text-transform: uppercase;
        }

        .info-grid .value {
            font-weight: 600;
            color: #0f172a;
            font-size: 11px;
        }

        .allocation-box {
            border: 2px dashed #E31E24;
            padding: 22px;
            text-align: center;
            margin: 20px 0;
            background-color: #fffafb;
            border-radius: 10px;
        }

        .allocation-box .room-number {
            font-size: 36px;
            font-weight: 900;
            margin: 8px 0;
            color: #E31E24;
            letter-spacing: 1px;
        }

        .allocation-box .hostel-name {
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
        }

        .allocation-box .location-sub {
            font-size: 12px;
            color: #475569;
            font-weight: 600;
            margin-top: 4px;
        }

        .instructions-list {
            margin: 10px 0;
            padding-left: 20px;
            font-size: 10.5px;
            color: #475569;
        }

        .instructions-list li {
            margin-bottom: 6px;
        }

        .signature-sections {
            margin-top: 40px;
            width: 100%;
            border-collapse: collapse;
        }

        .signature-sections td {
            width: 50%;
            text-align: center;
            vertical-align: bottom;
            padding-top: 35px;
        }

        .signature-line {
            width: 170px;
            border-bottom: 1.5px solid #94a3b8;
            margin: 0 auto 6px auto;
        }

        .signature-title {
            font-size: 10px;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
        }

        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 9.5px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 18px;
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
    @endphp

    <div class="top-accent"></div>

    <div class="container">
        <div class="header">
            <table class="header-table">
                <tr>
                    <td style="width: 70px;">
                        @if($logoData)
                            <img src="{{ $logoData }}" class="logo" alt="MIU Logo" />
                        @endif
                    </td>
                    <td class="uni-info" style="vertical-align: middle; padding-left: 10px;">
                        <h1>Mewar International University</h1>
                        <p>KM 20, Abuja-Keffi Expressway, Masaka, Nasarawa State, Nigeria</p>
                        <p>Academic Session: {{ $session->name }}</p>
                    </td>
                    <td style="text-align: right; vertical-align: middle;">
                        <div class="slip-badge">
                            Allocated
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="section-title">Student Information</div>
        <table class="info-grid">
            <tr>
                <td class="label">Full Name:</td>
                <td class="value">{{ $student->user->name }}</td>
                <td class="label">Matric Number:</td>
                <td class="value">{{ $student->matric_no ?? 'Pending' }}</td>
            </tr>
            <tr>
                <td class="label">Gender:</td>
                <td class="value" style="text-transform: capitalize;">{{ $student->gender }}</td>
                <td class="label">Current Level:</td>
                <td class="value">{{ $student->current_level ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Faculty:</td>
                <td class="value">{{ $student->faculty->name ?? ($student->department->faculty->name ?? 'N/A') }}</td>
                <td class="label">Department:</td>
                <td class="value">{{ $student->department->name ?? 'N/A' }}</td>
            </tr>
        </table>

        <div class="section-title">Accommodation Allocation</div>
        <div class="allocation-box">
            <div class="hostel-name">{{ $booking->room->floor->block->hostel->name }}</div>
            <div class="location-sub">{{ $booking->room->floor->block->name }} — {{ $booking->room->floor->name }}</div>
            <div class="room-number">ROOM {{ $booking->room->room_number }}</div>
        </div>

        <table class="info-grid">
            <tr>
                <td class="label">Booking Ref:</td>
                <td class="value" style="font-family: monospace;">{{ $booking->invoice->reference ?? 'N/A' }}</td>
                <td class="label">Allocation Date:</td>
                <td class="value">{{ $booking->updated_at->format('d M, Y') }}</td>
            </tr>
            {{-- <tr>
                <td class="label">Status:</td>
                <td class="value" style="color: #16a34a; font-weight: 800;">{{ strtoupper($booking->status) }}</td>
                <td class="label">Payment Status:</td>
                <td class="value" style="color: #16a34a; font-weight: 800;">CONFIRMED</td>
            </tr> --}}
        </table>

        <div class="section-title">Important Instructions</div>
        <ul class="instructions-list">
            <li>Students are required to occupy their allocated rooms within the first two weeks of academic resumption.
            </li>
            <li>This official slip must be printed and presented to the Hall Warden for key collection.</li>
            <li>Unauthorized exchange of rooms or transfer of bed spaces is strictly prohibited and attracts severe
                disciplinary actions.</li>
            <li>Please ensure that all university property in the room is handled with care. Report any damages to the
                warden immediately.</li>
        </ul>

        <table class="signature-sections">
            <tr>
                <td>
                    <div class="signature-line"></div>
                    <div class="signature-title">Hall Warden</div>
                </td>
                <td>
                    <div class="signature-line"></div>
                    <div class="signature-title">Student Signature</div>
                </td>
            </tr>
        </table>

        <div class="footer">
            <p>Mewar International University — Official Accommodation Allocation Slip.</p>
            <p>This is a computer-generated document and does not require a physical signature for initial verification.
            </p>
            <p style="margin-top: 5px; font-size: 8px;">Generated on {{ now()->format('d M, Y \a\t h:i A') }}</p>
        </div>
    </div>
</body>

</html>