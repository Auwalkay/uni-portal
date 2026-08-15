<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Announcement</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background-color: #f4f5f7;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        .header {
            background-image: linear-gradient(135deg, #2563eb, #4f46e5);
            padding: 30px 40px;
            color: #ffffff;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
            letter-spacing: -0.025em;
        }
        .header p {
            margin: 5px 0 0 0;
            font-size: 14px;
            color: #bfdbfe;
        }
        .content {
            padding: 40px;
            color: #1e293b;
            line-height: 1.6;
        }
        .content h2 {
            font-size: 20px;
            margin-top: 0;
            color: #0f172a;
            font-weight: 600;
        }
        .content p {
            font-size: 16px;
            margin-bottom: 24px;
        }
        .bulletin-box {
            background-color: #f8fafc;
            border-left: 4px solid #4f46e5;
            padding: 20px;
            border-radius: 0 8px 8px 0;
            font-size: 15px;
            margin-bottom: 30px;
            white-space: pre-wrap;
        }
        .button-container {
            text-align: center;
            margin-bottom: 30px;
        }
        .btn {
            display: inline-block;
            background-color: #4f46e5;
            color: #ffffff !important;
            text-decoration: none;
            padding: 12px 24px;
            font-size: 15px;
            font-weight: 600;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2);
            transition: background-color 0.2s;
        }
        .btn:hover {
            background-color: #4338ca;
        }
        .footer {
            background-color: #f8fafc;
            padding: 24px 40px;
            text-align: center;
            font-size: 12px;
            color: #64748b;
            border-top: 1px solid #f1f5f9;
        }
        .footer p {
            margin: 4px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>University Portal</h1>
            <p>Official Announcement & Bulletin Notification</p>
        </div>
        <div class="content">
            <p>Dear {{ $user->name }},</p>
            <p>A new official announcement has been published on the university portal:</p>
            
            <h2>{{ $bulletin->title }}</h2>
            
            <div class="bulletin-box">
                {!! nl2br(e($bulletin->content)) !!}
            </div>

            <div class="button-container">
                <a href="{{ url('/dashboard') }}" class="btn">View on Portal</a>
            </div>
            
            <p>Sincerely,<br><strong>University Administration</strong></p>
        </div>
        <div class="footer">
            <p>This is an automated notification. Please do not reply directly to this email.</p>
            <p>&copy; {{ date('Y') }} University Portal. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
