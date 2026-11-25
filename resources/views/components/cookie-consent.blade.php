<div id="cookie-consent" class="fixed bottom-0 left-0 right-0 z-50 bg-inverse-surface text-white p-4 shadow-lg hidden">
    <div class="max-w-4xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-4">
        <p class="text-sm">We use cookies to improve your experience and analyze traffic. By continuing, you agree to our <a href="{{ route('pages.show', 'cookie-policy') }}" class="link-primary text-white hover:text-secondary-container">Cookie Policy</a>.</p>
        <button onclick="acceptCookies()" class="btn-enroll btn-sm whitespace-nowrap">Accept</button>
    </div>
</div>
<script>
    if (!localStorage.getItem('cookie_consent')) {
        document.getElementById('cookie-consent').classList.remove('hidden');
    }
    function acceptCookies() {
        localStorage.setItem('cookie_consent', '1');
        document.getElementById('cookie-consent').classList.add('hidden');
    }
</script>
