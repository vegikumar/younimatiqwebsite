<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $mailSubject ?? 'Younimatiq Notification' }}</title>
    <!--[if mso]>
    <noscript>
    <xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml>
    </noscript>
    <![endif]-->
</head>
<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Arial, sans-serif; background-color: #f4f6f9; color: #1e293b; line-height: 1.6; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%;">

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color: #f4f6f9;">
        <tr>
            <td style="padding: 40px 15px;" align="center">

                <table role="presentation" width="640" cellspacing="0" cellpadding="0" border="0" style="max-width: 640px; width: 100%; background-color: #ffffff; border-radius: 16px; overflow: hidden; border: 1px solid #e2e8f0;">

                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #046bd2 0%, #0057bf 100%); padding: 30px 40px; text-align: center;">
                            <a href="{{ rtrim(config('app.url'), '/') }}" target="_blank" style="text-decoration: none; display: inline-block;">
                                <img src="{{ asset('logo.webp') }}" alt="Younimatiq" width="180" style="height: auto; max-width: 180px; display: block; margin: 0 auto;" />
                            </a>
                            <p style="color: rgba(255,255,255,0.9); font-size: 12px; font-weight: 600; margin: 10px 0 0 0; text-transform: uppercase; letter-spacing: 1px;">
                                Turn Screen Time to Skill Time
                            </p>
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td style="padding: 40px; background-color: #ffffff; font-size: 15px; color: #1e293b; line-height: 1.7;">
                            {!! $bodyContent !!}
                        </td>
                    </tr>

                    <!-- Divider -->
                    <tr>
                        <td style="padding: 0 40px;">
                            <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 0;">
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #0f172a; color: #94a3b8; padding: 30px 40px; font-size: 13px; border-top: 3px solid #046bd2;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td style="padding-bottom: 16px; text-align: center;">
                                        <p style="color: #ffffff; font-weight: 700; font-size: 14px; margin: 0 0 10px 0;">Need Support or Have Questions?</p>
                                        <p style="margin: 0 0 6px 0; color: #cbd5e1; font-size: 13px;">
                                            &#128222; US: <a href="tel:+12027944843" style="color: #60a5fa; text-decoration: none;">+1 (202) 794 4843</a>
                                            &nbsp;&bull;&nbsp;
                                            &#128222; AU: <a href="tel:+61470214835" style="color: #60a5fa; text-decoration: none;">+61 470 214 835</a>
                                        </p>
                                        <p style="margin: 0; color: #cbd5e1; font-size: 13px;">
                                            &#9993; <a href="mailto:support@younimatiq.com" style="color: #60a5fa; text-decoration: none;">support@younimatiq.com</a>
                                            &nbsp;&bull;&nbsp;
                                            <a href="https://wa.me/12027944843" target="_blank" style="color: #34d399; text-decoration: none;">WhatsApp Us</a>
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="border-top: 1px solid #1e293b; padding-top: 16px; text-align: center; font-size: 12px; color: #64748b;">
                                        <p style="margin: 0 0 4px 0;">
                                            &copy; {{ date('Y') }} <strong style="color: #94a3b8;">Younimatiq</strong>. All rights reserved.
                                        </p>
                                        <p style="margin: 0 0 4px 0;">
                                            Empowering young minds with AI, Coding, and Future-Ready Skills worldwide.
                                        </p>
                                        <p style="margin: 0; font-size: 11px;">
                                            <a href="{{ rtrim(config('app.url'), '/') }}/page/privacy-policy" style="color: #475569; text-decoration: none;">Privacy Policy</a>
                                            &nbsp;&bull;&nbsp;
                                            <a href="mailto:support@younimatiq.com?subject=Unsubscribe" style="color: #475569; text-decoration: none;">Unsubscribe</a>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>
