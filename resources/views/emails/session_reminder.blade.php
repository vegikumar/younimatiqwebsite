<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Session Reminder</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f7f6; padding: 20px; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05);">
        <h2 style="color: #003d86; margin-top: 0;">
            {{ ($kind ?? 'class') === 'trial' ? 'Trial class reminder' : 'Class reminder' }}
        </h2>
        <p style="font-size: 16px;">Hello {{ $recipientName }},</p>

        <p style="font-size: 16px;">
            This is a quick reminder that your upcoming
            <strong>{{ ($kind ?? 'class') === 'trial' ? 'trial class' : 'class' }}</strong>
            <strong>{{ $sessionName }}</strong>
            starts in <strong>{{ $timeString }}</strong>.
        </p>

        @if(!empty($whenLabel))
            <p style="font-size: 15px; background: #f0f6ff; border-radius: 8px; padding: 12px 16px;">
                <strong>When:</strong> {{ $whenLabel }}
            </p>
        @endif

        @if($joinUrl)
            @if(!$isTutor)
                <p style="font-size: 14px; color: #1e293b; background: #f0f7ff; border-left: 4px solid #046bd2; padding: 10px 14px; border-radius: 4px; margin: 15px 0;">
                    <strong>Important:</strong> Please ensure the student joins the class meeting using their <strong>full name ({{ $recipientName }})</strong> so the tutor can quickly identify and admit them from the waiting room.
                </p>
            @endif
            <div style="margin: 30px 0; text-align: center;">
                <a href="{{ $joinUrl }}" style="background-color: #1e5200; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 16px; display: inline-block;">
                    {{ $isTutor ? 'Start Session' : 'Join Session' }}
                </a>
            </div>
            <p style="font-size: 14px; color: #666; margin-top: 20px;">If the button above does not work, copy and paste this link into your browser:<br>
            <a href="{{ $joinUrl }}" style="color: #003d86; word-break: break-all;">{{ $joinUrl }}</a></p>
        @else
            <p style="font-size: 16px; margin: 30px 0; color: #555;"><em>The link to join will be provided shortly. Check your dashboard closer to class time.</em></p>
        @endif

        @if(!$isTutor && !empty($cantAttendUrl))
            <div style="margin: 28px 0 8px; text-align: center;">
                <a href="{{ $cantAttendUrl }}" style="background-color: #ffffff; color: #003d86; padding: 12px 22px; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 15px; display: inline-block; border: 2px solid #003d86;">
                    Can&apos;t join? Leave a note for support
                </a>
            </div>
            <p style="font-size: 13px; color: #666; text-align: center; margin: 0 0 8px;">
                Tell us if you need to reschedule — your note goes to support@younimatiq.com.
            </p>
        @endif

        <hr style="border: 0; border-top: 1px solid #eeeeee; margin: 30px 0;">
        <p style="font-size: 14px; color: #888; text-align: center; margin-bottom: 0;">&copy; {{ date('Y') }} Younimatiq. All rights reserved.</p>
    </div>
</body>
</html>
