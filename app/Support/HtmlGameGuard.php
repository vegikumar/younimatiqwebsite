<?php

namespace App\Support;

/**
 * Injects play-time protections into uploaded HTML activity games.
 *
 * Browsers cannot fully block OS-level screenshots. This guard hardens against
 * casual capture and download on phone and laptop (right-click, Save/Print/View
 * Source, copy, print, blackout while the tab is backgrounded / blurred).
 */
class HtmlGameGuard
{
    public static function inject(string $html, bool $requireEmbed = true): string
    {
        $payload = self::script($requireEmbed).self::style();
        $low = strtolower($html);
        $head = strpos($low, '<head');
        if ($head === false) {
            return $payload.$html;
        }
        $gt = strpos($low, '>', $head);
        if ($gt === false) {
            return $payload.$html;
        }

        return substr($html, 0, $gt + 1).$payload.substr($html, $gt + 1);
    }

    /**
     * @return array<string, string>
     */
    public static function responseHeaders(bool $allowSameOriginFrame = false): array
    {
        // Google Fonts are used by several existing HTML activities.
        $csp = "default-src 'self' 'unsafe-inline' 'unsafe-eval' data: blob:; "
            ."img-src 'self' data: blob:; "
            ."media-src 'self' data: blob:; "
            ."font-src 'self' data: https://fonts.gstatic.com; "
            ."style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
            ."style-src-elem 'self' 'unsafe-inline' https://fonts.googleapis.com; "
            ."connect-src 'self'; "
            ."form-action 'none'; "
            ."base-uri 'self'; "
            ."object-src 'none'; "
            .'frame-ancestors '.($allowSameOriginFrame ? "'self'" : "'none'");

        $headers = [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'inline',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0, private',
            'Pragma' => 'no-cache',
            'Expires' => '0',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'no-referrer',
            'X-Robots-Tag' => 'noindex, nofollow, noarchive, nosnippet',
            'X-Download-Options' => 'noopen',
            'X-Younimatiq-Activity' => '1',
            'Permissions-Policy' => 'display-capture=(), camera=(), microphone=(), geolocation=()',
            'Content-Security-Policy' => $csp,
        ];

        if ($allowSameOriginFrame) {
            $headers['X-Frame-Options'] = 'SAMEORIGIN';
        } else {
            $headers['X-Frame-Options'] = 'DENY';
        }

        return $headers;
    }

    private static function style(): string
    {
        return <<<'HTML'
<style id="yq-game-guard-css">
html,body{-webkit-user-select:none!important;user-select:none!important;-webkit-touch-callout:none!important;-webkit-user-drag:none!important;overscroll-behavior:none}
img,video,canvas,svg{-webkit-user-drag:none!important;user-select:none!important}
#yq-secure-blackout{position:fixed;inset:0;z-index:2147483646;background:#000;opacity:0;pointer-events:none;transition:opacity .08s linear}
#yq-secure-blackout.yq-on{opacity:1;pointer-events:auto}
#yq-secure-mark{position:fixed;inset:auto 12px 12px auto;z-index:2147483645;font:600 11px/1.2 system-ui,sans-serif;color:rgba(0,0,0,.28);pointer-events:none;user-select:none}
@media print{html,body{display:none!important}}
</style>
HTML;
    }

    private static function script(bool $requireEmbed): string
    {
        $embedCheck = $requireEmbed
            ? 'if(window.top===window.self){document.documentElement.innerHTML=\'<body style="font-family:sans-serif;padding:2rem;text-align:center"><h3>This game can only be played inside Younimatiq.</h3></body>\';return;}'
            : '';

        return '<script id="yq-game-guard">(function(){'
            .$embedCheck
            .'try{window.localStorage.getItem("x")}catch(e){var mk=function(){var s={};return{getItem:function(k){return k in s?s[k]:null},setItem:function(k,v){s[k]=String(v)},removeItem:function(k){delete s[k]},clear:function(){s={}},key:function(i){return Object.keys(s)[i]||null},get length(){return Object.keys(s).length}}};'
            .'try{Object.defineProperty(window,"localStorage",{value:mk()});Object.defineProperty(window,"sessionStorage",{value:mk()})}catch(_){}}'
            .'var blockedKeys=["s","u","p","c","a","x","i","j"];'
            .'function stop(e){try{e.preventDefault();e.stopPropagation()}catch(_){}return false}'
            .'document.addEventListener("contextmenu",stop,true);'
            .'document.addEventListener("dragstart",stop,true);'
            .'document.addEventListener("copy",stop,true);'
            .'document.addEventListener("cut",stop,true);'
            .'document.addEventListener("selectstart",stop,true);'
            .'document.addEventListener("keydown",function(e){var k=(e.key||"").toLowerCase();'
            .'if(k==="printscreen"||k==="f12"){stop(e);flash();return}'
            .'if((e.ctrlKey||e.metaKey)&&blockedKeys.indexOf(k)>-1){stop(e);return}'
            .'if((e.ctrlKey||e.metaKey)&&e.shiftKey&&(k==="i"||k==="j"||k==="c"||k==="s"||k==="u")){stop(e);return}'
            .'if(e.altKey&&k==="printscreen"){stop(e);flash()}'
            .'},true);'
            .'document.addEventListener("keyup",function(e){var k=(e.key||"").toLowerCase();if(k==="printscreen"){flash()}},true);'
            .'function ready(fn){if(document.body){fn()}else{document.addEventListener("DOMContentLoaded",fn)}}'
            .'var veil=null,hideTimer=null;'
            .'function ensure(){if(veil)return;veil=document.createElement("div");veil.id="yq-secure-blackout";veil.setAttribute("aria-hidden","true");'
            .'var mark=document.createElement("div");mark.id="yq-secure-mark";mark.textContent="Younimatiq · protected activity";'
            .'document.body.appendChild(veil);document.body.appendChild(mark)}'
            .'function flash(){ensure();veil.classList.add("yq-on");clearTimeout(hideTimer);hideTimer=setTimeout(function(){if(document.visibilityState==="visible"&&document.hasFocus()){veil.classList.remove("yq-on")}},900)}'
            .'function cover(on){ensure();if(on){veil.classList.add("yq-on")}else if(document.visibilityState==="visible"){veil.classList.remove("yq-on")}}'
            .'ready(function(){ensure();'
            .'document.addEventListener("visibilitychange",function(){cover(document.visibilityState!=="visible")});'
            .'window.addEventListener("blur",function(){cover(true)});'
            .'window.addEventListener("focus",function(){if(document.visibilityState==="visible")cover(false)});'
            .'window.addEventListener("pagehide",function(){cover(true)});'
            .'window.addEventListener("pageshow",function(){cover(false)});'
            .'try{if(navigator.mediaDevices&&navigator.mediaDevices.getDisplayMedia){'
            .'navigator.mediaDevices.getDisplayMedia=function(){cover(true);return Promise.reject(new Error("blocked"))}}catch(_){}'
            .'});'
            .'})();</script>';
    }
}
