export default function FilterBar({ children, className = '' }) {
    return <div className={`unslop-filters ${className}`.trim()}>{children}</div>;
}
