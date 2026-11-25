import { useEffect, useRef } from 'react';

/**
 * Interactive login mascot stage (ported from auth/partials/character-stage).
 * passwordVisible: when password field is focused and shown as text → closed eyes;
 * when focused and hidden → covering hands.
 */
export default function LoginCharacterStage({ passwordFocused = false, passwordVisible = false }) {
    const svgRef = useRef(null);
    const purpleRef = useRef(null);

    useEffect(() => {
        const purple = purpleRef.current;
        if (!purple) return;
        purple.classList.remove('state-covering', 'state-closed');
        if (passwordFocused) {
            purple.classList.add(passwordVisible ? 'state-closed' : 'state-covering');
        }
    }, [passwordFocused, passwordVisible]);

    useEffect(() => {
        const maxOffset = 6;
        const onMove = (e) => {
            if (passwordFocused && !passwordVisible) return;
            const svg = svgRef.current;
            if (!svg) return;
            const svgRect = svg.getBoundingClientRect();
            if (!svgRect.width) return;
            const svgX = ((e.clientX - svgRect.left) / svgRect.width) * 500;
            const svgY = ((e.clientY - svgRect.top) / svgRect.height) * 450;
            svg.querySelectorAll('.pupil').forEach((pupil) => {
                const baseX = parseFloat(pupil.getAttribute('data-cx') || pupil.getAttribute('cx'));
                const baseY = parseFloat(pupil.getAttribute('data-cy') || pupil.getAttribute('cy'));
                const dx = svgX - baseX;
                const dy = svgY - baseY;
                const angle = Math.atan2(dy, dx);
                const dist = Math.min(Math.hypot(dx, dy) * 0.05, maxOffset);
                pupil.style.transform = `translate(${Math.cos(angle) * dist}px, ${Math.sin(angle) * dist}px)`;
            });
        };
        document.addEventListener('mousemove', onMove);
        return () => document.removeEventListener('mousemove', onMove);
    }, [passwordFocused, passwordVisible]);

    return (
        <div className="hidden md:flex w-full md:w-1/2 bg-surface-container-low flex-col items-center justify-end pt-10 px-6 pb-2 relative login-character-stage">
            <style>{`
                .login-character-stage .pupil { transition: transform 0.05s ease-out; }
                .login-character-stage .hand {
                  transition: transform 0.35s cubic-bezier(0.175, 0.885, 0.32, 1.275);
                  transform-origin: 200px 320px;
                  opacity: 0;
                }
                .login-character-stage .hand-right { transform-origin: 300px 320px; }
                .login-character-stage .eyes-closed { display: none; }
                .login-character-stage .state-covering .hand { opacity: 1; }
                .login-character-stage .state-covering .hand-left { transform: translate(40px, -182px); }
                .login-character-stage .state-covering .hand-right { transform: translate(-50px, -182px); }
                .login-character-stage .state-closed .eyes-open { display: none; }
                .login-character-stage .state-closed .eyes-closed { display: block; }
            `}</style>
            <svg ref={svgRef} className="w-full max-w-[420px] h-auto overflow-visible" viewBox="0 0 500 450" aria-hidden="true">
                <defs>
                    <linearGradient id="purpleGrad" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stopColor="#ab47bc" />
                        <stop offset="100%" stopColor="#8e24aa" />
                    </linearGradient>
                    <linearGradient id="pinkGrad" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stopColor="#ec407a" />
                        <stop offset="100%" stopColor="#d81b60" />
                    </linearGradient>
                    <linearGradient id="greenGrad" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stopColor="#81c784" />
                        <stop offset="100%" stopColor="#4caf50" />
                    </linearGradient>
                    <linearGradient id="orangeGrad" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stopColor="#ffb74d" />
                        <stop offset="100%" stopColor="#f57c00" />
                    </linearGradient>
                    <linearGradient id="yellowGrad" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stopColor="#ffd54f" />
                        <stop offset="100%" stopColor="#ffb300" />
                    </linearGradient>
                    <linearGradient id="redGrad" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stopColor="#e57373" />
                        <stop offset="100%" stopColor="#e53935" />
                    </linearGradient>
                    <linearGradient id="blueGrad" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stopColor="#42a5f5" />
                        <stop offset="100%" stopColor="#1e88e5" />
                    </linearGradient>
                </defs>

                <ellipse cx="250" cy="438" rx="210" ry="10" fill="#e2e8f0" />

                <g id="pinkChar">
                    <path d="M 132,425 L 148,135 Q 170,128 200,135 L 188,425 Z" fill="url(#pinkGrad)" />
                    <circle cx="166" cy="162" r="5" fill="#ffffff" />
                    <circle cx="188" cy="160" r="5" fill="#ffffff" />
                    <circle className="pupil" data-cx="166" data-cy="162" cx="166" cy="162" r="2.5" fill="#111827" />
                    <circle className="pupil" data-cx="188" data-cy="160" cx="188" cy="160" r="2.5" fill="#111827" />
                    <line x1="172" y1="174" x2="188" y2="174" stroke="#111827" strokeWidth="2.5" strokeLinecap="round" />
                </g>

                <g id="purpleChar" ref={purpleRef}>
                    <rect x="195" y="40" width="150" height="385" rx="55" fill="url(#purpleGrad)" />
                    <g className="eyes-open">
                        <circle cx="245" cy="98" r="16" fill="#ffffff" />
                        <circle cx="285" cy="98" r="16" fill="#ffffff" />
                        <circle className="pupil" data-cx="245" data-cy="98" cx="245" cy="98" r="7" fill="#111827" />
                        <circle className="pupil" data-cx="285" data-cy="98" cx="285" cy="98" r="7" fill="#111827" />
                    </g>
                    <g className="eyes-closed">
                        <path d="M 233,98 Q 245,108 257,98" stroke="#ffffff" strokeWidth="4" strokeLinecap="round" fill="none" />
                        <path d="M 273,98 Q 285,108 297,98" stroke="#ffffff" strokeWidth="4" strokeLinecap="round" fill="none" />
                    </g>
                    <rect x="258" y="124" width="16" height="8" rx="2" fill="#111827" />
                    <circle className="hand hand-left" cx="205" cy="280" r="22" fill="#7b1fa2" />
                    <circle className="hand hand-right" cx="335" cy="280" r="22" fill="#7b1fa2" />
                </g>

                <g id="cactusChar">
                    <rect x="305" y="115" width="52" height="310" rx="26" fill="url(#greenGrad)" />
                    <path d="M 310,205 H 270 Q 258,205 258,185 V 160" stroke="url(#greenGrad)" strokeWidth="22" strokeLinecap="round" strokeLinejoin="round" fill="none" />
                    <path d="M 352,225 H 388 Q 400,225 400,195 V 150" stroke="url(#greenGrad)" strokeWidth="22" strokeLinecap="round" strokeLinejoin="round" fill="none" />
                    <circle cx="320" cy="150" r="7" fill="#ffffff" />
                    <circle cx="342" cy="150" r="7" fill="#ffffff" />
                    <circle className="pupil" data-cx="320" data-cy="150" cx="320" cy="150" r="3.5" fill="#111827" />
                    <circle className="pupil" data-cx="342" data-cy="150" cx="342" cy="150" r="3.5" fill="#111827" />
                    <path d="M 322,165 Q 331,173 340,165" stroke="#111827" strokeWidth="3" strokeLinecap="round" fill="none" />
                </g>

                <g>
                    <circle cx="380" cy="265" r="4" fill="#111827" />
                    <circle cx="370" cy="280" r="4" fill="#111827" />
                    <circle cx="390" cy="280" r="4" fill="#111827" />
                </g>

                <g id="orangeChar">
                    <path d="M 105,425 C 105,270 280,270 280,425 Z" fill="url(#orangeGrad)" />
                    <circle cx="218" cy="288" r="4" fill="#111827" />
                    <circle cx="240" cy="270" r="4" fill="#111827" />
                    <circle cx="242" cy="295" r="4" fill="#111827" />
                </g>

                <g id="yellowChar">
                    <rect x="52" y="340" width="210" height="85" rx="42.5" fill="url(#yellowGrad)" />
                    <circle className="pupil" data-cx="230" data-cy="365" cx="230" cy="365" r="4" fill="#111827" />
                    <line x1="235" y1="380" x2="262" y2="380" stroke="#111827" strokeWidth="4" strokeLinecap="round" />
                </g>

                <g id="redChar">
                    <path d="M 292,425 Q 288,380 315,330 Q 345,380 342,425 Z" fill="url(#redGrad)" />
                    <circle cx="308" cy="380" r="3" fill="#111827" />
                    <circle cx="322" cy="380" r="3" fill="#111827" />
                    <circle cx="315" cy="390" r="2.5" fill="#111827" />
                </g>

                <g id="blueChar">
                    <path d="M 355,425 C 355,330 422,330 422,425 Z" fill="url(#blueGrad)" />
                </g>
            </svg>
        </div>
    );
}
