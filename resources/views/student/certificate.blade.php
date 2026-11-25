<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Certificate - {{ $course->title }}</title>
    {{-- mautic tracking disabled: emails.younimatiq.com has no DNS --}}
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Georgia, serif; background: #f0f4f8; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 2rem; }
        .certificate { width: 900px; max-width: 100%; background: white; border: 12px solid #046bd2; padding: 60px; text-align: center; position: relative; }
        .certificate::before { content: ''; position: absolute; inset: 8px; border: 2px solid #d9f99d; pointer-events: none; }
        .logo { font-size: 2rem; font-weight: bold; color: #046bd2; margin-bottom: 1rem; letter-spacing: 2px; }
        h1 { font-size: 2.5rem; color: #1e293b; margin: 1.5rem 0; }
        .subtitle { color: #64748b; font-size: 1.1rem; margin-bottom: 2rem; }
        .name { font-size: 2.2rem; color: #046bd2; border-bottom: 2px solid #d9f99d; display: inline-block; padding: 0 2rem 0.5rem; margin: 1rem 0 2rem; }
        .course { font-size: 1.4rem; color: #334155; margin-bottom: 1rem; }
        .meta { display: flex; justify-content: space-around; margin-top: 3rem; color: #64748b; font-size: 0.95rem; }
        .print-btn { position: fixed; top: 1rem; right: 1rem; background: #046bd2; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 999px; cursor: pointer; font-weight: bold; }
        @media print { .print-btn { display: none; } body { background: white; } }
    </style>
</head>
<body>
    <button class="print-btn" onclick="window.print()">Print / Save PDF</button>
    <div class="certificate">
        <div class="logo">Younimatiq</div>
        <p class="subtitle">Certificate of Completion</p>
        <h1>This certifies that</h1>
        <div class="name">{{ $user->name }}</div>
        <p class="course">has successfully completed the course</p>
        <p class="course" style="font-weight: bold; color: #046bd2;">{{ $course->title }}</p>
        @if($course->tutor)
        <p class="subtitle" style="margin-top: 1rem;">Instructor: {{ $course->tutor->name }}</p>
        @endif
        <div class="meta">
            <span>Completed: {{ $enrollment->completed_at?->format('F j, Y') ?? now()->format('F j, Y') }}</span>
            <span>Certificate ID: CERT-{{ str_pad($enrollment->id, 6, '0', STR_PAD_LEFT) }}</span>
        </div>
    </div>
    @include('partials.anti_copy')
    @include('partials.content-protection')
</body>
</html>
