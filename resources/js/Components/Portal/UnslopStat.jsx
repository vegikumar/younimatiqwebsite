export default function UnslopStat({ label, value, sub = null, delta = null, className = '' }) {
    return (
        <div className={`unslop-stat ${className}`.trim()}>
            <span className="unslop-stat__label">{label}</span>
            <strong className="unslop-stat__value">{value}</strong>
            {sub ? <em className="unslop-stat__sub">{sub}</em> : null}
            {delta ? <em className="unslop-stat__delta">{delta}</em> : null}
        </div>
    );
}
