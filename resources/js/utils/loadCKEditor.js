export function loadCKEditor() {
    if (window.ClassicEditor) return Promise.resolve(window.ClassicEditor);
    return new Promise((resolve, reject) => {
        const existing = document.querySelector('script[data-ym-ckeditor]');
        if (existing) {
            existing.addEventListener('load', () => resolve(window.ClassicEditor));
            return;
        }
        const script = document.createElement('script');
        script.src = 'https://cdn.ckeditor.com/ckeditor5/40.0.0/classic/ckeditor.js';
        script.dataset.ymCkeditor = '1';
        script.onload = () => resolve(window.ClassicEditor);
        script.onerror = reject;
        document.head.appendChild(script);
    });
}

export function attachRichTextEditors(selector = '.rich-text') {
    return loadCKEditor().then((ClassicEditor) => {
        document.querySelectorAll(selector).forEach((el) => {
            if (el.dataset.ckAttached) return;
            el.dataset.ckAttached = '1';
            ClassicEditor.create(el)
                .then((editor) => {
                    editor.model.document.on('change:data', () => {
                        el.value = editor.getData();
                        el.dispatchEvent(new Event('input', { bubbles: true }));
                    });
                })
                .catch(console.error);
        });
    });
}
