import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { GameXpNote, useGameXp } from './GameXp';
import './styles/inequalities-practice-worksheet.css';

const STORAGE_KEY = 'yq-ineq-ws';

const S = [
    { sec: 'A', n: 1, t: 'pick', q: 'Choose &lt; or &gt; : &nbsp;12 ___ 7', opts: ['<', '>'], a: '>', h: 'The open side faces the bigger number.', why: '12 > 7, because 12 is bigger.' },
    { sec: 'A', n: 2, t: 'pick', q: 'Choose &lt; or &gt; : &nbsp;−6 ___ −2', opts: ['<', '>'], a: '<', h: 'Which one is further left on the number line?', why: '−6 < −2, because −6 is further left.' },
    { sec: 'A', n: 3, t: 'text', q: 'Write as an inequality: "p is at least 15"', a: ['p≥15', '15≤p'], h: '"At least" means 15 is allowed.', why: 'p ≥ 15' },
    { sec: 'A', n: 4, t: 'text', q: 'Write as an inequality, using w: "The bag can hold at most 8 kg"', a: ['w≤8', '8≥w'], h: '"At most" means 8 is allowed.', why: 'w ≤ 8' },
    { sec: 'B', n: 5, t: 'nl', q: 'Draw: x &gt; 1', a: { p: [[1, 0]], d: 'right' }, h: 'Is 1 included? Which way are the bigger numbers?', why: 'Open circle at 1, arrow right.' },
    { sec: 'B', n: 6, t: 'nl', q: 'Draw: x ≤ −2', a: { p: [[-2, 1]], d: 'left' }, h: '≤ includes −2, so use a filled circle.', why: 'Filled circle at −2, arrow left.' },
    { sec: 'B', n: 7, t: 'nl', q: 'Draw: −3 &lt; x ≤ 2', a: { p: [[-3, 0], [2, 1]], d: 'between' }, h: 'Two circles. −3 is not included; 2 is.', why: 'Open circle at −3, filled circle at 2, line between.' },
    { sec: 'B', n: 8, t: 'text', q: 'Write the inequality shown on this number line.', show: { p: [[0, 1]], d: 'right' }, a: ['x≥0', '0≤x'], h: 'Filled circle means the number is included.', why: 'x ≥ 0' },
    { sec: 'C', n: 9, t: 'text', q: 'Solve: x + 4 &gt; 10', a: ['x>6', '6<x'], h: 'Subtract 4 from both sides.', why: 'x > 6' },
    { sec: 'C', n: 10, t: 'text', q: 'Solve: y − 7 ≤ 3', a: ['y≤10', '10≥y'], h: 'Add 7 to both sides.', why: 'y ≤ 10' },
    { sec: 'C', n: 11, t: 'text', q: 'Solve: 6m ≥ 42', a: ['m≥7', '7≤m'], h: 'Divide both sides by 6.', why: 'm ≥ 7' },
    { sec: 'C', n: 12, t: 'text', q: 'Solve: n ÷ 5 &lt; 3', a: ['n<15', '15>n'], h: 'Multiply both sides by 5.', why: 'n < 15' },
    { sec: 'D', n: 13, t: 'text', q: 'Solve: 3x + 2 &lt; 17', a: ['x<5', '5>x'], h: 'Subtract 2 first, then divide by 3.', why: '3x < 15, so x < 5' },
    { sec: 'D', n: 14, t: 'text', q: 'Solve: 4y − 5 ≥ 11', a: ['y≥4', '4≤y'], h: 'Add 5 first, then divide by 4.', why: '4y ≥ 16, so y ≥ 4' },
    { sec: 'D', n: 15, t: 'text', q: 'Solve: −2a &gt; 14', a: ['a<-7', '-7>a'], h: 'You divide by −2. What happens to the sign?', why: 'Divide by −2 and flip: a < −7' },
    { sec: 'D', n: 16, t: 'text', q: 'Solve: 12 − 3k ≤ 0', a: ['k≥4', '4≤k'], h: 'Subtract 12, then divide by −3 and flip.', why: '−3k ≤ −12, so k ≥ 4' },
    { sec: 'D', n: 17, t: 'text', q: 'Solve: x ÷ (−4) ≥ 2', a: ['x≤-8', '-8≥x'], h: 'Multiply both sides by −4. Flip!', why: 'Multiply by −4 and flip: x ≤ −8' },
    { sec: 'E', n: 18, t: 'multi', q: 'Solve 5 ≤ 2x + 3 &lt; 13. Then list all the whole numbers that fit.', f: [{ l: 'Answer', a: ['1≤x<5'] }, { l: 'Whole numbers (e.g. 1, 2, 3)', a: ['1,2,3,4'], list: 1 }], h: 'Subtract 3 from all three parts, then divide all three by 2.', why: '1 ≤ x < 5, whole numbers 1, 2, 3, 4' },
    { sec: 'E', n: 19, t: 'multi', q: 'A taxi charges $4 to start plus $3 for every km. You have at most $25. Write an inequality using d for km, and find the greatest number of whole km you can travel.', f: [{ l: 'Inequality', a: ['4+3d≤25', '3d+4≤25', '25≥4+3d', '25≥3d+4'] }, { l: 'Greatest whole km', a: ['7', '7km'] }], h: 'Start cost + cost per km × d must be at most 25.', why: '4 + 3d ≤ 25, so 3d ≤ 21 and d ≤ 7: 7 km' },
    { sec: 'E', n: 20, t: 'text', q: 'Spot the error: Riya solved −5x &lt; 20 and wrote x &lt; −4. Test x = 0, then write the correct answer.', a: ['x>-4', '-4<x'], h: 'x = 0 gives 0 < 20, which is true. So 0 must be in the answer.', why: 'Dividing by −5 flips the sign: x > −4' },
];

const SEC = {
    A: ['Section A · Symbols and words', 'Sums 1–4', 'Choose the symbol, or write the words as an inequality.', 'blue'],
    B: ['Section B · Number lines', 'Sums 5–8', 'Pick ○ or ●, tap a number to place a circle, then choose the arrow. Tap a circle again to remove it.', 'orange'],
    C: ['Section C · One-step inequalities', 'Sums 9–12', 'Solve each inequality.', 'green'],
    D: ['Section D · Two-step and negative inequalities', 'Sums 13–17', '<b>Remember:</b> multiplying or dividing by a negative number flips the sign.', 'purple'],
    E: ['Section E · Challenge', 'Sums 18–20', 'Take your time and check with a test number.', 'navy'],
};

const SECTION_KEYS = ['A', 'B', 'C', 'D', 'E'];

const SYMBOL_KEYS = [
    { k: '<', label: '<' },
    { k: '>', label: '>' },
    { k: '≤', label: '≤' },
    { k: '≥', label: '≥' },
    { k: '−', label: '−' },
];

const emptyQuestionState = () => ({
    tries: 0,
    ok: false,
    shown: false,
    pick: null,
    pts: [],
    dir: null,
    tool: 0,
});

function createInitialSt() {
    return S.map(() => emptyQuestionState());
}

function norm(s) {
    return s
        .toLowerCase()
        .replace(/\s+/g, '')
        .replace(/[−–]/g, '-')
        .replace(/>=|=>/g, '≥')
        .replace(/<=|=</g, '≤')
        .replace(/and/g, ',')
        .replace(/\.$/, '');
}

function xPos(v) {
    return 30 + (v + 5) * 36;
}

function buildFlatInputSlots() {
    return S.map((x, i) => {
        if (x.t === 'text') return [{ q: i, f: 0 }];
        if (x.t === 'multi') return x.f.map((_, j) => ({ q: i, f: j }));
        return [];
    }).flat();
}

const INPUT_SLOTS = buildFlatInputSlots();

function getVal(vals, qIndex, fieldIndex) {
  const slot = INPUT_SLOTS.findIndex((s) => s.q === qIndex && s.f === fieldIndex);
    if (slot < 0) return '';
    return vals[slot] ?? '';
}

function setVal(vals, qIndex, fieldIndex, value) {
    const next = [...vals];
    const slot = INPUT_SLOTS.findIndex((s) => s.q === qIndex && s.f === fieldIndex);
    if (slot >= 0) next[slot] = value;
    return next;
}

function isRight(x, i, st, vals) {
    if (x.t === 'pick') return st[i].pick === x.a;
    if (x.t === 'text') {
        const v = norm(getVal(vals, i, 0));
        return x.a.includes(v);
    }
    if (x.t === 'multi') {
        return x.f.every((field, j) => {
            let v = norm(getVal(vals, i, j));
            if (field.list) {
                v = v
                    .split(/[,;]/)
                    .filter(Boolean)
                    .sort((a, b) => a.localeCompare(b))
                    .join(',');
            }
            return field.a.includes(v);
        });
    }
    if (x.t === 'nl') {
        const a = x.a;
        const p = [...st[i].pts].sort((m, n) => m[0] - n[0]);
        return (
            st[i].dir === a.d
            && p.length === a.p.length
            && p.every((q, k) => q[0] === a.p[k][0] && q[1] === a.p[k][1])
        );
    }
    return false;
}

function isEmpty(x, i, st, vals) {
    if (x.t === 'pick') return st[i].pick === null;
    if (x.t === 'nl') return !st[i].pts.length;
    if (x.t === 'text') return !getVal(vals, i, 0).trim();
    if (x.t === 'multi') {
        return x.f.some((_, j) => !getVal(vals, i, j).trim());
    }
    return false;
}

function loadSaved() {
    try {
        const raw = localStorage.getItem(STORAGE_KEY);
        if (!raw) return null;
        const data = JSON.parse(raw);
        return data;
    } catch {
        return null;
    }
}

function NumberLineSvg({ markerId, pts, dir, interactive, onHit }) {
    const ps = [...pts].sort((a, b) => a[0] - b[0]);
    return (
        <svg className="nl" viewBox="0 0 420 70" role="img" aria-label="Number line from −5 to 5">
            <defs>
                <marker
                    id={markerId}
                    viewBox="0 0 10 10"
                    refX="9"
                    refY="5"
                    markerWidth="6"
                    markerHeight="6"
                    orient="auto-start-reverse"
                >
                    <path d="M0 0L10 5L0 10z" fill="#2f6bd6" />
                </marker>
            </defs>
            <line x1="8" x2="412" y1="42" y2="42" stroke="currentColor" strokeWidth="1.4" opacity="0.7" />
            {Array.from({ length: 11 }, (_, idx) => -5 + idx).map((v) => (
                <g key={v}>
                    <line x1={xPos(v)} x2={xPos(v)} y1="36" y2="48" stroke="currentColor" strokeWidth="1.4" opacity="0.7" />
                    <text x={xPos(v)} y="65" textAnchor="middle" fontSize="13" fill="currentColor">
                        {v}
                    </text>
                </g>
            ))}
            {dir === 'right' && ps.length ? (
                <line
                    x1={xPos(ps.at(-1)[0])}
                    x2="414"
                    y1="18"
                    y2="18"
                    stroke="#2f6bd6"
                    strokeWidth="3"
                    markerEnd={`url(#${markerId})`}
                />
            ) : null}
            {dir === 'left' && ps.length ? (
                <line
                    x1={xPos(ps[0][0])}
                    x2="6"
                    y1="18"
                    y2="18"
                    stroke="#2f6bd6"
                    strokeWidth="3"
                    markerEnd={`url(#${markerId})`}
                />
            ) : null}
            {dir === 'between' && ps.length > 1 ? (
                <line
                    x1={xPos(ps[0][0])}
                    x2={xPos(ps.at(-1)[0])}
                    y1="18"
                    y2="18"
                    stroke="#2f6bd6"
                    strokeWidth="3"
                />
            ) : null}
            {ps.map(([v, f]) => (
                <circle
                    key={v}
                    cx={xPos(v)}
                    cy="18"
                    r="7"
                    fill={f ? '#2f6bd6' : 'var(--card)'}
                    stroke="#2f6bd6"
                    strokeWidth="3"
                />
            ))}
            {interactive
                ? Array.from({ length: 11 }, (_, idx) => -5 + idx).map((v) => (
                    <rect
                        key={`hit-${v}`}
                        className="hit"
                        x={xPos(v) - 18}
                        y="0"
                        width="36"
                        height="70"
                        onClick={() => onHit(v)}
                    >
                        <title>{v}</title>
                    </rect>
                ))
                : null}
        </svg>
    );
}

function QuestionCard({
    x,
    i,
    st,
    vals,
    fb,
    onPick,
    onSetTool,
    onSetDir,
    onClearNl,
    onNlHit,
    onValChange,
    onFocusField,
    onCheck,
    onHint,
    onShow,
}) {
    const qst = st[i];
    const pillColor = SEC[x.sec][3];
    let body = null;

    if (x.t === 'pick') {
        body = (
            <div className="row">
                {x.opts.map((o) => (
                    <button
                        key={o}
                        type="button"
                        className={`btn pick${qst.pick === o ? ' on' : ''}`}
                        onClick={() => onPick(i, o)}
                    >
                        {o === '<' ? '<' : '>'}
                    </button>
                ))}
            </div>
        );
    } else if (x.t === 'text') {
        body = (
            <>
                {x.show ? (
                    <NumberLineSvg markerId={`show-${i}`} pts={x.show.p} dir={x.show.d} interactive={false} />
                ) : null}
                <div className="row">
                    <input
                        type="text"
                        autoComplete="off"
                        aria-label={`Answer to sum ${x.n}`}
                        placeholder="Your answer"
                        value={getVal(vals, i, 0)}
                        onChange={(e) => onValChange(i, 0, e.target.value)}
                        onFocus={(e) => onFocusField(i, 0, e.target)}
                        onKeyDown={(e) => {
                            if (e.key === 'Enter') onCheck(i);
                        }}
                    />
                </div>
            </>
        );
    } else if (x.t === 'multi') {
        body = x.f.map((field, j) => (
            <div key={field.l}>
                <div className="sub">{field.l}</div>
                <div className="row">
                    <input
                        type="text"
                        autoComplete="off"
                        aria-label={field.l}
                        value={getVal(vals, i, j)}
                        onChange={(e) => onValChange(i, j, e.target.value)}
                        onFocus={(e) => onFocusField(i, j, e.target)}
                        onKeyDown={(e) => {
                            if (e.key === 'Enter') onCheck(i);
                        }}
                    />
                </div>
            </div>
        ));
    } else if (x.t === 'nl') {
        body = (
            <>
                <div className="tools">
                    <button
                        type="button"
                        className={`btn ghost${qst.tool === 0 ? ' on' : ''}`}
                        onClick={() => onSetTool(i, 0)}
                    >
                        ○ Open
                    </button>
                    <button
                        type="button"
                        className={`btn ghost${qst.tool === 1 ? ' on' : ''}`}
                        onClick={() => onSetTool(i, 1)}
                    >
                        ● Filled
                    </button>
                    <button
                        type="button"
                        className={`btn ghost${qst.dir === 'left' ? ' on' : ''}`}
                        onClick={() => onSetDir(i, 'left')}
                    >
                        ← Left
                    </button>
                    <button
                        type="button"
                        className={`btn ghost${qst.dir === 'right' ? ' on' : ''}`}
                        onClick={() => onSetDir(i, 'right')}
                    >
                        Right →
                    </button>
                    <button
                        type="button"
                        className={`btn ghost${qst.dir === 'between' ? ' on' : ''}`}
                        onClick={() => onSetDir(i, 'between')}
                    >
                        Between
                    </button>
                    <button type="button" className="btn ghost" onClick={() => onClearNl(i)}>
                        Clear
                    </button>
                </div>
                <div className="nlbox">
                    <NumberLineSvg
                        markerId={`nl-${i}`}
                        pts={qst.pts}
                        dir={qst.dir}
                        interactive
                        onHit={(v) => onNlHit(i, v)}
                    />
                </div>
            </>
        );
    }

    const cardCls = `card${fb?.cls === 'ok' ? ' ok' : ''}${fb?.cls === 'bad' ? ' bad' : ''}`;
    const showBtn = qst.tries >= 2;

    return (
        <div className={cardCls}>
            <div className="q">
                <span className={`pill p-${pillColor}`}>{x.n}</span>
                <div dangerouslySetInnerHTML={{ __html: x.q }} />
            </div>
            {body}
            <div className="row" style={{ marginTop: 10 }}>
                <button type="button" className="btn" onClick={() => onCheck(i)}>
                    Check
                </button>
                <button type="button" className="btn ghost" onClick={() => onHint(i)}>
                    Hint
                </button>
                {showBtn ? (
                    <button type="button" className="btn ghost" onClick={() => onShow(i)}>
                        Show answer
                    </button>
                ) : null}
            </div>
            {fb ? (
                <div className={`fb ${fb.cls}`} aria-live="polite" dangerouslySetInnerHTML={{ __html: fb.html }} />
            ) : (
                <div className="fb" aria-live="polite" />
            )}
        </div>
    );
}

function initialFeedbackFromSt(st) {
    const fb = {};
    S.forEach((x, i) => {
        if (st[i].ok) {
            fb[i] = { cls: 'ok', html: `✓ Correct! ${x.why}` };
        } else if (st[i].shown) {
            fb[i] = { cls: 'info', html: `<b>Answer:</b> ${x.why}` };
        }
    });
    return fb;
}

function doneMessage(scoredCorrect, finished) {
    if (finished < 20) {
        return `You've finished ${finished} of 20 sums. Keep going!`;
    }
    if (scoredCorrect >= 18) return 'Inequality Master! Brilliant work.';
    if (scoredCorrect >= 14) return 'Great job! Look back at any sums you missed.';
    return 'Well done for finishing. Re-read the revision notes for the sums you missed, then try again.';
}

export default function InequalitiesPracticeWorksheet() {
    const { claim } = useGameXp();
    const lastFieldRef = useRef({ q: 0, f: 0, el: null });
    const xpClaimedRef = useRef(false);

    const [name, setName] = useState('');
    const [st, setSt] = useState(createInitialSt);
    const [vals, setVals] = useState(() => INPUT_SLOTS.map(() => ''));
    const [fb, setFb] = useState({});

    useEffect(() => {
        const saved = loadSaved();
        if (!saved) return;
        if (saved.name) setName(saved.name);
        if (Array.isArray(saved.st) && saved.st.length === S.length) {
            const merged = saved.st.map((s, i) => ({ ...emptyQuestionState(), ...s }));
            setSt(merged);
            setFb(initialFeedbackFromSt(merged));
        }
        if (Array.isArray(saved.vals)) {
            const next = INPUT_SLOTS.map((_, k) => saved.vals[k] ?? '');
            setVals(next);
        }
    }, []);

    const scoredCorrect = useMemo(() => st.filter((s) => s.ok && !s.shown).length, [st]);
    const finished = useMemo(() => st.filter((s) => s.ok || s.shown).length, [st]);
    const meterWidth = scoredCorrect * 5;

    const persist = useCallback((nextName, nextSt, nextVals) => {
        try {
            localStorage.setItem(
                STORAGE_KEY,
                JSON.stringify({ name: nextName, st: nextSt, vals: nextVals }),
            );
        } catch {
            /* ignore */
        }
    }, []);

    useEffect(() => {
        persist(name, st, vals);
    }, [name, st, vals, persist]);

    useEffect(() => {
        if (finished < 20) {
            xpClaimedRef.current = false;
            return;
        }
        if (xpClaimedRef.current) return;
        xpClaimedRef.current = true;
        const points = Math.min(20, Math.max(1, Math.round((scoredCorrect / 20) * 20)));
        claim(points);
    }, [finished, scoredCorrect, claim]);

    const setFeedback = useCallback((i, cls, html) => {
        setFb((prev) => ({ ...prev, [i]: { cls, html } }));
    }, []);

    const clearFeedback = useCallback((i) => {
        setFb((prev) => {
            const next = { ...prev };
            delete next[i];
            return next;
        });
    }, []);

    const handleCheck = useCallback((i) => {
        const x = S[i];
        if (isEmpty(x, i, st, vals)) {
            setFeedback(i, 'info', 'Fill in your answer first.');
            return;
        }
        if (isRight(x, i, st, vals)) {
            const nextSt = [...st];
            nextSt[i] = { ...nextSt[i], ok: true };
            setSt(nextSt);
            const html = nextSt[i].shown
                ? '✓ Correct now. (This one does not score because you saw the answer.)'
                : `✓ Correct! ${x.why}`;
            setFeedback(i, 'ok', html);
        } else {
            const nextSt = [...st];
            nextSt[i] = { ...nextSt[i], tries: nextSt[i].tries + 1, ok: false };
            setSt(nextSt);
            const msg = nextSt[i].tries >= 2
                ? '✗ Not yet. Use the hint, or show the answer.'
                : '✗ Not yet. Check your working and try again.';
            setFeedback(i, 'bad', msg);
        }
    }, [st, vals, setFeedback]);

    const handleHint = useCallback((i) => {
        const x = S[i];
        setFeedback(i, 'info', `<b>Hint:</b> ${x.h}`);
    }, [setFeedback]);

    const handleShow = useCallback((i) => {
        const x = S[i];
        const nextSt = [...st];
        nextSt[i] = { ...nextSt[i], shown: true };
        setSt(nextSt);
        setFeedback(i, 'info', `<b>Answer:</b> ${x.why}`);
    }, [st, setFeedback]);

    const handlePick = useCallback((i, pick) => {
        const nextSt = [...st];
        nextSt[i] = { ...nextSt[i], pick };
        setSt(nextSt);
        clearFeedback(i);
    }, [st, clearFeedback]);

    const handleSetTool = useCallback((i, tool) => {
        const nextSt = [...st];
        nextSt[i] = { ...nextSt[i], tool };
        setSt(nextSt);
    }, [st]);

    const handleSetDir = useCallback((i, dir) => {
        const nextSt = [...st];
        nextSt[i] = { ...nextSt[i], dir };
        setSt(nextSt);
    }, [st]);

    const handleClearNl = useCallback((i) => {
        const nextSt = [...st];
        nextSt[i] = { ...nextSt[i], pts: [], dir: null };
        setSt(nextSt);
    }, [st]);

    const handleNlHit = useCallback((i, v) => {
        const nextSt = [...st];
        const pts = [...nextSt[i].pts];
        const k = pts.findIndex((p) => p[0] === v);
        if (k >= 0) {
            pts.splice(k, 1);
        } else if (pts.length < 2) {
            pts.push([v, nextSt[i].tool]);
        }
        nextSt[i] = { ...nextSt[i], pts };
        setSt(nextSt);
    }, [st]);

    const handleValChange = useCallback((qIndex, fieldIndex, value) => {
        setVals((prev) => setVal(prev, qIndex, fieldIndex, value));
    }, []);

    const handleFocusField = useCallback((q, f, el) => {
        lastFieldRef.current = { q, f, el };
    }, []);

    const handleSymbolKey = useCallback((sym) => {
        const { q, f, el } = lastFieldRef.current;
        if (!el || typeof document !== 'undefined' && !document.body.contains(el)) return;
        const current = getVal(vals, q, f);
        const a = el.selectionStart ?? current.length;
        const b = el.selectionEnd ?? a;
        const next = current.slice(0, a) + sym + current.slice(b);
        setVals((prev) => setVal(prev, q, f, next));
        requestAnimationFrame(() => {
            el.focus();
            el.setSelectionRange(a + sym.length, a + sym.length);
        });
    }, [vals]);

    const resetAll = useCallback(() => {
        try {
            localStorage.removeItem(STORAGE_KEY);
        } catch {
            /* ignore */
        }
        setName('');
        setSt(createInitialSt());
        setVals(INPUT_SLOTS.map(() => ''));
        setFb({});
        xpClaimedRef.current = false;
    }, []);

    return (
        <div className="ipw">
            <div className="wrap">
                <header className="top">
                    <span className="brand-text">YounimatiQ</span>
                    <div className="doc">
                        <span className="long">PRACTICE WORKSHEET · INEQUALITIES IN ALGEBRA · </span>
                        <b>YQ-MATH-ALG-INEQ-001-WS</b>
                    </div>
                </header>
            </div>
            <div className="rainbow" />
            <div className="wrap">
                <section className="hero">
                    <div className="dots" aria-hidden="true">
                        <span className="d1" />
                        <span className="d2" />
                        <span className="d3" />
                        <span className="d4" />
                    </div>
                    <p className="kicker">ALGEBRA · UNIT: INEQUALITIES</p>
                    <h1>Practice Worksheet: Inequalities in Algebra</h1>
                    <span className="badge">Class 1 · 20 sums · Easy to challenge</span>
                    <p>
                        Type your answer, press <b>Check</b>, and fix it if it&apos;s wrong. After two tries you can see the answer, but then that sum doesn&apos;t score.
                    </p>
                    <div className="meta">
                        <div>
                            <label htmlFor="ipw-name">Your name</label>
                            <input
                                type="text"
                                id="ipw-name"
                                autoComplete="off"
                                placeholder="Type your name"
                                value={name}
                                onChange={(e) => setName(e.target.value)}
                            />
                        </div>
                        <div className="scorebox">
                            <div className="score">
                                <span>{scoredCorrect}</span>
                                {' '}
                                <small>/ 20 correct</small>
                            </div>
                            <div className="meter">
                                <i style={{ width: `${meterWidth}%` }} />
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <div className="bar">
                <div className="wrap">
                    <span className="lbl">Symbol keys:</span>
                    {SYMBOL_KEYS.map(({ k, label }) => (
                        <button key={k} type="button" className="key" onClick={() => handleSymbolKey(k)}>
                            {label}
                        </button>
                    ))}
                    <span className="mini">
                        {scoredCorrect}
                        {' '}
                        / 20
                    </span>
                </div>
            </div>

            <main className="wrap">
                <div className="tip">
                    <span className="pill p-blue">HOW TO</span>
                    <div>
                        Tap a box, then use the symbol keys, or type <b>&gt;=</b> for ≥ and <b>&lt;=</b> for ≤. Write answers like <b>x &gt; 6</b>. Check each one with the <b>test-number trick</b>: put a number from your answer back into the original inequality.
                    </div>
                </div>

                {SECTION_KEYS.map((key) => {
                    const [heading, range, note] = SEC[key];
                    const items = S.map((x, i) => [x, i]).filter(([x]) => x.sec === key);
                    return (
                        <div key={key}>
                            <div className="band">
                                <h2>{heading}</h2>
                                <span>{range}</span>
                            </div>
                            <p className="note" dangerouslySetInnerHTML={{ __html: note }} />
                            <div className={`grid${key === 'E' ? ' one' : ''}`}>
                                {items.map(([x, i]) => (
                                    <QuestionCard
                                        key={x.n}
                                        x={x}
                                        i={i}
                                        st={st}
                                        vals={vals}
                                        fb={fb[i]}
                                        onPick={handlePick}
                                        onSetTool={handleSetTool}
                                        onSetDir={handleSetDir}
                                        onClearNl={handleClearNl}
                                        onNlHit={handleNlHit}
                                        onValChange={handleValChange}
                                        onFocusField={handleFocusField}
                                        onCheck={handleCheck}
                                        onHint={handleHint}
                                        onShow={handleShow}
                                    />
                                ))}
                            </div>
                        </div>
                    );
                })}

                <div className="done">
                    <div className="big">
                        {scoredCorrect}
                        {' '}
                        / 20
                    </div>
                    <p style={{ margin: '6px 0 12px' }}>{doneMessage(scoredCorrect, finished)}</p>
                    <button type="button" className="btn ghost" onClick={resetAll}>
                        Start again
                    </button>
                    <GameXpNote />
                </div>
            </main>

            <div className="rainbow" />
            <div className="wrap">
                <footer>
                    <span>© YounimatiQ · Future-Fit Education · www.younimatiq.com</span>
                    <span>Doc. No. YQ-MATH-ALG-INEQ-001-WS</span>
                </footer>
            </div>
        </div>
    );
}