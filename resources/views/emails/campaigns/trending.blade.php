<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $subject }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#111827;-webkit-text-size-adjust:100%;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#f3f4f6;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="560" cellspacing="0" cellpadding="0" border="0" style="max-width:560px;width:100%;background-color:#ffffff;border:1px solid #e5e7eb;border-radius:4px;overflow:hidden;">
                    <tr>
                        <td style="height:4px;line-height:4px;font-size:0;background-color:#2563eb;">&nbsp;</td>
                    </tr>
                    <tr>
                        <td style="padding:28px 32px 8px 32px;text-align:center;">
                            <a href="{{ $siteUrl }}" target="_blank" style="text-decoration:none;display:inline-block;">
                                <img src="{{ $logoUrl }}" alt="Younimatiq" width="140" style="height:auto;max-width:140px;display:block;margin:0 auto 8px auto;border:0;">
                            </a>
                            <p style="margin:0;font-size:15px;font-weight:700;color:#111827;letter-spacing:0.2px;">Younimatiq</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:12px 32px 0 32px;">
                            <h1 style="margin:0 0 18px 0;font-size:26px;line-height:1.25;font-weight:700;color:#111827;">{{ $heading }}</h1>
                            <p style="margin:0 0 14px 0;font-size:15px;line-height:1.5;color:#111827;">Hi {{ $recipientName }},</p>
                            <p style="margin:0 0 18px 0;font-size:15px;line-height:1.5;color:#374151;">{{ $introText }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 32px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                @foreach($courses as $course)
                                    <tr>
                                        <td style="padding:0 0 16px 18px;">
                                            <p style="margin:0 0 2px 0;font-size:15px;line-height:1.4;">
                                                <span style="color:#111827;font-size:16px;line-height:1;">&bull;&nbsp;</span>
                                                <a href="{{ $course['url'] }}" target="_blank" style="color:#2563eb;text-decoration:underline;font-weight:600;">{{ $course['title'] }}</a>
                                            </p>
                                            <p style="margin:0;padding-left:14px;font-size:13px;line-height:1.4;color:#6b7280;">{{ $course['age_label'] }}</p>
                                        </td>
                                    </tr>
                                @endforeach
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:8px 32px 28px 32px;">
                            <a href="{{ $ctaUrl }}" target="_blank" style="display:inline-block;background-color:#2563eb;color:#ffffff;text-decoration:none;font-size:15px;font-weight:700;padding:12px 28px;border-radius:8px;">
                                {{ $ctaText }}
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 32px 28px 32px;">
                            <p style="margin:0 0 4px 0;font-size:15px;line-height:1.5;color:#111827;">Warm regards,</p>
                            <p style="margin:0;font-size:15px;line-height:1.5;font-weight:700;color:#111827;">{{ $signOffName }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 32px 28px 32px;border-top:1px solid #f3f4f6;">
                            <p style="margin:18px 0 0 0;font-size:12px;line-height:1.5;color:#9ca3af;text-align:center;">
                                Questions? Email us at
                                <a href="mailto:{{ $supportEmail }}" style="color:#6b7280;text-decoration:underline;">{{ $supportEmail }}</a>
                            </p>
                            <p style="margin:8px 0 0 0;font-size:11px;line-height:1.4;color:#9ca3af;text-align:center;">
                                <a href="{{ $unsubscribeUrl }}" style="color:#9ca3af;text-decoration:underline;">Unsubscribe</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
