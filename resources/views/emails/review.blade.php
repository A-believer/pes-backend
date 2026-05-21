<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Customer Review</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background-color: #f8fafc;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            border: 1px solid #e2e8f0;
        }
        .header {
            background-color: #0f172a;
            padding: 32px;
            text-align: center;
            border-bottom: 3px solid #0ea5e9; /* Sky Blue highlight */
        }
        .header h1 {
            color: #ffffff;
            font-size: 20px;
            font-weight: 800;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            margin: 0;
        }
        .header p {
            color: #94a3b8;
            font-size: 13px;
            margin: 8px 0 0 0;
            font-weight: 500;
        }
        .content {
            padding: 40px 32px;
        }
        .badge {
            display: inline-block;
            padding: 6px 12px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            background-color: #e0f2fe;
            color: #0ea5e9;
            border-radius: 9999px;
            margin-bottom: 24px;
        }
        .stars-container {
            font-size: 28px;
            color: #eab308; /* Yellow stars */
            margin-bottom: 24px;
        }
        .lead-info {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 32px;
        }
        .lead-info td {
            padding: 12px 0;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: top;
        }
        .lead-info td.label {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            color: #64748b;
            width: 140px;
            letter-spacing: 0.05em;
        }
        .lead-info td.value {
            font-size: 15px;
            color: #0f172a;
            font-weight: 500;
        }
        .message-box {
            background-color: #f8fafc;
            border-radius: 12px;
            padding: 24px;
            border-left: 4px solid #0ea5e9;
            margin-top: 16px;
        }
        .message-title {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            color: #64748b;
            letter-spacing: 0.05em;
            margin-bottom: 8px;
        }
        .message-body {
            font-size: 14px;
            line-height: 1.6;
            color: #334155;
            margin: 0;
            white-space: pre-line;
        }
        .footer {
            background-color: #f8fafc;
            padding: 24px 32px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
        }
        .footer p {
            font-size: 12px;
            color: #94a3b8;
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Premium Expert Services</h1>
            <p>Customer Review Notification Portal</p>
        </div>
        <div class="content">
            <span class="badge">New Customer Review</span>
            
            <div class="stars-container">
                @for ($i = 0; $i < $submission->rating; $i++)
                    ★
                @endfor
                @for ($i = $submission->rating; $i < 5; $i++)
                    ☆
                @endfor
            </div>

            <table class="lead-info">
                <tr>
                    <td class="label">Reviewer Name</td>
                    <td class="value">{{ $submission->name }}</td>
                </tr>
                <tr>
                    <td class="label">Email Address</td>
                    <td class="value">
                        @if($submission->email)
                            <a href="mailto:{{ $submission->email }}" style="color: #0f172a; text-decoration: underline;">{{ $submission->email }}</a>
                        @else
                            <em style="color: #94a3b8;">Not provided</em>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="label">Rating Score</td>
                    <td class="value" style="font-weight: 700; color: #eab308;">{{ $submission->rating }} out of 5 Stars</td>
                </tr>
            </table>

            <div class="message-box">
                <div class="message-title">Review Message</div>
                <p class="message-body">{{ $submission->message }}</p>
            </div>
        </div>
        <div class="footer">
            <p>Sent automatically by Premium Expert Services API &bull; {{ date('Y-m-d H:i:s') }}</p>
        </div>
    </div>
</body>
</html>
