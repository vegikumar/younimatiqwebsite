export default function FormErrors({ errors, className = '' }) {
    if (!errors) return null;
    const list = Array.isArray(errors)
        ? errors
        : typeof errors === 'object'
          ? Object.values(errors).flat()
          : [String(errors)];
    const messages = list.filter(Boolean);
    if (!messages.length) return null;

    return (
        <div className={`p-4 rounded-xl bg-error-container text-on-error-container text-sm ${className}`}>
            <ul className="list-disc list-inside space-y-1">
                {messages.map((msg, i) => (
                    <li key={`${i}-${msg}`}>{msg}</li>
                ))}
            </ul>
        </div>
    );
}
