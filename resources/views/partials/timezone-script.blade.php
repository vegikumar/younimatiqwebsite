<script>
    (function () {
        function updateTimeElements(root) {
            const elements = root.querySelectorAll ? root.querySelectorAll('.dynamic-local-time[data-utc]:not(.tz-processed)') : [];
            elements.forEach(function (el) {
                const utc = el.getAttribute('data-utc');
                const formatType = el.getAttribute('data-format') || 'datetime'; // 'date', 'time', 'datetime', 'datetime-short'
                
                if (utc) {
                    try {
                        const dt = new Date(utc);
                        if (isNaN(dt.getTime())) return;

                        let options = {};
                        if (formatType === 'date') {
                            options = { month: 'short', day: 'numeric', year: 'numeric' };
                        } else if (formatType === 'time') {
                            options = { hour: 'numeric', minute: '2-digit', hour12: true };
                        } else if (formatType === 'datetime') {
                            options = { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit', hour12: true };
                        } else if (formatType === 'datetime-short') {
                            options = { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit', hour12: true };
                        } else {
                            options = { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit', hour12: true };
                        }

                        let formatted = dt.toLocaleString('en-US', options);
                        el.textContent = formatted;
                        el.classList.add('tz-processed');
                    } catch (e) {
                        console.error('Invalid date format', utc);
                    }
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            updateTimeElements(document);

            // Observe DOM for newly added elements (e.g. modals, ajax)
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.addedNodes.length) {
                        mutation.addedNodes.forEach(function(node) {
                            if (node.nodeType === 1) { // ELEMENT_NODE
                                if (node.classList && node.classList.contains('dynamic-local-time')) {
                                    updateTimeElements(node.parentElement || document);
                                } else {
                                    updateTimeElements(node);
                                }
                            }
                        });
                    }
                });
            });
            
            observer.observe(document.body, { childList: true, subtree: true });
        });
    })();
</script>
