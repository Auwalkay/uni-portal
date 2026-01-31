<!DOCTYPE html>
<html>

<head>
    <title>Course Registration Form</title>
    <style>
        body {
            font-family: sans-serif;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo {
            width: 80px;
            height: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f0f0f0;
        }

        .footer {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
        }

        .signature {
            border-top: 1px solid #000;
            width: 200px;
            padding-top: 5px;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="header">
        <h2>UNIVERSITY PORTAL</h2>
        <h3>Course Registration Form</h3>
        <p>{{ $session->name }} {{ $semester ? '- ' . $semester->name : '' }}</p>
    </div>

    <div>
        <p><strong>Name:</strong> {{ $student->user->name }} {{ $student->user->last_name }}</p>
        <p><strong>Matric No:</strong> {{ $student->matriculation_number }}</p>
        <p><strong>Department:</strong> {{ $student->department_id }}</p>
        <p><strong>Level:</strong> {{ $student->current_level }}</p>
    </div>

    @foreach($registrations as $index => $semester)

        <table>
            <caption>
                {{ $index }}
            </caption>
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Course Title</th>
                    <th>Units</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>

                @foreach($semester as $courses)
                    <tr>
                        <td>{{ $courses->course->code }}</td>
                        <td>{{ $courses->course->title }}</td>
                        <td>{{ $courses->course->units }}</td>
                        <td>Registered</td>
                    </tr>
                @endforeach

                <tr>
                    <td colspan="2" style="text-align: right; font-weight: bold;">Total Units</td>
                    <td colspan="2" style="font-weight: bold;">{{ $total_units }}</td>
                </tr>
            </tbody>
        </table>
    @endforeach

    <div class="footer">
        <br><br><br>
        <table style="border: none;">
            <tr style="border: none;">
                <td style="border: none;">
                    <br>___________________________<br>Student Signature
                </td>
                <td style="border: none; text-align: right;">
                    <br>___________________________<br>Course Adviser
                </td>
            </tr>
        </table>
    </div>
</body>

</html>