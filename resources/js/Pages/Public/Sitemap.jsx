import { Head, Link } from '@inertiajs/react';
import PublicLayout from '../../Layouts/PublicLayout';

function titleFromLoc(loc) {
    try {
        const path = new URL(loc, 'https://younimatiq.com').pathname || '/';
        if (path === '/') return 'Home';
        return path
            .replace(/^\//, '')
            .split('/')
            .map((s) => s.replace(/-/g, ' '))
            .map((s) => s.charAt(0).toUpperCase() + s.slice(1))
            .join(' / ');
    } catch {
        return loc;
    }
}

export default function Sitemap({ groups = {} }) {
    const entries = Object.entries(groups || {});

    return (
        <PublicLayout title="Sitemap">
            <Head title="Sitemap" />
            <div className="max-w-4xl mx-auto px-4 py-12 space-y-10">
                <h1 className="text-3xl font-bold">Sitemap</h1>
                {entries.map(([label, items]) => (
                    <section key={label} className="clay-card p-6">
                        <h2 className="text-xl font-bold mb-3">{label}</h2>
                        <ul className="grid sm:grid-cols-2 gap-2">
                            {(items || []).map((item) => (
                                <li key={item.loc}>
                                    <Link href={item.loc} className="text-primary hover:underline text-sm">
                                        {titleFromLoc(item.loc)}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </section>
                ))}
            </div>
        </PublicLayout>
    );
}
