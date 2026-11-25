import { useMemo, useRef, useState } from 'react';
import './styles/alien-verb-lab.css';
import { GameXpNote, useGameXp } from './GameXp';

const LABEL = { action: 'Action', helping: 'Helping', linking: 'Linking' };

/* PART A — Classify target verb */
const PART_A_DATA = [
    { id: 1, s: 'Zeke [whistled] at the classroom door.', t: 'action', e: 'Whistling is something Zeke DOES.' },
    { id: 2, s: 'Zeke [is] the new kid in second grade.', t: 'linking', e: '"Is" links Zeke to "the new kid".' },
    { id: 3, s: 'Harris [was] staring at Zeke in the science lab.', t: 'helping', e: '"Was" helps the main verb "staring".' },
    { id: 4, s: "Zeke's green lunch fruit [looked] strange.", t: 'linking', e: 'Swap test: the fruit IS strange. So it is linking.' },
    { id: 5, s: 'Roxy [kicked] the soccer ball to Harris.', t: 'action', e: 'Kicking is an action.' },
    { id: 6, s: 'Zeke [can] make colors swirl into a rainbow.', t: 'helping', e: '"Can" helps the main verb "make".' },
    { id: 7, s: "Zeke's mom [floated] into the living room.", t: 'action', e: 'Floating is something she does.' },
    { id: 8, s: 'Harris [felt] nervous on Saturday morning.', t: 'linking', e: 'Harris WAS nervous. "Felt" links him to "nervous".' },
    { id: 9, s: 'Xad [has] fixed a machine to watch Bonkas games.', t: 'helping', e: '"Has" helps the main verb "fixed".' },
    { id: 10, s: 'Roxy [seems] kind to everyone.', t: 'linking', e: 'Roxy IS kind. "Seems" is linking.' },
    { id: 11, s: 'Zeke [slurped] up a long purple strand.', t: 'action', e: 'Slurping is an action.' },
    { id: 12, s: 'The three friends [were] playing a comet video game.', t: 'helping', e: '"Were" helps the main verb "playing".' },
];

/* PART B — Verb teams: 1 tap for helping, 2 taps for main */
const PART_B_DATA = [
    { id: 1, s: 'Harris is reading his alien comic book.', h: 'is', m: 'reading' },
    { id: 2, s: 'Zeke was balancing on just one finger.', h: 'was', m: 'balancing' },
    { id: 3, s: 'Roxy will try out for the school play.', h: 'will', m: 'try' },
    { id: 4, s: "Zeke's parents are studying human clothes.", h: 'are', m: 'studying' },
    { id: 5, s: 'Harris has invited Zeke to his house.', h: 'has', m: 'invited' },
    { id: 6, s: 'Zeke can transform a tiny twig into a spoon.', h: 'can', m: 'transform' },
];

/* PART C — Fill in the blank + classify job */
const BANK_WORDS = ['is', 'will', 'looks', 'painted', 'were', 'smell'];
const PART_C_DATA = [
    { id: 1, pre: 'The purple space flowers', post: 'sweet like honey.', w: 'smell', t: 'linking', e: '"Smell" links the flowers to "sweet".' },
    { id: 2, pre: 'Harris and Zeke', post: 'the treehouse roof green.', w: 'painted', t: 'action', e: 'Painting is an action they did!' },
    { id: 3, pre: 'Tomorrow, the class', post: 'visit the science museum.', w: 'will', t: 'helping', e: '"Will" helps the main verb "visit".' },
    { id: 4, pre: 'Zeke', post: 'excited to show his spaceship.', w: 'is', t: 'linking', e: '"Is" links Zeke to the describing word "excited".' },
    { id: 5, pre: 'That shiny meteorite rock', post: 'glow-in-the-dark!', w: 'looks', t: 'linking', e: 'Swap test: That rock IS glow-in-the-dark.' },
    { id: 6, pre: 'The two dogs', post: 'barking at the flying saucer.', w: 'were', t: 'helping', e: '"Were" helps the main verb "barking".' },
];

/* PART D — Same word, different job */
const PART_D_DATA = [
    {
        word: 'smell',
        items: [
            { id: 'd1a', s: 'The Tragas fruit [smells] delicious.', t: 'linking', e: 'The fruit is not smelling anything! "Smells" links fruit to delicious.' },
            { id: 'd1b', s: 'Harris [smells] the odd purple stew.', t: 'action', e: 'Harris is actively doing the smelling, so it is an action!' },
        ],
    },
    {
        word: 'looks',
        items: [
            { id: 'd2a', s: 'Zeke [looks] through the giant telescope.', t: 'action', e: 'Zeke is actively looking through the glass!' },
            { id: 'd2b', s: 'The night sky [looks] magical.', t: 'linking', e: 'The sky is not looking at anything. It links "sky" to "magical".' },
        ],
    },
    {
        word: 'has',
        items: [
            { id: 'd3a', s: 'Zeke [has] a glowing blue compass.', t: 'action', e: 'No other verb follows. "Has" means owns, so it is the main verb.' },
            { id: 'd3b', s: 'Zeke [has] fixed the propulsion rocket.', t: 'helping', e: '"Has" helps the main verb "fixed".' },
        ],
    },
    {
        word: 'tastes',
        items: [
            { id: 'd4a', s: 'The star pudding [tastes] like cinnamon.', t: 'linking', e: 'The pudding does not taste anything! It links pudding to cinnamon.' },
            { id: 'd4b', s: 'Roxy [tastes] the warm muffin.', t: 'action', e: 'Roxy is doing the tasting, so it is an action.' },
        ],
    },
];

function runConfetti(canvas) {
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    const width = (canvas.width = canvas.offsetWidth || window.innerWidth);
    const height = (canvas.height = canvas.offsetHeight || window.innerHeight);
    const colors = ['#84cc16', '#f97316', '#0ea5e9', '#22c55e', '#facc15', '#a855f7'];
    const particles = Array.from({ length: 120 }, () => ({
        x: Math.random() * width,
        y: -20 - Math.random() * height * 0.4,
        w: 6 + Math.random() * 6,
        h: 8 + Math.random() * 8,
        vy: 2 + Math.random() * 3,
        vx: -1.5 + Math.random() * 3,
        r: Math.random() * 6,
        vr: -0.2 + Math.random() * 0.4,
        c: colors[Math.floor(Math.random() * colors.length)],
    }));
    let frames = 0;
    function tick() {
        ctx.clearRect(0, 0, width, height);
        particles.forEach((p) => {
            p.x += p.vx;
            p.y += p.vy;
            p.r += p.vr;
            ctx.save();
            ctx.translate(p.x, p.y);
            ctx.rotate(p.r);
            ctx.fillStyle = p.c;
            ctx.fillRect(-p.w / 2, -p.h / 2, p.w, p.h);
            ctx.restore();
        });
        if (++frames < 180) requestAnimationFrame(tick);
        else ctx.clearRect(0, 0, width, height);
    }
    tick();
}

function cleanWord(w) {
    return (w || '').replace(/[^a-zA-Z']/g, '').toLowerCase();
}

export default function AlienVerbLab() {
    const { claim } = useGameXp();
    const canvasRef = useRef(null);
    const reportRef = useRef(null);

    const [name, setName] = useState('');
    const [ansA, setAnsA] = useState({}); // { [id]: 'action' | 'helping' | 'linking' }
    const [tapB, setTapB] = useState({}); // { [rowId]: { [wordIdx]: 1 (helping) | 2 (main) } }
    const [ansC, setAnsC] = useState({}); // { [id]: { word: '', type: '' } }
    const [ansD, setAnsD] = useState({}); // { [id]: 'action' | 'helping' | 'linking' }
    const [writeE, setWriteE] = useState({ action: '', helping: '', linking: '' });

    const [checked, setChecked] = useState(false);
    const [results, setResults] = useState(null);

    // Track used bank words in Part C
    const usedBankWords = useMemo(() => {
        return Object.values(ansC)
            .map((c) => c?.word)
            .filter(Boolean);
    }, [ansC]);

    // Handle Part B word taps
    const handleTapWordB = (rowId, wordIndex) => {
        setTapB((prev) => {
            const row = { ...(prev[rowId] || {}) };
            const curState = row[wordIndex] || 0;
            const nextState = (curState + 1) % 3; // 0 -> 1 (help) -> 2 (main) -> 0 (clear)
            if (nextState === 0) {
                delete row[wordIndex];
            } else {
                row[wordIndex] = nextState;
            }
            return { ...prev, [rowId]: row };
        });
    };

    const handleCheck = () => {
        let scoreA = 0;
        PART_A_DATA.forEach((item) => {
            if (ansA[item.id] === item.t) scoreA++;
        });

        let scoreB = 0;
        PART_B_DATA.forEach((item) => {
            const rowState = tapB[item.id] || {};
            const words = item.s.split(' ');
            const hIdx = Object.keys(rowState).find((k) => rowState[k] === 1);
            const mIdx = Object.keys(rowState).find((k) => rowState[k] === 2);
            const hWord = hIdx !== undefined ? cleanWord(words[hIdx]) : '';
            const mWord = mIdx !== undefined ? cleanWord(words[mIdx]) : '';
            if (hWord === item.h) scoreB++;
            if (mWord === item.m) scoreB++;
        });

        let scoreC = 0;
        PART_C_DATA.forEach((item) => {
            const entry = ansC[item.id] || {};
            if (entry.word === item.w) scoreC++;
            if (entry.type === item.t) scoreC++;
        });

        let scoreD = 0;
        PART_D_DATA.forEach((pair) => {
            pair.items.forEach((item) => {
                if (ansD[item.id] === item.t) scoreD++;
            });
        });

        const totalEarned = scoreA + scoreB + scoreC + scoreD;
        const maxScore = PART_A_DATA.length + PART_B_DATA.length * 2 + PART_C_DATA.length * 2 + 8; // 12 + 12 + 12 + 8 = 44 points
        const pct = totalEarned / maxScore;
        const awardedXp = Math.min(15, Math.max(1, Math.round(pct * 15)));

        setResults({
            scoreA,
            totalA: PART_A_DATA.length,
            scoreB,
            totalB: PART_B_DATA.length * 2,
            scoreC,
            totalC: PART_C_DATA.length * 2,
            scoreD,
            totalD: 8,
            totalEarned,
            maxScore,
            pct,
            awardedXp,
        });
        setChecked(true);

        claim(awardedXp);

        if (pct >= 0.8) {
            setTimeout(() => runConfetti(canvasRef.current), 100);
        }

        setTimeout(() => {
            reportRef.current?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 150);
    };

    const handleReset = () => {
        setAnsA({});
        setTapB({});
        setAnsC({});
        setAnsD({});
        setWriteE({ action: '', helping: '', linking: '' });
        setChecked(false);
        setResults(null);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    // Helper for rendering bracketed verbs
    const renderBracketed = (text) => {
        const parts = text.split(/\[(.+?)\]/);
        if (parts.length === 3) {
            return (
                <span>
                    {parts[0]}
                    <span className="verb">{parts[1]}</span>
                    {parts[2]}
                </span>
            );
        }
        return <span>{text}</span>;
    };

    return (
        <div className="ym-avl">
            <div className="stars-bg" aria-hidden="true" />
            <canvas id="ym-avl-confetti" ref={canvasRef} />
            <div className="wrap">
                <header>
                    <span className="ufo" aria-hidden="true">🛸</span>
                    <h1>
                        Alien Verb Lab: <span>Action, Helping & Linking</span>
                    </h1>
                    <p>Help Zeke classify verbs and send a mission report back to planet Tragas!</p>
                </header>

                <div className="card">
                    <div className="name-row">
                        <label>
                            Earth Scientist Name:
                            <input
                                type="text"
                                placeholder="Your name (e.g. Maya)"
                                value={name}
                                onChange={(e) => setName(e.target.value)}
                            />
                        </label>
                    </div>

                    <div style={{ marginTop: 16 }}>
                        <div className="key">
                            <div className="k a">
                                <b>🟧 Action Verb</b>
                                Shows what someone or something DOES. (Can you act it out?)
                            </div>
                            <div className="k h">
                                <b>🟦 Helping Verb</b>
                                Works with a main verb in a team (is, are, was, will, has, can).
                            </div>
                            <div className="k l">
                                <b>🟩 Linking Verb</b>
                                Connects the subject to a describing word. (Swap test: swap for "is"!).
                            </div>
                        </div>
                        <div className="tip">
                            💡 <b>Zeke's Tip:</b> Helping verbs always have a buddy verb right after them! Linking verbs have NO action buddies.
                        </div>
                    </div>
                </div>

                {/* PART A */}
                <div className="card">
                    <h2>
                        <span className="num">Part A</span> Classify Verb Jobs
                    </h2>
                    <p className="instr">Read each story sentence and choose whether the yellow verb is Action, Helping, or Linking.</p>

                    {PART_A_DATA.map((item) => {
                        const cur = ansA[item.id];
                        const isCorrect = cur === item.t;
                        return (
                            <div key={item.id} className="q">
                                <div className="qs">
                                    <span className="n">{item.id}.</span> {renderBracketed(item.s)}
                                </div>
                                <div className="opts">
                                    {['action', 'helping', 'linking'].map((type) => (
                                        <button
                                            key={type}
                                            type="button"
                                            className={`opt ${type}${cur === type ? ` sel ${type}` : ''}`}
                                            onClick={() => setAnsA((prev) => ({ ...prev, [item.id]: type }))}
                                        >
                                            {LABEL[type]}
                                        </button>
                                    ))}
                                </div>
                                {checked && (
                                    <div className={`fb ${isCorrect ? 'ok' : 'no'}`}>
                                        {isCorrect ? (
                                            <span>✅ Correct! {item.e}</span>
                                        ) : (
                                            <span>
                                                ❌ Not quite. Answer: <b>{LABEL[item.t]}</b>. {item.e}
                                            </span>
                                        )}
                                    </div>
                                )}
                            </div>
                        );
                    })}
                </div>

                {/* PART B */}
                <div className="card">
                    <h2>
                        <span className="num">Part B</span> Find the Verb Team
                    </h2>
                    <p className="instr">
                        Each sentence has a helping verb and a main verb working together. Tap a word once for <b>helping (blue)</b>, and tap it again for <b>main verb (purple)</b>. A third tap clears it.
                    </p>
                    <div className="legend">
                        <span>
                            <i className="dot" style={{ background: 'var(--help)' }} /> 1 tap: helping verb
                        </span>
                        <span>
                            <i className="dot" style={{ background: '#a855f7' }} /> 2 taps: main verb
                        </span>
                    </div>

                    {PART_B_DATA.map((item) => {
                        const words = item.s.split(' ');
                        const rowState = tapB[item.id] || {};
                        const hIdx = Object.keys(rowState).find((k) => rowState[k] === 1);
                        const mIdx = Object.keys(rowState).find((k) => rowState[k] === 2);
                        const hWord = hIdx !== undefined ? cleanWord(words[hIdx]) : '';
                        const mWord = mIdx !== undefined ? cleanWord(words[mIdx]) : '';
                        const hOk = hWord === item.h;
                        const mOk = mWord === item.m;
                        const ok = hOk && mOk;

                        return (
                            <div key={item.id} className="q">
                                <div className="words">
                                    <span style={{ fontWeight: 800, marginRight: 6, color: '#94a3b8', alignSelf: 'center' }}>
                                        {item.id}.
                                    </span>
                                    {words.map((w, wIdx) => {
                                        const state = rowState[wIdx] || 0;
                                        const cls = `w${state === 1 ? ' h' : state === 2 ? ' m' : ''}`;
                                        return (
                                            <button
                                                key={wIdx}
                                                type="button"
                                                className={cls}
                                                onClick={() => handleTapWordB(item.id, wIdx)}
                                            >
                                                {w}
                                            </button>
                                        );
                                    })}
                                </div>
                                {checked && (
                                    <div className={`fb ${ok ? 'ok' : 'no'}`}>
                                        {ok ? (
                                            <span>
                                                ✅ Great team spotting! <b>"{item.h}"</b> helps <b>"{item.m}"</b>.
                                            </span>
                                        ) : (
                                            <span>
                                                ❌ {hOk ? 'Helping verb ✔' : `Helping verb: "${item.h}" ✘`} •{' '}
                                                {mOk ? 'Main verb ✔' : `Main verb: "${item.m}" ✘`}
                                            </span>
                                        )}
                                    </div>
                                )}
                            </div>
                        );
                    })}
                </div>

                {/* PART C */}
                <div className="card">
                    <h2>
                        <span className="num">Part C</span> Fill in the Blank
                    </h2>
                    <p className="instr">Choose the verb from the word bank that fits each sentence (use each word once). Then choose its job.</p>

                    <div className="bank">
                        <span style={{ fontWeight: 800, color: '#4338ca', marginRight: 4 }}>Word Bank:</span>
                        {BANK_WORDS.map((w) => (
                            <span key={w} className={`bank-tag${usedBankWords.includes(w) ? ' used' : ''}`}>
                                {w}
                            </span>
                        ))}
                    </div>

                    {PART_C_DATA.map((item) => {
                        const cur = ansC[item.id] || { word: '', type: '' };
                        const wOk = cur.word === item.w;
                        const tOk = cur.type === item.t;
                        const ok = wOk && tOk;

                        return (
                            <div key={item.id} className="q">
                                <div className="qs">
                                    <span className="n">{item.id}.</span> {item.pre} <b>[ _____ ]</b> {item.post}
                                </div>
                                <div className="c-pick">
                                    <label>
                                        Verb:{' '}
                                        <select
                                            value={cur.word}
                                            onChange={(e) =>
                                                setAnsC((prev) => ({
                                                    ...prev,
                                                    [item.id]: { ...(prev[item.id] || {}), word: e.target.value },
                                                }))
                                            }
                                        >
                                            <option value="">-- pick word --</option>
                                            {BANK_WORDS.map((w) => (
                                                <option key={w} value={w}>
                                                    {w}
                                                </option>
                                            ))}
                                        </select>
                                    </label>

                                    <div className="opts" style={{ marginLeft: 8 }}>
                                        {['action', 'helping', 'linking'].map((type) => (
                                            <button
                                                key={type}
                                                type="button"
                                                className={`opt ${type}${cur.type === type ? ` sel ${type}` : ''}`}
                                                onClick={() =>
                                                    setAnsC((prev) => ({
                                                        ...prev,
                                                        [item.id]: { ...(prev[item.id] || {}), type },
                                                    }))
                                                }
                                            >
                                                {LABEL[type]}
                                            </button>
                                        ))}
                                    </div>
                                </div>
                                {checked && (
                                    <div className={`fb ${ok ? 'ok' : 'no'}`}>
                                        {ok ? (
                                            <span>✅ Correct! {item.e}</span>
                                        ) : (
                                            <span>
                                                ❌ {wOk ? 'Word ✔' : `Word was "${item.w}" ✘`} •{' '}
                                                {tOk ? 'Job ✔' : `Job was ${LABEL[item.t]} ✘`}. {item.e}
                                            </span>
                                        )}
                                    </div>
                                )}
                            </div>
                        );
                    })}
                </div>

                {/* PART D */}
                <div className="card">
                    <h2>
                        <span className="num">Part D</span> Same Word, Different Job
                    </h2>
                    <p className="instr">Tricky! The very same verb can do different jobs depending on the sentence. Choose the job in each sentence.</p>

                    {PART_D_DATA.map((pair, pIdx) => (
                        <div key={pIdx} className="pair">
                            <h3>The word "{pair.word}"</h3>
                            {pair.items.map((item) => {
                                const cur = ansD[item.id];
                                const isCorrect = cur === item.t;
                                return (
                                    <div key={item.id} className="q">
                                        <div className="qs">{renderBracketed(item.s)}</div>
                                        <div className="opts">
                                            {['action', 'helping', 'linking'].map((type) => (
                                                <button
                                                    key={type}
                                                    type="button"
                                                    className={`opt ${type}${cur === type ? ` sel ${type}` : ''}`}
                                                    onClick={() => setAnsD((prev) => ({ ...prev, [item.id]: type }))}
                                                >
                                                    {LABEL[type]}
                                                </button>
                                            ))}
                                        </div>
                                        {checked && (
                                            <div className={`fb ${isCorrect ? 'ok' : 'no'}`}>
                                                {isCorrect ? (
                                                    <span>✅ Correct! {item.e}</span>
                                                ) : (
                                                    <span>
                                                        ❌ Not quite. Answer: <b>{LABEL[item.t]}</b>. {item.e}
                                                    </span>
                                                )}
                                            </div>
                                        )}
                                    </div>
                                );
                            })}
                        </div>
                    ))}
                </div>

                {/* PART E */}
                <div className="card">
                    <h2>
                        <span className="num">Part E</span> Write Your Own Report for Tragas
                    </h2>
                    <p className="instr">Zeke must send a report home about Earth. Write one sentence for each kind of verb.</p>

                    <div className="write">
                        <label>
                            <span className="tagx" style={{ background: 'var(--action)' }}>
                                Action
                            </span>{' '}
                            Something Zeke did on Earth:
                            <textarea
                                placeholder="Example starter: At recess, Zeke tossed the flying disc..."
                                value={writeE.action}
                                onChange={(e) => setWriteE((prev) => ({ ...prev, action: e.target.value }))}
                            />
                        </label>
                        <label>
                            <span className="tagx" style={{ background: 'var(--help)' }}>
                                Helping
                            </span>{' '}
                            Something a character is doing or will do:
                            <textarea
                                placeholder="Example starter: Harris is trying to build a telescope..."
                                value={writeE.helping}
                                onChange={(e) => setWriteE((prev) => ({ ...prev, helping: e.target.value }))}
                            />
                        </label>
                        <label>
                            <span className="tagx" style={{ background: 'var(--link)' }}>
                                Linking
                            </span>{' '}
                            Describe how a character feels or seems:
                            <textarea
                                placeholder="Example starter: Roxy seems excited about the alien adventure..."
                                value={writeE.linking}
                                onChange={(e) => setWriteE((prev) => ({ ...prev, linking: e.target.value }))}
                            />
                        </label>
                        <p className="starter">✏️ Check your sentences: Did each start with a capital letter and end with a full stop?</p>
                    </div>
                </div>

                <div className="actions">
                    <button type="button" className="btn" onClick={handleCheck}>
                        🚀 Check my answers
                    </button>
                    <button type="button" className="btn ghost" onClick={handleReset}>
                        🔄 Start over
                    </button>
                </div>

                {/* Results Report */}
                {results && (
                    <div className="card report" ref={reportRef}>
                        <div style={{ fontSize: '3rem' }}>
                            {results.pct >= 0.9 ? '🏆' : results.pct >= 0.7 ? '🚀' : results.pct >= 0.4 ? '🛸' : '👽'}
                        </div>
                        <h2>
                            Mission Report{name ? `, ${name}` : ''}!
                        </h2>
                        <div className="big">
                            {results.totalEarned} / {results.maxScore}
                        </div>
                        <p style={{ marginTop: 6, fontWeight: 700, fontSize: '1.2rem', color: '#365314' }}>
                            {results.pct >= 0.9
                                ? 'Zeke says: You are an Earth verb expert!'
                                : results.pct >= 0.7
                                ? 'Great flying! Check any red hints to complete the mission.'
                                : results.pct >= 0.4
                                ? "Good start! Read Zeke's Verb Guide again, then fix the red items."
                                : 'Keep exploring! Review the tips and try again.'}
                        </p>

                        <div className="bars">
                            <div>
                                <span>Part A: Verb jobs</span>
                                <span>{results.scoreA} / {results.totalA}</span>
                            </div>
                            <div>
                                <span>Part B: Verb teams</span>
                                <span>{results.scoreB} / {results.totalB}</span>
                            </div>
                            <div>
                                <span>Part C: Fill in blank</span>
                                <span>{results.scoreC} / {results.totalC}</span>
                            </div>
                            <div>
                                <span>Part D: Same word, different job</span>
                                <span>{results.scoreD} / {results.totalD}</span>
                            </div>
                            <div>
                                <span>Part E: Creative writing</span>
                                <span>Completed ✏️</span>
                            </div>
                        </div>

                        <GameXpNote />

                        <p className="instr" style={{ marginTop: 14 }}>
                            Any incorrect items above now show hints. You can fix them and press <b>Check my answers</b> again!
                        </p>
                    </div>
                )}
            </div>
        </div>
    );
}

