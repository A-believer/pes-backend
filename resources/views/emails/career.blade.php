<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>New Job Application</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; padding: 24px; margin: 0;">
    <div style="max-width: 600px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden;">
        <div style="background: linear-gradient(135deg, #0F172A, #1E293B); padding: 28px; text-align: left;">
            <span style="display: inline-block; background-color: #2563EB; color: #ffffff; padding: 4px 12px; border-radius: 9999px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">
                Careers Application
            </span>
            <h1 style="color: #ffffff; margin: 0; font-size: 22px; font-weight: 800;">New Candidate Application</h1>
            <p style="color: #94a3b8; margin: 6px 0 0 0; font-size: 14px;">Role: <strong style="color: #38BDF8;">{{ $submission->service }}</strong></p>
        </div>

        <div style="padding: 24px;">
            <table style="width: 100%; border-collapse: collapse; font-size: 14px; color: #334155;">
                <tr>
                    <td style="padding: 8px 0; font-weight: 700; width: 150px; color: #64748B;">Candidate Name:</td>
                    <td style="padding: 8px 0; font-weight: 700; color: #0F172A;">{{ $submission->name }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; font-weight: 700; color: #64748B;">Email Address:</td>
                    <td style="padding: 8px 0;"><a href="mailto:{{ $submission->email }}" style="color: #2563EB; font-weight: 600; text-decoration: none;">{{ $submission->email }}</a></td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; font-weight: 700; color: #64748B;">Telephone:</td>
                    <td style="padding: 8px 0;"><a href="tel:{{ $submission->phone }}" style="color: #0F172A; font-weight: 600; text-decoration: none;">{{ $submission->phone }}</a></td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; font-weight: 700; color: #64748B;">Location / Postcode:</td>
                    <td style="padding: 8px 0; color: #0F172A;">{{ $submission->postcode ?? 'Not provided' }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; font-weight: 700; color: #64748B;">Availability:</td>
                    <td style="padding: 8px 0; color: #2563EB; font-weight: 600;">{{ $submission->availability ?? 'Flexible' }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; font-weight: 700; color: #64748B;">Experience:</td>
                    <td style="padding: 8px 0; color: #0F172A;">{{ $submission->experience_level ?? 'Not specified' }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; font-weight: 700; color: #64748B;">Right to Work UK:</td>
                    <td style="padding: 8px 0; font-weight: 700; color: {{ $submission->has_right_to_work ? '#16A34A' : '#DC2626' }};">
                        {{ $submission->has_right_to_work ? '✓ Confirmed' : '✗ Not Confirmed' }}
                    </td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; font-weight: 700; color: #64748B;">Driving Licence:</td>
                    <td style="padding: 8px 0; color: #0F172A;">{{ $submission->has_driving_licence ? 'Yes' : 'No' }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; font-weight: 700; color: #64748B;">Attached CV:</td>
                    <td style="padding: 8px 0; color: #2563EB; font-weight: 600;">
                        {{ $submission->cv_original_name ? '📎 ' . $submission->cv_original_name : 'No file attached' }}
                    </td>
                </tr>
            </table>

            @if($submission->message)
            <div style="margin-top: 20px; padding: 16px; background: #f8fafc; border-radius: 12px; border: 1px solid #e2e8f0;">
                <div style="font-size: 12px; font-weight: 800; color: #64748B; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">Notes / Experience:</div>
                <div style="font-size: 14px; color: #334155; line-height: 1.6; white-space: pre-wrap;">{{ $submission->message }}</div>
            </div>
            @endif
        </div>

        <div style="background-color: #f1f5f9; padding: 16px; text-align: center; font-size: 12px; color: #94a3b8; border-top: 1px solid #e2e8f0;">
            Sent automatically from Premium Expert Services Management System
        </div>
    </div>
</body>
</html>
