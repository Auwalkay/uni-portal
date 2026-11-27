<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>SPGS Provisional Admission Letter</title>
    <style>
        @page {
            margin: 1.1cm 1.4cm;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            color: #1e293b;
            line-height: 1.42;
            margin: 0;
            padding: 0;
            background: #fff;
            font-size: 10.5px;
        }
        .watermark {
            position: fixed;
            top: 52%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.04;
            z-index: -1000;
            width: 360px;
        }
        .uni-title {
            text-align: center;
            color: #dc2626;
            font-size: 16px;
            font-weight: 700;
            letter-spacing: 0.6px;
            margin: 0;
            text-transform: uppercase;
        }
        .spgs-title {
            text-align: center;
            color: #0284c7;
            font-size: 19px;
            font-weight: 800;
            letter-spacing: 0.8px;
            margin: 2px 0 8px 0;
            text-transform: uppercase;
        }
        .header-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }
        .header-grid td {
            vertical-align: top;
            font-size: 8.5px;
            line-height: 1.3;
        }
        .header-left {
            width: 39%;
            text-align: left;
        }
        .header-center {
            width: 22%;
            text-align: center;
            vertical-align: middle !important;
        }
        .header-right {
            width: 39%;
            text-align: left;
            padding-left: 8px;
        }
        .logo {
            width: 68px;
            height: auto;
        }
        .officer-line {
            margin-bottom: 4px;
        }
        .officer-role {
            font-weight: 700;
            color: #0f172a;
        }
        .officer-email {
            color: #1d4ed8;
            font-size: 8px;
        }
        .divider {
            border-top: 2px solid #2563eb;
            margin: 4px 0 10px 0;
        }
        .ref-row {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 10px;
        }
        .ref-row td {
            vertical-align: top;
        }
        .ref-left {
            text-align: left;
            font-weight: 600;
        }
        .ref-right {
            text-align: right;
            font-weight: 600;
        }
        .salutation {
            margin: 6px 0 10px 0;
            font-size: 10.5px;
        }
        .offer-heading {
            text-align: center;
            font-size: 13.5px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin: 4px 0 6px 0;
            color: #0f172a;
        }
        .programme-heading {
            text-align: center;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            margin: 0 0 12px 0;
            color: #0f172a;
        }
        .content p {
            margin: 0 0 9px 0;
            text-align: justify;
        }
        .section-title {
            font-weight: 700;
            margin: 10px 0 5px 0;
            color: #0f172a;
        }
        .details-list {
            margin: 0 0 10px 18px;
            line-height: 1.5;
        }
        .details-list div {
            margin-bottom: 2px;
        }
        .conditions-list {
            margin: 4px 0 10px 0;
            padding-left: 18px;
        }
        .conditions-list li {
            margin-bottom: 5px;
            text-align: justify;
        }
        .footer {
            margin-top: 16px;
            width: 100%;
            display: table;
        }
        .signature-box {
            display: table-cell;
            width: 65%;
            vertical-align: bottom;
        }
        .signature-img {
            width: 105px;
            height: auto;
            margin-bottom: 2px;
        }
        .signature-line {
            border-bottom: 1px solid #334155;
            width: 160px;
            height: 24px;
            margin-bottom: 4px;
        }
        .signatory-role {
            font-weight: 700;
            font-size: 10.5px;
            text-transform: uppercase;
            color: #0f172a;
        }
        .signatory-unit {
            font-size: 10px;
            color: #334155;
        }
        .qr-box {
            display: table-cell;
            width: 35%;
            text-align: right;
            vertical-align: bottom;
        }
        .qr-box img {
            width: 72px;
            height: 72px;
            display: block;
            margin-left: auto;
        }
        .qr-box .scan-text {
            display: block;
            font-size: 7.5px;
            font-weight: 700;
            color: #64748b;
            margin-top: 3px;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    @php
        $logoPath = public_path('miu-logo.png');
        if (!file_exists($logoPath)) {
            $logoPath = public_path('miu-logo.jpeg');
        }
        $logoBase64 = file_exists($logoPath) ? base64_encode(file_get_contents($logoPath)) : null;

        $spgsSigBase64 = null;
        foreach (['images/spgs_secretary_signature.png', 'images/spgs_secretary_signature.jpeg', 'images/spgs_secretary_signature.jpg'] as $sigCandidate) {
            $candidatePath = public_path($sigCandidate);
            if (file_exists($candidatePath)) {
                $spgsSigBase64 = base64_encode(file_get_contents($candidatePath));
                break;
            }
        }

        $tier = $pg_tier ?? 'masters';
        $degreeLabel = $degree_label ?? match ($tier) {
            'phd' => 'Doctor of Philosophy (Ph.D.)',
            'pgd' => 'Postgraduate Diploma (PGD)',
            default => 'Master of Science (M.Sc.)',
        };
        $refCode = strtoupper((string) ($applicant->application_number ?? $applicant->jamb_registration_number ?? 'MIU/SPGS/ADM/' . date('Y') . '/001'));
    @endphp

    @if($logoBase64)
        <img src="data:image/jpeg;base64,{{ $logoBase64 }}" class="watermark" alt="Watermark">
    @endif

    <!-- SPGS Letterhead -->
    <div class="uni-title">Mewar International University</div>
    <div class="spgs-title">School of Postgraduate Studies</div>

    <table class="header-grid">
        <tr>
            <td class="header-left">
                <div class="officer-line">
                    <span class="officer-role">Dean:</span> Prof. Shiv Kumar Singh<br>
                    <span class="officer-email">dean@miu.edu.ng</span> // B.Sc, M.Sc, PhD
                </div>
                <div class="officer-line">
                    <span class="officer-role">Deputy Dean:</span> Prof. A.O. Lawal<br>
                    <span class="officer-email">depdean@miu.edu.ng</span> // B.Sc, M.Sc, PhD
                </div>
                <div class="officer-line">
                    <span class="officer-role">Sub-Dean:</span> Dr. Oyatayo Taiwo Taoridi<br>
                    <span class="officer-email">subdean@miu.edu.ng</span> // B.Sc, M.Sc, PhD
                </div>
            </td>
            <td class="header-center">
                @if($logoBase64)
                    <img src="data:image/jpeg;base64,{{ $logoBase64 }}" class="logo" alt="MIU Logo">
                @endif
            </td>
            <td class="header-right">
                <div class="officer-line">
                    <span class="officer-role">Address:</span> KM 21 Abuja Keffi, Road, Masaka,<br>
                    Nasarawa State, Nigeria.<br>
                    <span class="officer-role">Phone:</span> +2348149670513<br>
                    <span class="officer-role">E-mail:</span> <span class="officer-email">spgs@miu.edu.ng</span>
                </div>
                <div class="officer-line">
                    <span class="officer-role">Secretary:</span> Mr. Jonah Akuso<br>
                    <span class="officer-email">secretaryspgs@miu.edu.ng</span> // B.Sc, M.Sc.
                </div>
            </td>
        </tr>
    </table>

    <div class="divider"></div>

    <!-- Ref & Date -->
    <table class="ref-row">
        <tr>
            <td class="ref-left">Ref: {{ $refCode }}</td>
            <td class="ref-right">{{ now()->format('jS F, Y') }}</td>
        </tr>
    </table>

    <div class="salutation">
        Dear <strong>{{ strtoupper($applicant->user->name ?? $applicant->first_name ?? 'Applicant') }}</strong>,
    </div>

    <div class="offer-heading">Offer of Provisional Admission</div>
    <div class="programme-heading">
        {{ strtoupper($degreeLabel) }} PROGRAMME &ndash; {{ $session_name }} ACADEMIC SESSION
    </div>

    <div class="content">
        @if($tier === 'phd')
            <p>
                I am pleased to inform you that you have been offered <strong>Provisional Admission</strong> into the
                Doctor of Philosophy (Ph.D.) Programme of Mewar International University, Masaka, under the School of
                Postgraduate Studies for the {{ $session_name }} Academic Session.
            </p>

            <div class="section-title">Programme Details</div>
            <div class="details-list">
                <div><strong>Programme:</strong> {{ $programme_name }}</div>
                <div><strong>Duration:</strong> Minimum of Six (6) Semesters (Three Academic Years).</div>
                <div><strong>Mode of Study:</strong> Full-Time.</div>
            </div>

            <div class="section-title">Conditions of Admission</div>
            <p>This offer is subject to the fulfilment of the following conditions:</p>
            <ol class="conditions-list">
                <li>Verification of original academic credentials, including Master&rsquo;s Degree certificate (minimum CGPA of 3.5), academic transcripts, NYSC Discharge/Exemption Certificate and other relevant documents.</li>
                <li>Payment of the non-refundable application fee and all prescribed tuition and other applicable fees within the stipulated deadline.</li>
                <li>Prior to the commencement of the documentation process, candidates are required to complete all outstanding personal and academic information on the University&rsquo;s admission portal and ensure that all application fees have been duly paid.</li>
                <li>Submission and approval of a research synopsis and assignment of supervisor(s) by the School of Postgraduate Studies.</li>
                <li>Satisfactory medical fitness and compliance with the University&rsquo;s academic regulations and institutional policies.</li>
                <li>Confirmation of the authenticity of all information and documents submitted. The University reserves the right to withdraw this offer where any information is found to be false or admission requirements remain unfulfilled.</li>
            </ol>

            <p>
                You are required to report to the School of Postgraduate Studies, Mewar International University, Masaka Campus,
                for credential screening, registration and orientation in accordance with the approved Academic Calendar.
                Please bring this letter, your original credentials, four (4) recent passport photographs and evidence of payment of the acceptance fee.
            </p>
            <p>
                On behalf of the Vice-Chancellor, Management and Senate of the University, I congratulate you on this academic achievement
                and warmly welcome you to the postgraduate research community. We look forward to your meaningful contribution to
                scholarship, research and knowledge advancement.
            </p>

        @elseif($tier === 'pgd')
            <p>
                I am pleased to inform you that you have been offered <strong>Provisional Admission</strong> into the
                Postgraduate Diploma (PGD) Programme of Mewar International University, Masaka, under the School of
                Postgraduate Studies for the {{ $session_name }} Academic Session.
            </p>

            <div class="section-title" style="text-transform: uppercase;">Programme Details</div>
            <div class="details-list">
                <div><strong>Programme:</strong> {{ $programme_name }}</div>
                <div><strong>Duration:</strong> Two (2) Semesters (One Academic Session).</div>
                <div><strong>Mode of Study:</strong> Full-Time</div>
            </div>

            <div class="section-title" style="text-transform: uppercase;">Conditions of Admission</div>
            <p>This offer is subject to the fulfilment of the following conditions:</p>
            <ol class="conditions-list">
                <li>Verification of original academic credentials, including Bachelor&rsquo;s Degree or Higher National Diploma (HND) certificate, academic transcripts, NYSC Discharge/Exemption Certificate and other relevant documents.</li>
                <li>Payment of the non-refundable application fee and all prescribed tuition and other applicable fees within the stipulated deadline.</li>
                <li>Satisfactory medical fitness and compliance with the University&rsquo;s academic regulations and institutional policies.</li>
                <li>Verification of the authenticity of all submitted information and documents. The University reserves the right to withdraw this offer in the event of false information or failure to satisfy the admission requirements.</li>
            </ol>

            <p>
                You are required to report to the School of Postgraduate Studies, Mewar International University, Masaka Campus,
                for credential screening, registration and orientation in accordance with the approved Academic Calendar.
                Please present this letter, your original credentials, four (4) recent passport photographs and evidence of payment of the acceptance fee.
            </p>
            <p>
                On behalf of the Vice-Chancellor, Management and Senate, I congratulate you on your admission and warmly welcome you
                to the postgraduate community. We look forward to your successful academic engagement at Mewar International University.
            </p>

        @else
            <p>
                I am pleased to inform you that you have been offered <strong>Provisional Admission</strong> into the
                {{ $degreeLabel }} Programme of Mewar International University, Masaka, through the School of Postgraduate
                Studies for the {{ $session_name }} Academic Session.
            </p>

            <div class="section-title">Programme Details</div>
            <div class="details-list">
                <div><strong>Programme:</strong> {{ $programme_name }}</div>
                <div><strong>Duration:</strong> Minimum of Three (3) Semesters (18 Months).</div>
                <div><strong>Mode of Study:</strong> Full-Time as applicable.</div>
            </div>

            <div class="section-title">Conditions of Admission</div>
            <p>This offer is subject to the fulfilment of the following conditions:</p>
            <ol class="conditions-list">
                <li>Verification of original academic credentials, including Bachelor&rsquo;s Degree certificate, academic transcripts (minimum CGPA of 3.00), NYSC Discharge/Exemption Certificate and other relevant documents.</li>
                <li>Payment of the non-refundable prescribed tuition and application fees within the stipulated period.</li>
                <li>Prior to the commencement of the documentation process, candidates are required to complete all outstanding personal and academic information on the University&rsquo;s admission portal and ensure that all application fees have been duly paid.</li>
                <li>Satisfactory medical fitness and compliance with the University&rsquo;s academic regulations and institutional policies.</li>
                <li>Confirmation that all information and documents submitted in support of your application are authentic. The University reserves the right to withdraw this offer in the event of false information or failure to satisfy the admission requirements.</li>
            </ol>

            <p>
                You are required to report to the School of Postgraduate Studies, Mewar International University, Masaka Campus,
                for credential screening, registration and orientation in accordance with the approved Academic Calendar.
                Please come along with this letter, original credentials, four (4) recent passport photographs and evidence of payment of the application fee.
            </p>
            <p>
                On behalf of the Vice-Chancellor, Management and Senate of the University, I congratulate you on this achievement
                and warmly welcome you to the postgraduate community. We look forward to supporting your academic advancement
                through excellence in teaching, research and character development.
            </p>
        @endif
    </div>

    <!-- Footer -->
    <div class="footer">
        <div class="signature-box">
            <div style="margin-bottom: 6px;"><strong>Yours faithfully,</strong></div>
            @if($spgsSigBase64)
                <img src="data:image/png;base64,{{ $spgsSigBase64 }}" class="signature-img" alt="SPGS Secretary Signature">
            @else
                <div class="signature-line"></div>
            @endif
            <div class="signatory-role">Secretary</div>
            <div class="signatory-unit">School of Postgraduate Studies</div>
        </div>
        <div class="qr-box">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={{ urlencode(route('verify.admission', encrypt($applicant->id ?? 'unknown'))) }}" alt="Verification QR">
            <span class="scan-text">Scan to Verify Admission</span>
        </div>
    </div>
</body>
</html>
