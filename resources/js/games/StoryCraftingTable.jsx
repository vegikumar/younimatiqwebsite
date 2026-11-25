import { useEffect, useMemo, useRef, useState } from 'react';
import './styles/story-crafting-table.css';
import { GameXpNote, useGameXp } from './GameXp';

const STEP_NAMES = ['1 · BUILD', '2 · STORY', '3 · IMPROVE', '4 · SHARE'];
const INITIAL = {
    step: 0, build: 'house', mat: 'wood', tool: 'pickaxe', first: '', then: '', final: '', feel: '', because: '', sudden: false, lastAdd: '',
};
const OPT = {
    build: ['house', 'igloo', 'wall', 'tower', 'boat', 'underwater base'],
    mat: ['wood', 'stone', 'snow', 'ice', 'glass', 'diamond', 'sand'],
    tool: ['pickaxe', 'axe', 'shovel'],
    first: ['went exploring in the forest', 'collected wood with Evan', 'found a dark cave', 'planned a base with Grace'],
    then: ['a creeper came', 'night fell and zombies came', 'a big storm', 'my build fell down'],
    final: ['rebuilt it with my friends', 'built a wall to stay safe', 'ran home safely', 'found a shiny diamond'],
    feel: ['nervous', 'excited', 'scared', 'brave', 'proud', 'happy'],
    because: ['I wanted to stay safe', 'my friends needed a home', 'I love building', 'the night was coming'],
};
const MAT = {
    wood: ['#b07a3e', '#8a5a2a'], stone: ['#8a8a8a', '#6c6c6c'], glass: ['#cdeefc', '#9fd6ef'], diamond: ['#5ee6e0', '#2fb8b2'],
    sand: ['#e8d69a', '#cdb877'], snow: ['#f7fbff', '#c9d8e6'], ice: ['#a9d4f5', '#7fb4de'],
};
const SHAPES = {
    house: ['....rrrrrr....', '...rrrrrrrr...', '..rrrrrrrrrr..', '...########...', '...#..##..#...', '...########...', '...###dd###...', '...###dd###...'],
    igloo: ['..............', '.....####.....', '...########...', '..##########..', '.############.', '.############.', '.#####dd#####.', '######dd######'],
    wall: ['#.#.#.#.#.#.#.', '##############', '##############', '##############', '##############', '##############', '##############', '##############'],
    tower: ['....#.#.#.....', '....#####.....', '....#.#.#.....', '....#####.....', '....##.##.....', '....#####.....', '....##d##.....', '....##d##.....'],
    boat: ['..............', '......w.......', '......w.......', '....rrrrr.....', '......w.......', '.############.', '..##########..', '...########...'],
    'underwater base': ['..............', '....######....', '...#..##..#...', '..##########..', '..#.##..##.#..', '..##########..', '..##########..', '..###dddd###..'],
};

function beep(f, d, v = 0.05) {
    try {
        const ac = beep.ac || (beep.ac = new (window.AudioContext || window.webkitAudioContext)());
        const o = ac.createOscillator();
        const g = ac.createGain();
        o.type = 'square';
        o.frequency.value = f;
        g.gain.value = v;
        o.connect(g);
        g.connect(ac.destination);
        const t = ac.currentTime;
        g.gain.exponentialRampToValueAtTime(0.0001, t + d);
        o.start(t);
        o.stop(t + d);
    } catch {
        /* sound is optional */
    }
}

const an = (w) => (/^[aeiou]/i.test(w) ? 'an' : 'a');

function sentencesOf(S) {
    const out = [];
    out.push({ k: 'build', t: `I built ${an(S.build)} ${S.build}${S.because ? ` because ${S.because}` : ''}.` });
    out.push({ k: 'mat', t: `It is made of ${S.mat}. I used my ${S.tool}.` });
    if (S.first) out.push({ k: 'first', t: `First, I ${S.first}.` });
    if (S.then) {
        if (S.sudden) out.push({ k: 'then', t: /came$/.test(S.then) ? `Suddenly, ${S.then}!` : `Suddenly, ${S.then} happened!` });
        else out.push({ k: 'then', t: `Then, ${S.then} happened.` });
    }
    if (S.feel && S.then) out.push({ k: 'feel', t: `I felt ${S.feel}.` });
    if (S.final) out.push({ k: 'final', t: `Finally, I ${S.final}.` });
    return out;
}

function starsOf(S) {
    return (S.first ? 1 : 0) + (S.then ? 1 : 0) + (S.final ? 1 : 0) + (S.feel ? 1 : 0) + (S.because ? 1 : 0) + (S.sudden ? 1 : 0);
}

function drawBuild(canvas, S) {
    if (!canvas) return;
    const cx = canvas.getContext('2d');
    const W = canvas.width;
    const H = canvas.height;
    const water = S.build === 'underwater base' || S.build === 'boat';
    cx.fillStyle = S.build === 'underwater base' ? '#1d5f9e' : '#8fd0ff';
    cx.fillRect(0, 0, W, H);
    if (S.build !== 'underwater base') {
        cx.fillStyle = '#fff';
        cx.fillRect(14, 8, 20, 4);
        cx.fillRect(18, 5, 10, 3);
        cx.fillRect(110, 14, 24, 4);
        cx.fillStyle = '#ffe14a';
        cx.fillRect(136, 4, 12, 12);
    } else {
        cx.fillStyle = 'rgba(255,255,255,.35)';
        [[20, 20], [40, 40], [130, 25], [120, 55]].forEach(([x, y]) => cx.fillRect(x, y, 2, 2));
    }
    const gy = H - 16;
    if (S.build === 'boat') {
        cx.fillStyle = '#2f7fd0';
        cx.fillRect(0, gy - 4, W, 20);
        cx.fillStyle = '#5aa6ee';
        for (let x = 0; x < W; x += 12) cx.fillRect(x, gy - 4, 6, 2);
    } else if (S.build === 'underwater base') {
        cx.fillStyle = '#d8c27a';
        cx.fillRect(0, gy, W, 16);
    } else if (S.build === 'igloo') {
        cx.fillStyle = '#f2f7fc';
        cx.fillRect(0, gy, W, 16);
        cx.fillStyle = '#d3e2ef';
        for (let x = 0; x < W; x += 10) cx.fillRect(x, gy + 6, 5, 2);
    } else {
        cx.fillStyle = '#5fae35';
        cx.fillRect(0, gy, W, 4);
        cx.fillStyle = '#8b5a2b';
        cx.fillRect(0, gy + 4, W, 12);
    }
    const sh = SHAPES[S.build];
    const [m, md] = MAT[S.mat] || MAT.wood;
    const C = 6;
    const x0 = Math.floor((W - 14 * C) / 2);
    const y0 = gy - sh.length * C + (S.build === 'boat' ? 4 : 0);
    sh.forEach((row, r) => [...row].forEach((ch, c) => {
        if (ch === '.') return;
        const x = x0 + c * C;
        const y = y0 + r * C;
        let a = m;
        let b = md;
        if (ch === 'r') {
            a = S.build === 'boat' ? '#f4f1e6' : '#b8432f';
            b = S.build === 'boat' ? '#d8d2c0' : '#8e3222';
        }
        if (ch === 'w') { a = '#7a5230'; b = '#5c3c22'; }
        if (ch === 'd') { a = '#5c3c22'; b = '#3e2814'; }
        cx.fillStyle = a;
        cx.fillRect(x, y, C, C);
        cx.fillStyle = b;
        cx.fillRect(x, y + C - 1, C, 1);
        cx.fillRect(x + C - 1, y, 1, C);
        if (S.mat === 'glass' && ch === '#') {
            cx.fillStyle = 'rgba(255,255,255,.7)';
            cx.fillRect(x + 1, y + 1, 1, 2);
        }
    }));
}

function Chips({ k, options, value, onPick }) {
    return (
        <div className="chips" role="group">
            {options.map((option) => (
                <button key={option} type="button" className="chipb" aria-pressed={value === option} onClick={() => onPick(k, option)}>{option}</button>
            ))}
        </div>
    );
}

export default function StoryCraftingTable() {
    const { claim } = useGameXp();
    const canvasRef = useRef(null);
    const [S, setS] = useState(INITIAL);
    const [toast, setToast] = useState('');
    const lines = useMemo(() => sentencesOf(S), [S]);
    const stars = starsOf(S);

    useEffect(() => {
        drawBuild(canvasRef.current, S);
    }, [S]);

    const pick = (key, value) => {
        setS((prev) => {
            const locked = ['build', 'mat', 'tool'].includes(key);
            const next = prev[key] === value && !locked ? '' : value;
            return { ...prev, [key]: next, lastAdd: key === 'because' ? 'build' : key };
        });
        beep(660, 0.06);
        setTimeout(() => beep(880, 0.08), 60);
    };

    const typeOwn = (key, value) => {
        const clean = value.trim().replace(/[.!]+$/, '');
        setS((prev) => ({ ...prev, [key]: clean, lastAdd: key === 'because' ? 'build' : key }));
    };

    const storyText = lines.map((line) => line.t).join(' ');

    return (
        <div className="ym-sct">
            <div className="wrap">
                <header>
                    <div>
                        <h1>STORY CRAFTING TABLE</h1>
                        <p className="sub">Minecraft Storytellers · Read. Write. Create. Craft your build, write your story, then power it up!</p>
                    </div>
                    <div className="stars" aria-live="polite">STARS <b>{stars}</b>/6</div>
                </header>
                <nav className="steps" aria-label="Steps">
                    {STEP_NAMES.map((name, i) => <span key={name} className={`step${i === S.step ? ' on' : i < S.step ? ' done' : ''}`}>{name}</span>)}
                </nav>
                <div className="main">
                    <section className="table" aria-live="polite">
                        {S.step === 0 && (
                            <>
                                <h2>WHAT DID YOU BUILD?</h2>
                                <p className="hint">Imagine and plan: choose your build, its material and your tool.</p>
                                <div className="slot">
                                    <div className="frame">I built {an(S.build)} <span className="blank">{S.build}</span>.</div>
                                    <Chips k="build" options={OPT.build} value={S.build} onPick={pick} />
                                </div>
                                <div className="slot">
                                    <div className="frame">It is made of <span className="blank">{S.mat}</span>.</div>
                                    <Chips k="mat" options={OPT.mat} value={S.mat} onPick={pick} />
                                </div>
                                <div className="slot">
                                    <div className="frame">I used my <span className="blank">{S.tool}</span>.</div>
                                    <Chips k="tool" options={OPT.tool} value={S.tool} onPick={pick} />
                                </div>
                                <div className="nav"><span /><button type="button" className="btn" onClick={() => setS((prev) => ({ ...prev, step: 1, lastAdd: '' }))}>NEXT: STORY ▶</button></div>
                            </>
                        )}
                        {S.step === 1 && (
                            <>
                                <h2>MAKE YOUR OWN STORY</h2>
                                <p className="hint">Every story needs a Beginning, a Problem and an Ending. Pick one or write your own.</p>
                                {[['first', '1 · BEGINNING', 'First, I', 'or write: built a farm'], ['then', '2 · PROBLEM', 'Then,', 'or write: a spider came'], ['final', '3 · ENDING', 'Finally, I', 'or write: had a party']].map(([key, label, lead, placeholder]) => (
                                    <div className="slot" key={key}>
                                        <span className="label">{label}</span>
                                        <div className="frame">{lead} <span className="blank">{S[key] || '\u00a0'}</span>{key === 'then' ? ' happened.' : '.'}</div>
                                        <Chips k={key} options={OPT[key]} value={S[key]} onPick={pick} />
                                        <input type="text" placeholder={placeholder} maxLength={60} value={OPT[key].includes(S[key]) ? '' : S[key]} aria-label="Write your own" onChange={(event) => typeOwn(key, event.target.value)} />
                                    </div>
                                ))}
                                <div className="nav">
                                    <button type="button" className="btn ghost" onClick={() => setS((prev) => ({ ...prev, step: 0, lastAdd: '' }))}>◀ BACK</button>
                                    <button type="button" className="btn" disabled={!(S.first && S.then && S.final)} onClick={() => setS((prev) => ({ ...prev, step: 2, lastAdd: '' }))}>NEXT: IMPROVE ▶</button>
                                </div>
                            </>
                        )}
                        {S.step === 2 && (
                            <>
                                <h2>IMPROVE YOUR STORY</h2>
                                <p className="hint">Use all three power-ups to earn 6 stars!</p>
                                <div className="power">
                                    <div className={`pw${S.feel ? ' got' : ''}`}>
                                        <div className="top"><div className="badge">★</div><div><div className="title">Add a feeling word</div><div className="ex">nervous, excited… "I felt nervous."</div></div></div>
                                        <Chips k="feel" options={OPT.feel} value={S.feel} onPick={pick} />
                                    </div>
                                    <div className={`pw${S.because ? ' got' : ''}`}>
                                        <div className="top"><div className="badge">+</div><div><div className="title">Add "because"</div><div className="ex">I built {an(S.build)} {S.build} because ______.</div></div></div>
                                        <Chips k="because" options={OPT.because} value={S.because} onPick={pick} />
                                        <input type="text" placeholder="or write your own reason" maxLength={60} value={OPT.because.includes(S.because) ? '' : S.because} aria-label="Write your own" onChange={(event) => typeOwn('because', event.target.value)} />
                                    </div>
                                    <div className={`pw${S.sudden ? ' got' : ''}`}>
                                        <div className="top"><div className="badge">!</div><div><div className="title">Add "suddenly"</div><div className="ex">Turn your problem into a surprise: "Suddenly, a creeper came!"</div></div></div>
                                        <div className="chips">
                                            <button type="button" className="chipb" aria-pressed={S.sudden} onClick={() => { setS((prev) => ({ ...prev, sudden: !prev.sudden, lastAdd: 'then' })); beep(660, 0.06); }}>{S.sudden ? 'Suddenly is ON!' : 'Make it SUDDEN!'}</button>
                                        </div>
                                    </div>
                                </div>
                                <div className="nav">
                                    <button type="button" className="btn ghost" onClick={() => setS((prev) => ({ ...prev, step: 1, lastAdd: '' }))}>◀ BACK</button>
                                    <button type="button" className="btn" onClick={() => { setS((prev) => ({ ...prev, step: 3, lastAdd: '' })); claim(stars); [523, 659, 784, 1046].forEach((f, i) => setTimeout(() => beep(f, 0.16, 0.06), i * 110)); }}>FINISH ▶</button>
                                </div>
                            </>
                        )}
                        {S.step === 3 && (
                            <div className="final">
                                <h2 className="hero">GREAT JOB!</h2>
                                <p className="hint" style={{ margin: 0 }}>You earned <b>{stars}</b> of 6 points. {stars === 6 ? "You're a Minecraft Reading & Writing Hero!" : 'Go back and use every power-up to become a Reading & Writing Hero.'}</p>
                                <GameXpNote />
                                <div className="slot" style={{ alignSelf: 'stretch' }}>
                                    <span className="label">SHARE</span>
                                    <div className="frame" style={{ fontWeight: 600 }}>Read your story out loud to a friend, then tell them how you built it.</div>
                                    <div className="chips">
                                        <button type="button" className="chipb" onClick={() => {
                                            try {
                                                window.speechSynthesis.cancel();
                                                const utterance = new SpeechSynthesisUtterance(storyText);
                                                utterance.rate = 0.9;
                                                window.speechSynthesis.speak(utterance);
                                            } catch {
                                                setToast("Read-aloud isn't available here. Try reading it yourself!");
                                            }
                                        }}>🔊 Read it to me</button>
                                        <button type="button" className="chipb" onClick={() => {
                                            navigator.clipboard.writeText(storyText).then(() => setToast('Copied!')).catch(() => setToast('Could not copy. Select the story and copy it.'));
                                        }}>Copy my story</button>
                                    </div>
                                    <div className="toast" style={{ color: 'var(--ink)' }}>{toast}</div>
                                </div>
                                <div className="nav" style={{ alignSelf: 'stretch' }}>
                                    <button type="button" className="btn ghost" onClick={() => setS((prev) => ({ ...prev, step: 2, lastAdd: '' }))}>◀ EDIT</button>
                                    <button type="button" className="btn" onClick={() => { setS(INITIAL); setToast(''); }}>NEW STORY</button>
                                </div>
                            </div>
                        )}
                    </section>
                    <aside className="side">
                        <div className="preview"><canvas ref={canvasRef} width="160" height="96" aria-label="Picture of your build" /></div>
                        <div className="scroll">
                            <h3>MY STORY</h3>
                            <p className="story">
                                {lines.map((line) => <span key={line.k + line.t} className={line.k === S.lastAdd ? 'new' : ''}>{line.t} </span>)}
                            </p>
                        </div>
                    </aside>
                </div>
            </div>
        </div>
    );
}
