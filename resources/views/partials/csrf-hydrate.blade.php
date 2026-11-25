{{--
    Edge-cached pages carry a placeholder CSRF token ("deferred"). This script
    fetches a fresh, session-bound token once the page loads and applies it to:
      - window.__csrfToken        (for inline fetch() calls)
      - <meta name="csrf-token">  (some JS reads the meta)
      - any <input name="_token"> still set to the placeholder
    Include this once per standalone (non-layout) public page.
--}}
<script>
    (function () {
        var meta = document.querySelector('meta[name="csrf-token"]');
        var current = meta ? meta.getAttribute('content') : null;
        if (current && current !== 'deferred') {
            window.__csrfToken = current;
            return;
        }

        window.__csrfToken = '';

        function applyToken(token) {
            if (!token) return;
            window.__csrfToken = token;
            if (meta) meta.setAttribute('content', token);
            document.querySelectorAll('input[name="_token"]').forEach(function (input) {
                if (input.value === 'deferred') input.value = token;
            });
        }

        fetch('/csrf-token', { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (data) { if (data && data.token) applyToken(data.token); })
            .catch(function () {});
    })();
</script>
