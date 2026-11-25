<style>
    body {
        -webkit-touch-callout: none;
        -webkit-user-select: none;
        -khtml-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
        user-select: none;
        -webkit-tap-highlight-color: transparent;
    }
    input, textarea, select, [contenteditable="true"], .ck, .ck-content, .ck-editor__editable, .ck-editor {
        -webkit-user-select: auto;
        user-select: auto;
    }
    input:-webkit-autofill,
    input:-webkit-autofill:hover,
    input:-webkit-autofill:focus,
    textarea:-webkit-autofill,
    select:-webkit-autofill {
        -webkit-text-fill-color: #111827;
        caret-color: #111827;
        box-shadow: 0 0 0 1000px #fff inset;
        transition: background-color 99999s ease-in-out 0s;
    }
    footer input:-webkit-autofill,
    footer input:-webkit-autofill:hover,
    footer input:-webkit-autofill:focus {
        -webkit-text-fill-color: #fff;
        caret-color: #fff;
        box-shadow: 0 0 0 1000px rgba(255, 255, 255, 0.12) inset;
    }
    img, video, picture, svg image {
        -webkit-user-drag: none;
        -khtml-user-drag: none;
        -moz-user-drag: none;
        -o-user-drag: none;
        user-drag: none;
        pointer-events: auto;
    }
    body.content-protected-blurred main,
    body.content-protected-blurred footer,
    body.content-protected-blurred header,
    body.content-protected-blurred .lead-modal-panel,
    body.content-protected-blurred video,
    body.content-protected-blurred img {
        filter: blur(22px) brightness(0.85);
        -webkit-filter: blur(22px) brightness(0.85);
    }
    #content-protection-blur {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 2147483646;
        backdrop-filter: blur(28px);
        -webkit-backdrop-filter: blur(28px);
        background: rgba(15, 23, 42, 0.72);
        pointer-events: all;
        touch-action: none;
    }
    .younimatiq-watermark {
        position: fixed;
        inset: 0;
        z-index: 30;
        pointer-events: none;
        background-repeat: repeat;
        background-position: 0 0;
        -webkit-user-select: none;
        user-select: none;
        -webkit-user-drag: none;
    }
    .younimatiq-watermark.is-absolute {
        position: absolute;
        z-index: 20;
    }
    body.content-protected-blurred #content-protection-blur {
        display: block;
    }
    @media print {
        body * {
            visibility: hidden !important;
        }
        body::before {
            content: "Printing is disabled.";
            visibility: visible !important;
            display: block;
            position: fixed;
            inset: 0;
            padding: 2rem;
            font: 600 18px system-ui, sans-serif;
        }
    }
</style>
<div id="content-protection-blur" aria-hidden="true"></div>
<script defer>
(function () {
    var contentBlurTimeout = null;

    function isAssignmentPage() {
        return document.body.classList.contains('assignment-paste-enabled');
    }

    function isEditableTarget(target) {
        if (!target || !target.closest) {
            return false;
        }
        return !!target.closest('input, textarea, select, [contenteditable="true"], form, #leadModal');
    }

    function allowClipboardAction(target) {
        return isAssignmentPage() || isEditableTarget(target);
    }

    function isFormInteractionActive() {
        if (document.body.classList.contains('modal-open')) {
            return true;
        }
        var leadModal = document.getElementById('leadModal');
        if (leadModal && leadModal.classList.contains('open')) {
            return true;
        }
        return isEditableTarget(document.activeElement);
    }

    function setContentBlur(active, force) {
        if (active && !force && isFormInteractionActive()) {
            return;
        }
        document.body.classList.toggle('content-protected-blurred', active);
    }

    function clearPendingContentBlur() {
        if (contentBlurTimeout) {
            clearTimeout(contentBlurTimeout);
            contentBlurTimeout = null;
        }
    }

    function flashProtection() {
        document.body.classList.add('content-protected-blurred');
        clearPendingContentBlur();
        contentBlurTimeout = setTimeout(function () {
            contentBlurTimeout = null;
            if (document.visibilityState === 'visible') {
                setContentBlur(false);
            }
        }, 1200);
    }

    document.addEventListener('contextmenu', function (event) {
        event.preventDefault();
    });

    document.addEventListener('selectstart', function (event) {
        if (!allowClipboardAction(event.target)) {
            event.preventDefault();
        }
    });

    document.addEventListener('copy', function (event) {
        event.preventDefault();
    });

    document.addEventListener('cut', function (event) {
        event.preventDefault();
    });

    document.addEventListener('dragstart', function (event) {
        if (event.target && (event.target.tagName === 'IMG' || event.target.tagName === 'VIDEO')) {
            event.preventDefault();
        }
    });

    document.addEventListener('keydown', function (e) {
        var key = e.key || '';
        var lower = key.toLowerCase();

        if (key === 'F12' || lower === 'printscreen' || key === 'Snapshot') {
            e.preventDefault();
            flashProtection();
        }

        if (e.ctrlKey && e.shiftKey && ['i', 'j', 'c'].includes(lower)) {
            e.preventDefault();
            flashProtection();
        }

        if (e.shiftKey && (e.metaKey || e.ctrlKey)) {
            flashProtection();
        }

        if (e.metaKey && e.shiftKey && ['3', '4', '5', 's'].includes(lower)) {
            e.preventDefault();
            flashProtection();
        }

        if ((e.ctrlKey || e.metaKey) && ['u', 's', 'p'].includes(lower)) {
            if (!isEditableTarget(document.activeElement)) {
                e.preventDefault();
            }
        }

        if (e.altKey && lower === 'printscreen') {
            e.preventDefault();
            flashProtection();
        }

        if (lower === 'printscreen' && navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText('').catch(function () {});
        }
    });

    document.addEventListener('keyup', function (e) {
        var key = (e.key || '').toLowerCase();
        if ((key === 'printscreen' || key === 'snapshot') && navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText('').catch(function () {});
            flashProtection();
        }
    });

    function handleActiveState() {
        clearPendingContentBlur();
        if (document.visibilityState === 'visible') {
            setContentBlur(false);
        }
    }

    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'hidden') {
            clearPendingContentBlur();
            document.body.classList.add('content-protected-blurred');
        } else {
            handleActiveState();
        }
    });

    window.addEventListener('pagehide', function () {
        document.body.classList.add('content-protected-blurred');
    });

    window.addEventListener('blur', function () {
        clearPendingContentBlur();
        setContentBlur(true);
    });

    window.addEventListener('focus', handleActiveState);
    window.addEventListener('pageshow', handleActiveState);

    document.addEventListener('focusin', function (event) {
        if (isEditableTarget(event.target)) {
            handleActiveState();
        }
    });

    ['pointerdown', 'mousedown', 'touchstart', 'scroll', 'click'].forEach(function (evt) {
        document.addEventListener(evt, function () {
            if (document.visibilityState === 'visible' && !contentBlurTimeout) {
                setContentBlur(false);
            }
        }, { passive: true });
    });

    window.addEventListener('beforeprint', function () {
        document.body.classList.add('content-protected-blurred');
    });

    // Block Screen Recording via Web APIs (getDisplayMedia / captureStream)
    try {
        if (navigator.mediaDevices && typeof navigator.mediaDevices.getDisplayMedia === 'function') {
            navigator.mediaDevices.getDisplayMedia = function () {
                flashProtection();
                return Promise.reject(new DOMException('Screen recording is not permitted on this page.', 'NotAllowedError'));
            };
        }
    } catch (_) {}

    try {
        if (window.HTMLMediaElement && HTMLMediaElement.prototype.captureStream) {
            HTMLMediaElement.prototype.captureStream = function () {
                flashProtection();
                throw new DOMException('Media stream recording is not permitted.', 'SecurityError');
            };
        }
        if (window.HTMLCanvasElement && HTMLCanvasElement.prototype.captureStream) {
            HTMLCanvasElement.prototype.captureStream = function () {
                flashProtection();
                throw new DOMException('Canvas recording is not permitted.', 'SecurityError');
            };
        }
    } catch (_) {}

    function protectMediaElement(el) {
        if (!el) return;
        if (el.tagName === 'VIDEO') {
            el.setAttribute('controlsList', 'nodownload noplaybackrate nofullscreen');
            el.setAttribute('disablePictureInPicture', 'true');
            el.setAttribute('disableRemotePlayback', 'true');
            el.setAttribute('data-clarity-mask', 'true');
            el.oncontextmenu = function () { return false; };
            el.ondragstart = function () { return false; };
        } else if (el.tagName === 'IMG') {
            el.setAttribute('draggable', 'false');
            el.setAttribute('data-clarity-mask', 'true');
            el.oncontextmenu = function () { return false; };
            el.ondragstart = function () { return false; };
        }
    }

    // Defer media hardening + MutationObserver until after load: they are not
    // needed for first paint and the full-subtree observer is costly on the
    // large home page. Event-level protection above stays immediate.
    function protectAllMedia() {
        document.querySelectorAll('video, img').forEach(protectMediaElement);
    }

    function startMediaProtection() {
        protectAllMedia();

        // Protect dynamically added videos (e.g. lazy loaded or marquee clones)
        try {
            var mediaObserver = new MutationObserver(function (mutations) {
                mutations.forEach(function (m) {
                    m.addedNodes.forEach(function (node) {
                        if (node.nodeType === 1) {
                            protectMediaElement(node);
                            if (node.querySelectorAll) {
                                node.querySelectorAll('video, img').forEach(protectMediaElement);
                            }
                        }
                    });
                });
            });
            mediaObserver.observe(document.documentElement, { childList: true, subtree: true });
        } catch (_) {}
    }

    if (document.readyState === 'complete') {
        startMediaProtection();
    } else {
        window.addEventListener('load', startMediaProtection, { once: true });
    }
})();
</script>
