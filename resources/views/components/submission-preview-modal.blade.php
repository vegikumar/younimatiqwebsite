<dialog id="submission-preview-modal" class="m-auto p-0 rounded-2xl shadow-xl border-0 backdrop:bg-black/70 w-[min(960px,95vw)] max-h-[92vh] bg-white overflow-hidden">
    <div class="flex flex-col max-h-[92vh]">
        <div class="px-4 py-3 border-b border-outline-variant flex items-center justify-between gap-3 bg-surface-container-low shrink-0">
            <h3 class="font-bold text-base text-on-surface truncate" id="submission-preview-title">Preview</h3>
            <div class="flex items-center gap-2 shrink-0">
                <a id="submission-preview-open" href="#" target="_blank" rel="noopener noreferrer"
                   class="text-xs font-bold text-primary hover:underline inline-flex items-center gap-1">
                    <span class="material-symbols-outlined text-[16px]">open_in_new</span>
                    Open
                </a>
                <button type="button" onclick="closeSubmissionPreview()" class="p-1.5 rounded-lg hover:bg-surface-container text-on-surface" aria-label="Close preview">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
        </div>
        <div id="submission-preview-content" class="flex-1 min-h-[50vh] bg-surface-container-low flex items-center justify-center overflow-auto p-4"></div>
    </div>
</dialog>

<script>
    function openSubmissionPreview(title, url, type) {
        const modal = document.getElementById('submission-preview-modal');
        const content = document.getElementById('submission-preview-content');
        const titleEl = document.getElementById('submission-preview-title');
        const openLink = document.getElementById('submission-preview-open');
        if (!modal || !content) return;

        titleEl.textContent = title || 'Preview';
        openLink.href = url;
        content.innerHTML = '';

        if (type === 'image') {
            const img = document.createElement('img');
            img.src = url;
            img.alt = title || 'Preview';
            img.className = 'max-w-full max-h-[75vh] object-contain rounded-lg shadow bg-white';
            content.appendChild(img);
        } else {
            const iframe = document.createElement('iframe');
            iframe.src = type === 'pdf' ? (url + (url.includes('#') ? '' : '#toolbar=1')) : url;
            iframe.className = 'w-full h-[75vh] rounded-lg border-0 bg-white shadow';
            iframe.title = title || 'Preview';
            content.appendChild(iframe);
        }

        if (typeof modal.showModal === 'function') {
            modal.showModal();
        } else {
            modal.setAttribute('open', 'open');
        }
    }

    function closeSubmissionPreview() {
        const modal = document.getElementById('submission-preview-modal');
        const content = document.getElementById('submission-preview-content');
        if (content) content.innerHTML = '';
        if (!modal) return;
        if (typeof modal.close === 'function') {
            modal.close();
        } else {
            modal.removeAttribute('open');
        }
    }

    document.getElementById('submission-preview-modal')?.addEventListener('click', function (e) {
        if (e.target === this) closeSubmissionPreview();
    });
</script>
