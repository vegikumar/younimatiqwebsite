<dialog id="secure-viewer-modal" class="m-auto p-0 rounded-2xl shadow-xl border-0 backdrop:bg-black/80 w-full max-w-4xl max-h-[90vh] bg-black">
    <div class="flex flex-col h-full max-h-[90vh]">
        <div class="p-4 border-b border-outline flex justify-between items-center text-white shrink-0">
            <h3 class="font-bold text-xl flex items-center gap-2" id="secure-viewer-title">Secure Viewer</h3>
            <button type="button" onclick="closeSecureViewer()" class="text-outline hover:text-white"><span class="material-symbols-outlined">close</span></button>
        </div>
        <div class="flex-1 relative flex items-center justify-center overflow-hidden" id="secure-viewer-content" oncontextmenu="return false;">
        </div>
    </div>
    <x-content-watermark :absolute="true" />
</dialog>

<script>
    function openSecureViewer(title, streamUrl, type) {
        document.getElementById('secure-viewer-title').textContent = title;
        const modal = document.getElementById('secure-viewer-modal');
        const contentContainer = document.getElementById('secure-viewer-content');
        
        // Reset container
        contentContainer.innerHTML = '';
        modal.showModal();

        if (type === 'video') {
            const video = document.createElement('video');
            video.src = streamUrl;
            video.controls = true;
            video.autoplay = true;
            video.controlsList = "nodownload"; 
            video.className = "w-full h-full max-h-[80vh] object-contain";
            video.oncontextmenu = () => false; 
            contentContainer.appendChild(video);
        } else {
            const iframe = document.createElement('iframe');
            iframe.src = streamUrl + '#toolbar=0';
            iframe.className = "w-full h-[80vh] bg-white border-0";
            contentContainer.appendChild(iframe);
        }
    }

    function closeSecureViewer() {
        const modal = document.getElementById('secure-viewer-modal');
        const contentContainer = document.getElementById('secure-viewer-content');
        contentContainer.innerHTML = '';
        modal.close();
    }
</script>
