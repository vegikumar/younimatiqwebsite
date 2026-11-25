@php
    try {
        $claritySettings = \Illuminate\Support\Facades\Cache::rememberForever('global_settings', function () {
            return \App\Models\Setting::pluck('value', 'key')->toArray();
        });
    } catch (\Throwable) {
        $claritySettings = [];
    }

    $defaultClarityId = 'yn3mggp6o4';
    $clarityId = trim((string) ($claritySettings['microsoft_clarity_id'] ?? ''));
    $clarityValid = $clarityId !== '' && preg_match('/^[a-z0-9]+$/i', $clarityId);
    $clarityId = $clarityValid ? $clarityId : $defaultClarityId;
@endphp

@if($clarityId !== '')
<!-- Microsoft Clarity — deferred until after interaction/load to protect LCP/TBT -->
<script type="text/javascript">
    (function(c,l,a,r,i){
        c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
        var loaded=false;
        function load(){
            if(loaded)return;loaded=true;
            window.removeEventListener('scroll',load);
            window.removeEventListener('pointerdown',load);
            window.removeEventListener('keydown',load);
            var t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
            var y=l.getElementsByTagName(r)[0];
            if(y&&y.parentNode){y.parentNode.insertBefore(t,y);}
            else{l.head.appendChild(t);}
        }
        window.addEventListener('scroll',load,{once:true,passive:true});
        window.addEventListener('pointerdown',load,{once:true,passive:true});
        window.addEventListener('keydown',load,{once:true});
        setTimeout(load,3500);
    })(window, document, "clarity", "script", @json($clarityId));
</script>
<!-- End Microsoft Clarity -->
@endif
