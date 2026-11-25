import { usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import Icon from '../Icon';
import { getStoredLikedIds, likeProject } from '../../lib/projectLike';

function ProjectCard({ project, liked, onLike, duplicate }) {
    return (
        <div className="clay-card flex flex-col overflow-hidden hover:scale-105 transition-all duration-300 group !p-0 flex-none w-[280px] sm:w-[320px] md:w-[350px] snap-start border border-outline-variant shadow-[0_4px_24px_rgba(0,0,0,0.03)] shrink-0 bg-white mx-3">
            <div className="w-full pt-[56.25%] relative overflow-hidden bg-black">
                {project.media_url ? (
                    project.is_image ? (
                        <img loading="lazy" src={project.media_url} width="350" height="197" className="absolute top-0 left-0 w-full h-full object-cover" alt={project.title} />
                    ) : (
                        <video
                            src={project.media_url}
                            preload="metadata"
                            muted
                            loop
                            playsInline
                            className="absolute top-0 left-0 w-full h-full object-contain bg-black"
                            tabIndex={duplicate ? -1 : 0}
                        />
                    )
                ) : (
                    <div className="absolute top-0 left-0 w-full h-full flex items-center justify-center text-white/50">
                        <Icon name="play_circle" className="text-4xl" />
                    </div>
                )}
            </div>
            <div className="p-5 flex flex-col justify-between flex-1 bg-white">
                <div>
                    <h3 className="text-xl font-bold text-on-surface mb-1">{project.title}</h3>
                    <p className="text-primary text-sm font-semibold mb-2">{project.category}</p>
                    <p className="text-on-surface-variant text-sm">
                        Created by: <span className="font-bold">{project.author_name}</span>
                    </p>
                </div>
                <div className="mt-4 flex items-center justify-between">
                    <div className="flex items-center gap-4 text-on-surface-variant text-sm">
                        <div className="flex items-center gap-1" title="Views">
                            <Icon name="visibility" className="text-base" />
                            <span>{Number(project.views_count || 0).toLocaleString()}</span>
                        </div>
                        <button
                            type="button"
                            className={`flex items-center gap-1 transition-colors ${liked ? 'text-error cursor-default' : 'text-on-surface-variant hover:text-error'}`}
                            disabled={liked}
                            aria-pressed={liked}
                            onClick={() => onLike(project.id)}
                            tabIndex={duplicate ? -1 : 0}
                        >
                            <Icon name="favorite" className="text-base" style={{ fontVariationSettings: "'FILL' 1" }} />
                            <span>{Number(project.likes_count || 0).toLocaleString()}</span>
                        </button>
                    </div>
                    {project.project_url ? (
                        <a
                            href={project.project_url}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="flex items-center gap-1 text-primary font-bold text-sm group-hover:translate-x-1 transition-transform"
                            tabIndex={duplicate ? -1 : 0}
                        >
                            View Project <Icon name="arrow_forward" className="text-base" />
                        </a>
                    ) : null}
                </div>
            </div>
        </div>
    );
}

export default function FeaturedProjects({ projects = [], likedProjectIds = [] }) {
    const { csrfToken } = usePage().props;
    const stored = useMemo(() => getStoredLikedIds(), []);
    const [liked, setLiked] = useState(() => new Set([...likedProjectIds, ...stored]));
    const [counts, setCounts] = useState(() =>
        Object.fromEntries(projects.map((p) => [p.id, p.likes_count ?? 0]))
    );

    const onLike = async (projectId) => {
        if (liked.has(projectId)) return;
        setLiked((prev) => new Set(prev).add(projectId));
        setCounts((c) => ({ ...c, [projectId]: (c[projectId] ?? 0) + 1 }));
        try {
            const data = await likeProject(projectId, csrfToken);
            if (typeof data.likes !== 'undefined') {
                setCounts((c) => ({ ...c, [projectId]: data.likes }));
            }
        } catch {
            setLiked((prev) => {
                const n = new Set(prev);
                n.delete(projectId);
                return n;
            });
            setCounts((c) => ({ ...c, [projectId]: Math.max(0, (c[projectId] ?? 1) - 1) }));
        }
    };

    if (!projects.length) {
        return <p className="text-center text-on-surface-variant">Check back soon for our students&apos; amazing creations!</p>;
    }

    const enriched = projects.map((p) => ({ ...p, likes_count: counts[p.id] ?? p.likes_count }));

    return (
        <>
            <style>{`
                #projects .marquee-wrapper { display:flex; overflow:hidden; width:100%; mask-image:linear-gradient(to right, transparent, black 5%, black 95%, transparent); }
                #projects .marquee-track { display:flex; flex-wrap:nowrap; gap:1.5rem; animation:projects-marquee-slow 55s linear infinite; padding:1rem 0; width:max-content; }
                #projects .marquee-group { display:flex; flex-wrap:nowrap; gap:1.5rem; flex-shrink:0; }
                #projects .marquee-wrapper:hover .marquee-track { animation-play-state:paused; }
                @keyframes projects-marquee-slow { 0%{transform:translateX(0)} 100%{transform:translateX(-50%)} }
            `}</style>
            <div className="marquee-wrapper mt-4">
                <div className="marquee-track">
                    {[false, true].map((dup) => (
                        <div key={dup ? 'b' : 'a'} className="marquee-group" aria-hidden={dup || undefined}>
                            {enriched.map((project) => (
                                <ProjectCard
                                    key={`${dup}-${project.id}`}
                                    project={project}
                                    liked={liked.has(project.id)}
                                    onLike={onLike}
                                    duplicate={dup}
                                />
                            ))}
                        </div>
                    ))}
                </div>
            </div>
        </>
    );
}
