export default function FormField({
    label,
    name,
    error,
    required = false,
    hint,
    children,
    className = '',
}) {
    return (
        <div className={className}>
            {label ? (
                <label htmlFor={name} className="block text-sm font-bold mb-2">
                    {label}
                    {required ? <span className="text-error"> *</span> : null}
                </label>
            ) : null}
            {children}
            {hint && !error ? <p className="mt-1.5 text-xs text-on-surface-variant">{hint}</p> : null}
            {error ? <p className="mt-1.5 text-xs font-medium text-error">{Array.isArray(error) ? error[0] : error}</p> : null}
        </div>
    );
}
