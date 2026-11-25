import { createContext, useCallback, useContext, useRef, useState } from 'react';

const GameXpContext = createContext({
    claim: async () => null,
    note: null,
});

export function GameXpProvider({ gameKey, already = null, children }) {
    const [note, setNote] = useState(already?.awarded
        ? { awarded: false, already: true, points: already.points }
        : null);
    const sent = useRef(Boolean(already?.awarded));

    const claim = useCallback(async (points) => {
        if (sent.current) {
            setNote((current) => current || { awarded: false, already: true, points: already?.points || 0 });
            return;
        }

        const earned = Number(points) || 0;
        if (earned < 1) {
            setNote({
                awarded: false,
                already: false,
                points: 0,
                message: 'Earn at least 1 point, then play again to add XP.',
            });
            return;
        }

        sent.current = true;
        const token = document.querySelector('meta[name="csrf-token"]')?.content;
        try {
            const response = await fetch(`/student/games/${gameKey}/xp`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': token || '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ points: earned }),
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                sent.current = false;
                setNote({
                    awarded: false,
                    already: false,
                    points: 0,
                    message: data.message || 'XP could not be added. Finish the game and try again.',
                });
                return;
            }
            setNote({
                awarded: Boolean(data.awarded),
                already: Boolean(data.already),
                points: Number(data.points) || earned,
            });
        } catch {
            sent.current = false;
            setNote({
                awarded: false,
                already: false,
                points: 0,
                message: 'XP could not be added. Check your connection and finish the game again.',
            });
        }
    }, [already?.points, gameKey]);

    return (
        <GameXpContext.Provider value={{ claim, note }}>
            {children}
        </GameXpContext.Provider>
    );
}

export function useGameXp() {
    return useContext(GameXpContext);
}

export function GameXpNote() {
    const { note } = useGameXp();
    if (!note) return null;

    const text = note.awarded
        ? `+${note.points} XP added. This game adds XP only once.`
        : note.already
            ? `You already added ${note.points} XP from this game. Playing again does not add more.`
            : note.message;

    if (!text) return null;

    return (
        <p style={{ margin: '12px 0 0', padding: '10px 12px', borderRadius: 12, background: '#F6F1FF', color: '#45198C', fontWeight: 800, fontSize: 14, lineHeight: 1.4 }}>
            {text}
        </p>
    );
}
