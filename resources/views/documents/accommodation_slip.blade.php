<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Accommodation Allocation Slip</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 14px;
            color: #333;
            line-height: 1.6;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
            border-bottom: 2px solid #000;
            padding-bottom: 20px;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
            text-transform: uppercase;
        }

        .header h2 {
            margin: 5px 0;
            font-size: 18px;
            color: #555;
        }

        .section-title {
            background-color: #f2f2f2;
            padding: 8px 12px;
            font-weight: bold;
            margin-top: 30px;
            margin-bottom: 15px;
            text-transform: uppercase;
            border-left: 5px solid #000;
        }

        .info-grid {
            width: 100%;
            margin-bottom: 20px;
        }

        .info-grid td {
            padding: 8px 0;
        }

        .info-grid .label {
            width: 150px;
            font-weight: bold;
            color: #666;
        }

        .allocation-box {
            border: 2px solid #000;
            padding: 20px;
            text-align: center;
            margin: 30px 0;
            background-color: #fafafa;
        }

        .allocation-box .room-number {
            font-size: 32px;
            font-weight: 900;
            margin: 10px 0;
        }

        .allocation-box .hostel-name {
            font-size: 18px;
            font-weight: bold;
        }

        .footer {
            margin-top: 60px;
            text-align: center;
            font-size: 11px;
            color: #888;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }

        .qr-placeholder {
            margin-top: 20px;
            font-style: italic;
            font-size: 10px;
        }

        .signature-sections {
            margin-top: 50px;
            width: 100%;
        }

        .signature-sections td {
            width: 50%;
            text-align: center;
            padding-top: 40px;
        }

        .signature-line {
            width: 180px;
            border-bottom: 1px solid #000;
            margin: 0 auto 5px auto;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Mewar International University</h1>
        <h2>Accommodation Allocation Slip</h2>
        <p>Academic Session: {{ $session->name }}</p>
    </div>

    <div class="section-title">Student Information</div>
    <table class="info-grid">
        <tr>
            <td class="label">Full Name:</td>
            <td>{{ $student->user->name }}</td>
            <td class="label">Matric Number:</td>
            <td>{{ $student->matric_no }}</td>
        </tr>
        <tr>
            <td class="label">Gender:</td>
            <td style="text-transform: capitalize;">{{ $student->gender }}</td>
            <td class="label">Level:</td>
            <td>{{ $student->current_level }}</td>
        </tr>
        <tr>
            <td class="label">Faculty:</td>
            <td>{{ $student->faculty->name ?? ($student->department->faculty->name ?? 'N/A') }}</td>
            <td class="label">Department:</td>
            <td>{{ $student->department->name ?? 'N/A' }}</td>
        </tr>
    </table>

    <div class="section-title">Allocation Details</div>
    <div class="allocation-box">
        <div class="hostel-name">{{ $booking->room->floor->block->hostel->name }}</div>
        <div>{{ $booking->room->floor->block->name }} Wing - {{ $booking->room->floor->name }}</div>
        <div class="room-number">ROOM {{ $booking->room->room_number }}</div>
    </div>

    <table class="info-grid">
        <tr>
            <td class="label">Booking Ref:</td>
            <td>{{ $booking->invoice->reference }}</td>
            <td class="label">Allocation Date:</td>
            <td>{{ $booking->updated_at->format('d M, Y') }}</td>
        </tr>
        <tr>
            <td class="label">Status:</td>
            <td style="color: green; font-weight: bold;">{{ strtoupper($booking->status) }}</td>
            <td class="label">Payment:</td>
            <td>CONFIRMED</td>
        </tr>
    </table>

    <div class="section-title">Instructions</div>
    <ul style="font-size: 12px;">
        <li>Students are expected to occupy their allocated rooms within the first two weeks of the semester.</li>
        <li>This slip must be presented to the Hall Warden for key collection.</li>
        <li>Unauthorized exchange of rooms is strictly prohibited and attracts disciplinary action.</li>
        <li>Please ensure all university properties in the room are handled with care.</li>
    </ul>

    <table class="signature-sections">
        <tr>
            <td>
                <div class="signature-line"></div>
                <div>Hall Warden</div>
            </td>
            <td>
                <div class="signature-line"></div>
                <div>Student Signature</div>
            </td>
        </tr>
    </table>

    <div class="footer">
        <p>This is a computer-generated document and does not require a physical signature for initial processing.</p>
        <p>Generated on {{ now()->format('d M, Y H:i A') }}</p>
    </div>
</body>

</html>