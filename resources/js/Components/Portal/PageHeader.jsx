export default function PageHeader({ eyebrow, title, description, actions = null, variant = 'default' }) {
    if (variant === 'staff') {
        return (
            <div className="unslop-top">
                <div>
                    {eyebrow ? <p className="learn-kicker mb-1">{eyebrow}</p> : null}
                    <h1>{title}</h1>
                    {description ? <p>{description}</p> : null}
                </div>
                {actions ? <div className="unslop-actions">{actions}</div> : null}
            </div>
        );
    }

    const heroClass =
        variant === 'tutor'
            ? 'portal-hero portal-hero--tutor'
            : variant === 'admin'
              ? 'portal-hero portal-hero--admin'
              : variant === 'parent'
                ? 'portal-hero portal-hero--parent'
                : variant === 'student'
                  ? 'portal-hero'
                  : 'portal-hero';

    if (variant && variant !== 'default') {
        return (
            <div className={`${heroClass} unslop-top flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between`}>
                <div className="min-w-0 relative z-10">
                    {eyebrow ? <p className="portal-hero__eyebrow learn-kicker">{eyebrow}</p> : null}
                    <h1>{title}</h1>
                    {description ? <p>{description}</p> : null}
                </div>
                {actions ? <div className="relative z-10 unslop-actions shrink-0">{actions}</div> : null}
            </div>
        );
    }

    return (
        <div className="unslop-top">
            <div className="min-w-0">
                {eyebrow ? <p className="learn-kicker mb-1">{eyebrow}</p> : null}
                <h1>{title}</h1>
                {description ? <p>{description}</p> : null}
            </div>
            {actions ? <div className="unslop-actions shrink-0">{actions}</div> : null}
        </div>
    );
}
