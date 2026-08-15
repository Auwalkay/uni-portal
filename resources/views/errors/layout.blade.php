<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') | Mewar International University</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #f8fafc;
            --card-color: #ffffff;
            --text-color: #0f172a;
            --text-muted: #475569;
            --border-color: #e2e8f0;
            --accent-color: #0b2265; /* MIU Deep Navy */
            --accent-hover: #071744;
            --btn-text: #ffffff;
            --btn-sec-bg: #f1f5f9;
            --btn-sec-text: #0f172a;
            --btn-sec-border: #cbd5e1;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --bg-color: #090d16;
                --card-color: #111827;
                --text-color: #f8fafc;
                --text-muted: #94a3b8;
                --border-color: #1f2937;
                --accent-color: #3b82f6; /* Modern Blue */
                --accent-hover: #2563eb;
                --btn-text: #ffffff;
                --btn-sec-bg: #1f2937;
                --btn-sec-text: #f8fafc;
                --btn-sec-border: #374151;
            }
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            margin: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 24px;
            box-sizing: border-box;
            transition: background-color 0.3s ease;
        }

        .error-card {
            width: 100%;
            max-width: 520px;
            background-color: var(--card-color);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 48px 40px;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.05), 0 2px 4px -2px rgb(0 0 0 / 0.05), 0 10px 15px -3px rgb(0 0 0 / 0.03);
            text-align: left;
            position: relative;
        }

        .header-section {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 40px;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 24px;
        }

        .logo-wrap {
            display: flex;
            align-items: center;
        }

        .logo-wrap img {
            height: 48px;
            width: auto;
            object-fit: contain;
        }

        .status-badge {
            font-family: monospace;
            font-size: 14px;
            font-weight: 700;
            color: var(--text-muted);
            background-color: var(--btn-sec-bg);
            border: 1px solid var(--border-color);
            padding: 4px 12px;
            border-radius: 6px;
            letter-spacing: 0.05em;
        }

        .error-title {
            font-size: 24px;
            font-weight: 700;
            line-height: 1.25;
            margin: 0 0 12px 0;
            color: var(--text-color);
            letter-spacing: -0.02em;
        }

        .error-message {
            font-size: 15px;
            color: var(--text-muted);
            line-height: 1.6;
            margin: 0 0 32px 0;
        }

        .actions-wrap {
            display: flex;
            flex-direction: row;
            gap: 12px;
            margin-bottom: 40px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.15s ease;
            box-sizing: border-box;
        }

        .btn-primary {
            background-color: var(--accent-color);
            color: var(--btn-text);
            border: 1px solid transparent;
        }

        .btn-primary:hover {
            background-color: var(--accent-hover);
        }

        .btn-secondary {
            background-color: var(--btn-sec-bg);
            color: var(--btn-sec-text);
            border: 1px solid var(--btn-sec-border);
        }

        .btn-secondary:hover {
            opacity: 0.9;
        }

        .footer-support {
            font-size: 12px;
            color: var(--text-muted);
            line-height: 1.5;
            border-top: 1px solid var(--border-color);
            padding-top: 24px;
        }

        .footer-support a {
            color: var(--accent-color);
            text-decoration: none;
            font-weight: 600;
        }

        .footer-support a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="header-section">
            <div class="logo-wrap">
                <img src="/miu-logo.png" alt="Mewar International University Logo">
            </div>
            <div class="status-badge">HTTP @yield('code')</div>
        </div>
        
        <h1 class="error-title">@yield('title')</h1>
        <p class="error-message">@yield('message')</p>
        
        <div class="actions-wrap">
            <a href="/" class="btn btn-primary">Return to Portal Home</a>
            <button onclick="window.history.back()" class="btn btn-secondary">Go Back</button>
        </div>

        <div class="footer-support">
            Need assistance? Please contact the Mewar ICT Support Desk at 
            <a href="mailto:support@mewaruniversity.edu.ng">support@mewaruniversity.edu.ng</a> 
            or file a ticket via the Portal Helpdesk.
        </div>
    </div>
</body>
</html>
