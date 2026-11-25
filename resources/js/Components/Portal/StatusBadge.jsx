const TONES = {
    primary: 'unslop-chip',
    secondary: 'unslop-chip',
    tertiary: 'unslop-chip is-ok',
    success: 'unslop-chip is-ok',
    warning: 'unslop-chip is-warn',
    error: 'unslop-chip is-warn',
    neutral: 'unslop-chip',
};

export default function StatusBadge({ children, tone = 'neutral', className = '' }) {
    return <span className={`${TONES[tone] || TONES.neutral} ${className}`.trim()}>{children}</span>;
}
