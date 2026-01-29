<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Admission Letter</title>
    <style>
        body {
            font-family: 'Times New Roman', Times, serif;
            line-height: 1.6;
            color: #000;
        }

        .header {
            text-align: center;
            margin-bottom: 50px;
        }

        .logo {
            max-width: 100px;
            margin-bottom: 20px;
        }

        .university-name {
            font-size: 24px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .address {
            font-size: 14px;
        }

        .content {
            margin: 0 50px;
        }

        .date {
            text-align: right;
            margin-bottom: 30px;
        }

        .recipient {
            margin-bottom: 30px;
        }

        .greeting {
            margin-bottom: 20px;
        }

        .body-text {
            text-align: justify;
            margin-bottom: 20px;
        }

        .signature {
            margin-top: 60px;
        }

        .registrar {
            font-weight: bold;
            margin-top: 50px;
            border-top: 1px solid #000;
            display: inline-block;
            padding-top: 5px;
            min-width: 200px;
        }
    </style>
</head>

<body>
    <div class="header">
        <!-- <img src="{{ public_path('logo.png') }}" class="logo" /> -->
        <div class="university-name">University Portal</div>
        <div class="address">PMB 1234, Laravel Avenue, Code City</div>
    </div>

    <div class="content">
        <div class="date">
            Date: {{ now()->format('F d, Y') }}
        </div>

        <div class="recipient">
            <strong>{{ $applicant->user->name }}</strong><br>
            JAMB No: {{ $applicant->jamb_registration_number }}<br>
        </div>

        <div class="greeting">
            Dear {{ $applicant->first_name ?? 'Applicant' }},
        </div>

        <div class="body-text">
            <strong>OFFER OF PROVISIONAL ADMISSION INTO {{ strtoupper($applicant->application_mode) }}
                PROGRAMME</strong>
        </div>

        <div class="body-text">
            We are pleased to inform you that you have been offered provisional admission into the
            <strong>{{ $programme_name }}</strong> programme
            in the Faculty of <strong>{{ $faculty_name }}</strong>
            for the 2025/2026 Academic Session.
        </div>

        <div class="body-text">
            This offer is subject to the validation of your credentials and payment of the necessary acceptance fees.
            Please report to the University Admissions Office for further instructions.
        </div>

        <div class="signature">
            Yours faithfully,<br><br>

            <div class="registrar">
                Dr. A. B. C. Registrar<br>
                Registrar
            </div>
        </div>
    </div>
</body>

</html>