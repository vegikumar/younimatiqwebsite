@push('head')
<style>
    /* Eye & Animation Handling */
    .pupil {
      transition: transform 0.05s ease-out;
    }

    /* Hands for Purple Character */
    .hand {
      transition: transform 0.35s cubic-bezier(0.175, 0.885, 0.32, 1.275);
      transform-origin: 200px 320px;
      opacity: 0;
    }

    .hand-right {
      transform-origin: 300px 320px;
    }

    .eyes-closed {
      display: none;
    }

    /* Interactive States */
    .state-covering .hand {
      opacity: 1;
    }
    .state-covering .hand-left {
      transform: translate(40px, -182px);
    }
    .state-covering .hand-right {
      transform: translate(-50px, -182px);
    }

    .state-closed .eyes-open {
      display: none;
    }
    .state-closed .eyes-closed {
      display: block;
    }
</style>
@endpush

<!-- Left Side: Character Stage -->
<div class="hidden md:flex w-full md:w-1/2 bg-surface-container-low flex-col items-center justify-end pt-10 px-6 pb-2 relative">
    <svg class="w-full max-w-[420px] h-auto overflow-visible" viewBox="0 0 500 450" id="characterSvg">
        <defs>
          <linearGradient id="purpleGrad" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#ab47bc"/>
            <stop offset="100%" stop-color="#8e24aa"/>
          </linearGradient>
          <linearGradient id="pinkGrad" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#ec407a"/>
            <stop offset="100%" stop-color="#d81b60"/>
          </linearGradient>
          <linearGradient id="greenGrad" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#81c784"/>
            <stop offset="100%" stop-color="#4caf50"/>
          </linearGradient>
          <linearGradient id="orangeGrad" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#ffb74d"/>
            <stop offset="100%" stop-color="#f57c00"/>
          </linearGradient>
          <linearGradient id="yellowGrad" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#ffd54f"/>
            <stop offset="100%" stop-color="#ffb300"/>
          </linearGradient>
          <linearGradient id="redGrad" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#e57373"/>
            <stop offset="100%" stop-color="#e53935"/>
          </linearGradient>
          <linearGradient id="blueGrad" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#42a5f5"/>
            <stop offset="100%" stop-color="#1e88e5"/>
          </linearGradient>
        </defs>

        <!-- Base Platform Shadow -->
        <ellipse cx="250" cy="438" rx="210" ry="10" fill="#e2e8f0" />

        <!-- 1. PINK CHARACTER -->
        <g id="pinkChar">
          <path d="M 132,425 L 148,135 Q 170,128 200,135 L 188,425 Z" fill="url(#pinkGrad)" />
          <circle cx="166" cy="162" r="5" fill="#ffffff" />
          <circle cx="188" cy="160" r="5" fill="#ffffff" />
          <circle class="pupil" data-cx="166" data-cy="162" cx="166" cy="162" r="2.5" fill="#111827" />
          <circle class="pupil" data-cx="188" data-cy="160" cx="188" cy="160" r="2.5" fill="#111827" />
          <line x1="172" y1="174" x2="188" y2="174" stroke="#111827" stroke-width="2.5" stroke-linecap="round" />
        </g>

        <!-- 2. PURPLE CHARACTER -->
        <g id="purpleChar">
          <rect x="195" y="40" width="150" height="385" rx="55" fill="url(#purpleGrad)" />

          <!-- Open Eyes -->
          <g class="eyes-open">
            <circle cx="245" cy="98" r="16" fill="#ffffff" />
            <circle cx="285" cy="98" r="16" fill="#ffffff" />
            <circle class="pupil" id="pupilL" data-cx="245" data-cy="98" cx="245" cy="98" r="7" fill="#111827" />
            <circle class="pupil" id="pupilR" data-cx="285" data-cy="98" cx="285" cy="98" r="7" fill="#111827" />
          </g>

          <!-- Squeezed Closed Eyes (Show Password State) -->
          <g class="eyes-closed">
            <path d="M 233,98 Q 245,108 257,98" stroke="#ffffff" stroke-width="4" stroke-linecap="round" fill="none" />
            <path d="M 273,98 Q 285,108 297,98" stroke="#ffffff" stroke-width="4" stroke-linecap="round" fill="none" />
          </g>

          <rect x="258" y="124" width="16" height="8" rx="2" fill="#111827" />

          <!-- Hands -->
          <circle class="hand hand-left" cx="205" cy="280" r="22" fill="#7b1fa2" />
          <circle class="hand hand-right" cx="335" cy="280" r="22" fill="#7b1fa2" />
        </g>

        <!-- 3. GREEN CACTUS CHARACTER -->
        <g id="cactusChar">
          <rect x="305" y="115" width="52" height="310" rx="26" fill="url(#greenGrad)" />
          <path d="M 310,205 H 270 Q 258,205 258,185 V 160" stroke="url(#greenGrad)" stroke-width="22" stroke-linecap="round" stroke-linejoin="round" fill="none" />
          <path d="M 352,225 H 388 Q 400,225 400,195 V 150" stroke="url(#greenGrad)" stroke-width="22" stroke-linecap="round" stroke-linejoin="round" fill="none" />
          
          <circle cx="320" cy="150" r="7" fill="#ffffff" />
          <circle cx="342" cy="150" r="7" fill="#ffffff" />
          <circle class="pupil" data-cx="320" data-cy="150" cx="320" cy="150" r="3.5" fill="#111827" />
          <circle class="pupil" data-cx="342" data-cy="150" cx="342" cy="150" r="3.5" fill="#111827" />
          <path d="M 322,165 Q 331,173 340,165" stroke="#111827" stroke-width="3" stroke-linecap="round" fill="none" />
        </g>

        <!-- FLOATING BLACK DOTS -->
        <g id="floatingDotsRight">
          <circle cx="380" cy="265" r="4" fill="#111827" />
          <circle cx="370" cy="280" r="4" fill="#111827" />
          <circle cx="390" cy="280" r="4" fill="#111827" />
        </g>

        <!-- 4. ORANGE BLOB -->
        <g id="orangeChar">
          <path d="M 105,425 C 105,270 280,270 280,425 Z" fill="url(#orangeGrad)" />
          <circle cx="218" cy="288" r="4" fill="#111827" />
          <circle cx="240" cy="270" r="4" fill="#111827" />
          <circle cx="242" cy="295" r="4" fill="#111827" />
        </g>

        <!-- 5. YELLOW CAPSULE -->
        <g id="yellowChar">
          <rect x="52" y="340" width="210" height="85" rx="42.5" fill="url(#yellowGrad)" />
          <circle class="pupil" data-cx="230" data-cy="365" cx="230" cy="365" r="4" fill="#111827" />
          <line x1="235" y1="380" x2="262" y2="380" stroke="#111827" stroke-width="4" stroke-linecap="round" />
        </g>

        <!-- 6. RED TEARDROP -->
        <g id="redChar">
          <path d="M 292,425 Q 288,380 315,330 Q 345,380 342,425 Z" fill="url(#redGrad)" />
          <circle cx="308" cy="380" r="3" fill="#111827" />
          <circle cx="322" cy="380" r="3" fill="#111827" />
          <circle cx="315" cy="390" r="2.5" fill="#111827" />
        </g>

        <!-- 7. BLUE ARCH -->
        <g id="blueChar">
          <path d="M 355,425 C 355,330 422,330 422,425 Z" fill="url(#blueGrad)" />
        </g>
    </svg>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Find inputs that might be on the page
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        const passwordConfirmationInput = document.getElementById('password_confirmation');
        const togglePasswordBtn = document.getElementById('togglePassword');
        const togglePasswordIcon = document.getElementById('togglePasswordIcon');
        
        const purpleChar = document.getElementById('purpleChar');
        const pupils = document.querySelectorAll('.pupil');
        const characterSvg = document.getElementById('characterSvg');

        const maxOffset = 6; 

        // Tracking mouse cursor across all pupils
        document.addEventListener('mousemove', (e) => {
            const isPasswordFocused = (document.activeElement === passwordInput && passwordInput?.type === 'password') || 
                                      (document.activeElement === passwordConfirmationInput && passwordConfirmationInput?.type === 'password');
            if (isPasswordFocused) return;

            if (!characterSvg) return;
            const svgRect = characterSvg.getBoundingClientRect();
            
            // If svg is not visible, don't do maths
            if(svgRect.width === 0) return;

            const svgX = ((e.clientX - svgRect.left) / svgRect.width) * 500;
            const svgY = ((e.clientY - svgRect.top) / svgRect.height) * 450;

            pupils.forEach(pupil => {
                const baseX = parseFloat(pupil.getAttribute('data-cx') || pupil.getAttribute('cx'));
                const baseY = parseFloat(pupil.getAttribute('data-cy') || pupil.getAttribute('cy'));

                const dx = svgX - baseX;
                const dy = svgY - baseY;
                const angle = Math.atan2(dy, dx);
                const dist = Math.min(Math.hypot(dx, dy) * 0.05, maxOffset);

                const moveX = Math.cos(angle) * dist;
                const moveY = Math.sin(angle) * dist;

                pupil.style.transform = `translate(${moveX}px, ${moveY}px)`;
            });
        });

        function updateCharState() {
            if (!purpleChar) return;
            
            const isPasswordFocused = document.activeElement === passwordInput || document.activeElement === passwordConfirmationInput;
            const isPasswordHidden = (document.activeElement === passwordInput && passwordInput?.type === 'password') || 
                                     (document.activeElement === passwordConfirmationInput && passwordConfirmationInput?.type === 'password');

            purpleChar.classList.remove('state-covering', 'state-closed');

            if (isPasswordFocused) {
                if (isPasswordHidden) {
                    purpleChar.classList.add('state-covering');
                } else {
                    purpleChar.classList.add('state-closed');
                }
            }
        }

        if (passwordInput) {
            passwordInput.addEventListener('focus', updateCharState);
            passwordInput.addEventListener('blur', updateCharState);
        }

        if (passwordConfirmationInput) {
            passwordConfirmationInput.addEventListener('focus', updateCharState);
            passwordConfirmationInput.addEventListener('blur', updateCharState);
        }

        // Password visibility toggling is now handled by AlpineJS on the blade templates
        window.updateCharState = updateCharState; // Expose this for AlpineJS to call

        if (emailInput) {
            emailInput.addEventListener('focus', () => {
                if(purpleChar) purpleChar.classList.remove('state-covering', 'state-closed');
            });
        }
    });
</script>
