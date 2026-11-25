import { Link } from '@inertiajs/react';
import Icon from '../Icon';

export default function DashboardSection({
    title,
    description,
    icon,
    actionHref,
    actionLabel = 'View all',
    children,
    className = '',
}) {
    return (
        <section className={`unslop-panel p-5 ${className}`.trim()}>
            <div className="unslop-card-head border-b border-[var(--u-hairline,rgba(31,27,22,0.08))] pb-3 mb-4">
                <div className="min-w-0">
                    <h2 className="flex items-center gap-2">
                        {icon ? <Icon name={icon} className="text-[var(--u-muted,#7b7266)] text-lg" /> : null}
                        <span>{title}</span>
                    </h2>
                    {description ? <p>{description}</p> : null}
                </div>
                {actionHref ? (
                    <Link href={actionHref} className="unslop-link shrink-0">
                        {actionLabel}
                    </Link>
                ) : null}
            </div>
            {children}
        </section>
    );
}
