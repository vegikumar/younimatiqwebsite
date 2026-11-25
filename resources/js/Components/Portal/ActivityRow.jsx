import Icon from '../Icon';

export default function ActivityRow({
    icon = 'event',
    iconTone = '',
    title,
    description = null,
    meta = null,
    trailing = null,
    className = '',
}) {
    void iconTone;

    return (
        <div className={`unslop-row ${className}`.trim()}>
            <div className="flex items-start gap-3 min-w-0">
                <div className="grid h-9 w-9 shrink-0 place-items-center rounded-[10px] bg-[var(--u-raised,#efe9df)] text-[var(--u-muted,#7b7266)]">
                    <Icon name={icon} className="text-base" />
                </div>
                <div className="min-w-0 flex-1">
                    <strong>{title}</strong>
                    {description ? <span className="block mt-0.5">{description}</span> : null}
                    {meta ? <span className="block mt-1">{meta}</span> : null}
                </div>
            </div>
            {trailing ? <div className="shrink-0 self-center">{trailing}</div> : null}
        </div>
    );
}
