export default function SoftStatCard({
    label,
    value,
    sub,
    trend,
    delta = null,
    icon = 'analytics',
    tone = 'lilac',
    accentBar = false,
    className = '',
}) {
    const deltaText = delta ?? trend;
    void icon;
    void tone;
    void accentBar;

    return (
        <div className={`unslop-stat ${className}`.trim()}>
            <span className="unslop-stat__label">{label}</span>
            <strong className="unslop-stat__value">{value}</strong>
            {sub ? <em className="unslop-stat__sub">{sub}</em> : null}
            {deltaText ? <em className="unslop-stat__delta">{deltaText}</em> : null}
        </div>
    );
}
