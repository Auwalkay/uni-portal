<!DOCTYPE html>
<html>
<head>
    <title>Student Fee Report</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #333; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { margin-bottom: 5px; color: #1e293b; font-size: 24px; }
        .header p { margin: 0; color: #64748b; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background-color: #f1f5f9; color: #475569; font-weight: bold; text-align: left; padding: 12px 8px; border: 1px solid #e2e8f0; text-transform: uppercase; font-size: 9px; }
        td { padding: 10px 8px; border: 1px solid #e2e8f0; vertical-align: middle; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .footer { text-align: right; font-size: 9px; color: #94a3b8; margin-top: 30px; border-top: 1px solid #eee; padding-top: 10px; }
        .status-paid { color: #16a34a; font-weight: bold; }
        .status-partial { color: #d97706; font-weight: bold; }
        .status-unpaid { color: #dc2626; font-weight: bold; }
        .matric { font-family: monospace; font-size: 9px; color: #64748b; }
    </style>
</head>
<body>
    <div class="header">
        @if(file_exists(public_path('miu-logo.png')))
            <img src="{{ public_path('miu-logo.png') }}" style="max-height: 60px; margin-bottom: 15px;">
        @endif
        <h1>Student Fee Collection Report</h1>
        <p>Academic Session: {{ $session->name }}</p>
        <div style="font-size: 9px; margin-top: 10px; color: #999;">Generated on: {{ $date }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Matric No.</th>
                <th>Student Name</th>
                <th>Programme</th>
                <th class="text-right">Billed (NGN)</th>
                <th class="text-right">Paid (NGN)</th>
                <th class="text-right">Balance (NGN)</th>
                <th class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $totalBilled = 0;
                $totalPaid = 0;
                $totalBalance = 0;
            @endphp
            @foreach($students as $student)
                @php 
                    $totalBilled += $student->total_billed;
                    $totalPaid += $student->total_paid;
                    $totalBalance += $student->balance;
                @endphp
                <tr>
                    <td class="matric">{{ $student->matriculation_number }}</td>
                    <td class="font-bold">{{ $student->user->name }}</td>
                    <td style="font-size: 9px;">{{ $student->program->name }} ({{ $student->current_level }}L)</td>
                    <td class="text-right">{{ number_format($student->total_billed, 2) }}</td>
                    <td class="text-right font-bold" style="color: #16a34a;">{{ number_format($student->total_paid, 2) }}</td>
                    <td class="text-right font-bold" style="color: #dc2626;">{{ number_format($student->balance, 2) }}</td>
                    <td class="text-center">
                        <span class="status-{{ $student->fee_status === 'partially_paid' ? 'partial' : $student->fee_status }}">
                            {{ strtoupper(str_replace('_', ' ', $student->fee_status)) }}
                        </span>
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background-color: #f8fafc;">
                <td colspan="3" class="font-bold text-right" style="padding: 15px;">TOTALS</td>
                <td class="text-right font-bold">{{ number_format($totalBilled, 2) }}</td>
                <td class="text-right font-bold" style="color: #16a34a;">{{ number_format($totalPaid, 2) }}</td>
                <td class="text-right font-bold" style="color: #dc2626;">{{ number_format($totalBalance, 2) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        &copy; {{ date('Y') }} University Portal - Bursary Department Official Report
    </div>
</body>
</html>
