import { Link } from '@inertiajs/react';
import Icon from '../Icon';

export default function QuickLinks({ items = [], title = 'Quick actions' }) {
    if (!items.length) return null;
    return (
        <section className="unslop-panel unslop-quick p-5">
            <div className="unslop-card-head">
                <div>
                    <h2>{title}</h2>
                </div>
            </div>
            <div className="unslop-shortcuts">
                {items.map((item) => (
                    <Link key={item.href} href={item.href}>
                        <Icon name={item.icon} className="icon" />
                        <span className="truncate">{item.label}</span>
                    </Link>
                ))}
            </div>
        </section>
    );
}
