import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { GameXpNote, useGameXp } from './GameXp';
import './styles/mission-crocodile-gate.css';

const STORAGE_KEY = 'yq-ineq-mission-v1';

const COL = {
    1: 'var(--blue)',
    2: 'var(--orange)',
    3: 'var(--green)',
    4: 'var(--purple)',
    5: 'var(--pink)',
};

const SYM = ['>', '<', '≥', '≤', '≠'];

const GATES = [
    {
        id: 1,
        name: 'Crocodile Gate',
        skill: 'Inequality symbols',
        intro: "Cadet, the station's main door is guarded by Snappy the robo-crocodile. His jaws only open for the correct symbol. Remember: the hungry crocodile always opens his mouth toward the BIGGER number.",
        win: "Snappy's jaws swing open! The main door is unlocked and the lights in the corridor flicker back on.",
        qs: [
            {
                t: 'choice',
                q: 'The door panel shows: <span class="m">9 __ 4</span>. Which symbol makes it true?',
                opts: ['>', '<', '≥ only', '='],
                a: 0,
                h: ['Which number is bigger, 9 or 4?', 'The open mouth faces the bigger number.', '9 is bigger, so the mouth opens toward 9: 9 > 4.'],
                why: '9 is greater than 4, so 9 > 4.',
            },
            {
                t: 'choice',
                q: 'Engine rule: "Fuel <b>f</b> must be <b>at least</b> 10 units." Which code matches?',
                opts: ['f > 10', 'f ≥ 10', 'f < 10', 'f ≤ 10'],
                a: 1,
                h: ['Is exactly 10 units allowed?', '"At least" means 10 is allowed, and anything bigger.', 'Allowed AND bigger means greater than or equal to: ≥.'],
                why: '"At least 10" includes 10, so we use ≥: f ≥ 10.',
            },
            {
                t: 'choice',
                q: "Snappy's final test. True or false: <span class=\"m\">−4 > −1</span>",
                opts: ['True', 'False'],
                a: 1,
                h: ['Picture both numbers on a number line.', 'Numbers further right are bigger.', '−1 is to the right of −4, so −1 is bigger.'],
                why: '−4 is further left than −1, so −4 is smaller. −4 > −1 is false.',
            },
        ],
    },
    {
        id: 2,
        name: 'Number Line Bridge',
        skill: 'Number line',
        intro: 'The bridge to the control room is broken. Each section is a number line, and you must rebuild it exactly. Filled circle means the number is IN the club. Open circle means it is NOT.',
        win: 'The bridge locks into place with a satisfying clunk. You cross to the control room.',
        qs: [
            {
                t: 'line',
                q: 'Build the bridge for <span class="m">x &lt; 4</span>',
                ans: { p: 4, fill: false, dir: 'L' },
                h: ['Where does the circle go? Look at the number in the inequality.', 'Is 4 itself allowed? Test it: is 4 < 4 true?', 'Open circle at 4, arrow pointing left (toward smaller numbers).'],
                why: '4 is not included (open circle) and every smaller number works (arrow left).',
            },
            {
                t: 'line',
                q: 'Build the bridge for <span class="m">x ≥ −3</span>',
                ans: { p: -3, fill: true, dir: 'R' },
                h: ['Put the circle at −3.', 'The line under ≥ means "or equal to". Is −3 in the club?', 'Filled circle at −3, arrow pointing right.'],
                why: '−3 is included (filled circle) and every bigger number works (arrow right).',
            },
            {
                t: 'choice',
                q: 'A bridge section has been drawn for you. Which inequality does it show?',
                draw: { a: 1, af: false, b: 4, bf: true },
                opts: ['1 ≤ x < 4', '1 < x ≤ 4', '1 < x < 4', '1 ≤ x ≤ 4'],
                a: 1,
                h: ['Look at each circle separately.', 'The circle at 1 is open. The circle at 4 is filled.', '1 is out (<), 4 is in (≤).'],
                why: 'Open at 1 means 1 < x. Filled at 4 means x ≤ 4. Together: 1 < x ≤ 4.',
            },
        ],
    },
    {
        id: 3,
        name: 'Mirror Reactor',
        skill: 'Rules for inequalities',
        intro: 'The reactor runs on a mirror. Adding, subtracting, or multiplying by a positive number keep the sign the same. But multiplying or dividing by a NEGATIVE is like looking in a mirror at zero: everything swaps, so the sign flips!',
        win: 'The mirror reactor hums to life. The station is now at half power!',
        qs: [
            {
                t: 'choice',
                q: 'Start with the true fact <span class="m">2 &lt; 5</span>. Multiply both sides by <b>−1</b>. What is the new true statement?',
                opts: ['−2 < −5', '−2 > −5', '2 > 5', '−2 = −5'],
                a: 1,
                h: ['Did we multiply by a positive or a negative?', 'Multiplying by a negative means the sign flips.', '−2 is to the right of −5 on the number line, so −2 > −5.'],
                why: 'Multiplying by a negative reverses the sign: −2 > −5.',
            },
            {
                t: 'choice',
                q: 'Reactor code: <span class="m">x &gt; 4</span>. Multiply both sides by <b>−2</b>.',
                opts: ['−2x > −8', '−2x < −8', '2x > 8', '−2x < 8'],
                a: 1,
                h: ['4 × (−2) is…?', 'You multiplied by a negative. What happens to the sign?', '4 × (−2) = −8 and the sign flips: −2x < −8.'],
                why: '−2 × 4 = −8, and multiplying by a negative flips > into <.',
            },
            {
                t: 'choice',
                q: 'Safety check. If <span class="m">y &lt; 4</span>, is it true that <span class="m">2y &lt; 8</span>?',
                opts: ['True', 'False'],
                a: 0,
                h: ['What did we do to both sides?', 'We multiplied by 2. Positive or negative?', 'Multiplying by a positive keeps the sign.'],
                why: 'We multiplied both sides by 2 (positive), so the sign stays: 2y < 8.',
            },
        ],
    },
    {
        id: 4,
        name: 'Solver Core',
        skill: 'Solving inequalities',
        intro: 'The core computer needs three passwords. Solve each inequality exactly like an equation, using the same moves. Keep one eye on the sign: flip it only if you multiply or divide by a negative.',
        win: 'ACCESS GRANTED. The core computer reboots and the engines start warming up.',
        qs: [
            {
                t: 'solve',
                q: 'Password 1: solve <span class="m">x + 5 &gt; 12</span>',
                v: 'x',
                ans: { s: '>', n: 7 },
                h: ['What is stuck to x?', 'Subtract 5 from both sides.', '12 − 5 = 7, and the sign stays: x > 7.'],
                why: 'x + 5 − 5 > 12 − 5, so x > 7.',
            },
            {
                t: 'solve',
                q: 'Password 2: solve <span class="m">3b − 2 ≥ 10</span>',
                v: 'b',
                ans: { s: '≥', n: 4 },
                h: ['Undo the −2 first.', 'Add 2 to both sides: 3b ≥ 12.', 'Now divide both sides by 3 (positive): b ≥ 4.'],
                why: 'Add 2: 3b ≥ 12. Divide by 3: b ≥ 4. Check: b = 5 gives 13 ≥ 10 ✓.',
            },
            {
                t: 'solve',
                q: 'Password 3 (careful!): solve <span class="m">10 − 2x &lt; 4</span>',
                v: 'x',
                ans: { s: '>', n: 3 },
                h: ['Subtract 10 from both sides first: −2x < −6.', 'Now you divide by −2. Which rule applies?', 'Dividing by a negative flips the sign: x > 3.'],
                why: '−2x < −6. Divide by −2 and flip: x > 3. Check: x = 4 gives 2 < 4 ✓.',
            },
        ],
    },
    {
        id: 5,
        name: 'Sandwich Lock',
        skill: 'Double inequalities',
        intro: 'Final lock, Cadet! The launch bay is sealed by the Sandwich Lock. x is the filling and must stay in the middle. Whatever you do to the filling, do to BOTH slices of bread.',
        win: 'The launch bay opens. Station Orbit-Q is back at full power. Mission complete!',
        qs: [
            {
                t: 'choice',
                q: 'Unlock code: solve <span class="m">−1 ≤ 3x + 5 &lt; 11</span>',
                opts: ['−2 ≤ x < 2', '−2 < x ≤ 2', '2 ≤ x < −2', '−6 ≤ x < 6'],
                a: 0,
                h: ['Subtract 5 from all three parts.', 'You get −6 ≤ 3x < 6.', 'Divide all three parts by 3: −2 ≤ x < 2.'],
                why: 'Subtract 5: −6 ≤ 3x < 6. Divide by 3: −2 ≤ x < 2.',
            },
            {
                t: 'choice',
                q: 'Which whole numbers fit <span class="m">−2 ≤ x &lt; 2</span>?',
                opts: ['−1, 0, 1', '−2, −1, 0, 1', '−2, −1, 0, 1, 2', '−1, 0, 1, 2'],
                a: 1,
                h: ['Is −2 in the club? Check the symbol next to it.', '≤ means −2 IS included.', '< 2 means 2 is NOT included.'],
                why: '−2 is included (≤) and 2 is not (<): −2, −1, 0, 1.',
            },
            {
                t: 'solve',
                q: 'Launch fuel: the space phone plan costs $10 plus $2 per GB. You can spend <b>at most $30</b>. Solve <span class="m">10 + 2g ≤ 30</span>',
                v: 'g',
                ans: { s: '≤', n: 10 },
                h: ['Subtract 10 from both sides.', '2g ≤ 20.', 'Divide by 2 (positive): g ≤ 10.'],
                why: 'Subtract 10: 2g ≤ 20. Divide by 2: g ≤ 10 GB.',
            },
        ],
    },
];

function loadResults() {
    try {
        const raw = localStorage.getItem(STORAGE_KEY);
        if (raw) {
            const data = JSON.parse(raw);
            if (data?.results) return data.results;
        }
    } catch {
        /* ignore */
    }
    return {};
}

function saveResults(results) {
    try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify({ results }));
    } catch {
        /* ignore */
    }
}

function totalStars(results) {
    return Object.values(results).reduce((a, b) => a + b, 0);
}

function gateDone(results, i) {
    return GATES[i].qs.every((_, j) => results[`${i}-${j}`]);
}

function gateStars(results, i) {
    return GATES[i].qs.reduce((a, _, j) => a + (results[`${i}-${j}`] || 0), 0);
}

function unlocked(results, i) {
    return i === 0 || gateDone(results, i - 1);
}

function firstOpen(results, gi) {
    const g = GATES[gi];
    for (let j = 0; j < g.qs.length; j += 1) {
        if (!results[`${gi}-${j}`]) return j;
    }
    return 0;
}

function parseNum(s) {
    return parseFloat(String(s).replace('−', '-'));
}

function wrongMsg(Q, pick, line, sv) {
    if (Q.t === 'line') {
        const L = line;
        const A = Q.ans;
        if (L.p !== A.p) return 'The bridge wobbles. Check where the circle sits.';
        if (L.fill !== A.fill) return 'Close! Check the circle: is the boundary number in the club or not?';
        return 'Almost! Which way should the arrow point?';
    }
    if (Q.t === 'solve') {
        const v = parseNum(sv.n);
        if (v === Q.ans.n) return "Your number is right, but the symbol is not. Did you flip when you should, or flip when you shouldn't?";
        if (sv.s === Q.ans.s) return 'Right symbol, wrong number. Redo the arithmetic one step at a time.';
        return 'Code rejected. Try one step at a time, or ask ARIA for a hint.';
    }
    return 'Code rejected. Have another look, or ask ARIA for a hint.';
}

function rankForStars(s) {
    if (s >= 42) return 'Station Commander';
    if (s >= 33) return 'Chief Engineer';
    if (s >= 24) return 'Flight Officer';
    return 'Cadet First Class';
}

function BotIcon() {
    return (
        <svg className="bot" viewBox="0 0 46 46" aria-hidden="true">
            <rect x="6" y="10" width="34" height="28" rx="10" fill="#4C8BF0" />
            <circle cx="17" cy="24" r="4" fill="#fff" />
            <circle cx="29" cy="24" r="4" fill="#fff" />
            <rect x="21" y="2" width="4" height="8" fill="#F5C64F" />
            <circle cx="23" cy="3" r="3" fill="#E65E8C" />
            <rect x="16" y="31" width="14" height="3" rx="1.5" fill="#120D45" />
        </svg>
    );
}

function CrocIcon() {
    return (
        <svg className="croc" viewBox="0 0 170 170" role="img" aria-label="Snappy the robot crocodile">
            <circle cx="85" cy="85" r="80" fill="#271F7A" />
            <path d="M30 98 L140 60 L140 74 L44 106 Z" fill="#7BC453" />
            <path d="M30 102 L140 128 L140 114 L44 104 Z" fill="#5FA23D" />
            <path d="M60 92 l6 -10 l6 8 l6 -11 l6 8 l6 -11 l6 8 l6 -11 l6 8" fill="none" stroke="#fff" strokeWidth="3" strokeLinejoin="round" />
            <path d="M60 106 l6 8 l6 -6 l6 9 l6 -6 l6 9 l6 -6 l6 9" fill="none" stroke="#fff" strokeWidth="3" strokeLinejoin="round" />
            <circle cx="118" cy="62" r="9" fill="#F5C64F" />
            <circle cx="120" cy="62" r="4" fill="#120D45" />
            <text x="148" y="100" fontFamily="Fredoka,sans-serif" fontSize="30" fontWeight="700" fill="#F5C64F">
                9
            </text>
            <text x="8" y="112" fontFamily="Fredoka,sans-serif" fontSize="22" fontWeight="700" fill="#BDB7EA">
                4
            </text>
        </svg>
    );
}

function LockIcon() {
    return (
        <svg width="16" height="18" viewBox="0 0 16 18" aria-hidden="true">
            <rect x="1" y="8" width="14" height="10" rx="2" fill="#120D45" />
            <path d="M4 8V5a4 4 0 0 1 8 0v3" fill="none" stroke="#120D45" strokeWidth="2" />
        </svg>
    );
}

function NumberLineSvg({ config, interactive, onTick }) {
    const W = 640;
    const H = 96;
    const x0 = 30;
    const x1 = 610;
    const y = 52;
    const step = (x1 - x0) / 20;
    const X = (v) => x0 + (v + 10) * step;
    const col = '#F5C64F';

    const dot = (v, f) => (
        <circle key={`dot-${v}`} cx={X(v)} cy={y} r="9" fill={f ? col : '#271F7A'} stroke={col} strokeWidth="4" />
    );

    let overlay = null;
    if (config.a !== undefined) {
        overlay = (
            <>
                <line x1={X(config.a)} y1={y} x2={X(config.b)} y2={y} stroke={col} strokeWidth="7" />
                {dot(config.a, config.af)}
                {dot(config.b, config.bf)}
            </>
        );
    } else if (config.p !== undefined) {
        const end = config.dir === 'L' ? x0 - 6 : x1 + 6;
        overlay = (
            <>
                {config.dir ? (
                    <>
                        <line x1={X(config.p)} y1={y} x2={end} y2={y} stroke={col} strokeWidth="7" />
                        {config.dir === 'L' ? (
                            <path d={`M${end - 10} ${y} l14 -11 v22z`} fill={col} />
                        ) : (
                            <path d={`M${end + 10} ${y} l-14 -11 v22z`} fill={col} />
                        )}
                    </>
                ) : null}
                {dot(config.p, config.fill === true)}
            </>
        );
    }

    const ticks = [];
    for (let v = -10; v <= 10; v += 1) {
        const x = X(v);
        ticks.push(
            <g
                key={v}
                className={interactive ? 'tick' : undefined}
                role={interactive ? 'button' : undefined}
                tabIndex={interactive ? 0 : undefined}
                onClick={interactive ? () => onTick?.(v) : undefined}
                onKeyDown={
                    interactive
                        ? (e) => {
                              if (e.key === 'Enter' || e.key === ' ') {
                                  e.preventDefault();
                                  onTick?.(v);
                              }
                          }
                        : undefined
                }
            >
                <rect x={x - step / 2} y={y - 30} width={step} height="66" fill="transparent" />
                <line x1={x} y1={y - 8} x2={x} y2={y + 8} stroke="#BDB7EA" strokeWidth={v === 0 ? 3 : 2} />
                <text x={x} y={y + 30} textAnchor="middle" fontFamily="Nunito,sans-serif" fontWeight="800" fontSize="14" fill={v === 0 ? '#F4F2FF' : '#BDB7EA'}>
                    {v}
                </text>
            </g>
        );
    }

    return (
        <svg viewBox={`0 0 ${W} ${H}`} role="img" aria-label="Number line from minus 10 to 10">
            <line x1={x0 - 14} y1={y} x2={x1 + 14} y2={y} stroke="#BDB7EA" strokeWidth="3" />
            <path d={`M${x0 - 18} ${y} l10 -7 v14z M${x1 + 18} ${y} l-10 -7 v14z`} fill="#BDB7EA" />
            {ticks}
            {overlay}
        </svg>
    );
}

const EMPTY_LINE = { p: 0, fill: null, dir: null };

export default function MissionCrocodileGate() {
    const { claim } = useGameXp();
    const xpEndRef = useRef(false);

    const [screen, setScreen] = useState('intro');
    const [gate, setGate] = useState(0);
    const [qIndex, setQIndex] = useState(0);
    const [results, setResults] = useState(() => loadResults());
    const [attempts, setAttempts] = useState(0);
    const [hints, setHints] = useState(0);
    const [solved, setSolved] = useState(false);
    const [pick, setPick] = useState(null);
    const [line, setLine] = useState(EMPTY_LINE);
    const [sv, setSv] = useState({ s: null, n: '' });
    const [msg, setMsg] = useState(null);

    useEffect(() => {
        saveResults(results);
    }, [results]);

    const stars = useMemo(() => totalStars(results), [results]);

    const hudPower = useMemo(() => {
        let done = 0;
        GATES.forEach((g, i) => g.qs.forEach((_, j) => {
            if (results[`${i}-${j}`]) done += 1;
        }));
        return Math.round((done / 15) * 100);
    }, [results]);

    const resetQuestion = useCallback(() => {
        setAttempts(0);
        setHints(0);
        setSolved(false);
        setPick(null);
        setLine({ ...EMPTY_LINE });
        setSv({ s: null, n: '' });
        setMsg(null);
    }, []);

    useEffect(() => {
        if (screen !== 'end') {
            xpEndRef.current = false;
            return;
        }
        if (xpEndRef.current) return;
        xpEndRef.current = true;
        const points = Math.min(20, Math.max(1, Math.round((stars / 45) * 20)));
        claim(points);
    }, [screen, stars, claim]);

    const check = () => {
        const Q = GATES[gate].qs[qIndex];
        let ok = false;

        if (Q.t === 'choice') {
            if (pick === null) {
                setMsg({ type: 'hint', text: 'Pick an answer first, Cadet.' });
                return;
            }
            ok = pick === Q.a;
        }
        if (Q.t === 'line') {
            if (line.fill === null || !line.dir) {
                setMsg({ type: 'hint', text: 'Set all three parts: position, circle type and arrow.' });
                return;
            }
            ok = line.p === Q.ans.p && line.fill === Q.ans.fill && line.dir === Q.ans.dir;
        }
        if (Q.t === 'solve') {
            const v = parseNum(sv.n);
            if (!sv.s || Number.isNaN(v)) {
                setMsg({ type: 'hint', text: 'Pick a symbol and type a number.' });
                return;
            }
            ok = sv.s === Q.ans.s && v === Q.ans.n;
        }

        if (ok) {
            const st = Math.max(1, 3 - hints - attempts);
            const key = `${gate}-${qIndex}`;
            setResults((prev) => ({
                ...prev,
                [key]: Math.max(prev[key] || 0, st),
            }));
            setSolved(true);
            setMsg({ type: 'ok', text: `${'★'.repeat(st)} Code accepted! ${Q.why}` });
        } else {
            setAttempts((n) => n + 1);
            setMsg({ type: 'no', text: wrongMsg(Q, pick, line, sv) });
        }
    };

    const showHint = () => {
        const Q = GATES[gate].qs[qIndex];
        if (hints >= Q.h.length) return;
        setMsg({ type: 'hint', text: `Hint ${hints + 1}: ${Q.h[hints]}` });
        setHints((n) => n + 1);
    };

    const allGatesDone = GATES.every((_, i) => gateDone(results, i));

    const renderIntro = () => (
        <>
            <section className="hero pop">
                <div>
                    <div className="eyebrow">YounimatiQ Space Agency · Algebra Mission</div>
                    <h1>
                        Mission: <em>Crocodile Gate</em>
                    </h1>
                    <p>
                        A solar storm has knocked out <b>Space Station Orbit-Q</b>. Every door, bridge and reactor is now locked by a code, and every code is an{' '}
                        <b>inequality</b>.
                    </p>
                    <p>
                        You are the cadet on duty. Crack <b>5 gates</b> and <b>15 codes</b> to bring the station back to full power before the next storm hits.
                    </p>
                    <div className="row" style={{ marginTop: 14 }}>
                        <button type="button" className="btn" onClick={() => setScreen('map')}>
                            Start the mission
                        </button>
                        {stars > 0 ? (
                            <button
                                type="button"
                                className="btn ghost"
                                onClick={() => {
                                    setResults({});
                                    setScreen('intro');
                                }}
                            >
                                Restart from zero
                            </button>
                        ) : null}
                    </div>
                </div>
                <CrocIcon />
            </section>
            <div className="aria">
                <BotIcon />
                <div>
                    <div className="who">ARIA · STATION ROBOT</div>
                    <p>
                        Earn up to <b>3 stars</b> per code. Stuck? Ask me for a hint, but each hint costs a star. Wrong answers cost a star too, so check before you send!
                    </p>
                </div>
            </div>
        </>
    );

    const renderMap = () => (
        <>
            <div className="aria pop">
                <BotIcon />
                <div>
                    <div className="who">ARIA · MISSION MAP</div>
                    <p>
                        {stars ? 'Good work, Cadet. Pick the next glowing gate.' : 'Five gates stand between us and full power. Start with Gate 1.'}
                    </p>
                </div>
            </div>
            <nav className="map" aria-label="Mission gates">
                {GATES.map((g, i) => {
                    const un = unlocked(results, i);
                    const dn = gateDone(results, i);
                    const gs = gateStars(results, i);
                    const cur = un && !dn;
                    return (
                        <button
                            key={g.id}
                            type="button"
                            className={`gate${cur ? ' cur' : ''}`}
                            disabled={!un}
                            aria-label={`Gate ${i + 1}: ${g.name}${un ? '' : ' (locked)'}`}
                            onClick={() => {
                                setGate(i);
                                setScreen('gate');
                            }}
                        >
                            <span className="num" style={{ background: COL[g.id] }}>
                                {un ? g.id : <LockIcon />}
                            </span>
                            <span className="nm">{g.name}</span>
                            <span className="st">
                                {dn
                                    ? `${'★'.repeat(Math.round(gs / 3))} ${gs}/9`
                                    : un
                                      ? 'Ready'
                                      : 'Locked'}
                            </span>
                        </button>
                    );
                })}
            </nav>
            {allGatesDone ? (
                <div className="row">
                    <button type="button" className="btn" onClick={() => setScreen('end')}>
                        See mission report
                    </button>
                </div>
            ) : null}
        </>
    );

    const renderGateIntro = () => {
        const g = GATES[gate];
        return (
            <section className="card pop">
                <div className="cardtop">
                    <span className="tag" style={{ background: COL[g.id] }}>
                        Gate {g.id} · {g.skill}
                    </span>
                </div>
                <h2 style={{ fontSize: 'clamp(26px,5vw,38px)' }}>{g.name}</h2>
                <div className="aria">
                    <BotIcon />
                    <div>
                        <div className="who">ARIA · BRIEFING</div>
                        <p>{g.intro}</p>
                    </div>
                </div>
                <div className="actions">
                    <button type="button" className="btn ghost" onClick={() => setScreen('map')}>
                        Back to map
                    </button>
                    <button
                        type="button"
                        className="btn"
                        onClick={() => {
                            setQIndex(firstOpen(results, gate));
                            resetQuestion();
                            setScreen('q');
                        }}
                    >
                        Crack the codes
                    </button>
                </div>
            </section>
        );
    };

    const renderQuestion = () => {
        const g = GATES[gate];
        const Q = g.qs[qIndex];
        const hintsLeft = Q.h.length - hints;

        let body = null;
        if (Q.t === 'choice') {
            body = (
                <>
                    {Q.draw ? (
                        <div className="nl">
                            <NumberLineSvg config={Q.draw} />
                        </div>
                    ) : null}
                    <div className="opts" role="group" aria-label="Answer choices">
                        {Q.opts.map((o, k) => {
                            let cls = 'opt';
                            if (solved && k === Q.a) cls += ' right';
                            else if (msg?.type === 'no' && pick === k) cls += ' wrong';
                            else if (pick === k) cls += ' sel';
                            return (
                                <button
                                    key={o}
                                    type="button"
                                    className={cls}
                                    disabled={solved}
                                    onClick={() => {
                                        setPick(k);
                                        if (msg?.type === 'no') setMsg(null);
                                    }}
                                >
                                    {o}
                                </button>
                            );
                        })}
                    </div>
                </>
            );
        } else if (Q.t === 'line') {
            body = (
                <>
                    <div className="nl">
                        <NumberLineSvg
                            config={line}
                            interactive={!solved}
                            onTick={(v) => setLine((L) => ({ ...L, p: v }))}
                        />
                    </div>
                    <div className="ctrls">
                        <div className="grp">
                            <span className="lab">1 · Circle position</span>
                            <div className="seg">
                                <button
                                    type="button"
                                    className="small"
                                    aria-label="Move left"
                                    onClick={() => setLine((L) => ({ ...L, p: Math.max(-10, L.p - 1) }))}
                                >
                                    ◀
                                </button>
                                <span className="val" aria-live="polite">
                                    {line.p}
                                </span>
                                <button
                                    type="button"
                                    className="small"
                                    aria-label="Move right"
                                    onClick={() => setLine((L) => ({ ...L, p: Math.min(10, L.p + 1) }))}
                                >
                                    ▶
                                </button>
                            </div>
                        </div>
                        <div className="grp">
                            <span className="lab">2 · Circle type</span>
                            <div className="seg">
                                <button
                                    type="button"
                                    className="tog"
                                    aria-pressed={line.fill === false}
                                    onClick={() => setLine((L) => ({ ...L, fill: false }))}
                                >
                                    ○ Open
                                </button>
                                <button
                                    type="button"
                                    className="tog"
                                    aria-pressed={line.fill === true}
                                    onClick={() => setLine((L) => ({ ...L, fill: true }))}
                                >
                                    ● Filled
                                </button>
                            </div>
                        </div>
                        <div className="grp">
                            <span className="lab">3 · Arrow</span>
                            <div className="seg">
                                <button
                                    type="button"
                                    className="tog"
                                    aria-pressed={line.dir === 'L'}
                                    onClick={() => setLine((L) => ({ ...L, dir: 'L' }))}
                                >
                                    ◀ Left
                                </button>
                                <button
                                    type="button"
                                    className="tog"
                                    aria-pressed={line.dir === 'R'}
                                    onClick={() => setLine((L) => ({ ...L, dir: 'R' }))}
                                >
                                    Right ▶
                                </button>
                            </div>
                        </div>
                    </div>
                    <p className="line-tip">Tip: you can also tap a number on the line to move the circle.</p>
                </>
            );
        } else if (Q.t === 'solve') {
            body = (
                <>
                    <div className="grp">
                        <span className="lab">Pick the symbol</span>
                        <div className="opts five">
                            {SYM.map((s) => (
                                <button
                                    key={s}
                                    type="button"
                                    className={`opt${sv.s === s ? ' sel' : ''}`}
                                    disabled={solved}
                                    onClick={() => setSv((prev) => ({ ...prev, s }))}
                                >
                                    {s}
                                </button>
                            ))}
                        </div>
                    </div>
                    <div className="solve">
                        <span>{Q.v}</span>
                        <span style={{ color: 'var(--yellow)', minWidth: 28, textAlign: 'center' }}>{sv.s || '?'}</span>
                        <label htmlFor="mcg-num" className="lab" style={{ position: 'absolute', left: -9999 }}>
                            Your number
                        </label>
                        <input
                            id="mcg-num"
                            inputMode="numeric"
                            autoComplete="off"
                            value={sv.n}
                            placeholder="?"
                            disabled={solved}
                            onChange={(e) => setSv((prev) => ({ ...prev, n: e.target.value }))}
                            onKeyDown={(e) => {
                                if (e.key === 'Enter') check();
                            }}
                        />
                    </div>
                </>
            );
        }

        return (
            <section className="card pop">
                <div className="cardtop">
                    <span className="tag" style={{ background: COL[g.id] }}>
                        Gate {g.id} · {g.name}
                    </span>
                    <div className="prog" aria-label={`Code ${qIndex + 1} of 3`}>
                        {g.qs.map((_, j) => (
                            <i
                                key={j}
                                className={results[`${gate}-${j}`] ? 'done' : j === qIndex ? 'now' : ''}
                            />
                        ))}
                    </div>
                </div>
                <div className="q" dangerouslySetInnerHTML={{ __html: Q.q }} />
                {body}
                {msg ? (
                    <div className={`feedback ${msg.type} pop`} role="status">
                        {msg.text}
                    </div>
                ) : null}
                <div className="actions">
                    {solved ? (
                        <>
                            <button type="button" className="btn ghost" onClick={() => setScreen('map')}>
                                Mission map
                            </button>
                            <button
                                type="button"
                                className="btn"
                                onClick={() => {
                                    if (qIndex < 2) {
                                        setQIndex((n) => n + 1);
                                        resetQuestion();
                                    } else {
                                        setScreen('gwin');
                                    }
                                }}
                            >
                                {qIndex < 2 ? 'Next code' : 'Open the gate'}
                            </button>
                        </>
                    ) : (
                        <>
                            <button type="button" className="btn hintbtn" disabled={!hintsLeft} onClick={showHint}>
                                {hintsLeft ? `Ask ARIA for a hint (${hintsLeft} left)` : 'No hints left'}
                            </button>
                            <button type="button" className="btn" onClick={check}>
                                Send code
                            </button>
                        </>
                    )}
                </div>
            </section>
        );
    };

    const renderGateWin = () => {
        const g = GATES[gate];
        const gs = gateStars(results, gate);
        const last = gate === 4;
        return (
            <section className="badge pop">
                <span className="tag" style={{ background: COL[g.id] }}>
                    Gate {g.id} cleared
                </span>
                <h2 style={{ fontSize: 'clamp(26px,5vw,38px)' }}>{g.name} unlocked!</h2>
                <div className="stars" aria-label={`${gs} of 9 stars`}>
                    {'★'.repeat(gs)}
                    <span style={{ color: 'var(--line)' }}>{'★'.repeat(9 - gs)}</span>
                </div>
                <div className="aria" style={{ textAlign: 'left', maxWidth: 560 }}>
                    <BotIcon />
                    <div>
                        <div className="who">ARIA</div>
                        <p>{g.win}</p>
                    </div>
                </div>
                <div className="row" style={{ justifyContent: 'center' }}>
                    <button
                        type="button"
                        className="btn ghost"
                        onClick={() => {
                            setQIndex(0);
                            resetQuestion();
                            setScreen('q');
                        }}
                    >
                        Replay this gate
                    </button>
                    <button type="button" className="btn" onClick={() => setScreen(last ? 'end' : 'map')}>
                        {last ? 'Mission report' : 'Back to mission map'}
                    </button>
                </div>
            </section>
        );
    };

    const renderEnd = () => (
        <section className="badge pop">
            <span className="tag" style={{ background: 'var(--yellow)' }}>
                Mission complete
            </span>
            <h2 style={{ fontSize: 'clamp(24px,4.5vw,34px)' }}>Orbit-Q is back at full power</h2>
            <div className="stars">
                ★ {stars} / 45
            </div>
            <div>Your new rank</div>
            <div className="rank">{rankForStars(stars)}</div>
            <div className="log">
                {GATES.map((g, i) => (
                    <div key={g.id}>
                        <span style={{ color: COL[g.id] }}>●</span> {g.name}
                        <br />
                        <span style={{ color: 'var(--yellow)' }}>{gateStars(results, i)}/9 ★</span>
                    </div>
                ))}
            </div>
            <p className="end-note">
                You used symbols, number lines, the flip rule, solving and double inequalities to save the station. Replay any gate to chase 3 stars on every code.
            </p>
            <GameXpNote />
            <div className="row" style={{ justifyContent: 'center' }}>
                <button type="button" className="btn ghost" onClick={() => setScreen('map')}>
                    Mission map
                </button>
                <button
                    type="button"
                    className="btn"
                    onClick={() => {
                        setResults({});
                        setScreen('intro');
                    }}
                >
                    Play again from zero
                </button>
            </div>
        </section>
    );

    let content = null;
    if (screen === 'intro') content = renderIntro();
    else if (screen === 'map') content = renderMap();
    else if (screen === 'gate') content = renderGateIntro();
    else if (screen === 'q') content = renderQuestion();
    else if (screen === 'gwin') content = renderGateWin();
    else if (screen === 'end') content = renderEnd();

    return (
        <div className="mcg">
            <div className="wrap">
                <header>
                    <div className="brand">YounimatiQ</div>
                    <div className="hud">
                        <span className="chip" title="Stars earned">
                            <span className="s">★</span> {stars} / 45
                        </span>
                        <span className="chip" title="Station power">
                            Power {hudPower}%
                        </span>
                    </div>
                </header>
                <main className="screen">{content}</main>
                <p className="foot">© YounimatiQ · Future-Fit Education · Algebra: Inequalities</p>
            </div>
        </div>
    );
}