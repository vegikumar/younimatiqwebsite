export default function KpiCard({ label, value, sub, icon = 'analytics', tone = 'primary' }) {
    void icon;
    void tone;

    return (
        <div className="unslop-stat portal-kpi">
            <span className="unslop-stat__label">{label}</span>
            <strong className="unslop-stat__value">{value}</strong>
            {sub ? <em className="unslop-stat__sub">{sub}</em> : null}
        </div>
    );
}
