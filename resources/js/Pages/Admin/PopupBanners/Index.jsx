import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import PortalCard from '../../../Components/Portal/PortalCard';
import Icon from '../../../Components/Icon';

function BannerForm({ csrfToken, banner = null, specs }) {
    const editing = Boolean(banner);
    const action = editing ? `/admin/popup-banners/${banner.id}` : '/admin/popup-banners';

    return (
        <PortalCard className="p-5 md:p-8 space-y-4">
            <h2 className="font-headline-md font-bold flex items-center gap-2">
                <Icon name={editing ? 'edit' : 'add_photo_alternate'} className="text-primary" />
                {editing ? 'Edit banner' : 'Create popup banner'}
            </h2>

            <div className="rounded-xl border border-outline-variant bg-surface-container-low p-4 text-sm space-y-2">
                <p className="font-bold">Image size guide</p>
                <ul className="list-disc pl-5 space-y-1 text-on-surface-variant">
                    <li><span className="font-bold text-on-surface">Best:</span> {specs.recommended}</li>
                    <li><span className="font-bold text-on-surface">Wide offer:</span> {specs.landscape}</li>
                    <li><span className="font-bold text-on-surface">Square:</span> {specs.square}</li>
                    <li><span className="font-bold text-on-surface">Format:</span> {specs.formats} · max {specs.max_size}</li>
                    <li>{specs.safe_zone}</li>
                </ul>
            </div>

            <form method="POST" action={action} encType="multipart/form-data" className="space-y-4">
                <input type="hidden" name="_token" value={csrfToken} />
                {editing ? <input type="hidden" name="_method" value="PUT" /> : null}

                <div>
                    <label className="block text-sm font-bold mb-2" htmlFor={`title-${banner?.id || 'new'}`}>Title (admin only)</label>
                    <input
                        id={`title-${banner?.id || 'new'}`}
                        name="title"
                        required
                        defaultValue={banner?.title || ''}
                        placeholder="Summer 20% off"
                        className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary"
                    />
                </div>

                <div>
                    <label className="block text-sm font-bold mb-2" htmlFor={`image-${banner?.id || 'new'}`}>
                        Banner image {editing ? '(leave empty to keep current)' : ''}
                    </label>
                    <input
                        id={`image-${banner?.id || 'new'}`}
                        type="file"
                        name="image"
                        accept="image/jpeg,image/png,image/webp"
                        required={!editing}
                        className="block w-full text-sm"
                    />
                    {banner?.image_url ? (
                        <img src={banner.image_url} alt="" className="mt-3 max-h-40 rounded-xl border border-outline-variant object-contain bg-white" />
                    ) : null}
                </div>

                <div>
                    <label className="block text-sm font-bold mb-2" htmlFor={`link-${banner?.id || 'new'}`}>Redirect link (optional)</label>
                    <input
                        id={`link-${banner?.id || 'new'}`}
                        type="url"
                        name="link_url"
                        defaultValue={banner?.link_url || ''}
                        placeholder="https://younimatiq.com/pricing"
                        className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary"
                    />
                    <p className="mt-1 text-xs text-on-surface-variant">If set, clicking the banner opens this URL. Leave blank for image-only (coupon display).</p>
                </div>

                <div>
                    <label className="block text-sm font-bold mb-2" htmlFor={`coupon-${banner?.id || 'new'}`}>Coupon code to show (optional)</label>
                    <input
                        id={`coupon-${banner?.id || 'new'}`}
                        name="coupon_code"
                        defaultValue={banner?.coupon_code || ''}
                        placeholder="SAVE20"
                        className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary uppercase"
                    />
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label className="block text-sm font-bold mb-2" htmlFor={`delay-${banner?.id || 'new'}`}>Show after (seconds)</label>
                        <input
                            id={`delay-${banner?.id || 'new'}`}
                            type="number"
                            name="delay_seconds"
                            min="0"
                            max="30"
                            defaultValue={banner?.delay_seconds ?? 2}
                            className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary"
                        />
                    </div>
                    <div>
                        <label className="block text-sm font-bold mb-2" htmlFor={`sort-${banner?.id || 'new'}`}>Priority (lower first)</label>
                        <input
                            id={`sort-${banner?.id || 'new'}`}
                            type="number"
                            name="sort_order"
                            min="0"
                            max="9999"
                            defaultValue={banner?.sort_order ?? 0}
                            className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary"
                        />
                    </div>
                    <div className="flex flex-col justify-end gap-2 pb-1">
                        <label className="inline-flex items-center gap-2 text-sm font-bold">
                            <input type="checkbox" name="is_active" value="1" defaultChecked={banner ? banner.is_active : true} />
                            Active
                        </label>
                        <label className="inline-flex items-center gap-2 text-sm font-bold">
                            <input type="checkbox" name="show_once" value="1" defaultChecked={banner ? banner.show_once : true} />
                            Hide after close (per visitor)
                        </label>
                    </div>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label className="block text-sm font-bold mb-2" htmlFor={`starts-${banner?.id || 'new'}`}>Starts at (optional)</label>
                        <input
                            id={`starts-${banner?.id || 'new'}`}
                            type="datetime-local"
                            name="starts_at"
                            defaultValue={banner?.starts_at || ''}
                            className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary"
                        />
                    </div>
                    <div>
                        <label className="block text-sm font-bold mb-2" htmlFor={`ends-${banner?.id || 'new'}`}>Ends at (optional)</label>
                        <input
                            id={`ends-${banner?.id || 'new'}`}
                            type="datetime-local"
                            name="ends_at"
                            defaultValue={banner?.ends_at || ''}
                            className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary"
                        />
                    </div>
                </div>

                <button type="submit" className="btn-primary btn-block">
                    {editing ? 'Save changes' : 'Publish popup banner'}
                </button>
            </form>
        </PortalCard>
    );
}

export default function Index({ banners = [], specs }) {
    const { flash, csrfToken, errors = {} } = usePage().props;
    const [editingId, setEditingId] = useState(null);
    const editing = banners.find((b) => b.id === editingId) || null;
    const errorList = Object.values(errors || {}).flat();

    const destroy = (id) => {
        if (!confirm('Delete this popup banner?')) return;
        router.delete(`/admin/popup-banners/${id}`, { preserveScroll: true });
    };

    return (
        <DashboardLayout title="Popup Banners">
            <Head title="Popup Banners" />
            <div className="staff-page space-y-6">
                <PortalCard className="p-5 md:p-8 border-t-8 border-primary relative overflow-hidden">
                    <h1 className="font-headline-xl text-headline-xl text-on-surface mb-2">Popup Banners</h1>
                    <p className="font-body-lg text-body-lg text-on-surface-variant">
                        Offer and coupon popups for the public website. Upload artwork, set an optional redirect link, and schedule when it shows.
                    </p>
                </PortalCard>

                {flash?.success ? <div className="p-4 bg-tertiary-fixed text-tertiary rounded-xl font-medium">{flash.success}</div> : null}
                {errorList.length ? (
                    <div className="p-4 bg-error-container rounded-xl text-on-error-container">
                        <ul className="list-disc pl-5 space-y-1">
                            {errorList.map((error) => <li key={error}>{error}</li>)}
                        </ul>
                    </div>
                ) : null}

                <div className="grid grid-cols-1 xl:grid-cols-5 gap-6">
                    <div className="xl:col-span-2">
                        <BannerForm csrfToken={csrfToken} specs={specs} banner={editing} />
                        {editing ? (
                            <button
                                type="button"
                                className="mt-3 text-sm font-bold text-primary"
                                onClick={() => setEditingId(null)}
                            >
                                Cancel edit · create new instead
                            </button>
                        ) : null}
                    </div>

                    <div className="xl:col-span-3 space-y-4">
                        {banners.length === 0 ? (
                            <PortalCard className="p-8 text-center text-on-surface-variant">No popup banners yet. Create one on the left.</PortalCard>
                        ) : banners.map((banner) => (
                            <PortalCard key={banner.id} className="p-5">
                                <div className="flex flex-wrap gap-4 items-start justify-between">
                                    <div className="flex gap-4 min-w-0">
                                        {banner.image_url ? (
                                            <img src={banner.image_url} alt="" className="w-24 h-32 object-cover rounded-xl border border-outline-variant bg-white shrink-0" />
                                        ) : null}
                                        <div className="min-w-0 space-y-1">
                                            <p className="text-xs font-bold uppercase tracking-wide text-on-surface-variant">
                                                {banner.is_live ? 'Live on site' : banner.is_active ? 'Scheduled / inactive window' : 'Off'}
                                            </p>
                                            <h3 className="font-extrabold text-lg">{banner.title}</h3>
                                            {banner.coupon_code ? <p className="text-sm font-mono font-bold text-primary">Code: {banner.coupon_code}</p> : null}
                                            {banner.link_url ? (
                                                <a href={banner.link_url} target="_blank" rel="noreferrer" className="text-xs text-primary break-all">{banner.link_url}</a>
                                            ) : (
                                                <p className="text-xs text-on-surface-variant">No redirect link</p>
                                            )}
                                            <p className="text-xs text-on-surface-variant">
                                                Delay {banner.delay_seconds}s · Priority {banner.sort_order}
                                                {banner.show_once ? ' · Hide after close' : ' · Show every visit'}
                                                {banner.updated_at ? ` · Updated ${banner.updated_at}` : ''}
                                            </p>
                                        </div>
                                    </div>
                                    <div className="flex gap-2 shrink-0">
                                        <button type="button" onClick={() => setEditingId(banner.id)} className="px-3 py-2 border rounded-lg text-sm font-bold">Edit</button>
                                        <button type="button" onClick={() => destroy(banner.id)} className="px-3 py-2 border rounded-lg text-sm font-bold text-error">Delete</button>
                                    </div>
                                </div>
                            </PortalCard>
                        ))}
                    </div>
                </div>
            </div>
        </DashboardLayout>
    );
}
