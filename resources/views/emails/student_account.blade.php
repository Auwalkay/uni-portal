<!DOCTYPE html>
<html>

<head>
    <title>Welcome to the University Portal</title>
</head>

<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 5px;">
        <h2 style="color: #2c3e50;">Congratulations, {{ $user->name }}!</h2>

        <p>A student account has been created for you on the University Portal.</p>

        <p>Below are your login credentials:</p>
        <p>
            <strong>Email:</strong> {{ $user->email }}<br>
            <strong>Password:</strong> {{ $password }}
        </p>

        <p>Please log in to your portal to complete your registration and clearance.</p>

        <p>
            <a href="{{ route('login') }}"
                style="display: inline-block; padding: 10px 20px; background-color: #3498db; color: #ffffff; text-decoration: none; border-radius: 3px;">
                Login to Portal
            </a>
        </p>

        <p>Best regards,<br>The Admissions Team</p>
    </div>
</body>

</html>