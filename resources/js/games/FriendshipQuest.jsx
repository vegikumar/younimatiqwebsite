import { useEffect, useRef, useState } from 'react';
import './styles/friendship-quest.css';
import { GameXpNote, useGameXp } from './GameXp';

const FRIENDS = {
    Tyler: { c: '#8dc63f', s: 'Brave hero, loves to explore' },
    Evan: { c: '#ffd04a', s: 'Loyal, funny, great at building' },
    Grace: { c: '#5ec8f0', s: 'Smart planner, solves puzzles' },
    Tobi: { c: '#ff8a4d', s: 'Brave, quick thinker' },
    Candace: { c: '#e07bd8', s: 'Creative, full of ideas' },
};
const ORDER = ['Tyler', 'Evan', 'Grace', 'Tobi', 'Candace'];
const QUESTIONS = [
    { q: 'Who leads the friends with courage?', a: 'Tyler', why: 'Tyler is the brave hero.', part: 'tree trunk' },
    { q: 'Who can find a safe path through the forest?', a: 'Grace', why: 'Grace helps the group stay safe.', part: 'leaves' },
    { q: 'Who is the best at crafting and building?', a: 'Evan', why: 'Evan is an expert builder.', part: 'floor' },
    { q: 'Who has lots of creative ideas for the design?', a: 'Candace', why: 'Candace is creative and full of ideas.', part: 'walls' },
    { q: 'A creeper is coming! Who thinks fast?', a: 'Tobi', why: 'Tobi is a quick thinker.', part: 'windows' },
    { q: 'Everyone is sad. Who can make them laugh?', a: 'Evan', why: 'Evan brings humor to every challenge.', part: 'door' },
    { q: 'The roof needs a smart plan. Who is the best planner?', a: 'Grace', why: 'Grace is great at planning.', part: 'roof' },
    { q: 'Who loves to explore and climbs up first?', a: 'Tyler', why: 'Tyler is always ready to explore.', part: 'ladder and flag' },
];

function beep(f, d, v = 0.05, t = 'triangle') {
    try {
        const ac = beep.ac || (beep.ac = new (window.AudioContext || window.webkitAudioContext)());
        const o = ac.createOscillator();
        const g = ac.createGain();
        o.type = t;
        o.frequency.value = f;
        g.gain.value = v;
        o.connect(g);
        g.connect(ac.destination);
        const n = ac.currentTime;
        g.gain.exponentialRampToValueAtTime(0.0001, n + d);
        o.start(n);
        o.stop(n + d);
    } catch {
        /* sound is optional */
    }
}

function say(text) {
    try {
        window.speechSynthesis.cancel();
        const utterance = new SpeechSynthesisUtterance(text);
        utterance.rate = 0.9;
        window.speechSynthesis.speak(utterance);
    } catch {
        /* read-aloud is optional */
    }
}

function drawHouse(canvas, built) {
    if (!canvas) return;
    const cx = canvas.getContext('2d');
    const T = 5;
    const W = canvas.width;
    const H = canvas.height;
    const block = (x, y, a, d) => {
        cx.fillStyle = a;
        cx.fillRect(x, y, T, T);
        cx.fillStyle = d;
        cx.fillRect(x, y + T - 1, T, 1);
        cx.fillRect(x + T - 1, y, 1, T);
    };
    const rect = (c0, r0, w, h, a, d) => {
        for (let r = 0; r < h; r += 1) {
            for (let c = 0; c < w; c += 1) block((c0 + c) * T, (r0 + r) * T, a, d);
        }
    };
    const g = cx.createLinearGradient(0, 0, 0, H);
    g.addColorStop(0, '#2a1d4a');
    g.addColorStop(1, '#5b3f8f');
    cx.fillStyle = g;
    cx.fillRect(0, 0, W, H);
    cx.fillStyle = '#fff';
    [[12, 10], [40, 22], [70, 8], [120, 16], [146, 30], [100, 34]].forEach(([x, y]) => cx.fillRect(x, y, 1, 1));
    cx.fillStyle = '#f3e9a6';
    cx.fillRect(130, 8, 10, 10);
    cx.fillRect(132, 6, 6, 14);
    cx.fillRect(128, 10, 14, 6);
    rect(0, 25, 32, 1, '#5fae35', '#4a8c28');
    rect(0, 26, 32, 2, '#7a4e24', '#5c3a1a');
    if (built >= 1) rect(15, 13, 2, 12, '#7a5230', '#5c3c22');
    if (built >= 2) {
        rect(8, 4, 16, 5, '#3f9a2a', '#2f7a1e');
        rect(6, 6, 4, 3, '#3f9a2a', '#2f7a1e');
        rect(22, 6, 4, 3, '#3f9a2a', '#2f7a1e');
        rect(10, 2, 12, 2, '#4fb236', '#3a8a25');
    }
    if (built >= 3) rect(9, 13, 14, 1, '#b07a3e', '#8a5a2a');
    if (built >= 4) rect(10, 9, 12, 4, '#c8955a', '#9c6b3a');
    if (built >= 5) {
        [[11, 10], [12, 10], [19, 10], [20, 10]].forEach(([c, r]) => {
            cx.fillStyle = '#ffd977';
            cx.fillRect(c * T, r * T, T, T);
        });
    }
    if (built >= 6) rect(15, 10, 2, 3, '#5c3c22', '#3e2814');
    if (built >= 7) {
        rect(9, 8, 14, 1, '#b8432f', '#8e3222');
        rect(10, 7, 12, 1, '#b8432f', '#8e3222');
        rect(12, 6, 8, 1, '#b8432f', '#8e3222');
        rect(14, 5, 4, 1, '#b8432f', '#8e3222');
    }
    if (built >= 8) {
        for (let r = 14; r < 25; r += 1) {
            cx.fillStyle = '#d9a066';
            cx.fillRect(21 * T, r * T, 1, T);
            cx.fillRect(22 * T + 3, r * T, 1, T);
            cx.fillRect(21 * T, r * T + 2, T + 4, 1);
        }
        cx.fillStyle = '#3e2814';
        cx.fillRect(16 * T, 1 * T, 1, 4 * T);
        cx.fillStyle = '#8dc63f';
        cx.fillRect(16 * T + 1, 1 * T, 10, 6);
    }
    if (built === 0) {
        cx.fillStyle = 'rgba(255,255,255,.6)';
        cx.font = '8px sans-serif';
        cx.textAlign = 'center';
        cx.fillText("Let's build!", W / 2, H / 2);
    }
}

export default function FriendshipQuest() {
    const { claim } = useGameXp();
    const canvasRef = useRef(null);
    const [index, setIndex] = useState(0);
    const [built, setBuilt] = useState(0);
    const [firstTry, setFirstTry] = useState(0);
    const [missed, setMissed] = useState(false);
    const [wrong, setWrong] = useState([]);
    const [correct, setCorrect] = useState(null);
    const [done, setDone] = useState(false);

    useEffect(() => {
        drawHouse(canvasRef.current, built);
    }, [built]);

    const restart = () => {
        setIndex(0);
        setBuilt(0);
        setFirstTry(0);
        setMissed(false);
        setWrong([]);
        setCorrect(null);
        setDone(false);
    };

    const question = QUESTIONS[index];

    const pick = (name) => {
        if (correct || wrong.includes(name)) return;
        if (name === question.a) {
            [523, 659, 784].forEach((f, k) => setTimeout(() => beep(f, 0.14), k * 90));
            if (!missed) setFirstTry((n) => n + 1);
            setBuilt((n) => n + 1);
            setCorrect(name);
            return;
        }
        beep(140, 0.22, 0.04, 'sawtooth');
        setMissed(true);
        setWrong((list) => [...list, name]);
    };

    const next = () => {
        if (index < QUESTIONS.length - 1) {
            setIndex((n) => n + 1);
            setMissed(false);
            setWrong([]);
            setCorrect(null);
            return;
        }
        [523, 659, 784, 1046].forEach((f, k) => setTimeout(() => beep(f, 0.18, 0.06), k * 110));
        setDone(true);
        claim(firstTry);
    };

    return (
        <div className="ym-fq">
            <div className="wrap">
                <header>
                    <div>
                        <h1>Friendship Quest<span>Build the Tree House</span></h1>
                        <div className="rule" />
                        <p className="sub">Pick the right friend. Every right answer builds the tree house!</p>
                    </div>
                    <div className="count">BUILT <b>{built}</b>/8</div>
                </header>
                <div className="game">
                    <div className="house">
                        <div className="view">
                            <canvas ref={canvasRef} width="160" height="140" aria-label="The tree house, built one part at a time" />
                        </div>
                        <div className="built">
                            {built === 0 ? 'Answer the first question to start building.' : done ? <><b>The tree house is finished!</b></> : <>You built the <b>{QUESTIONS[Math.max(0, built - 1)]?.part}</b>!</>}
                        </div>
                    </div>
                    <section className="right" aria-live="polite">
                        {done ? (
                            <div className="q end">
                                <h2>Tree house <span>built!</span></h2>
                                <p>You got <b>{firstTry} of {QUESTIONS.length}</b> points. Great teamwork!</p>
                                <GameXpNote />
                                <p className="lesson">Courage, trust, and smart decisions help us survive unexpected problems.</p>
                                <p><b>Say it:</b> "I am most like ___ because ___."</p>
                                <div style={{ display: 'flex', gap: 10, flexWrap: 'wrap' }}>
                                    <button type="button" className="speak" onClick={() => say('Courage, trust, and smart decisions help us survive unexpected problems.')}>🔊 Read to me</button>
                                    <button type="button" className="btn" onClick={restart}>Play again</button>
                                </div>
                            </div>
                        ) : (
                            <>
                                <div className="q">
                                    <h2>{question.q}</h2>
                                    <button type="button" className="speak" onClick={() => say(question.q)}>🔊 Read to me</button>
                                </div>
                                <div className="cards">
                                    {ORDER.map((name) => (
                                        <button
                                            key={name}
                                            type="button"
                                            className={`card${correct === name ? ' ok' : ''}${wrong.includes(name) ? ' no' : ''}`}
                                            disabled={Boolean(correct) || wrong.includes(name)}
                                            onClick={() => pick(name)}
                                        >
                                            <div className="av" style={{ background: FRIENDS[name].c }}>{name[0]}</div>
                                            <div className="nm">{name}</div>
                                            <div className="skill">{FRIENDS[name].s}</div>
                                        </button>
                                    ))}
                                </div>
                                <div className={`fb${correct ? ' good' : wrong.length ? ' bad' : ''}`}>
                                    {correct ? (
                                        <>
                                            <span><b>Yes!</b> {question.why}</span>
                                            <button type="button" className="btn" onClick={next}>{index < QUESTIONS.length - 1 ? 'Next ▶' : 'Finish ▶'}</button>
                                        </>
                                    ) : wrong.length ? (
                                        <span>Not {wrong[wrong.length - 1]}. Read the cards and try again!</span>
                                    ) : (
                                        <span>Tap a friend.</span>
                                    )}
                                </div>
                            </>
                        )}
                    </section>
                </div>
            </div>
        </div>
    );
}
