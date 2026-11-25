<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $subject }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f6fb;font-family:Nunito,Arial,sans-serif;color:#1f1635;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f6fb;padding:24px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" style="max-width:640px;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #e8e4f3;">
                <tr>
                    <td style="padding:24px 28px 8px;text-align:left;">
                        @if(!empty($logoUrl))
                            <img src="{{ $logoUrl }}" alt="Younimatiq" height="40" style="display:block;margin-bottom:16px;">
                        @endif
                        <p style="margin:0 0 8px;font-size:14px;color:#64748b;">Hi {{ $recipientName }},</p>
                        <div style="font-size:15px;line-height:1.6;color:#1f1635;">
                            {!! $bodyHtml !!}
                        </div>
                        <p style="margin:28px 0 0;font-size:14px;color:#64748b;">
                            — {{ $signOffName }}
                        </p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:18px 28px 24px;border-top:1px solid #f1f5f9;font-size:12px;color:#94a3b8;">
                        <a href="{{ $siteUrl }}" style="color:#7130D8;text-decoration:none;">younimatiq.com</a>
                        · <a href="mailto:{{ $supportEmail }}" style="color:#7130D8;text-decoration:none;">{{ $supportEmail }}</a>
                        @if(!empty($unsubscribeUrl))
                            · <a href="{{ $unsubscribeUrl }}" style="color:#94a3b8;">Unsubscribe</a>
                        @endif
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
