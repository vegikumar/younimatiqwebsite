<!-- Anti-Copying Script -->
<script defer>
    let obscureTimeout = null;

    function isAssignmentPage() {
        return document.body.classList.contains('assignment-paste-enabled');
    }

    function isEditableTarget(target) {
        return !!(target && target.closest && target.closest('input, textarea, select, [contenteditable="true"]'));
    }

    function allowClipboardShortcut(key, target) {
        return key === 'v';
    }

    const obscureContent = () => {
        document.body.style.filter = 'blur(20px)';
        document.body.style.opacity = '0';
        document.body.classList.add('content-protected-blurred');
    };
    
    const revealContent = () => {
        if (obscureTimeout) {
            clearTimeout(obscureTimeout);
            obscureTimeout = null;
        }
        document.body.style.filter = '';
        document.body.style.opacity = '';
        document.body.classList.remove('content-protected-blurred');
    };

    const forceObscureFor = (ms) => {
        obscureContent();
        if (obscureTimeout) clearTimeout(obscureTimeout);
        obscureTimeout = setTimeout(() => {
            obscureTimeout = null;
            if (!document.hidden) {
                revealContent();
            }
        }, ms);
    };

    document.addEventListener('contextmenu', event => event.preventDefault());
    document.addEventListener('keydown', event => {
        const key = event.key.toLowerCase();

        // Block Ctrl/Cmd + C, V, X, P, S (allow paste/copy/cut on assignments + form fields)
        if (event.ctrlKey || event.metaKey) {
            if (allowClipboardShortcut(key, event.target)) {
                return;
            }
            if (['c', 'v', 'p', 's', 'x'].includes(key)) {
                event.preventDefault();
            }
        }
        
        // PRE-EMPTIVE BLUR: Blur the screen the exact moment Win/Cmd + Shift are held down together, 
        // *before* they even press 'S' (Windows) or '3/4' (Mac). This beats the OS-level freeze frame!
        if (event.shiftKey && (event.metaKey || event.ctrlKey)) {
            forceObscureFor(2000);
        }
        
        // Block Mac/Windows Snipping tool shortcuts: Cmd/Win + Shift + 3, 4, 5, S
        if (event.shiftKey && (event.metaKey || event.ctrlKey)) {
            if (['3', '4', '5', 's'].includes(key)) {
                event.preventDefault();
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText('').catch(() => {});
                }
            }
        }
        
        // Block Win + PrtScn (usually metaKey + PrintScreen, though often swallowed by OS)
        if (event.metaKey && (key === 'printscreen' || event.key === 'PrintScreen')) {
            event.preventDefault();
            forceObscureFor(2000);
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText('').catch(() => {});
            }
        }
        
        // Prevent Print Screen
        if (key === 'printscreen' || event.key === 'PrintScreen') {
            event.preventDefault();
            forceObscureFor(2000);
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText('').catch(() => {});
            }
        }
        
        // Block Developer Tools Shortcuts (F12, Ctrl+Shift+I/J/C, Ctrl+U)
        if (event.key === 'F12') {
            event.preventDefault();
        }
        if (event.ctrlKey && event.shiftKey && ['i', 'j', 'c'].includes(key)) {
            event.preventDefault();
        }
        if ((event.ctrlKey || event.metaKey) && key === 'u') {
            event.preventDefault(); // Block View Source
        }
    });
    
    document.addEventListener('keyup', event => {
        if (event.key === 'PrintScreen' || event.key.toLowerCase() === 'printscreen') {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText('').catch(() => {});
            }
            forceObscureFor(2000);
        }
    });
    
    document.addEventListener('dragstart', event => event.preventDefault());

    // Block 3-finger swipe (common screenshot gesture on many Android devices)
    document.addEventListener('touchstart', event => {
        if (event.touches.length >= 3) {
            event.preventDefault();
            forceObscureFor(2000);
        }
    }, { passive: false });

    document.addEventListener('touchmove', event => {
        if (event.touches.length >= 3) {
            event.preventDefault();
        }
    }, { passive: false });

    window.addEventListener('blur', () => {
        if (isAssignmentPage() || isEditableTarget(document.activeElement)) {
            return;
        }
        obscureContent();
    });

    window.addEventListener('focus', revealContent);
    window.addEventListener('pageshow', revealContent);
    
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            obscureContent();
        } else {
            revealContent();
        }
    });

    // Fallback for returning to the tab/window reliably on user interaction
    const fallbackReveal = () => {
        if (!document.hidden) {
            revealContent();
        }
    };
    
    ['touchstart', 'pointerdown', 'mousedown', 'scroll', 'click'].forEach(evt => {
        document.addEventListener(evt, fallbackReveal, { passive: true });
    });
</script>
<style>
    body {
        -webkit-touch-callout: none; /* iOS Safari - Disable long-press popup */
        -webkit-user-select: none; /* Safari */
        -moz-user-select: none; /* Firefox */
        -ms-user-select: none; /* IE10+/Edge */
        user-select: none; /* Standard */
    }
    input, textarea, select, [contenteditable="true"], .ck, .ck-content, .ck-editor__editable, .ck-editor {
        -webkit-user-select: text;
        -moz-user-select: text;
        -ms-user-select: text;
        user-select: text;
    }
    body.assignment-paste-enabled input,
    body.assignment-paste-enabled textarea,
    body.assignment-paste-enabled select,
    body.assignment-paste-enabled [contenteditable="true"] {
        -webkit-user-select: text;
        -moz-user-select: text;
        -ms-user-select: text;
        user-select: text;
    }
</style>
