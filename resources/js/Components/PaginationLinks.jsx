import { Link } from '@inertiajs/react';

export default function PaginationLinks({ links = [] }) {
    if (!links?.length) return null;

    return (
        <div className="mt-8 flex flex-wrap justify-center gap-2">
            {links.map((link, i) => (
                <Link
                    key={i}
                    href={link.url || '#'}
                    className={`px-3 py-1.5 rounded-lg text-sm font-bold border ${
                        link.active ? 'bg-primary text-white border-primary' : 'bg-white'
                    } ${!link.url ? 'pointer-events-none opacity-40' : ''}`}
                    dangerouslySetInnerHTML={{ __html: link.label }}
                />
            ))}
        </div>
    );
}
