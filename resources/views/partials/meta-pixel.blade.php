@php
    try {
        $pixelSettings = \Illuminate\Support\Facades\Cache::rememberForever('global_settings', function () {
            return \App\Models\Setting::pluck('value', 'key')->toArray();
        });
    } catch (\Throwable) {
        $pixelSettings = [];
    }

    $defaultPixelId = '28440548075614134';
    $pixelId = trim((string) ($pixelSettings['meta_pixel_id'] ?? ''));
    $pixelValid = $pixelId !== '' && ctype_digit($pixelId) && ! preg_match('/DEMO/i', $pixelId);
    $pixelId = $pixelValid ? $pixelId : $defaultPixelId;
@endphp

<!-- Meta Pixel Code — deferred until after page load to protect LCP/TBT -->
<script>
!function(f,b,e,v,n,t,s)
{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};
if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
n.queue=[];} (window, document);
fbq('init', '{{ $pixelId }}');
fbq('track', 'PageView');
(function () {
  var loaded = false;
  function loadPixel() {
    if (loaded) return;
    loaded = true;
    window.removeEventListener('scroll', loadPixel);
    window.removeEventListener('pointerdown', loadPixel);
    window.removeEventListener('keydown', loadPixel);
    var t = document.createElement('script');
    t.async = true;
    t.src = 'https://connect.facebook.net/en_US/fbevents.js';
    var s = document.getElementsByTagName('script')[0];
    s.parentNode.insertBefore(t, s);
  }
  window.addEventListener('scroll', loadPixel, { once: true, passive: true });
  window.addEventListener('pointerdown', loadPixel, { once: true, passive: true });
  window.addEventListener('keydown', loadPixel, { once: true });
  setTimeout(loadPixel, 5000);
})();
</script>
<noscript><img height="1" width="1" style="display:none"
src="https://www.facebook.com/tr?id={{ $pixelId }}&ev=PageView&noscript=1"
/></noscript>
<!-- End Meta Pixel Code -->
