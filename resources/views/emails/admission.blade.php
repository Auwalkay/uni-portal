<!DOCTYPE html>
<html>

<head>
    <title>Admission Notification</title>
</head>

<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 5px;">
        <h2 style="color: #2c3e50;">Congratulations, {{ $applicant->user->name }}!</h2>

        <p>We are pleased to inform you that you have been offered provisional admission into the
            <strong>{{ $applicant->programme->name }}</strong> programme at our University.</p>

        <p>To accept this offer, please log in to your portal and proceed with the clearance process.</p>

        <p>Your JAMB Registration Number is: <strong>{{ $applicant->jamb_registration_number }}</strong></p>

        <p>
            <a href="{{ url('/login') }}"
                style="display: inline-block; padding: 10px 20px; background-color: #3498db; color: #ffffff; text-decoration: none; border-radius: 3px;">Login
                to Portal</a>
        </p>

        <p>Best regards,<br>The Admissions Team</p>
    </div>
</body>

</html>