import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { GameXpNote, useGameXp } from './GameXp';
import './styles/algebra-world-quest.css';

const STORAGE_KEY = 'yq-mc-world';
const TOTAL = 39;

const M = [
    { name: 'Spawn Point', topic: 'Variables and expressions', col: 'blue', story: 'You wake up in a new world with a few chests. Before you can build, learn the language of algebra: a chest is a <b>variable</b>, a number that can change.' },
    { name: 'Crafting Table', topic: 'Operations on expressions', col: 'blue', story: 'Time to craft! Combine, remove, copy and share piles of chests and emeralds. Only like items stack: chests with chests, emeralds with emeralds.' },
    { name: 'Villager Village', topic: 'Equations', col: 'orange', story: 'The villagers only trade if you find the missing number. Keep the trade balanced: whatever you do to one side, do to the other.' },
    { name: 'Symbol Temple', topic: 'Inequality symbols', col: 'green', story: 'The temple doors are carved with strange symbols. The open side always faces the bigger number. The line underneath means "or equal to".' },
    { name: 'Number Line Road', topic: 'Draw and read number lines', col: 'purple', story: 'Build the road home. A glass block ○ means the number is NOT included. A gold block ● means it IS included.' },
    { name: 'Portal of Flipping', topic: 'Rules for inequalities', col: 'purple', story: 'Step through the portal carefully! Adding, subtracting, or × ÷ by a positive keeps the sign. × or ÷ by a NEGATIVE flips it.' },
    { name: 'Mob Defence', topic: 'Solving inequalities', col: 'green', story: 'Night falls and mobs appear. Solve each inequality to build your defences. Watch out for negatives!' },
    { name: 'The Safe Zone', topic: 'Double inequalities', col: 'navy', story: 'Fence in a safe zone where x is trapped between two walls. Whatever you do to the middle, do to both walls.' },
    { name: 'Ender Boss', topic: 'Mixed challenge', col: 'navy', story: 'The final battle! Use everything you have learned to defeat the boss and finish your quest.' },
];

const S = [
    { m: 0, t: 'pick', q: 'In 7c + 4, which part is the variable?', opts: ['7', 'c', '4', '+'], a: 'c', h: 'The variable is the letter.', why: 'c is the variable.' },
    { m: 0, t: 'pick', q: 'In 7c + 4, what is the coefficient?', opts: ['7', 'c', '4', '11'], a: '7', h: 'The coefficient is the number stuck to the letter.', why: '7 is the coefficient (7 × c).' },
    { m: 0, t: 'pick', q: 'Which one is an EQUATION?', opts: ['5x − 2', '3y + 1 = 10', '8 + k', '4m'], a: '3y + 1 = 10', h: 'An equation has an = sign.', why: '3y + 1 = 10 has an equals sign.' },
    { m: 0, t: 'text', q: 'Write an expression: you have n blocks, then mine 9 more.', a: ['n+9', '9+n'], h: '"More" means add.', why: 'n + 9' },
    { m: 0, t: 'text', q: 'Each chest holds x = 6 emeralds. Find the value of 3x + 4.', a: ['22'], h: 'Replace x with 6: 3 × 6 + 4.', why: '3 × 6 + 4 = 22' },
    { m: 1, t: 'text', q: 'Simplify: 6a + 2a − 3a', a: ['5a'], h: '6 + 2 − 3.', why: '5a' },
    { m: 1, t: 'text', q: 'Add: (5x + 3) + (x + 6)', a: ['6x+9', '9+6x'], h: 'x on its own means 1x.', why: '6x + 9' },
    { m: 1, t: 'text', q: 'Subtract: (8y + 9) − (3y + 4)', a: ['5y+5', '5+5y'], h: 'The minus takes away BOTH parts of the bracket.', why: '5y + 5' },
    { m: 1, t: 'text', q: 'Multiply: 4(3p + 2)', a: ['12p+8', '8+12p'], h: 'Multiply 4 by both terms.', why: '12p + 8' },
    { m: 1, t: 'text', q: 'Divide: (15d + 10) ÷ 5', a: ['3d+2', '2+3d'], h: 'Divide both terms by 5.', why: '3d + 2' },
    { m: 2, t: 'text', q: 'Solve: x + 8 = 20', a: ['x=12', '12', '12=x'], h: 'Subtract 8 from both sides.', why: 'x = 12' },
    { m: 2, t: 'text', q: 'Solve: m − 5 = 9', a: ['m=14', '14', '14=m'], h: 'Add 5 to both sides.', why: 'm = 14' },
    { m: 2, t: 'text', q: 'Solve: 6b = 42', a: ['b=7', '7', '7=b'], h: 'Divide both sides by 6.', why: 'b = 7' },
    { m: 2, t: 'text', q: 'Solve: 4x − 3 = 21', a: ['x=6', '6', '6=x'], h: 'Add 3 first, then divide by 4.', why: '4x = 24, so x = 6' },
    { m: 2, t: 'multi', q: 'A baker charges 3 emeralds to enter plus 4 emeralds per bread loaf (b). You spend 23. Write the equation, then solve it.', f: [{ l: 'Equation', a: ['3+4b=23', '4b+3=23', '23=3+4b', '23=4b+3'] }, { l: 'b =', a: ['b=5', '5'] }], h: 'Entry cost + 4 × b = total.', why: '3 + 4b = 23, so 4b = 20 and b = 5' },
    { m: 3, t: 'pick', q: 'Choose the symbol: 9 ___ −9', opts: ['<', '>'], a: '>', h: 'Positive numbers are bigger than negatives.', why: '9 > −9' },
    { m: 3, t: 'pick', q: 'Choose the symbol: −8 ___ −3', opts: ['<', '>'], a: '<', h: 'Which is further left on the number line?', why: '−8 < −3' },
    { m: 3, t: 'text', q: 'Write as an inequality: you need at least 12 wood (w).', a: ['w≥12', '12≤w'], h: '"At least" includes 12.', why: 'w ≥ 12' },
    { m: 3, t: 'text', q: 'Write as an inequality: a minecart holds no more than 4 players (p).', a: ['p≤4', '4≥p'], h: '"No more than" means at most.', why: 'p ≤ 4' },
    { m: 3, t: 'pick', q: 'Which TWO symbols make 5 ___ 5 true?', opts: ['< and >', '≤ and ≥', '≠ and <', '> and ≥'], a: '≤ and ≥', h: '5 is equal to 5.', why: '5 ≤ 5 and 5 ≥ 5 are both true.' },
    { m: 4, t: 'nl', q: 'Draw: x &gt; −1', a: { p: [[-1, 0]], d: 'right' }, h: 'Is −1 included? Which way are bigger numbers?', why: 'Open circle at −1, arrow right.' },
    { m: 4, t: 'nl', q: 'Draw: x ≤ 3', a: { p: [[3, 1]], d: 'left' }, h: '≤ includes 3, so use a gold block (filled).', why: 'Filled circle at 3, arrow left.' },
    { m: 4, t: 'text', q: 'What inequality does this road show?', show: { p: [[-4, 1]], d: 'right' }, a: ['x≥-4', '-4≤x'], h: 'Filled circle means −4 is included.', why: 'x ≥ −4' },
    { m: 4, t: 'text', q: 'What inequality does this road show?', show: { p: [[1, 0]], d: 'left' }, a: ['x<1', '1>x'], h: 'Open circle means 1 is not included.', why: 'x < 1' },
    { m: 5, t: 'pick', q: 'True or false? If x &lt; 5, then x + 3 &lt; 8.', opts: ['True', 'False'], a: 'True', h: 'Adding keeps the sign.', why: 'True: adding 3 keeps the sign.' },
    { m: 5, t: 'pick', q: 'True or false? If y &gt; 2, then 3y &gt; 6.', opts: ['True', 'False'], a: 'True', h: 'Multiplying by a positive keeps the sign.', why: 'True: × 3 keeps the sign.' },
    { m: 5, t: 'pick', q: 'True or false? If m &lt; 4, then −m &lt; −4.', opts: ['True', 'False'], a: 'False', h: 'Multiplying by −1 goes through the portal…', why: 'False: it flips, so −m > −4.' },
    { m: 5, t: 'text', q: 'Multiply both sides of x &gt; 3 by −2. Write the new inequality.', a: ['-2x<-6', '-6>-2x'], h: '× by a negative flips the sign.', why: '−2x < −6' },
    { m: 6, t: 'text', q: 'Solve: x + 7 ≤ 15', a: ['x≤8', '8≥x'], h: 'Subtract 7 from both sides.', why: 'x ≤ 8' },
    { m: 6, t: 'text', q: 'Solve: 5k &gt; 35', a: ['k>7', '7<k'], h: 'Divide both sides by 5.', why: 'k > 7' },
    { m: 6, t: 'text', q: 'Solve: 3t − 4 ≥ 11', a: ['t≥5', '5≤t'], h: 'Add 4, then divide by 3.', why: '3t ≥ 15, so t ≥ 5' },
    { m: 6, t: 'text', q: 'Solve: 10 − 2x &lt; 4 (watch the sign!)', a: ['x>3', '3<x'], h: 'Subtract 10, then divide by −2 and flip.', why: '−2x < −6, so x > 3' },
    { m: 6, t: 'text', q: 'Solve: −6n ≥ 18', a: ['n≤-3', '-3≥n'], h: 'Divide by −6. Flip!', why: 'n ≤ −3' },
    { m: 7, t: 'nl', q: 'Draw the safe zone: −1 &lt; x ≤ 4', a: { p: [[-1, 0], [4, 1]], d: 'between' }, h: 'Two circles: −1 is out, 4 is in.', why: 'Open circle at −1, filled circle at 4, line between.' },
    { m: 7, t: 'multi', q: 'Solve 2 ≤ 3x − 1 &lt; 11. Then list the whole numbers that fit.', f: [{ l: 'Answer', a: ['1≤x<4'] }, { l: 'Whole numbers (e.g. 1, 2)', a: ['1,2,3'], list: 1 }], h: 'Add 1 to all three parts, then divide all three by 3.', why: '3 ≤ 3x < 12, so 1 ≤ x < 4: whole numbers 1, 2, 3' },
    { m: 7, t: 'text', q: 'Write the double inequality this road shows.', show: { p: [[-2, 1], [2, 0]], d: 'between' }, a: ['-2≤x<2'], h: 'Filled at −2 (in), open at 2 (out).', why: '−2 ≤ x < 2' },
    { m: 8, t: 'multi', q: 'A flight launch costs 10 emeralds plus 3 per rocket (r). Your budget is at most 40. Write an inequality, then find the most rockets you can buy.', f: [{ l: 'Inequality', a: ['10+3r≤40', '3r+10≤40', '40≥10+3r', '40≥3r+10'] }, { l: 'Most rockets', a: ['10', 'r=10'] }], h: 'Launch cost + 3 × r must be at most 40.', why: '10 + 3r ≤ 40, so 3r ≤ 30 and r ≤ 10: 10 rockets' },
    { m: 8, t: 'text', q: 'Spot the griefer: Aman solved −3x &gt; 12 and wrote x &gt; −4. What is the correct answer?', a: ['x<-4', '-4>x'], h: 'Test x = 0: is 0 > 12?', why: 'Dividing by −3 flips the sign: x < −4' },
    { m: 8, t: 'text', q: 'Solve: 2(x + 3) = 18', a: ['x=6', '6', '6=x'], h: 'Multiply out the bracket first: 2x + 6 = 18.', why: '2x + 6 = 18, so 2x = 12 and x = 6' },
];

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
        .replace(/[*×]/g, '')
        .replace(/\.$/, '');
}

function xPos(v) {
    return 30 + (v + 5) * 36;
}

function getVal(vals, i, f) {
    return vals[`${i}-${f}`] ?? '';
}

function setVal(vals, i, f, value) {
    return { ...vals, [`${i}-${f}`]: value };
}

function missionItems(m) {
    return S.map((x, i) => [x, i]).filter(([x]) => x.m === m);
}

function isRight(x, i, st, vals) {
    if (x.t === 'pick') return st[i].pick === x.a;
    if (x.t === 'text') return x.a.includes(norm(getVal(vals, i, 0)));
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
    if (x.t === 'multi') return x.f.some((_, j) => !getVal(vals, i, j).trim());
    return false;
}

function initialFeedbackFromSt(st) {
    const fb = {};
    S.forEach((x, i) => {
        if (st[i].ok) {
            fb[i] = {
                cls: 'ok',
                html: st[i].shown
                    ? '✓ Correct now. (No diamond: you saw the answer.)'
                    : `✓ Diamond earned! ${x.why}`,
            };
        } else if (st[i].shown) {
            fb[i] = { cls: 'info', html: `<b>Answer:</b> ${x.why}` };
        }
    });
    return fb;
}

function doneMessage(diamonds, cleared) {
    if (cleared < TOTAL) {
        return `You've cleared ${cleared} of ${TOTAL} sums. Keep exploring!`;
    }
    if (diamonds >= 34) return 'Ender Boss defeated! You are an Algebra Legend.';
    if (diamonds >= 27) return 'Quest complete! Great adventuring. Look back at any sums you missed.';
    return 'Quest complete! Revisit the missions where you lost diamonds, then try a new world.';
}

function ChestIcon() {
    return (
        <svg width="26" height="26" viewBox="0 0 8 8" aria-hidden="true">
            <rect width="8" height="8" fill="#8b5a2b" />
            <rect width="8" height="3" fill="#5fae3a" />
            <rect x="1" y="3" width="1" height="1" fill="#5fae3a" />
            <rect x="5" y="3" width="1" height="1" fill="#5fae3a" />
            <rect x="3" y="5" width="1" height="1" fill="#6e4520" />
        </svg>
    );
}

function NumberLineSvg({ markerId, pts, dir, interactive, onHit }) {
    const ps = [...pts].sort((a, b) => a[0] - b[0]);
    const fmt = (v) => String(v).replace('-', '−');
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
                        {fmt(v)}
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
    sumNum,
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
    const pillColor = M[x.m].col;
    let body = null;

    if (x.t === 'pick') {
        body = (
            <div className="opts">
                {x.opts.map((o) => (
                    <button
                        key={o}
                        type="button"
                        className={`btn pick${qst.pick === o ? ' on' : ''}`}
                        onClick={() => onPick(i, o)}
                    >
                        {o}
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
                        aria-label={`Answer to sum ${sumNum}`}
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
                        ○ Glass (open)
                    </button>
                    <button
                        type="button"
                        className={`btn ghost${qst.tool === 1 ? ' on' : ''}`}
                        onClick={() => onSetTool(i, 1)}
                    >
                        ● Gold (filled)
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
    const showBtn = qst.tries >= 2 && !qst.ok;

    return (
        <div className={cardCls}>
            <div className="q">
                <span className={`pill p-${pillColor}`}>{sumNum}</span>
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

export default function AlgebraWorldQuest() {
    const { claim } = useGameXp();
    const lastFieldRef = useRef({ i: 0, f: 0, el: null });
    const mapRef = useRef(null);
    const xpClaimedRef = useRef(false);

    const [name, setName] = useState('');
    const [st, setSt] = useState(createInitialSt);
    const [vals, setVals] = useState({});
    const [fb, setFb] = useState({});
    const [cur, setCur] = useState(0);
    const [unlockAll, setUnlockAll] = useState(false);
    const [hydrated, setHydrated] = useState(false);

    const mDone = useCallback((m, state) => missionItems(m).every(([, i]) => state[i].ok || state[i].shown), []);

    const missionOpen = useCallback((m, state, unlocked) => {
        if (unlocked || m === 0) return true;
        return [...Array(m).keys()].every((k) => mDone(k, state));
    }, [mDone]);

    const mGot = useCallback((m, state) => missionItems(m).filter(([, i]) => state[i].ok && !state[i].shown).length, []);

    useEffect(() => {
        try {
            const raw = localStorage.getItem(STORAGE_KEY);
            if (!raw) {
                setHydrated(true);
                return;
            }
            const d = JSON.parse(raw);
            if (d.name) setName(d.name);
            if (Array.isArray(d.st) && d.st.length === S.length) {
                const merged = d.st.map((s) => ({ ...emptyQuestionState(), ...s }));
                setSt(merged);
                setFb(initialFeedbackFromSt(merged));
            }
            if (d.vals && typeof d.vals === 'object') setVals(d.vals);
            setUnlockAll(!!d.unlockAll);
            const savedCur = typeof d.cur === 'number' ? d.cur : 0;
            const state = Array.isArray(d.st) ? d.st.map((s) => ({ ...emptyQuestionState(), ...s })) : createInitialSt();
            const unlocked = !!d.unlockAll;
            setCur(missionOpen(savedCur, state, unlocked) ? savedCur : 0);
        } catch {
            /* ignore */
        }
        setHydrated(true);
    }, [missionOpen]);

    const diamonds = useMemo(() => st.filter((s) => s.ok && !s.shown).length, [st]);
    const cleared = useMemo(() => st.filter((s) => s.ok || s.shown).length, [st]);
    const meterWidth = (diamonds / TOTAL) * 100;

    const persist = useCallback((payload) => {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(payload));
        } catch {
            /* ignore */
        }
    }, []);

    useEffect(() => {
        if (!hydrated) return;
        persist({ name, st, vals, cur, unlockAll });
    }, [name, st, vals, cur, unlockAll, persist, hydrated]);

    useEffect(() => {
        if (cleared < TOTAL) {
            xpClaimedRef.current = false;
            return;
        }
        if (xpClaimedRef.current) return;
        xpClaimedRef.current = true;
        const points = Math.min(20, Math.max(1, Math.round((diamonds / TOTAL) * 20)));
        claim(points);
    }, [cleared, diamonds, claim]);

    const setFeedback = useCallback((i, cls, html) => {
        setFb((prev) => ({ ...prev, [i]: { cls, html } }));
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
                ? '✓ Correct now. (No diamond: you saw the answer.)'
                : `✓ Diamond earned! ${x.why}`;
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
        setFeedback(i, 'info', `<b>Hint:</b> ${S[i].h}`);
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
        setFb((prev) => {
            const next = { ...prev };
            delete next[i];
            return next;
        });
    }, [st]);

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
        if (k >= 0) pts.splice(k, 1);
        else if (pts.length < 2) pts.push([v, nextSt[i].tool]);
        nextSt[i] = { ...nextSt[i], pts };
        setSt(nextSt);
    }, [st]);

    const handleValChange = useCallback((i, f, value) => {
        setVals((prev) => setVal(prev, i, f, value));
    }, []);

    const handleFocusField = useCallback((i, f, el) => {
        lastFieldRef.current = { i, f, el };
    }, []);

    const handleSymbolKey = useCallback((sym) => {
        const { i, f, el } = lastFieldRef.current;
        if (!el || (typeof document !== 'undefined' && !document.body.contains(el))) return;
        const current = getVal(vals, i, f);
        const a = el.selectionStart ?? current.length;
        const b = el.selectionEnd ?? a;
        const next = current.slice(0, a) + sym + current.slice(b);
        setVals((prev) => setVal(prev, i, f, next));
        requestAnimationFrame(() => {
            el.focus();
            el.setSelectionRange(a + sym.length, a + sym.length);
        });
    }, [vals]);

    const goMission = useCallback((k) => {
        if (k < 0 || k >= M.length || !missionOpen(k, st, unlockAll)) return;
        setCur(k);
        const reduced = typeof window !== 'undefined' && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        mapRef.current?.scrollIntoView({ behavior: reduced ? 'auto' : 'smooth', block: 'start' });
    }, [st, unlockAll, missionOpen]);

    const resetWorld = useCallback(() => {
        try {
            localStorage.removeItem(STORAGE_KEY);
        } catch {
            /* ignore */
        }
        setName('');
        setSt(createInitialSt());
        setVals({});
        setFb({});
        setCur(0);
        setUnlockAll(false);
        xpClaimedRef.current = false;
    }, []);

    const nextUnlocked = missionOpen(cur + 1, st, unlockAll);
    const mission = M[cur];
    const its = missionItems(cur);

  const msgText = cleared < TOTAL
    ? `You've cleared ${cleared} of ${TOTAL} sums. Keep exploring!`
    : doneMessage(diamonds, cleared);

    return (
        <div className="ym-awq">
            <div className="wrap">
                <header className="top">
                    <img src="/logo.webp" alt="YounimatiQ – Future-Fit Education" />
                    <div className="doc">
                        <span className="long">MINECRAFT WORLD QUEST · ALGEBRA · </span>
                        <b>YQ-MATH-ALG-001-WQ</b>
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
                    <p className="kicker">ALGEBRA · MINECRAFT-THEMED MISSION</p>
                    <h1>Minecraft World Quest: Algebra Adventure</h1>
                    <span className="badge">8 missions + Ender Boss · 39 sums</span>
                    <p>
                        Travel across the world map from Spawn Point to the Ender Boss. Finish every sum in a mission to unlock the next one. Each correct answer earns a diamond. After two tries you can see the answer, but then that sum earns no diamond.
                    </p>
                    <div className="meta">
                        <div>
                            <label htmlFor="awq-name">Player name</label>
                            <input
                                type="text"
                                id="awq-name"
                                autoComplete="off"
                                placeholder="Type your player name"
                                value={name}
                                onChange={(e) => setName(e.target.value)}
                            />
                        </div>
                        <div className="scorebox">
                            <div className="score">
                                <span>{diamonds}</span>
                                {' '}
                                <small>/ 39 diamonds</small>
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
                        {diamonds}
                        {' '}
                        / 39
                    </span>
                </div>
            </div>

            <main className="wrap">
                <div className="tip">
                    <span className="pill p-blue">HOW TO</span>
                    <div>
                        Tap a box, then use the symbol keys, or type <b>&gt;=</b> for ≥ and <b>&lt;=</b> for ≤. Write answers like <b>6x + 4</b>, <b>x = 5</b> or <b>x &gt; 6</b>. A chest stands for an unknown number, so 3c means 3 chests. Check inequalities with the <b>test-number trick</b>.
                    </div>
                </div>

                <div className="map" ref={mapRef} role="navigation" aria-label="World map">
                    {M.map((m, k) => {
                        const open = missionOpen(k, st, unlockAll);
                        const done = mDone(k, st);
                        const n = missionItems(k).length;
                        const got = mGot(k, st);
                        const nodeCls = `node${k === cur ? ' cur' : ''}${done ? ' done' : ''}${open ? '' : ' lock'}`;
                        return (
                            <button
                                key={m.name}
                                type="button"
                                className={nodeCls}
                                disabled={!open}
                                title={open ? m.name : 'Locked: finish the mission before'}
                                onClick={() => goMission(k)}
                            >
                                <span className="ic">
                                    {open ? (done ? '✓' : <ChestIcon />) : '?'}
                                </span>
                                <span className="nm">
                                    {k + 1}
                                    .
                                    {' '}
                                    {m.name}
                                </span>
                                <span className="ct">{open ? `${got} / ${n}` : 'Locked'}</span>
                            </button>
                        );
                    })}
                </div>

                <CurrentMission
                    cur={cur}
                    mission={mission}
                    its={its}
                    st={st}
                    vals={vals}
                    fb={fb}
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

                <div className="nav">
                    <button type="button" className="btn ghost" disabled={cur === 0} onClick={() => goMission(cur - 1)}>
                        ← Previous mission
                    </button>
                    <button
                        type="button"
                        className="btn"
                        disabled={cur === M.length - 1 || !nextUnlocked}
                        onClick={() => goMission(cur + 1)}
                    >
                        {cur === M.length - 1
                            ? 'Final mission'
                            : nextUnlocked
                                ? 'Next mission →'
                                : 'Finish this mission to unlock the next'}
                    </button>
                </div>

                <div className="done">
                    <div className="big">
                        {diamonds}
                        {' '}
                        / 39
                    </div>
                    <p style={{ margin: '6px 0 12px' }}>{msgText}</p>
                    <button type="button" className="btn ghost" onClick={resetWorld}>
                        Start a new world
                    </button>
                    <p className="teach">
                        Teacher?
                        {' '}
                        <button type="button" onClick={() => setUnlockAll(true)}>
                            Unlock all missions
                        </button>
                    </p>
                    <GameXpNote />
                </div>
            </main>

            <div className="rainbow" />
            <div className="wrap">
                <footer>
                    <span>© YounimatiQ · Future-Fit Education · www.younimatiq.com</span>
                    <span>Doc. No. YQ-MATH-ALG-001-WQ</span>
                </footer>
            </div>
        </div>
    );
}

function CurrentMission({
    cur,
    mission,
    its,
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
    return (
        <div>
            <div className="band">
                <h2>
                    <ChestIcon />
                    Mission
                    {' '}
                    {cur + 1}
                    {' '}
                    ·
                    {' '}
                    {mission.name}
                </h2>
                <span>{mission.topic}</span>
            </div>
            <div className="story">
                <span className="pill p-navy">STORY</span>
                <div dangerouslySetInnerHTML={{ __html: mission.story }} />
            </div>
            <div className={`grid${cur >= 7 ? ' one' : ''}`}>
                {its.map(([x, i]) => (
                    <QuestionCard
                        key={i}
                        x={x}
                        i={i}
                        sumNum={i + 1}
                        st={st}
                        vals={vals}
                        fb={fb[i]}
                        onPick={onPick}
                        onSetTool={onSetTool}
                        onSetDir={onSetDir}
                        onClearNl={onClearNl}
                        onNlHit={onNlHit}
                        onValChange={onValChange}
                        onFocusField={onFocusField}
                        onCheck={onCheck}
                        onHint={onHint}
                        onShow={onShow}
                    />
                ))}
            </div>
        </div>
    );
}