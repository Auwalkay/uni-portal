<!DOCTYPE html>
<html>
<head>
    <title>Attendance Report</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { margin-bottom: 5px; color: #333; }
        .header p { margin: 0; color: #666; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background-color: #f8fafc; color: #475569; font-weight: bold; text-align: left; padding: 10px; border: 1px solid #e2e8f0; }
        td { padding: 10px; border: 1px solid #e2e8f0; color: #1e293b; }
        .footer { text-align: right; font-size: 10px; color: #94a3b8; margin-top: 20px; }
        .rate-bar-bg { width: 100px; height: 8px; background: #f1f5f9; border-radius: 4px; overflow: hidden; display: inline-block; vertical-align: middle; }
        .rate-bar-fill { height: 100%; background: #3b82f6; }
        .text-green { color: #16a34a; font-weight: bold; }
        .text-amber { color: #d97706; font-weight: bold; }
        .text-red { color: #dc2626; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Attendance Report</h1>
        <p>{{ $title }}</p>
        <span style="font-size: 10px; color: #999;">Generated on: {{ $date }}</span>
    </div>

    <table>
        <thead>
            <tr>
                <th>Staff ID</th>
                <th>Staff Name</th>
                <th>Department</th>
                <th>Total Days</th>
                <th>Present</th>
                <th>Late</th>
                <th>Absent</th>
                <th>Leave</th>
                <th>Rate</th>
            </tr>
        </thead>
        <tbody>
            @foreach($stats as $staff)
                @php 
                    $rate = round(($staff->present_count / ($staff->total_days ?: 1)) * 100, 1);
                @endphp
                <tr>
                    <td style="font-family: monospace; font-size: 10px;">{{ $staff->staff->staff_number }}</td>
                    <td><strong>{{ $staff->staff->user->name }}</strong></td>
                    <td style="font-size: 10px;">{{ $staff->staff->department->name }}</td>
                    <td style="text-align: center;">{{ $staff->total_days }}</td>
                    <td style="text-align: center;" class="text-green">{{ $staff->present_count }}</td>
                    <td style="text-align: center;" class="text-amber">{{ $staff->late_count }}</td>
                    <td style="text-align: center;" class="text-red">{{ $staff->absent_count }}</td>
                    <td style="text-align: center;">{{ $staff->leave_count }}</td>
                    <td>
                        <span style="font-weight: bold; color: {{ $rate >= 80 ? '#16a34a' : '#d97706' }}">{{ $rate }}%</span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        &copy; {{ date('Y') }} University Portal - Academic Management System
    </div>
</body>
</html>
