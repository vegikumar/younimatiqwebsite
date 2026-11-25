@props(['assignment', 'submission'])

@php
    $questions = collect($assignment->questions ?? [])->keyBy('id');
    $answers = $submission->answers ?? [];
    $content = trim((string) ($submission->content ?? ''));
    $contentIsUrl = $content !== '' && filter_var($content, FILTER_VALIDATE_URL);
    $filePath = $submission->file_path ?? null;
    $fileUrl = $filePath ? route('secure.asset', ['path' => $filePath]) : null;
    $fileExt = $filePath ? strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) : null;
    $fileIsImage = in_array($fileExt, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
    $fileIsPdf = $fileExt === 'pdf';
@endphp

<div class="mt-3 space-y-4">
    {{-- Legacy text / URL submission --}}
    @if($content !== '')
        <div class="p-3 bg-white rounded-lg border border-outline-variant/30">
            <p class="text-xs font-bold text-on-surface-variant uppercase mb-2">Submitted text / link</p>
            @if($contentIsUrl)
                <a href="{{ $content }}" target="_blank" rel="noopener noreferrer"
                   class="text-primary font-bold text-sm break-all hover:underline inline-flex items-start gap-1">
                    <span class="material-symbols-outlined text-[18px] shrink-0 mt-0.5">link</span>
                    {{ $content }}
                </a>
                <div class="mt-3 flex flex-wrap gap-2">
                    <button type="button"
                            onclick="openSubmissionPreview(@js('Student Project'), @js($content), @js('iframe'))"
                            class="btn-secondary btn-sm !px-3 text-xs font-bold inline-flex items-center gap-1">
                        <span class="material-symbols-outlined text-[16px]">visibility</span>
                        Preview
                    </button>
                    <a href="{{ $content }}" target="_blank" rel="noopener noreferrer"
                       class="btn-primary btn-sm !px-3 text-xs font-bold inline-flex items-center gap-1">
                        <span class="material-symbols-outlined text-[16px]">open_in_new</span>
                        Open Link
                    </a>
                </div>
            @else
                <p class="text-sm whitespace-pre-wrap">{{ $content }}</p>
            @endif
        </div>
    @endif

    {{-- Legacy file submission --}}
    @if($fileUrl)
        <div class="p-3 bg-white rounded-lg border border-outline-variant/30">
            <p class="text-xs font-bold text-on-surface-variant uppercase mb-2">Submitted file</p>
            @if($fileIsImage)
                <button type="button" class="block text-left w-full"
                        onclick="openSubmissionPreview(@js('Submitted image'), @js($fileUrl), @js('image'))">
                    <img src="{{ $fileUrl }}" alt="Submitted work"
                         class="max-h-56 rounded-lg border object-contain mt-1 bg-surface-container-low cursor-zoom-in">
                </button>
            @elseif($fileIsPdf)
                <div class="rounded-lg border overflow-hidden bg-surface-container-low mt-1">
                    <iframe src="{{ $fileUrl }}#toolbar=0" class="w-full h-64 border-0" title="Submitted PDF preview"></iframe>
                </div>
            @endif
            <div class="mt-3 flex flex-wrap gap-2">
                <button type="button"
                        onclick="openSubmissionPreview(@js('Submitted work'), @js($fileUrl), @js($fileIsImage ? 'image' : ($fileIsPdf ? 'pdf' : 'iframe')))"
                        class="btn-secondary btn-sm !px-3 text-xs font-bold inline-flex items-center gap-1">
                    <span class="material-symbols-outlined text-[16px]">visibility</span>
                    Preview
                </button>
            </div>
        </div>
    @endif

    {{-- Question-based answers --}}
    @if(!empty($answers))
        @foreach($questions as $qId => $q)
            @php $ans = $answers[$qId] ?? null; @endphp
            @if(!$ans) @continue @endif
            <div class="p-3 bg-white rounded-lg border border-outline-variant/30">
                <p class="text-xs font-bold text-on-surface-variant uppercase mb-1">{{ $q['question'] ?? 'Question' }}</p>

                @if(($ans['type'] ?? '') === 'mcq')
                    @php
                        $optIndex = (int) ($ans['value'] ?? -1);
                        $optText = $q['options'][$optIndex]['text'] ?? '—';
                        $isCorrect = $ans['correct'] ?? null;
                    @endphp
                    <p class="text-sm">{{ $optText }}</p>
                    @if($isCorrect !== null)
                        <span class="inline-block mt-1 text-xs font-bold px-2 py-0.5 rounded-full {{ $isCorrect ? 'bg-tertiary-fixed text-tertiary' : 'bg-error-container text-error' }}">
                            {{ $isCorrect ? 'Correct' : 'Incorrect' }}
                        </span>
                    @endif

                @elseif(($ans['type'] ?? '') === 'short_answer')
                    @php
                        $shortValue = trim((string) ($ans['value'] ?? ''));
                        $shortIsUrl = $shortValue !== '' && filter_var($shortValue, FILTER_VALIDATE_URL);
                    @endphp
                    @if($shortIsUrl)
                        <a href="{{ $shortValue }}" target="_blank" rel="noopener noreferrer" class="text-primary font-bold text-sm break-all hover:underline">{{ $shortValue }}</a>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <button type="button"
                                    onclick="openSubmissionPreview(@js('Answer link'), @js($shortValue), @js('iframe'))"
                                    class="btn-secondary btn-sm !px-3 text-xs font-bold inline-flex items-center gap-1">
                                <span class="material-symbols-outlined text-[16px]">visibility</span>
                                Preview
                            </button>
                            <a href="{{ $shortValue }}" target="_blank" rel="noopener noreferrer" class="btn-primary btn-sm !px-3 text-xs font-bold inline-flex items-center gap-1">
                                <span class="material-symbols-outlined text-[16px]">open_in_new</span>
                                Open Link
                            </a>
                        </div>
                    @else
                        <p class="text-sm font-mono whitespace-pre-wrap">{{ $shortValue !== '' ? $shortValue : '—' }}</p>
                    @endif
                    @if(isset($ans['correct']))
                        <span class="inline-block mt-1 text-xs font-bold px-2 py-0.5 rounded-full {{ $ans['correct'] ? 'bg-tertiary-fixed text-tertiary' : 'bg-error-container text-error' }}">
                            {{ $ans['correct'] ? 'Correct' : 'Incorrect' }}
                        </span>
                    @endif

                @elseif(in_array(($ans['type'] ?? ''), ['draw', 'image_upload'], true) && !empty($ans['image_path']))
                    @php $imgUrl = route('secure.asset', ['path' => $ans['image_path']]); @endphp
                    <button type="button" class="block text-left"
                            onclick="openSubmissionPreview(@js(($ans['type'] ?? '') === 'draw' ? 'Drawing answer' : 'Uploaded image'), @js($imgUrl), @js('image'))">
                        <img src="{{ $imgUrl }}"
                             alt="{{ ($ans['type'] ?? '') === 'draw' ? 'Drawing answer' : 'Uploaded answer' }}"
                             class="max-h-56 rounded-lg border object-contain mt-1 bg-surface-container-low cursor-zoom-in">
                    </button>
                    @if(!empty($ans['original_name']))
                        <p class="text-xs text-on-surface-variant mt-1">{{ $ans['original_name'] }}</p>
                    @endif
                    <div class="mt-2 flex flex-wrap gap-2">
                        <button type="button"
                                onclick="openSubmissionPreview(@js(($ans['type'] ?? '') === 'draw' ? 'Drawing answer' : 'Uploaded image'), @js($imgUrl), @js('image'))"
                                class="btn-secondary btn-sm !px-3 text-xs font-bold inline-flex items-center gap-1">
                            <span class="material-symbols-outlined text-[16px]">visibility</span>
                            Preview
                        </button>
                    </div>
                    <p class="text-xs {{ ($ans['type'] ?? '') === 'draw' ? 'text-warning' : 'text-on-secondary-container' }} mt-2 font-medium">
                        {{ ($ans['type'] ?? '') === 'draw' ? 'Drawing — review manually' : 'Image upload — review manually' }}
                    </p>
                @endif
            </div>
        @endforeach
    @endif
</div>
