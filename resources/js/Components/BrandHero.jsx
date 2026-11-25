export default function BrandHero({ eyebrow, centered = true, compact = false, className = '', children }) {
    return (
        <section className={`px-4 md:px-8 w-[98%] max-w-[1600px] mx-auto ${className}`}>
            <div
                className={[
                    'brand-panel',
                    centered ? 'text-center' : '',
                    compact ? 'brand-panel--compact p-5 md:p-8 lg:p-6 md:p-12' : 'p-5 md:p-10 md:p-6 md:p-16',
                ]
                    .filter(Boolean)
                    .join(' ')}
            >
                {eyebrow ? <p className="brand-eyebrow">{eyebrow}</p> : null}
                {children}
            </div>
        </section>
    );
}
