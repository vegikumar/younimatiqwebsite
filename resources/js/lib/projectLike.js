const STORAGE_KEY = 'younimatiq_community_liked_projects';

export function getStoredLikedIds() {
    try {
        const raw = localStorage.getItem(STORAGE_KEY);
        const parsed = raw ? JSON.parse(raw) : [];
        return Array.isArray(parsed) ? parsed.map(Number) : [];
    } catch {
        return [];
    }
}

export function storeLikedId(projectId) {
    const ids = getStoredLikedIds();
    if (!ids.includes(projectId)) {
        ids.push(projectId);
        localStorage.setItem(STORAGE_KEY, JSON.stringify(ids));
    }
}

export async function likeProject(projectId, csrfToken) {
    const res = await fetch(`/community/projects/${projectId}/like`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        keepalive: true,
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
        throw new Error(data.error || 'Unable to like project');
    }
    storeLikedId(Number(projectId));
    return data;
}
