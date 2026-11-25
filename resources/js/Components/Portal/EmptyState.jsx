import Icon from '../Icon';

export default function EmptyState({ icon = 'inbox', title, description, action = null }) {
    return (
        <div className="rounded-2xl border border-dashed border-[var(--u-hairline,rgba(31,27,22,0.12))] bg-[var(--u-card,#fff)] px-5 py-6 md:px-6 md:py-8 text-center">
            <div className="mx-auto mb-4 grid h-14 w-14 place-items-center rounded-2xl bg-[var(--u-raised,#efe9df)] text-[var(--u-muted,#7b7266)]">
                <Icon name={icon} className="text-3xl" />
            </div>
            <h3 className="text-base font-semibold text-[var(--u-ink,#1f1b16)] mb-1">{title}</h3>
            {description ? <p className="text-sm text-[var(--u-secondary,#5e554a)] max-w-md mx-auto">{description}</p> : null}
            {action ? <div className="mt-5 flex justify-center">{action}</div> : null}
        </div>
    );
}
