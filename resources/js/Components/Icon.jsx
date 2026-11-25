/**
 * Material Symbols ligature icon.
 * Fixed box so every icon in a row shares one column and never spills text.
 */
export default function Icon({ name, className = '', title }) {
    const icon = typeof name === 'string' && name.trim() ? name.trim() : 'circle';

    return (
        <span
            className={`material-symbols-outlined ym-icon ${className}`.trim()}
            aria-hidden={title ? undefined : true}
            title={title || undefined}
        >
            {icon}
        </span>
    );
}
