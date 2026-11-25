@php
    try {
        $trackingSettings = \Illuminate\Support\Facades\Cache::rememberForever('global_settings', function () {
            return \App\Models\Setting::pluck('value', 'key')->toArray();
        });
    } catch (\Throwable) {
        $trackingSettings = [];
    }

    $defaultGaId = 'G-K74YPBHGMC';
    $gaId = trim((string) ($trackingSettings['google_analytics_id'] ?? ''));
    $gaValid = $gaId !== '' && preg_match('/^G-[A-Z0-9]+$/i', $gaId) && ! preg_match('/DEMO/i', $gaId);
    $gaId = $gaValid ? $gaId : $defaultGaId;

    $gscToken = trim((string) ($trackingSettings['google_site_verification'] ?? ''));
@endphp

@if($gscToken !== '')
<meta name="google-site-verification" content="{{ $gscToken }}">
@endif

<!-- Google tag (gtag.js) — {{ $gaId }} (deferred to protect LCP/TBT) -->
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', @json($gaId));
  (function () {
    var loaded = false;
    function loadGtag() {
      if (loaded) return;
      loaded = true;
      window.removeEventListener('scroll', loadGtag);
      window.removeEventListener('pointerdown', loadGtag);
      window.removeEventListener('keydown', loadGtag);
      var s = document.createElement('script');
      s.async = true;
      s.src = 'https://www.googletagmanager.com/gtag/js?id=' + @json($gaId);
      document.head.appendChild(s);
    }
    window.addEventListener('scroll', loadGtag, { once: true, passive: true });
    window.addEventListener('pointerdown', loadGtag, { once: true, passive: true });
    window.addEventListener('keydown', loadGtag, { once: true });
    setTimeout(loadGtag, 3500);
  })();
</script>
<!-- End Google tag (gtag.js) -->
