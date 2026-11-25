import { useCallback, useEffect, useRef, useState } from 'react';
import './styles/verb-detective.css';
import { GameXpNote, useGameXp } from './GameXp';

const LEVELS = [
    {
        id: 'rookie',
        name: 'Rookie Detective',
        icon: '🐣',
        desc: 'Easy cases to warm up',
        qs: [
            { s: 'The dog [barks] at the mailman.', t: 'action', e: 'Barking is something the dog DOES.' },
            { s: 'My sister [is] a doctor.', t: 'linking', e: '"Is" links "sister" to what she is: a doctor. No action here.' },
            { s: 'We [are] playing football.', t: 'helping', e: '"Are" helps the main verb "playing".' },
            { s: 'Riya [jumped] over the puddle.', t: 'action', e: 'Jumping is an action you can act out!' },
            { s: 'The soup [is] hot.', t: 'linking', e: '"Is" links "soup" to the describing word "hot".' },
            { s: 'I [can] swim very fast.', t: 'helping', e: '"Can" helps the main verb "swim".' },
            { s: 'Birds [fly] south in winter.', t: 'action', e: 'Flying is what the birds DO.' },
            { s: 'They [were] tired after the trip.', t: 'linking', e: '"Were" links "they" to the describing word "tired".' },
            { s: 'He [will] visit his grandma tomorrow.', t: 'helping', e: '"Will" helps the main verb "visit" and shows the future.' },
            { s: 'Aarav [kicked] the ball into the goal.', t: 'action', e: 'Kicking is an action.' },
            { s: 'You [are] my best friend.', t: 'linking', e: '"Are" links "you" to "my best friend".' },
            { s: 'She [has] finished her homework.', t: 'helping', e: '"Has" helps the main verb "finished".' },
        ],
    },
    {
        id: 'agent',
        name: 'Super Agent',
        icon: '🦊',
        desc: 'Medium cases with new verbs',
        qs: [
            { s: 'The children [were] singing loudly.', t: 'helping', e: '"Were" helps the main verb "singing".' },
            { s: 'Mom [seems] happy today.', t: 'linking', e: '"Seems" links "Mom" to "happy". Try it: Mom IS happy.' },
            { s: 'The baby [is] sleeping in the crib.', t: 'helping', e: '"Is" helps the main verb "sleeping".' },
            { s: 'The chef [cooks] pizza every Friday.', t: 'action', e: 'Cooking is something the chef DOES.' },
            { s: 'The flowers [smell] lovely.', t: 'linking', e: '"Smell" links "flowers" to "lovely". The flowers ARE lovely.' },
            { s: 'I [should] drink more water.', t: 'helping', e: '"Should" helps the main verb "drink".' },
            { s: 'The kitten [became] a big cat.', t: 'linking', e: '"Became" links "kitten" to what it is now: a big cat.' },
            { s: 'We [painted] the fence blue.', t: 'action', e: 'Painting is an action.' },
            { s: 'The lion [might] attack the zebra.', t: 'helping', e: '"Might" helps the main verb "attack".' },
            { s: 'The classroom [was] quiet.', t: 'linking', e: '"Was" links "classroom" to "quiet".' },
            { s: 'Tom [did] not eat his peas.', t: 'helping', e: '"Did" helps the main verb "eat".' },
            { s: 'The scientist [mixed] two liquids.', t: 'action', e: 'Mixing is something the scientist DOES.' },
        ],
    },
    {
        id: 'master',
        name: 'Master Sleuth',
        icon: '🦉',
        desc: 'Tricky! Same word, different job',
        qs: [
            { s: 'The pie [tastes] sweet.', t: 'linking', e: 'The pie isn\'t tasting anything! "Tastes" links "pie" to "sweet".' },
            { s: 'Dad [tastes] the soup before serving.', t: 'action', e: 'Here Dad really DOES the tasting, so it\'s an action.' },
            { s: 'Grandpa [looks] tired today.', t: 'linking', e: '"Looks" links "Grandpa" to "tired". Grandpa IS tired.' },
            { s: 'Grandpa [looks] at the stars.', t: 'action', e: 'Grandpa is doing the looking, so it\'s an action.' },
            { s: 'She [has] a red bicycle.', t: 'action', e: 'No other verb follows, so "has" is the main verb here (it means owns).' },
            { s: 'She [has] ridden her bicycle to school.', t: 'helping', e: '"Has" helps the main verb "ridden".' },
            { s: 'The sunflower [grew] tall.', t: 'linking', e: '"Grew" links "sunflower" to "tall". The sunflower became tall.' },
            { s: 'The farmer [grew] tomatoes.', t: 'action', e: 'The farmer DID the growing of tomatoes, so it\'s an action.' },
            { s: 'I [feel] sleepy.', t: 'linking', e: '"Feel" links "I" to "sleepy". I AM sleepy.' },
            { s: 'I [felt] the soft blanket.', t: 'action', e: 'You touched the blanket, so it\'s an action.' },
            { s: 'He [is] reading a comic.', t: 'helping', e: '"Is" helps the main verb "reading".' },
            { s: 'He [is] a great reader.', t: 'linking', e: '"Is" links "he" to "a great reader".' },
            { s: 'They [do] their chores every day.', t: 'action', e: 'No other verb follows. "Do" is the main action here.' },
            { s: '[Do] you like mangoes?', t: 'helping', e: '"Do" helps the main verb "like" to make a question.' },
            { s: 'The sky [turned] dark.', t: 'linking', e: '"Turned" links "sky" to "dark". The sky became dark.' },
            { s: 'She [turned] the page.', t: 'action', e: 'Turning a page is an action you can do.' },
        ],
    },
];

const LABEL = { action: 'Action', helping: 'Helping', linking: 'Linking' };
const PRAISE = ['Brilliant! 🌟', 'Nailed it! 🎯', 'Super sleuth! 🔍', 'Case solved! ✅', 'Awesome job! 🚀', 'You got it! 💡'];

function beep(frequencies, duration = 0.12, type = 'sine') {
    try {
        const AudioCtx = window.AudioContext || window.webkitAudioContext;
        if (!AudioCtx) return;
        const ctx = beep.ctx || (beep.ctx = new AudioCtx());
        if (ctx.state === 'suspended') ctx.resume();
        const freqs = Array.isArray(frequencies) ? frequencies : [frequencies];
        freqs.forEach((f, i) => {
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = type;
            osc.frequency.setValueAtTime(f, ctx.currentTime + i * duration);
            gain.gain.setValueAtTime(0.06, ctx.currentTime + i * duration);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + (i + 1) * duration);
            osc.connect(gain).connect(ctx.destination);
            osc.start(ctx.currentTime + i * duration);
            osc.stop(ctx.currentTime + (i + 1) * duration);
        });
    } catch {
        /* audio optional */
    }
}

function runConfetti(canvas) {
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    const width = (canvas.width = canvas.offsetWidth || window.innerWidth);
    const height = (canvas.height = canvas.offsetHeight || window.innerHeight);
    const colors = ['#f97316', '#0ea5e9', '#22c55e', '#facc15', '#ec4899', '#a78bfa'];
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

export default function VerbDetective() {
    const { claim } = useGameXp();
    const [screen, setScreen] = useState('home'); // home | learn | play | results
    const [levelIndex, setLevelIndex] = useState(0);
    const [qIndex, setQIndex] = useState(0);
    const [score, setScore] = useState(0);
    const [streak, setStreak] = useState(0);
    const [bestStreak, setBestStreak] = useState(0);
    const [rightCount, setRightCount] = useState(0);
    const [locked, setLocked] = useState(false);
    const [picked, setPicked] = useState(null);
    const [shake, setShake] = useState(false);
    const [wrongCases, setWrongCases] = useState([]);
    const [feedback, setFeedback] = useState(null);
    const [bestStars, setBestStars] = useState(() => {
        try {
            return JSON.parse(localStorage.getItem('ym_vd_stars') || '{}');
        } catch {
            return {};
        }
    });

    const canvasRef = useRef(null);
    const currentLevel = LEVELS[levelIndex];
    const currentQ = currentLevel?.qs[qIndex];
    const totalQ = currentLevel?.qs.length || 0;

    const startLevel = (lvlIdx) => {
        setLevelIndex(lvlIdx);
        setQIndex(0);
        setScore(0);
        setStreak(0);
        setBestStreak(0);
        setRightCount(0);
        setLocked(false);
        setPicked(null);
        setWrongCases([]);
        setFeedback(null);
        setScreen('play');
    };

    const handleAnswer = (type) => {
        if (locked || !currentQ) return;
        setLocked(true);
        setPicked(type);
        const isRight = type === currentQ.t;

        if (isRight) {
            const nextStreak = streak + 1;
            setStreak(nextStreak);
            if (nextStreak > bestStreak) setBestStreak(nextStreak);
            const pts = 10 + (nextStreak >= 3 ? 5 : 0);
            setScore((s) => s + pts);
            setRightCount((r) => r + 1);
            beep([523, 659, 784], 0.12);
            const cheer = PRAISE[Math.floor(Math.random() * PRAISE.length)];
            setFeedback({
                ok: true,
                title: `✅ ${cheer}${nextStreak >= 3 ? ' 🔥 Streak bonus! +5 pts' : ''}`,
                desc: currentQ.e,
            });
        } else {
            setStreak(0);
            setShake(true);
            setTimeout(() => setShake(false), 400);
            setWrongCases((prev) => [...prev, { q: currentQ, picked: type }]);
            beep([220, 180], 0.15, 'sawtooth');
            setFeedback({
                ok: false,
                title: `❌ Not quite! It's a ${LABEL[currentQ.t]} verb.`,
                desc: currentQ.e,
            });
        }
    };

    const nextQuestion = useCallback(() => {
        if (!locked) return;
        if (qIndex < totalQ - 1) {
            setQIndex((n) => n + 1);
            setLocked(false);
            setPicked(null);
            setFeedback(null);
        } else {
            // Finish level
            setScreen('results');
            const finalRight = rightCount + (picked === currentQ.t ? 0 : 0); // rightCount already updated
            const pct = finalRight / totalQ;
            const stars = pct >= 0.9 ? 3 : pct >= 0.7 ? 2 : pct >= 0.4 ? 1 : 0;
            const earnedXp = Math.min(15, Math.max(1, Math.round(pct * 15)));

            setBestStars((prev) => {
                const next = { ...prev, [currentLevel.id]: Math.max(prev[currentLevel.id] || 0, stars) };
                try {
                    localStorage.setItem('ym_vd_stars', JSON.stringify(next));
                } catch {}
                return next;
            });

            claim(earnedXp);

            if (stars >= 2) {
                beep([523, 659, 784, 1047], 0.14);
                setTimeout(() => runConfetti(canvasRef.current), 100);
            }
        }
    }, [locked, qIndex, totalQ, rightCount, picked, currentQ, currentLevel?.id, claim]);

    // Keyboard support
    useEffect(() => {
        if (screen !== 'play') return;
        const onKeyDown = (e) => {
            if (!locked) {
                if (e.key === '1') handleAnswer('action');
                else if (e.key === '2') handleAnswer('helping');
                else if (e.key === '3') handleAnswer('linking');
            } else if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                nextQuestion();
            }
        };
        window.addEventListener('keydown', onKeyDown);
        return () => window.removeEventListener('keydown', onKeyDown);
    }, [screen, locked, nextQuestion]);

    // Parse sentence to highlight target verb inside brackets
    const renderSentence = (text) => {
        const parts = text.split(/\[(.+?)\]/);
        if (parts.length === 3) {
            return (
                <div className="sentence">
                    {parts[0]}
                    <span className="verb">{parts[1]}</span>
                    {parts[2]}
                </div>
            );
        }
        return <div className="sentence">{text}</div>;
    };

    const targetVerb = currentQ?.s?.match(/\[(.+?)\]/)?.[1] || 'this verb';

    const pct = totalQ ? rightCount / totalQ : 0;
    const finalStars = pct >= 0.9 ? 3 : pct >= 0.7 ? 2 : pct >= 0.4 ? 1 : 0;

    return (
        <div className="ym-vd">
            <canvas id="ym-vd-confetti" ref={canvasRef} />
            <div className="wrap">
                {screen === 'home' && (
                    <div className="card">
                        <span className="badge-icon" aria-hidden="true">🕵️‍♂️</span>
                        <h1>VERB DETECTIVE</h1>
                        <p className="sub">Action, Helping & Linking Verbs</p>

                        <h2>Choose your mission:</h2>
                        <div className="levels">
                            {LEVELS.map((lvl, idx) => {
                                const stars = bestStars[lvl.id] || 0;
                                return (
                                    <button
                                        key={lvl.id}
                                        type="button"
                                        className="lvl"
                                        onClick={() => startLevel(idx)}
                                    >
                                        <span className="ic">{lvl.icon}</span>
                                        <b>{lvl.name}</b>
                                        <span>{lvl.desc}</span>
                                        <div className="stars" aria-label={`${stars} of 3 stars`}>
                                            {'★'.repeat(stars)}
                                            <span style={{ color: '#cbd5e1' }}>{'★'.repeat(3 - stars)}</span>
                                        </div>
                                    </button>
                                );
                            })}
                        </div>

                        <div className="row">
                            <button
                                type="button"
                                className="btn ghost"
                                onClick={() => setScreen('learn')}
                            >
                                📖 Detective Training (Rules & Tips)
                            </button>
                        </div>
                    </div>
                )}

                {screen === 'learn' && (
                    <div className="card">
                        <h2>🕵️‍♀️ Detective Training: 3 Kinds of Verbs</h2>
                        <p>Every verb in English has a special job. Here is how detectives crack the case:</p>

                        <div className="learn">
                            <div className="lc a">
                                <h3>🟧 1. Action Verbs</h3>
                                <p>Tells what someone or something <b>DOES</b>. You can usually act it out with your body or mind!</p>
                                <div className="ex">
                                    Example: Riya <mark>jumped</mark> over the puddle. • Aarav <mark>kicked</mark> the ball.
                                </div>
                            </div>

                            <div className="lc h">
                                <h3>🟦 2. Helping Verbs</h3>
                                <p>Helps the main verb! They work as a <b>TEAM</b> to show time or ability (is, was, are, will, can, has, have, should).</p>
                                <div className="ex">
                                    Example: We <mark>are</mark> playing football. • She <mark>can</mark> swim fast.
                                </div>
                            </div>

                            <div className="lc l">
                                <h3>🟩 3. Linking Verbs</h3>
                                <p>Connects the subject to a word that describes or names it. <b>NO action happens!</b></p>
                                <div className="ex">
                                    Example: The soup <mark>is</mark> hot. • My sister <mark>is</mark> a doctor. • Mom <mark>seems</mark> happy.
                                </div>
                            </div>
                        </div>

                        <div className="tip">
                            💡 <b>The Secret Detective Swap Trick:</b> If you can swap the verb for <b>"is"</b> or <b>"are"</b> and the sentence still makes sense, it is a <b>Linking Verb</b>!
                        </div>

                        <div className="row">
                            <button type="button" className="btn" onClick={() => setScreen('home')}>
                                ➜ Back to Cases
                            </button>
                        </div>
                    </div>
                )}

                {screen === 'play' && currentQ && (
                    <div className="card">
                        <div className="hud">
                            <span className="pill">
                                {currentLevel.icon} {currentLevel.name}
                            </span>
                            <span className="pill">Score: {score}</span>
                            <span className="pill">
                                {streak >= 3 ? '🔥 ' : ''}Streak: {streak}
                            </span>
                        </div>

                        <div className="bar" aria-hidden="true">
                            <i style={{ width: `${((qIndex + 1) / totalQ) * 100}%` }} />
                        </div>

                        <div className="qnum">
                            Case {qIndex + 1} of {totalQ}
                        </div>

                        <div className={shake ? 'shake' : ''}>
                            {renderSentence(currentQ.s)}
                        </div>

                        <p className="ask">
                            What kind of verb is <b>"{targetVerb}"</b>?
                        </p>

                        <div className="choices">
                            {['action', 'helping', 'linking'].map((type, i) => {
                                let cls = `ch ${type}`;
                                if (locked) {
                                    if (type === currentQ.t) cls += ' correct';
                                    else if (type === picked) cls += ' wrong-pick';
                                }
                                return (
                                    <button
                                        key={type}
                                        type="button"
                                        className={cls}
                                        disabled={locked}
                                        onClick={() => handleAnswer(type)}
                                    >
                                        <span>{type === 'action' ? '🟧' : type === 'helping' ? '🟦' : '🟩'} {LABEL[type]}</span>
                                        <small>[Press {i + 1}]</small>
                                    </button>
                                );
                            })}
                        </div>

                        {feedback && (
                            <div className={`feedback ${feedback.ok ? 'ok' : 'no'}`}>
                                <b>{feedback.title}</b>
                                <br />
                                <span>{feedback.desc}</span>
                            </div>
                        )}

                        {locked && (
                            <div className="row">
                                <button type="button" className="btn" onClick={nextQuestion}>
                                    {qIndex === totalQ - 1 ? 'See results 🏁' : 'Next case ➜'}
                                </button>
                            </div>
                        )}
                    </div>
                )}

                {screen === 'results' && (
                    <div className="card res">
                        <span className="badge-icon" aria-hidden="true">
                            {finalStars === 3 ? '🏆' : finalStars === 2 ? '🥈' : finalStars === 1 ? '🔍' : '📚'}
                        </span>
                        <h2>
                            {finalStars === 3
                                ? 'Master Detective!'
                                : finalStars === 2
                                ? 'Great Detective!'
                                : finalStars === 1
                                ? 'Good Effort!'
                                : 'Keep Training!'}
                        </h2>
                        <p className="sub" style={{ color: 'var(--muted)' }}>
                            {finalStars === 3
                                ? 'Amazing work! You cracked almost every case.'
                                : finalStars === 2
                                ? 'Really good! Review the tricky ones and aim for 3 stars.'
                                : finalStars === 1
                                ? 'You are learning fast! Review the cases below and try again.'
                                : 'Visit Detective Training, then try this mission again.'}
                        </p>

                        <div style={{ fontSize: '2.4rem', color: '#f59e0b', margin: '8px 0' }}>
                            {'★'.repeat(finalStars)}
                            <span style={{ color: '#cbd5e1' }}>{'★'.repeat(3 - finalStars)}</span>
                        </div>

                        <div className="stats">
                            <div className="stat">
                                <b>{rightCount} / {totalQ}</b>
                                <span>Solved</span>
                            </div>
                            <div className="stat">
                                <b>{score}</b>
                                <span>Points</span>
                            </div>
                            <div className="stat">
                                <b>{bestStreak}</b>
                                <span>Best Streak</span>
                            </div>
                        </div>

                        <GameXpNote />

                        {wrongCases.length > 0 && (
                            <div className="review">
                                <h3 style={{ fontSize: '1.25rem', marginBottom: 10 }}>🔍 Cases to Review:</h3>
                                {wrongCases.map((w, idx) => (
                                    <div key={idx} className="rv">
                                        <div className="s">{w.q.s.replace(/\[(.+?)\]/, '$1')}</div>
                                        <div>
                                            You chose <span className={`tag ${w.picked}`}>{LABEL[w.picked]}</span>,
                                            correct answer: <span className={`tag ${w.q.t}`}>{LABEL[w.q.t]}</span>
                                        </div>
                                        <div style={{ fontSize: '0.9rem', color: 'var(--muted)', marginTop: 4 }}>{w.q.e}</div>
                                    </div>
                                ))}
                            </div>
                        )}

                        <div className="row">
                            <button type="button" className="btn" onClick={() => startLevel(levelIndex)}>
                                Play again
                            </button>
                            <button type="button" className="btn ghost" onClick={() => setScreen('home')}>
                                Choose another mission
                            </button>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}

