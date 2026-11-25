@props(['enrollment', 'course'])

@if($enrollment?->hasUploadedCertificate())
    <a href="{{ route('student.courses.certificate.download', $course) }}"
       class="inline-flex items-center gap-1.5 btn-secondary btn-sm !px-4 text-xs font-bold">
        <span class="material-symbols-outlined text-[16px]">download</span>
        Download Certificate
    </a>
@elseif(($enrollment->status ?? null) === 'completed')
    <a href="{{ route('student.courses.certificate', $course) }}"
       target="_blank"
       class="inline-flex items-center gap-1.5 btn-secondary btn-sm !px-4 text-xs font-bold">
        <span class="material-symbols-outlined text-[16px]">workspace_premium</span>
        View Certificate
    </a>
@endif
