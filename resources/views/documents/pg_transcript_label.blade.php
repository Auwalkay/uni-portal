<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SPGS Official Transcript Request Label</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #1e293b;
            line-height: 1.5;
            margin: 24px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #312e81;
            padding-bottom: 14px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
            color: #1e1b4b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .header h2 {
            margin: 4px 0 0;
            font-size: 13px;
            color: #4338ca;
            text-transform: uppercase;
        }
        .ref-badge {
            display: inline-block;
            margin-top: 8px;
            padding: 4px 12px;
            background-color: #eef2ff;
            border: 1px solid #818cf8;
            font-weight: bold;
            font-size: 12px;
            color: #1e1b4b;
        }
        .section {
            margin-bottom: 18px;
            padding: 12px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
        }
        .section-title {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            color: #4338ca;
            margin-bottom: 8px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 4px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        td {
            padding: 4px 6px;
            vertical-align: top;
        }
        td.label {
            width: 36%;
            font-weight: bold;
            color: #475569;
        }
        .mailing-label {
            margin-top: 22px;
            padding: 16px;
            border: 2px dashed #1e1b4b;
            background-color: #f8fafc;
        }
        .mailing-label h3 {
            margin: 0 0 8px;
            font-size: 13px;
            text-transform: uppercase;
            color: #1e1b4b;
        }
        .cut-note {
            font-size: 10px;
            color: #64748b;
            text-align: center;
            margin-bottom: 6px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ config('app.name', 'University Portal') }}</h1>
        <h2>School of Postgraduate Studies (SPGS) — Official Transcript Request Form &amp; Label</h2>
        <div class="ref-badge">
            SPGS Reference: {{ $applicant->jamb_registration_number }} | Session: {{ $session_name }}
        </div>
    </div>

    <p>
        <strong>INSTRUCTIONS TO THE APPLICANT:</strong> Please complete and submit this Official Transcript Request Form to the Registrar / Academic Office of your former institution(s). Official academic transcripts must be forwarded directly by your former institution to the Secretary, School of Postgraduate Studies, either in a sealed and stamped official envelope or via official institutional email, bearing the SPGS Reference Number above.
    </p>

    <div class="section">
        <div class="section-title">1. Applicant &amp; Target Postgraduate Programme Details</div>
        <table>
            <tr>
                <td class="label">Full Name of Applicant:</td>
                <td>{{ trim(($applicant->title ? $applicant->title . ' ' : '') . $applicant->first_name . ' ' . $applicant->last_name) }}</td>
            </tr>
            @if($applicant->has_name_change && $applicant->previous_full_name)
            <tr>
                <td class="label">Former Name on Transcript:</td>
                <td>{{ $applicant->previous_full_name }}</td>
            </tr>
            @endif
            <tr>
                <td class="label">SPGS Application Reference:</td>
                <td><strong>{{ $applicant->jamb_registration_number }}</strong></td>
            </tr>
            <tr>
                <td class="label">Target PG Programme:</td>
                <td>{{ $programme_name }} ({{ strtoupper(str_replace('_', '-', $applicant->study_mode ?? 'Full-Time')) }})</td>
            </tr>
            <tr>
                <td class="label">Department / Faculty:</td>
                <td>{{ $department_name }} / {{ $faculty_name }}</td>
            </tr>
            @if($applicant->area_of_specialization)
            <tr>
                <td class="label">Proposed Specialization:</td>
                <td>{{ $applicant->area_of_specialization }}</td>
            </tr>
            @endif
        </table>
    </div>

    <div class="section">
        <div class="section-title">2. Awarding Institution(s) to Forward Official Transcript</div>
        <table>
            @forelse($qualifications as $index => $qual)
            <tr>
                <td class="label">Qualification #{{ $index + 1 }} ({{ $qual->qualification_type }}):</td>
                <td>
                    <strong>{{ $qual->institution_name }}</strong><br>
                    Discipline: {{ $qual->course_of_study }} | Class: {{ $qual->class_of_degree }}
                    @if($qual->cgpa) | CGPA: {{ number_format((float) $qual->cgpa, 2) }} / {{ number_format((float) ($qual->scale_max ?? 5.0), 2) }} @endif
                    | Year: {{ $qual->graduation_year }}
                </td>
            </tr>
            @empty
            <tr>
                <td class="label">Awarding Institution:</td>
                <td>
                    <strong>{{ $applicant->previous_institution }}</strong><br>
                    Qualification: {{ $applicant->previous_qualification }} ({{ $applicant->previous_course_of_study ?? 'N/A' }}) — {{ $applicant->previous_class_of_degree }} ({{ $applicant->graduation_year }})
                </td>
            </tr>
            @endforelse
        </table>
    </div>

    <div class="cut-note">✂ DETACH OR AFFIX THIS OFFICIAL TRANSCRIPT LABEL TO THE TRANSCRIPT ENVELOPE ✂</div>

    <div class="mailing-label">
        <h3>OFFICIAL ACADEMIC TRANSCRIPT — DO NOT OPEN (DELIVER TO SPGS)</h3>
        <table>
            <tr>
                <td class="label">TO:</td>
                <td>
                    <strong>The Secretary, School of Postgraduate Studies (SPGS)</strong><br>
                    {{ config('app.name', 'University Portal') }}<br>
                    Admissions &amp; Academic Records Division ({{ $faculty_name }} / {{ $department_name }})<br>
                    Official Transcript Desk Email: <strong>transcripts.spgs@university.edu.ng</strong>
                </td>
            </tr>
            <tr>
                <td class="label">APPLICANT NAME:</td>
                <td><strong>{{ strtoupper($applicant->first_name . ' ' . $applicant->last_name) }}</strong></td>
            </tr>
            <tr>
                <td class="label">SPGS APPLICATION REF:</td>
                <td><strong>{{ $applicant->jamb_registration_number }}</strong></td>
            </tr>
            <tr>
                <td class="label">PROGRAMME APPLIED FOR:</td>
                <td>{{ $programme_name }} ({{ $session_name }} Academic Session)</td>
            </tr>
        </table>
    </div>
</body>
</html>
