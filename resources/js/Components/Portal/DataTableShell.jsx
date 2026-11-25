export default function DataTableShell({
    children,
    className = '',
    toolbar = null,
    mobileCards = null,
    minWidth = '640px',
}) {
    return (
        <div className={`min-w-0 ${className}`.trim()}>
            {toolbar ? <div className="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">{toolbar}</div> : null}
            {mobileCards ? <div className="space-y-3 md:hidden">{mobileCards}</div> : null}
            <div className={`overflow-x-auto overscroll-x-contain -mx-1 px-1 ${mobileCards ? 'hidden md:block' : ''}`.trim()}>
                <div style={{ minWidth }} className="min-w-0">
                    {children}
                </div>
            </div>
        </div>
    );
}
