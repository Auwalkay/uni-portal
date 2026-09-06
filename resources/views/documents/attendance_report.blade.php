<!DOCTYPE html>
<html>
<head>
    <title>University Attendance & Compliance Report</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 11px; color: #1e293b; line-height: 1.4; }
        .header { text-align: center; margin-bottom: 20px; border-b: 2px solid #e2e8f0; padding-bottom: 15px; }
        .header h1 { margin: 0 0 5px 0; color: #0f172a; font-size: 20px; text-transform: uppercase; letter-spacing: 0.5px; }
        .header p { margin: 0; color: #475569; font-weight: bold; font-size: 13px; }
        .header .meta { font-size: 10px; color: #64748b; margin-top: 5px; }
        
        .kpi-container { width: 100%; margin-bottom: 20px; text-align: center; }
        .kpi-box { display: inline-block; width: 18%; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px; margin: 0 0.8%; vertical-align: top; }
        .kpi-title { font-size: 9px; text-transform: uppercase; color: #64748b; font-weight: bold; margin-bottom: 4px; }
        .kpi-value { font-size: 16px; font-weight: bold; color: #0f172a; }
        .kpi-sub { font-size: 9px; color: #94a3b8; margin-top: 2px; }

        .section-title { font-size: 13px; font-weight: bold; color: #0f172a; margin-top: 20px; margin-bottom: 8px; text-transform: uppercase; border-bottom: 1px solid #cbd5e1; padding-bottom: 4px; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th { background-color: #f1f5f9; color: #334155; font-weight: bold; text-align: left; padding: 8px 6px; border: 1px solid #cbd5e1; font-size: 10px; text-transform: uppercase; }
        td { padding: 7px 6px; border: 1px solid #e2e8f0; color: #1e293b; font-size: 10px; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .footer { text-align: right; font-size: 9px; color: #94a3b8; margin-top: 25px; border-top: 1px solid #e2e8f0; padding-top: 8px; }
        .text-green { color: #16a34a; font-weight: bold; }
        .text-amber { color: #d97706; font-weight: bold; }
        .text-red { color: #dc2626; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>University Attendance & Punctuality Report</h1>
        <p>{{ $title }}</p>
        <div class="meta">Report Generated: {{ $date }} | System-Wide Staff Attendance Audit</div>
    </div>

    @if(isset($overallStats))
    <div class="kpi-container">
        <div class="kpi-box">
            <div class="kpi-title">Total Staff</div>
            <div class="kpi-value">{{ $overallStats['total_staff'] }}</div>
            <div class="kpi-sub">Tracked</div>
        </div>
        <div class="kpi-box">
            <div class="kpi-title">Avg Attendance</div>
            <div class="kpi-value text-green">{{ $overallStats['avg_attendance_rate'] }}%</div>
            <div class="kpi-sub">Overall Rate</div>
        </div>
        <div class="kpi-box">
            <div class="kpi-title">Punctuality Score</div>
            <div class="kpi-value text-amber">{{ $overallStats['avg_punctuality_rate'] }}%</div>
            <div class="kpi-sub">On-Time Arrival</div>
        </div>
        <div class="kpi-box">
            <div class="kpi-title">Total Absences</div>
            <div class="kpi-value text-red">{{ $overallStats['total_absent'] }}</div>
            <div class="kpi-sub">Unlogged Days</div>
        </div>
        <div class="kpi-box">
            <div class="kpi-title">Hours Logged</div>
            <div class="kpi-value">{{ $overallStats['total_hours_worked'] }} hrs</div>
            <div class="kpi-sub">Total Time</div>
        </div>
    </div>
    @endif

    <div class="section-title">Staff Attendance & Punctuality Breakdown</div>
    <table>
        <thead>
            <tr>
                <th>Staff ID</th>
                <th>Staff Name</th>
                <th>Department</th>
                <th class="text-center">Days</th>
                <th class="text-center">Present</th>
                <th class="text-center">Late</th>
                <th class="text-center">Absent</th>
                <th class="text-center">Leave</th>
                <th class="text-center">Avg Clock In</th>
                <th class="text-center">Hours</th>
                <th class="text-right">Punctuality</th>
                <th class="text-right">Rate</th>
            </tr>
        </thead>
        <tbody>
            @foreach($stats as $staff)
                <tr>
                    <td style="font-family: monospace; font-weight: bold; color: #475569;">{{ $staff->staff->staff_number }}</td>
                    <td><strong>{{ $staff->staff->user->name }}</strong></td>
                    <td>{{ $staff->staff->department->name }}</td>
                    <td class="text-center">{{ $staff->total_days }}</td>
                    <td class="text-center text-green">{{ $staff->present_count }}</td>
                    <td class="text-center text-amber">{{ $staff->late_count }}</td>
                    <td class="text-center text-red">{{ $staff->absent_count }}</td>
                    <td class="text-center">{{ $staff->leave_count }}</td>
                    <td class="text-center" style="font-weight: bold;">{{ $staff->avg_clock_in }}</td>
                    <td class="text-center">{{ $staff->total_hours_formatted }}</td>
                    <td class="text-right" style="font-weight: bold;">{{ $staff->punctuality_rate }}%</td>
                    <td class="text-right">
                        <span style="font-weight: bold; color: {{ $staff->rate >= 80 ? '#16a34a' : ($staff->rate >= 50 ? '#d97706' : '#dc2626') }}">{{ $staff->rate }}%</span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if(isset($departmentSummary) && count($departmentSummary) > 0)
    <div class="section-title">Departmental Performance Summary</div>
    <table>
        <thead>
            <tr>
                <th>Department Name</th>
                <th class="text-center">Staff Count</th>
                <th class="text-center">Total Days</th>
                <th class="text-center">Present</th>
                <th class="text-center">Late</th>
                <th class="text-center">Absent</th>
                <th class="text-right">Avg Punctuality</th>
                <th class="text-right">Avg Attendance Rate</th>
            </tr>
        </thead>
        <tbody>
            @foreach($departmentSummary as $dept)
                <tr>
                    <td><strong>{{ $dept['department_name'] }}</strong></td>
                    <td class="text-center">{{ $dept['staff_count'] }}</td>
                    <td class="text-center">{{ $dept['total_days'] }}</td>
                    <td class="text-center text-green">{{ $dept['present_count'] }}</td>
                    <td class="text-center text-amber">{{ $dept['late_count'] }}</td>
                    <td class="text-center text-red">{{ $dept['absent_count'] }}</td>
                    <td class="text-right" style="font-weight: bold;">{{ $dept['avg_punctuality'] }}%</td>
                    <td class="text-right" style="font-weight: bold; color: {{ $dept['avg_rate'] >= 80 ? '#16a34a' : '#d97706' }};">
                        {{ $dept['avg_rate'] }}%
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <div class="footer">
        &copy; {{ date('Y') }} University Portal Academic & HR Portal — Official Attendance & Compliance Audit Report
    </div>
</body>
</html>
