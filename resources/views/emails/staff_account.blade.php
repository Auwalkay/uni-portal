<!DOCTYPE html>
<html>
<head>
    <title>Staff Account Created</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 5px;">
        <h2 style="color: #2c3e50;">Welcome, {{ $user->name }}!</h2>

        <p>A staff account has been created for you on the University Portal.</p>

        <p>Below are your login credentials:</p>
        <p>
            <strong>Email:</strong> {{ $user->email }}<br>
            <strong>Password:</strong> {{ $password }}
        </p>

        <p>For security reasons, we recommend that you change your password after your first login.</p>

        <p>
            <a href="{{ url('/login') }}" 
               style="display: inline-block; padding: 10px 20px; background-color: #2c3e50; color: #ffffff; text-decoration: none; border-radius: 3px;">
               Login to Portal
            </a>
        </p>

        <p>Best regards,<br>The Management</p>
    </div>
</body>
</html>
