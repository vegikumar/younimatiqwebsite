export default function PortalCard({
    children,
    className = '',
    padding = true,
    title = null,
    description = null,
    actions = null,
    as: Tag = 'section',
}) {
    return (
        <Tag className={`staff-card unslop-panel ${className}`.trim()}>
            {title || actions ? (
                <div className={`unslop-card-head ${padding ? 'px-5 pt-5' : ''}`.trim()}>
                    <div>
                        {title ? <h2>{title}</h2> : null}
                        {description ? <p>{description}</p> : null}
                    </div>
                    {actions ? <div className="flex flex-wrap items-center gap-2">{actions}</div> : null}
                </div>
            ) : null}
            <div className={padding ? `p-5 ${title || actions ? 'pt-4' : ''}`.trim() : undefined}>{children}</div>
        </Tag>
    );
}
