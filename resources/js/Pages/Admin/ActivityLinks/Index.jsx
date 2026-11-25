import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import PageHeader from '../../../Components/Portal/PageHeader';
import PortalCard from '../../../Components/Portal/PortalCard';
import Icon from '../../../Components/Icon';

function PersonPicker({ id, label, people, picked, setPicked, emptyLabel, chipClass }) {
    const [query, setQuery] = useState('');
    const needle = query.trim().toLowerCase();
    const matches = needle.length < 2
        ? []
        : people.filter((p) => p.name.toLowerCase().includes(needle) || p.email.toLowerCase().includes(needle)).slice(0, 8);
    const chosen = people.filter((p) => picked.has(p.id));

    const toggle = (personId) => {
        setPicked((current) => {
            const next = new Set(current);
            if (next.has(personId)) next.delete(personId);
            else next.add(personId);
            return next;
        });
    };

    return (
        <div className="space-y-2">
            <label className="block text-sm font-bold" htmlFor={id}>{label}</label>
            <input
                id={id}
                value={query}
                onChange={(event) => setQuery(event.target.value)}
                placeholder="Type at least 2 letters of a name or email"
                className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary"
            />
            {matches.length ? (
                <ul className="border border-outline-variant rounded-xl divide-y">
                    {matches.map((person) => (
                        <li key={person.id}>
                            <button type="button" className="w-full text-left px-3 py-2 text-sm hover:bg-surface-container-low" onClick={() => toggle(person.id)}>
                                <span className="font-bold">{picked.has(person.id) ? 'Remove' : 'Add'}</span> {person.name}
                                <span className="text-on-surface-variant"> · {person.email}</span>
                            </button>
                        </li>
                    ))}
                </ul>
            ) : needle.length >= 2 ? (
                <p className="text-sm text-on-surface-variant">No matches.</p>
            ) : null}
            <div className="flex flex-wrap gap-2">
                {chosen.length === 0 ? (
                    <span className="text-sm text-on-surface-variant">{emptyLabel}</span>
                ) : (
                    chosen.map((person) => (
                        <button key={person.id} type="button" className={`px-2 py-1 rounded-full text-sm font-bold ${chipClass}`} onClick={() => toggle(person.id)}>
                            {person.name} ×
                        </button>
                    ))
                )}
            </div>
        </div>
    );
}

function AssignGame({ game, students, tutors, csrfToken }) {
    const [pickedStudents, setPickedStudents] = useState(() => new Set(game.assigned_ids || []));
    const [pickedTutors, setPickedTutors] = useState(() => new Set(game.assigned_tutor_ids || []));
    const [copied, setCopied] = useState(false);
    const shareUrl = game.share_url || game.preview_url;
    const tutorShareUrl = game.tutor_share_url || shareUrl;

    const copyLink = async () => {
        if (!shareUrl) return;
        try {
            await navigator.clipboard.writeText(shareUrl);
            setCopied(true);
            window.setTimeout(() => setCopied(false), 1600);
        } catch {
            window.prompt('Copy this game link:', shareUrl);
        }
    };

    return (
        <PortalCard className="p-5 space-y-3">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div className="min-w-0">
                    <h3 className="font-extrabold text-lg">{game.title}</h3>
                    <p className="text-sm text-on-surface-variant">{game.summary}</p>
                    {shareUrl ? <p className="mt-1 text-xs text-on-surface-variant break-all">Student: {shareUrl}</p> : null}
                    {tutorShareUrl ? <p className="mt-1 text-xs text-on-surface-variant break-all">Tutor: {tutorShareUrl}</p> : null}
                    <p className="mt-1 text-xs font-bold text-tertiary">Login required — React player. Students earn XP when signed in. Tutors can open by assign or by link.</p>
                </div>
                <div className="flex flex-wrap gap-2 shrink-0">
                    <button type="button" onClick={copyLink} className="px-3 py-2 border rounded-lg text-sm font-bold">
                        {copied ? 'Copied!' : 'Copy student link'}
                    </button>
                    <a href={game.preview_url || shareUrl} target="_blank" rel="noreferrer" className="px-3 py-2 border rounded-lg text-sm font-bold">Open game</a>
                </div>
            </div>
            <form method="POST" action={`/admin/activity-links/games/${game.key}/assign`} className="space-y-4">
                <input type="hidden" name="_token" value={csrfToken} />
                {[...pickedStudents].map((id) => <input key={`s-${id}`} type="hidden" name="student_ids[]" value={id} />)}
                {[...pickedTutors].map((id) => <input key={`t-${id}`} type="hidden" name="tutor_ids[]" value={id} />)}

                <PersonPicker
                    id={`students-${game.key}`}
                    label="Assign students"
                    people={students}
                    picked={pickedStudents}
                    setPicked={setPickedStudents}
                    emptyLabel="No students assigned."
                    chipClass="bg-primary/10"
                />
                <p className="text-xs text-on-surface-variant">Finished missions drop off the student list. Assigned games show under Student → Activities.</p>

                <PersonPicker
                    id={`tutors-${game.key}`}
                    label="Assign tutors"
                    people={tutors}
                    picked={pickedTutors}
                    setPicked={setPickedTutors}
                    emptyLabel="No tutors assigned."
                    chipClass="bg-secondary/10"
                />
                <p className="text-xs text-on-surface-variant">Assigned tutors see it under Tutor → Activity Links. Tutors can also open any published link while signed in.</p>

                <button type="submit" className="px-4 py-2 bg-primary text-white font-bold rounded-lg text-sm">
                    Save assignments ({pickedStudents.size} students · {pickedTutors.size} tutors)
                </button>
            </form>
        </PortalCard>
    );
}

function slugify(value) {
    return value
        .toLowerCase()
        .trim()
        .replace(/['"]/g, '')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '')
        .slice(0, 80);
}

function AssignTutors({ link, tutors, csrfToken }) {
    const [query, setQuery] = useState('');
    const [picked, setPicked] = useState(() => new Set(link.assigned_tutor_ids || []));
    const needle = query.trim().toLowerCase();
    const matches = needle.length < 2
        ? []
        : tutors.filter((tutor) => tutor.name.toLowerCase().includes(needle) || tutor.email.toLowerCase().includes(needle)).slice(0, 8);
    const chosen = tutors.filter((tutor) => picked.has(tutor.id));

    const toggle = (id) => {
        setPicked((current) => {
            const next = new Set(current);
            if (next.has(id)) next.delete(id);
            else next.add(id);
            return next;
        });
    };

    return (
        <form method="POST" action={`/admin/activity-links/${link.id}/assign-tutors`} className="space-y-3 border-t border-outline-variant/30 pt-4 mt-4">
            <input type="hidden" name="_token" value={csrfToken} />
            {[...picked].map((id) => (
                <input key={id} type="hidden" name="tutor_ids[]" value={id} />
            ))}
            <label className="block text-sm font-bold" htmlFor={`tutor-find-${link.id}`}>
                Assign to tutors
            </label>
            <p className="text-xs text-on-surface-variant">
                Tutors see assigned links under Tutor → Games (access only — they cannot assign to students).
            </p>
            <input
                id={`tutor-find-${link.id}`}
                value={query}
                onChange={(event) => setQuery(event.target.value)}
                placeholder="Type at least 2 letters of a tutor name or email"
                className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary"
            />
            {matches.length ? (
                <ul className="border border-outline-variant rounded-xl divide-y">
                    {matches.map((tutor) => (
                        <li key={tutor.id}>
                            <button type="button" className="w-full text-left px-3 py-2 text-sm hover:bg-surface-container-low" onClick={() => toggle(tutor.id)}>
                                <span className="font-bold">{picked.has(tutor.id) ? 'Remove' : 'Add'}</span> {tutor.name}
                                <span className="text-on-surface-variant"> · {tutor.email}</span>
                            </button>
                        </li>
                    ))}
                </ul>
            ) : needle.length >= 2 ? (
                <p className="text-sm text-on-surface-variant">No tutor matches that search.</p>
            ) : null}
            <div className="flex flex-wrap gap-2">
                {chosen.length === 0 ? (
                    <span className="text-sm text-on-surface-variant">No tutors assigned.</span>
                ) : (
                    chosen.map((tutor) => (
                        <button key={tutor.id} type="button" className="px-2 py-1 rounded-full bg-secondary/10 text-sm font-bold" onClick={() => toggle(tutor.id)}>
                            {tutor.name} ×
                        </button>
                    ))
                )}
            </div>
            <button type="submit" className="px-4 py-2 bg-secondary text-white font-bold rounded-lg text-sm">
                Save tutor assignments ({picked.size})
            </button>
        </form>
    );
}

export default function Index({ links = [], games = [], students = [], tutors = [], htmlGamePoints = 20 }) {
    const { csrfToken, flash, errors = {}, appUrl = '' } = usePage().props;
    const errorList = Object.values(errors).flat();
    const baseUrl = String(appUrl || '').replace(/\/$/, '');
    const [title, setTitle] = useState('');
    const [slug, setSlug] = useState('');
    const [slugTouched, setSlugTouched] = useState(false);
    const [copiedId, setCopiedId] = useState(null);

    const previewSlug = slug || 'your-link-name';

    const onTitle = (value) => {
        setTitle(value);
        if (!slugTouched) setSlug(slugify(value));
    };

    const copy = async (url, id) => {
        try {
            await navigator.clipboard.writeText(url);
            setCopiedId(id);
            window.setTimeout(() => setCopiedId((current) => (current === id ? null : current)), 1600);
        } catch {
            window.prompt('Copy this link:', url);
        }
    };

    const destroy = (id) => {
        if (!confirm('Delete this activity and its public link?')) return;
        router.delete(`/admin/activity-links/${id}`, { preserveScroll: true });
    };

    return (
        <DashboardLayout title="Activity Links">
            <Head title="Activity Links" />
            <div className="staff-page space-y-6">
                <PageHeader
                    variant="staff"
                    eyebrow="Learning"
                    title="Activity links"
                    description="Upload HTML games, then assign them to students and tutors. Tutors can also open published links while signed in."
                />

                {flash?.success ? <div className="p-4 bg-tertiary-fixed text-tertiary rounded-xl font-medium">{flash.success}</div> : null}
                {errorList.length ? (
                    <div className="p-4 bg-error-container rounded-xl text-on-error-container">
                        <ul className="list-disc pl-5 space-y-1">
                            {errorList.map((error) => <li key={error}>{error}</li>)}
                        </ul>
                    </div>
                ) : null}

                <PortalCard className="p-5 md:p-8">
                    <h2 className="text-lg font-extrabold mb-1">How to use this page</h2>
                    <p className="text-sm text-on-surface-variant mb-4">Every activity opens in React after login. Guests are redirected to sign in. XP is awarded only to signed-in students.</p>
                    <ol className="space-y-3 text-sm leading-relaxed">
                        <li><span className="font-extrabold">Create the game</span> with title, description, subject, grade, and status.</li>
                        <li><span className="font-extrabold">Add the HTML</span> by pasting code or uploading a .html file (max 10 MB). Optional thumbnail supported.</li>
                        <li><span className="font-extrabold">Share the link</span> — students must sign in to open the React player and earn XP.</li>
                    </ol>
                </PortalCard>

                <PortalCard className="p-5 md:p-6">
                    <h2 className="text-lg font-extrabold mb-3">HTML game XP</h2>
                    <form method="POST" action="/admin/activity-links/game-points" className="flex flex-wrap items-end gap-3">
                        <input type="hidden" name="_token" value={csrfToken} />
                        <div>
                            <label className="block text-sm font-bold mb-2" htmlFor="html-game-points">Points per day (0–1000)</label>
                            <input
                                id="html-game-points"
                                type="number"
                                name="html_game_points"
                                min="0"
                                max="1000"
                                defaultValue={htmlGamePoints}
                                className="w-40 p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary"
                            />
                        </div>
                        <button type="submit" className="px-4 py-3 bg-primary text-white font-bold rounded-lg text-sm">Save XP</button>
                    </form>
                </PortalCard>

                <section className="space-y-4">
                    <h2 className="text-lg font-extrabold">Shareable games</h2>
                    <p className="text-sm text-on-surface-variant">Assign to students (Activities) and tutors (Activity Links). Tutors can also open by share link.</p>
                    <div className="grid grid-cols-1 xl:grid-cols-2 gap-4">
                        {games.map((game) => (
                            <AssignGame key={game.key} game={game} students={students} tutors={tutors} csrfToken={csrfToken} />
                        ))}
                    </div>
                </section>

                <div className="grid grid-cols-1 xl:grid-cols-5 gap-6">
                    <PortalCard className="p-5 md:p-8 xl:col-span-2">
                        <h2 className="text-lg font-extrabold mb-4 flex items-center gap-2">
                            <Icon name="upload_file" className="text-primary" /> Upload HTML game
                        </h2>
                        <form method="POST" action="/admin/activity-links" encType="multipart/form-data" className="space-y-4">
                            <input type="hidden" name="_token" value={csrfToken} />
                            <div>
                                <label className="block text-sm font-bold mb-2" htmlFor="activity-title">Title</label>
                                <input
                                    id="activity-title"
                                    name="title"
                                    value={title}
                                    onChange={(event) => onTitle(event.target.value)}
                                    required
                                    placeholder="Math Dash"
                                    className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none"
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-bold mb-2" htmlFor="activity-description">Description</label>
                                <textarea
                                    id="activity-description"
                                    name="description"
                                    rows={3}
                                    placeholder="Short blurb for students"
                                    className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none"
                                />
                            </div>
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label className="block text-sm font-bold mb-2" htmlFor="activity-subject">Subject</label>
                                    <input id="activity-subject" name="subject" placeholder="Math" className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary" />
                                </div>
                                <div>
                                    <label className="block text-sm font-bold mb-2" htmlFor="activity-grade">Grade</label>
                                    <input id="activity-grade" name="grade" placeholder="1-3" className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary" />
                                </div>
                            </div>
                            <input type="hidden" name="access" value="members" />
                            <div>
                                <label className="block text-sm font-bold mb-2" htmlFor="activity-status">Status</label>
                                <select id="activity-status" name="status" defaultValue="published" className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary">
                                    <option value="published">Published</option>
                                    <option value="draft">Draft</option>
                                </select>
                                <p className="mt-1 text-xs text-on-surface-variant">Login required to open and earn XP.</p>
                            </div>
                            <div>
                                <label className="block text-sm font-bold mb-2" htmlFor="activity-slug">Link name</label>
                                <input
                                    id="activity-slug"
                                    name="slug"
                                    value={slug}
                                    onChange={(event) => {
                                        setSlugTouched(true);
                                        setSlug(slugify(event.target.value));
                                    }}
                                    placeholder="math-dash"
                                    className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none"
                                />
                                <p className="mt-2 text-xs text-on-surface-variant break-all">
                                    Link: {baseUrl || 'https://younimatiq.com'}/{previewSlug}
                                </p>
                            </div>
                            <div>
                                <label className="block text-sm font-bold mb-2" htmlFor="activity-html">HTML code</label>
                                <textarea
                                    id="activity-html"
                                    name="html"
                                    rows={10}
                                    spellCheck={false}
                                    placeholder={'<!DOCTYPE html>\n<html>\n...\n</html>'}
                                    className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none font-mono text-xs leading-5"
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-bold mb-2" htmlFor="activity-file">Or HTML file</label>
                                <input id="activity-file" type="file" name="file" accept=".html,.htm,text/html" className="block w-full text-sm" />
                            </div>
                            <div>
                                <label className="block text-sm font-bold mb-2" htmlFor="activity-thumb">Thumbnail</label>
                                <input id="activity-thumb" type="file" name="thumbnail" accept="image/*" className="block w-full text-sm" />
                            </div>
                            <button type="submit" className="btn-primary btn-block">Upload &amp; create link</button>
                        </form>
                    </PortalCard>

                    <div className="xl:col-span-3 space-y-4">
                        {links.length === 0 ? (
                            <PortalCard className="p-8 text-center text-on-surface-variant">No activity links yet. Create the first one on the left.</PortalCard>
                        ) : links.map((link) => (
                            <PortalCard key={link.id} className="p-5">
                                <form method="POST" action={`/admin/activity-links/${link.id}`} encType="multipart/form-data" className="space-y-3">
                                    <input type="hidden" name="_token" value={csrfToken} />
                                    <input type="hidden" name="_method" value="PUT" />
                                    <div className="flex flex-wrap items-start justify-between gap-3">
                                        <div className="min-w-0 flex gap-3">
                                            {link.thumbnail_url ? (
                                                <img src={link.thumbnail_url} alt="" className="w-14 h-14 rounded-xl object-cover border border-outline-variant" />
                                            ) : null}
                                            <div className="min-w-0">
                                                <p className="text-xs font-bold uppercase tracking-wide text-on-surface-variant">
                                                    {link.status === 'published' ? 'Published' : 'Draft'}
                                                    {' · Login required · React player'}
                                                </p>
                                                <a href={link.url} target="_blank" rel="noreferrer" className="font-bold text-primary break-all">{link.url}</a>
                                                <p className="text-xs text-on-surface-variant mt-1">
                                                    {[link.subject, link.grade].filter(Boolean).join(' · ') || 'HTML activity'}
                                                    {link.updated_at ? ` · Updated ${link.updated_at}` : ''}
                                                </p>
                                            </div>
                                        </div>
                                        <button type="button" onClick={() => copy(link.url, link.id)} className="px-3 py-2 border rounded-lg text-sm font-bold">
                                            {copiedId === link.id ? 'Copied' : 'Copy link'}
                                        </button>
                                    </div>
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        <input name="title" defaultValue={link.title} required className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl" aria-label="Title" />
                                        <input name="slug" defaultValue={link.slug} required className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl" aria-label="Link name" />
                                    </div>
                                    <textarea name="description" rows={2} defaultValue={link.description || ''} placeholder="Description" className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl" aria-label="Description" />
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        <input name="subject" defaultValue={link.subject || ''} placeholder="Subject" className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl" aria-label="Subject" />
                                        <input name="grade" defaultValue={link.grade || ''} placeholder="Grade" className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl" aria-label="Grade" />
                                    </div>
                                    <input type="hidden" name="access" value="members" />
                                    <select name="status" defaultValue={link.status || 'published'} className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl" aria-label="Status">
                                        <option value="published">Published</option>
                                        <option value="draft">Draft</option>
                                    </select>
                                    <div>
                                        <label className="block text-sm font-bold mb-2" htmlFor={`html-${link.id}`}>Replace HTML (optional)</label>
                                        <textarea
                                            id={`html-${link.id}`}
                                            name="html"
                                            rows={6}
                                            spellCheck={false}
                                            defaultValue=""
                                            placeholder="Leave blank to keep the current protected game. Paste new HTML only if replacing."
                                            className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl font-mono text-xs leading-5 outline-none focus:border-primary"
                                        />
                                        <p className="mt-1 text-xs text-on-surface-variant">Source is stored on the server only — it is not reloaded into this form.</p>
                                    </div>
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        <div>
                                            <label className="block text-sm font-bold mb-2">Replace HTML file</label>
                                            <input type="file" name="file" accept=".html,.htm,text/html" className="block w-full text-sm" />
                                        </div>
                                        <div>
                                            <label className="block text-sm font-bold mb-2">Thumbnail</label>
                                            <input type="file" name="thumbnail" accept="image/*" className="block w-full text-sm" />
                                            {link.thumbnail_url ? (
                                                <label className="mt-2 inline-flex items-center gap-2 text-xs font-bold">
                                                    <input type="checkbox" name="remove_thumbnail" value="1" />
                                                    Remove current thumbnail
                                                </label>
                                            ) : null}
                                        </div>
                                    </div>
                                    <div className="flex flex-wrap items-center gap-3">
                                        <button type="submit" className="px-4 py-2 bg-primary text-white font-bold rounded-lg text-sm">Save</button>
                                        <button type="button" onClick={() => destroy(link.id)} className="px-4 py-2 border text-error font-bold rounded-lg text-sm">Delete</button>
                                    </div>
                                </form>
                                <AssignTutors link={link} tutors={tutors} csrfToken={csrfToken} />
                            </PortalCard>
                        ))}
                    </div>
                </div>
            </div>
        </DashboardLayout>
    );
}
