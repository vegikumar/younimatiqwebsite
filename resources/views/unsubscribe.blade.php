<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unsubscribe · Younimatiq</title>
    <style>
        body { margin:0; font-family: Arial, Helvetica, sans-serif; background:#f3f4f6; color:#111827; }
        .wrap { max-width:480px; margin:48px auto; padding:0 16px; }
        .card { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:28px; }
        h1 { margin:0 0 8px; font-size:22px; }
        p { margin:0 0 16px; color:#4b5563; line-height:1.5; font-size:14px; }
        label { display:block; font-size:13px; font-weight:700; margin-bottom:6px; }
        input[type=email] { width:100%; box-sizing:border-box; padding:12px; border:1px solid #d1d5db; border-radius:8px; font-size:14px; }
        button { margin-top:14px; width:100%; border:0; background:#2563eb; color:#fff; font-weight:700; padding:12px; border-radius:8px; cursor:pointer; }
        .ok { background:#ecfdf5; color:#065f46; border:1px solid #a7f3d0; padding:12px; border-radius:8px; margin-bottom:16px; font-size:14px; }
        a { color:#2563eb; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            <h1>Unsubscribe</h1>
            <p>Stop receiving Younimatiq class digests and marketing emails. Class reminders for enrolled students are not affected.</p>

            @if(session('status') || !empty($already))
                <div class="ok">{{ session('status') ?: 'This email is already unsubscribed from marketing emails.' }}</div>
            @endif

            <form method="POST" action="{{ route('unsubscribe.store') }}">
                @csrf
                @if(!empty($signed) && !empty($email))
                    <input type="hidden" name="signed" value="1">
                    <input type="hidden" name="sig_email" value="{{ $email }}">
                @endif
                <label for="email">Email address</label>
                <input id="email" type="email" name="email" value="{{ old('email', $email ?? '') }}" required {{ !empty($signed) && !empty($email) ? 'readonly' : '' }}>
                @error('email')
                    <p style="color:#b91c1c;margin-top:8px;">{{ $message }}</p>
                @enderror
                <button type="submit">Unsubscribe</button>
            </form>

            <p style="margin-top:18px;margin-bottom:0;font-size:12px;color:#9ca3af;">
                Or email <a href="mailto:support@younimatiq.com?subject=Unsubscribe">support@younimatiq.com</a>
            </p>
        </div>
    </div>
</body>
</html>
