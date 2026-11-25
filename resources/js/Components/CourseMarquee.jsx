import { useEffect, useRef } from 'react';

export default function CourseMarquee({ children, className = '' }) {
    const trackRef = useRef(null);
    const innerRef = useRef(null);

    useEffect(() => {
        const track = trackRef.current;
        const inner = innerRef.current;
        if (!track || !inner) return undefined;

        let paused = false;
        let offset = 0;
        let raf;
        const speed = 0.55;

        const onEnter = () => {
            paused = true;
        };
        const onLeave = () => {
            paused = false;
        };
        track.addEventListener('mouseenter', onEnter);
        track.addEventListener('mouseleave', onLeave);

        const tick = () => {
            if (!paused) {
                offset += speed;
                const half = inner.scrollWidth / 2;
                if (half > 0 && offset >= half) offset = 0;
                inner.style.transform = `translateX(${-offset}px)`;
            }
            raf = window.requestAnimationFrame(tick);
        };
        raf = window.requestAnimationFrame(tick);

        return () => {
            window.cancelAnimationFrame(raf);
            track.removeEventListener('mouseenter', onEnter);
            track.removeEventListener('mouseleave', onLeave);
        };
    }, [children]);

    return (
        <div
            ref={trackRef}
            className={className}
            style={{
                overflow: 'hidden',
                position: 'relative',
                WebkitMaskImage: 'linear-gradient(to right, transparent 0%, black 5%, black 95%, transparent 100%)',
                maskImage: 'linear-gradient(to right, transparent 0%, black 5%, black 95%, transparent 100%)',
            }}
        >
            <div ref={innerRef} className="flex gap-6 pb-4" style={{ width: 'max-content', willChange: 'transform' }}>
                <div className="flex gap-6">{children}</div>
                <div className="flex gap-6" aria-hidden="true">
                    {children}
                </div>
            </div>
        </div>
    );
}
