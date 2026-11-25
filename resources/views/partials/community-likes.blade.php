<script>
    const likedProjectsStorageKey = 'younimatiq_community_liked_projects';

    function getStoredLikedIds() {
        try {
            const raw = localStorage.getItem(likedProjectsStorageKey);
            const parsed = raw ? JSON.parse(raw) : [];
            return Array.isArray(parsed) ? parsed.map(Number) : [];
        } catch (e) {
            return [];
        }
    }

    function storeLikedId(projectId) {
        const ids = getStoredLikedIds();
        if (!ids.includes(projectId)) {
            ids.push(projectId);
            localStorage.setItem(likedProjectsStorageKey, JSON.stringify(ids));
        }
    }

    function markProjectLiked(projectId, likesCount) {
        document.querySelectorAll('.like-btn[data-project-id="' + projectId + '"]').forEach(function (btn) {
            btn.dataset.liked = '1';
            btn.disabled = true;
            btn.setAttribute('aria-pressed', 'true');
            btn.classList.remove('text-on-surface-variant', 'hover:text-error', 'text-primary');
            btn.classList.add('text-error', 'cursor-default');
            if (typeof likesCount !== 'undefined' && likesCount !== null) {
                const countEl = btn.querySelector('.like-count');
                if (countEl) countEl.innerText = likesCount;
            }
        });
        storeLikedId(Number(projectId));
    }

    function revertOptimisticLike(projectId, previousCount) {
        document.querySelectorAll('.like-btn[data-project-id="' + projectId + '"]').forEach(function (btn) {
            btn.dataset.liked = '0';
            btn.disabled = false;
            btn.setAttribute('aria-pressed', 'false');
            btn.classList.add('text-on-surface-variant', 'hover:text-error');
            btn.classList.remove('text-error', 'cursor-default');
            const countEl = btn.querySelector('.like-count');
            if (countEl) countEl.innerText = previousCount;
        });
    }

    function incrementLike(projectId, btn) {
        if (!btn || btn.dataset.liked === '1' || btn.disabled) {
            return;
        }

        const countEl = btn.querySelector('.like-count');
        const previousCount = countEl ? parseInt(countEl.innerText.replace(/,/g, ''), 10) || 0 : 0;
        const optimisticCount = previousCount + 1;
        markProjectLiked(projectId, optimisticCount);

        fetch(`/community/projects/${projectId}/like`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            keepalive: true
        })
        .then(async (response) => {
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw new Error(data.error || 'Unable to like project');
            }
            if (typeof data.likes !== 'undefined') {
                markProjectLiked(projectId, data.likes);
            }
        })
        .catch((error) => {
            console.error('Error:', error);
            revertOptimisticLike(projectId, previousCount);
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        getStoredLikedIds().forEach(function (projectId) {
            document.querySelectorAll('.like-btn[data-project-id="' + projectId + '"]').forEach(function (btn) {
                if (btn.dataset.liked !== '1') {
                    btn.dataset.liked = '1';
                    btn.disabled = true;
                    btn.setAttribute('aria-pressed', 'true');
                    btn.classList.remove('text-on-surface-variant', 'hover:text-error', 'text-primary');
                    btn.classList.add('text-error', 'cursor-default');
                }
            });
        });
    });
</script>
