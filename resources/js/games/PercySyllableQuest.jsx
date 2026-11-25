import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { GameXpNote, useGameXp } from './GameXp';
import './styles/percy-syllable-quest.css';

const MAX_XP = 390;
const MAX_HEARTS = 5;
const BOSS_MAX_HP = 8;

const CHAMBERS = [
    { key: 'gate', label: 'Demigod Gate', icon: '🏛️' },
    { key: 'beats', label: 'Echo Tunnel', icon: '👏' },
    { key: 'sword', label: 'Syllable Sword', icon: '🗡️' },
    { key: 'shield', label: 'Shield Hall', icon: '🛡️' },
    { key: 'boss', label: 'Final Battle', icon: '👹' },
];

const GATE_ITEMS = [
    { q: 'What is Percy Jackson?', o: ['A demigod', 'A satyr', 'A sea monster', 'A teacher'], a: 'A demigod', why: 'Percy is a demigod: half human, half god.', hint: 'He is half human and half god.' },
    { q: "Who is Percy's father, the god of the sea?", o: ['Poseidon', 'Zeus', 'Hades', 'Apollo'], a: 'Poseidon', why: "Poseidon, god of the sea, is Percy's father.", hint: 'Think of the god who rules the oceans.' },
    { q: 'What is Riptide?', o: ["Percy's magic sword", "Percy's school", 'A hellhound', 'A secret tunnel'], a: "Percy's magic sword", why: "Riptide is Percy's magic sword.", hint: 'Percy fights with it.' },
    { q: "Who is Percy's loyal satyr friend?", o: ['Grover', 'Tyson', 'Quintus', 'Rachel'], a: 'Grover', why: "Grover is Percy's loyal satyr friend.", hint: 'A satyr is half human, half goat.' },
    { q: 'What is Camp Half-Blood?', o: ['A training camp for demigods', 'A school for monsters', 'A city under the sea', 'A shop in New York'], a: 'A training camp for demigods', why: 'Camp Half-Blood is where demigods train to survive.', hint: 'It is where demigods go to learn and train.' },
    { q: 'When monsters attack Percy at his new school, who helps him escape?', o: ['Rachel', 'Annabeth', 'Grover', 'Tyson'], a: 'Rachel', why: "Rachel, a mortal girl, sees through the monsters' disguises and helps Percy escape.", hint: 'She is a mortal girl who can see through disguises.' },
];

const BEAT_WORDS_POOL = [
    ['Percy', 'Per · cy', 2],
    ['camp', 'camp', 1],
    ['monster', 'mon · ster', 2],
    ['hero', 'he · ro', 2],
    ['adventure', 'ad · ven · ture', 3],
    ['labyrinth', 'lab · y · rinth', 3],
    ['dangerous', 'dan · ger · ous', 3],
    ['mist', 'mist', 1],
    ['mysterious', 'mys · te · ri · ous', 4],
];

const SWORD_WORDS_POOL = [
    ['monster', 3],
    ['rabbit', 3],
    ['napkin', 3],
    ['campus', 3],
    ['robot', 2],
    ['tiger', 2],
    ['zero', 2],
    ['music', 2],
    ['hotel', 2],
    ['sunset', 3],
    ['meter', 2],
    ['percy', 3],
];

const SHIELD_ITEMS_POOL = [
    ['me', 'meter', 'open'],
    ['ti', 'tiger', 'open'],
    ['ze', 'zero', 'open'],
    ['mu', 'music', 'open'],
    ['ro', 'robot', 'open'],
    ['ho', 'hotel', 'open'],
    ['he', 'hero', 'open'],
    ['mon', 'monster', 'closed'],
    ['nap', 'napkin', 'closed'],
    ['rab', 'rabbit', 'closed'],
    ['cam', 'campus', 'closed'],
    ['sun', 'sunset', 'closed'],
    ['kin', 'napkin', 'closed'],
    ['bot', 'robot', 'closed'],
];

const BOSS_QUESTIONS = [
    { q: 'How many syllables are in LABYRINTH?', o: ['2', '3', '4', '1'], a: '3', why: 'lab · y · rinth = 3 beats.' },
    { q: 'Which word starts with an OPEN syllable?', o: ['tiger', 'monster', 'napkin', 'sunset'], a: 'tiger', why: '"ti" ends in a vowel, so it is open.' },
    { q: 'A CLOSED syllable ends in a…', o: ['consonant', 'vowel', 'full stop', 'capital letter'], a: 'consonant', why: 'Closed syllables end in a consonant and have a short vowel sound.' },
    { q: 'How do you split ROBOT?', o: ['ro - bot', 'rob - ot', 'r - obot', 'robo - t'], a: 'ro - bot', why: 'ro-bot: open, then closed.' },
    { q: 'SUNSET is made of which syllables?', o: ['closed + closed', 'open + open', 'open + closed', 'closed + open'], a: 'closed + closed', why: 'sun-set: both end in a consonant.' },
    { q: 'Which letter is a vowel?', o: ['U', 'R', 'T', 'P'], a: 'U', why: 'The vowels are A, E, I, O, U.' },
    { q: "What does an OPEN syllable's vowel usually sound like?", o: ['Long, it says its name', 'Short', 'Silent', 'Like a consonant'], a: 'Long, it says its name', why: 'In open syllables like "me" or "ti", the vowel says its name.' },
    { q: "Who sees through the monsters' disguises and helps Percy escape?", o: ['Rachel', 'Grover', 'Annabeth', 'Tyson'], a: 'Rachel', why: 'Rachel, a mortal girl, can see through the disguises.' },
    { q: 'How do you split MUSIC?', o: ['mu - sic', 'mus - ic', 'm - usic', 'musi - c'], a: 'mu - sic', why: 'mu-sic: open, then closed.' },
    { q: 'How many syllables are in ADVENTURE?', o: ['3', '2', '4', '5'], a: '3', why: 'ad · ven · ture = 3 beats.' },
];

const CHAMBER_SHELL = {
    gate: {
        idx: 0,
        eyebrow: 'Story Check',
        title: '🏛️ The Demigod Gate',
        story: (hero) => (
            <>
                A stone gate blocks the tunnel. Carved above it: <i>&quot;Only those who know a demigod&apos;s world may pass.&quot;</i> Answer the gate&apos;s riddles, {hero}!
            </>
        ),
        rule: null,
        clearMsg: "The gate rumbles open. You know Percy's world well!",
        next: 'beats',
    },
    beats: {
        idx: 1,
        eyebrow: 'Syllables',
        title: '👏 The Echo Tunnel',
        story: () => (
            <>
                In this tunnel, every word echoes in <b>beats</b>. Say the word slowly, tap the clap button once for each beat, then press <b>Check</b>.
            </>
        ),
        rule: (
            <>
                A <b>syllable</b> is one beat of a word, with a vowel sound. <i>Per·cy</i> = 2 claps 👏👏
            </>
        ),
        clearMsg: 'The tunnel echoes with your perfect beats!',
        next: 'sword',
    },
    sword: {
        idx: 2,
        eyebrow: 'Splitting Words',
        title: '🗡️ The Syllable Sword',
        story: () => (
            <>
                The monster has glued words together! Swing <b>Riptide</b> between the letters to slice each word into its two syllables. Tap the gap where the word should split.
            </>
        ),
        rule: (
            <>
                Clap the beats first, then cut between them. Example: <b>mon | ster</b>, <b>ro | bot</b>.
            </>
        ),
        clearMsg: 'Riptide sliced every word perfectly!',
        next: 'shield',
    },
    shield: {
        idx: 3,
        eyebrow: 'Open & Closed Syllables',
        title: '🛡️ The Shield Hall',
        story: () => (
            <>
                Two magic shields guard the hall. Look at each syllable and send it to the right shield: <b>OPEN</b> or <b>CLOSED</b>.
            </>
        ),
        rule: (
            <>
                <b>Closed</b> = ends in a <b>consonant</b>, short vowel (<i>mon</i>, <i>nap</i>). <b>Open</b> = ends in a <b>vowel</b>, long vowel that says its name (<i>me</i>, <i>ti</i>).
            </>
        ),
        clearMsg: 'Both shields are glowing. You are ready for the final battle!',
        next: 'bossIntro',
    },
};

const CONFETTI_COLORS = ['#2F7BEA', '#F7931E', '#4DB33D', '#7B3FE4', '#E5336E', '#F8B92B'];

function shuffle(list) {
    const a = list.slice();
    for (let i = a.length - 1; i > 0; i -= 1) {
        const j = Math.floor(Math.random() * (i + 1));
        [a[i], a[j]] = [a[j], a[i]];
    }
    return a;
}

function tone(freqs, dur = 0.12, type = 'sine') {
    try {
        const AudioCtx = window.AudioContext || window.webkitAudioContext;
        if (!AudioCtx) return;
        const ctx = tone.ctx || (tone.ctx = new AudioCtx());
        if (ctx.state === 'suspended') ctx.resume();
        const arr = Array.isArray(freqs) ? freqs : [freqs];
        arr.forEach((f, i) => {
            const o = ctx.createOscillator();
            const g = ctx.createGain();
            o.type = type;
            o.frequency.value = f;
            const t = ctx.currentTime + i * dur;
            g.gain.setValueAtTime(0.12, t);
            g.gain.exponentialRampToValueAtTime(0.001, t + dur);
            o.connect(g);
            g.connect(ctx.destination);
            o.start(t);
            o.stop(t + dur);
        });
    } catch {
        /* optional */
    }
}

const sGood = () => tone([660, 880], 0.1);
const sBad = () => tone([220, 160], 0.14, 'square');
const sClap = () => tone([300], 0.05, 'triangle');
const sWin = () => tone([523, 659, 784, 1046], 0.13);

function rankForXp(xp) {
    if (xp >= 330) return ['🏆', 'Labyrinth Champion'];
    if (xp >= 260) return ['🥇', 'Brave Demigod'];
    return ['🛡️', 'Hero in Training'];
}

function spawnConfettiPieces(count = 70) {
    return Array.from({ length: count }, (_, i) => ({
        id: `${Date.now()}-${i}`,
        left: Math.random() * 100,
        background: CONFETTI_COLORS[i % CONFETTI_COLORS.length],
        duration: 1.6 + Math.random() * 1.6,
        delay: Math.random() * 0.4,
    }));
}

function Feedback({ feedback }) {
    if (!feedback) return <div className="feedback" />;
    return (
        <div className={`feedback show ${feedback.ok ? 'good' : 'bad'}`}>
            {feedback.node}
        </div>
    );
}

function ChamberClear({ message, nextLabel, onNext }) {
    return (
        <>
            <div className="center" style={{ padding: '20px 0' }}>
                <div className="badge">🏆</div>
                <h3 className="fred" style={{ fontSize: 26, color: 'var(--navy)' }}>
                    Chamber unlocked!
                </h3>
                <p style={{ color: 'var(--muted)', marginTop: 4 }}>{message}</p>
            </div>
            <div className="row" style={{ marginTop: 12 }}>
                <button type="button" className="btn" onClick={onNext}>
                    {nextLabel}
                </button>
            </div>
        </>
    );
}

function OutOfHearts({ onRetry }) {
    return (
        <>
            <Feedback
                feedback={{
                    ok: false,
                    node: '💔 Out of lives! The Labyrinth shifted… but heroes never give up. Try this chamber again!',
                }}
            />
            <div className="row" style={{ marginTop: 12 }}>
                <button type="button" className="btn" onClick={onRetry}>
                    Try Again ↻
                </button>
            </div>
        </>
    );
}

export default function PercySyllableQuest() {
    const { claim } = useGameXp();
    const claimedVictory = useRef(false);

    const [heroName, setHeroName] = useState('');
    const [nameDraft, setNameDraft] = useState('');
    const [xp, setXp] = useState(0);
    const [hearts, setHearts] = useState(MAX_HEARTS);
    const [correct, setCorrect] = useState(0);
    const [total, setTotal] = useState(0);
    const [hudChamber, setHudChamber] = useState(0);

    const [scene, setScene] = useState('intro');
    const [chamberKey, setChamberKey] = useState('gate');

    const [beatWords, setBeatWords] = useState(() => shuffle(BEAT_WORDS_POOL).slice(0, 6));
    const [swordWords, setSwordWords] = useState(() => shuffle(SWORD_WORDS_POOL).slice(0, 7));
    const [shieldItems, setShieldItems] = useState(() => shuffle(SHIELD_ITEMS_POOL).slice(0, 8));
    const [bossQuestions, setBossQuestions] = useState(() => shuffle(BOSS_QUESTIONS));

    const [qIndex, setQIndex] = useState(0);
    const [tried, setTried] = useState(false);
    const [feedback, setFeedback] = useState(null);
    const [awaitingNext, setAwaitingNext] = useState(false);
    const [nextLabel, setNextLabel] = useState('');
    const [chamberCleared, setChamberCleared] = useState(false);
    const [noHearts, setNoHearts] = useState(false);

    const [mcqOpts, setMcqOpts] = useState([]);
    const [mcqPick, setMcqPick] = useState(null);
    const [mcqLocked, setMcqLocked] = useState(false);

    const [claps, setClaps] = useState(0);
    const [beatsLocked, setBeatsLocked] = useState(false);

    const [cutMarks, setCutMarks] = useState({});
    const [swordLocked, setSwordLocked] = useState(false);

    const [shieldDisabled, setShieldDisabled] = useState(false);

    const [bossHp, setBossHp] = useState(BOSS_MAX_HP);
    const [bossQRound, setBossQRound] = useState(0);
    const [monsterHit, setMonsterHit] = useState(false);
    const [bossFail, setBossFail] = useState(false);

    const [confettiPieces, setConfettiPieces] = useState([]);

    const [chamberClearMsg, setChamberClearMsg] = useState('');
    const [chamberNextScene, setChamberNextScene] = useState('beats');
    const [nextAction, setNextAction] = useState(null);

    const hero = heroName || 'Demigod';
    const xpPct = Math.min(100, (xp / MAX_XP) * 100);
    const heartsDisplay =
        '❤️'.repeat(Math.max(0, hearts)) + '🤍'.repeat(Math.max(0, MAX_HEARTS - hearts));

    const shell = CHAMBER_SHELL[chamberKey];

    const addXp = useCallback((n) => {
        setXp((v) => v + n);
    }, []);

    const burstConfetti = useCallback(() => {
        const batch = spawnConfettiPieces();
        setConfettiPieces((prev) => [...prev, ...batch]);
        window.setTimeout(() => {
            setConfettiPieces((prev) => prev.filter((p) => !batch.find((b) => b.id === p.id)));
        }, 3800);
    }, []);

    const resetChamberRound = useCallback(() => {
        setQIndex(0);
        setTried(false);
        setFeedback(null);
        setAwaitingNext(false);
        setChamberCleared(false);
        setNoHearts(false);
        setMcqPick(null);
        setMcqLocked(false);
        setClaps(0);
        setBeatsLocked(false);
        setCutMarks({});
        setSwordLocked(false);
        setShieldDisabled(false);
        setHearts(MAX_HEARTS);
    }, []);

    const enterChamber = useCallback(
        (key) => {
            const meta = CHAMBER_SHELL[key];
            if (!meta) return;
            setChamberKey(key);
            setHudChamber(meta.idx);
            setScene('chamber');
            if (key === 'beats') setBeatWords(shuffle(BEAT_WORDS_POOL).slice(0, 6));
            if (key === 'sword') setSwordWords(shuffle(SWORD_WORDS_POOL).slice(0, 7));
            if (key === 'shield') setShieldItems(shuffle(SHIELD_ITEMS_POOL).slice(0, 8));
            resetChamberRound();
        },
        [resetChamberRound],
    );

    const beginGame = () => {
        const name = nameDraft.trim();
        setHeroName(name);
        sWin();
        enterChamber('gate');
    };

    const finishChamber = useCallback(
        (message, nextScene) => {
            sWin();
            burstConfetti();
            setChamberCleared(true);
            setFeedback(null);
            setAwaitingNext(false);
            setChamberClearMsg(message);
            setChamberNextScene(nextScene);
        },
        [burstConfetti],
    );

    const advanceFromClear = () => {
        if (chamberNextScene === 'bossIntro') {
            setHudChamber(4);
            setHearts(MAX_HEARTS);
            setScene('bossIntro');
            return;
        }
        enterChamber(chamberNextScene);
    };

    const retryChamber = () => {
        if (chamberKey === 'beats') setBeatWords(shuffle(BEAT_WORDS_POOL).slice(0, 6));
        if (chamberKey === 'sword') setSwordWords(shuffle(SWORD_WORDS_POOL).slice(0, 7));
        if (chamberKey === 'shield') setShieldItems(shuffle(SHIELD_ITEMS_POOL).slice(0, 8));
        resetChamberRound();
    };

    useEffect(() => {
        if (scene !== 'chamber' || chamberKey !== 'gate' || chamberCleared || noHearts) return;
        const item = GATE_ITEMS[qIndex];
        if (!item) return;
        setMcqOpts(shuffle(item.o));
        setMcqPick(null);
        setMcqLocked(false);
        setFeedback(null);
        setAwaitingNext(false);
    }, [scene, chamberKey, qIndex, chamberCleared, noHearts]);

    useEffect(() => {
        if (scene !== 'boss' || bossFail) return;
        const item = bossQuestions[bossQRound % bossQuestions.length];
        if (!item) return;
        setMcqOpts(shuffle(item.o));
        setMcqPick(null);
        setMcqLocked(false);
        setFeedback(null);
        setAwaitingNext(false);
    }, [scene, bossQRound, bossQuestions, bossFail, bossHp]);

    useEffect(() => {
        if (scene === 'victory' && !claimedVictory.current) {
            claimedVictory.current = true;
            sWin();
            burstConfetti();
            window.setTimeout(burstConfetti, 700);
            const points = Math.min(20, Math.max(1, Math.round((xp / MAX_XP) * 20)));
            claim(points);
        }
    }, [scene, xp, claim, burstConfetti]);

    const handleGateAnswer = (option) => {
        if (mcqLocked || chamberCleared || noHearts) return;
        const item = GATE_ITEMS[qIndex];
        setTotal((t) => t + 1);
        if (option === item.a) {
            setMcqPick({ text: option, status: 'right' });
            setMcqLocked(true);
            sGood();
            if (!tried) setCorrect((c) => c + 1);
            addXp(tried ? 5 : 10);
            setFeedback({ ok: true, node: <>✅ {item.why}</> });
            const nextIdx = qIndex + 1;
            const done = nextIdx >= GATE_ITEMS.length;
            setNextLabel(done ? 'Unlock the chamber ➜' : 'Next ➜');
            setAwaitingNext(true);
            setNextAction(() => () => {
                if (done) {
                    finishChamber(shell.clearMsg, shell.next);
                } else {
                    setQIndex(nextIdx);
                    setTried(false);
                    setAwaitingNext(false);
                }
            });
        } else {
            setMcqPick({ text: option, status: 'wrong' });
            sBad();
            setTried(true);
            setFeedback({ ok: false, node: <>❌ Not quite! {item.hint || 'Try again, hero.'}</> });
            setHearts((h) => {
                const next = Math.max(0, h - 1);
                if (next <= 0) setNoHearts(true);
                return next;
            });
        }
    };

    const runNext = () => {
        if (nextAction) nextAction();
    };

    const handleBeatsCheck = () => {
        if (beatsLocked || chamberCleared || noHearts) return;
        const [w, split, n] = beatWords[qIndex];
        if (!claps) {
            setFeedback({ ok: false, node: 'Tap the 👏 button once for each beat first!' });
            return;
        }
        setTotal((t) => t + 1);
        if (claps === n) {
            sGood();
            if (!tried) setCorrect((c) => c + 1);
            addXp(tried ? 5 : 10);
            setBeatsLocked(true);
            setFeedback({
                ok: true,
                node: (
                    <>
                        ✅ Yes! <b>{split}</b> has <b>{n}</b> beat{n > 1 ? 's' : ''}.
                    </>
                ),
            });
            const nextIdx = qIndex + 1;
            const done = nextIdx >= beatWords.length;
            setNextLabel(done ? 'Leave the tunnel ➜' : 'Next word ➜');
            setAwaitingNext(true);
            setNextAction(() => () => {
                if (done) finishChamber(shell.clearMsg, shell.next);
                else {
                    setQIndex(nextIdx);
                    setTried(false);
                    setClaps(0);
                    setBeatsLocked(false);
                    setAwaitingNext(false);
                    setFeedback(null);
                }
            });
        } else {
            sBad();
            setTried(true);
            setFeedback({
                ok: false,
                node: (
                    <>
                        ❌ Not quite. Say <b>{w}</b> very slowly and clap again. Each beat has one vowel sound.
                    </>
                ),
            });
            setClaps(0);
            setHearts((h) => {
                const next = Math.max(0, h - 1);
                if (next <= 0) setNoHearts(true);
                return next;
            });
        }
    };

    const handleSwordCut = (cutIndex) => {
        if (swordLocked || chamberCleared || noHearts) return;
        const [w, correctCut] = swordWords[qIndex];
        setTotal((t) => t + 1);
        if (cutIndex === correctCut) {
            setCutMarks({ [cutIndex]: 'hit' });
            setSwordLocked(true);
            sGood();
            if (!tried) setCorrect((c) => c + 1);
            addXp(tried ? 5 : 10);
            setFeedback({
                ok: true,
                node: (
                    <>
                        ⚔️ Slice! <b>
                            {w.slice(0, correctCut)} - {w.slice(correctCut)}
                        </b>
                    </>
                ),
            });
            const nextIdx = qIndex + 1;
            const done = nextIdx >= swordWords.length;
            setNextLabel(done ? 'Sheathe the sword ➜' : 'Next word ➜');
            setAwaitingNext(true);
            setNextAction(() => () => {
                if (done) finishChamber(shell.clearMsg, shell.next);
                else {
                    setQIndex(nextIdx);
                    setTried(false);
                    setCutMarks({});
                    setSwordLocked(false);
                    setAwaitingNext(false);
                    setFeedback(null);
                }
            });
        } else {
            setCutMarks((m) => ({ ...m, [cutIndex]: 'miss' }));
            sBad();
            setTried(true);
            setFeedback({
                ok: false,
                node: (
                    <>
                        ❌ That cut makes <b>
                            {w.slice(0, cutIndex)} | {w.slice(cutIndex)}
                        </b>
                        . Clap the beats: where does the first beat end?
                    </>
                ),
            });
            setHearts((h) => {
                const next = Math.max(0, h - 1);
                if (next <= 0) setNoHearts(true);
                return next;
            });
        }
    };

    const handleShieldPick = (type) => {
        if (shieldDisabled || chamberCleared || noHearts) return;
        const [s, , correctType] = shieldItems[qIndex];
        setTotal((t) => t + 1);
        const last = s.slice(-1).toUpperCase();
        if (type === correctType) {
            sGood();
            if (!tried) setCorrect((c) => c + 1);
            addXp(tried ? 5 : 10);
            setShieldDisabled(true);
            setFeedback({
                ok: true,
                node:
                    correctType === 'open' ? (
                        <>
                            ✅ <b>{s}</b> ends in the vowel <b>{last}</b>, so it is OPEN. The vowel says its name!
                        </>
                    ) : (
                        <>
                            ✅ <b>{s}</b> ends in the consonant <b>{last}</b>, so it is CLOSED. The vowel sound is short.
                        </>
                    ),
            });
            const nextIdx = qIndex + 1;
            const done = nextIdx >= shieldItems.length;
            setNextLabel(done ? 'Enter the final chamber ➜' : 'Next syllable ➜');
            setAwaitingNext(true);
            setNextAction(() => () => {
                if (done) finishChamber(shell.clearMsg, shell.next);
                else {
                    setQIndex(nextIdx);
                    setTried(false);
                    setShieldDisabled(false);
                    setAwaitingNext(false);
                    setFeedback(null);
                }
            });
        } else {
            sBad();
            setTried(true);
            setFeedback({
                ok: false,
                node: (
                    <>
                        ❌ Look at the last letter of <b>{s}</b>: <b>{last}</b>. Is it a vowel or a consonant?
                    </>
                ),
            });
            setHearts((h) => {
                const next = Math.max(0, h - 1);
                if (next <= 0) setNoHearts(true);
                return next;
            });
        }
    };

    const startBossFight = () => {
        setBossQuestions(shuffle(BOSS_QUESTIONS));
        setBossHp(BOSS_MAX_HP);
        setBossQRound(0);
        setBossFail(false);
        setHearts(MAX_HEARTS);
        setHudChamber(4);
        setScene('boss');
    };

    const handleBossAnswer = (option) => {
        if (mcqLocked || bossFail) return;
        const item = bossQuestions[bossQRound % bossQuestions.length];
        setTotal((t) => t + 1);
        setMcqLocked(true);
        if (option === item.a) {
            setMcqPick({ text: option, status: 'right' });
            sGood();
            setCorrect((c) => c + 1);
            addXp(15);
            const newHp = bossHp - 1;
            setBossHp(newHp);
            setMonsterHit(true);
            window.setTimeout(() => setMonsterHit(false), 500);
            setFeedback({ ok: true, node: <>⚔️ Direct hit! {item.why}</> });
            if (newHp <= 0) {
                setNextLabel('Finish it! ⚡');
                setAwaitingNext(true);
                setNextAction(() => () => {
                    setHudChamber(5);
                    setScene('victory');
                });
            } else {
                setNextLabel('Strike again ➜');
                setAwaitingNext(true);
                setNextAction(() => () => {
                    setBossQRound((r) => r + 1);
                    setAwaitingNext(false);
                });
            }
        } else {
            setMcqPick({ text: option, status: 'wrong', reveal: item.a });
            sBad();
            setFeedback({
                ok: false,
                node: (
                    <>
                        💥 The monster strikes! The answer was <b>{item.a}</b>. {item.why}
                    </>
                ),
            });
            setHearts((h) => {
                const nh = Math.max(0, h - 1);
                if (nh <= 0) {
                    setBossFail(true);
                    setNextLabel('Fight Again ⚔️');
                    setAwaitingNext(true);
                    setNextAction(() => () => startBossFight());
                } else {
                    setNextLabel('Keep fighting ➜');
                    setAwaitingNext(true);
                    setNextAction(() => () => {
                        setBossQRound((r) => r + 1);
                        setAwaitingNext(false);
                    });
                }
                return nh;
            });
        }
    };

    const playAgain = () => {
        claimedVictory.current = false;
        setHeroName('');
        setNameDraft('');
        setXp(0);
        setHearts(MAX_HEARTS);
        setCorrect(0);
        setTotal(0);
        setHudChamber(0);
        setScene('intro');
        resetChamberRound();
    };

    const accuracy = total ? Math.round((correct / total) * 100) : 0;
    const victoryRank = useMemo(() => rankForXp(xp), [xp]);

    const renderChamberStage = () => {
        if (noHearts) return <OutOfHearts onRetry={retryChamber} />;
        if (chamberCleared) {
            return (
                <ChamberClear
                    message={chamberClearMsg}
                    nextLabel="Next Chamber ➜"
                    onNext={advanceFromClear}
                />
            );
        }

        if (chamberKey === 'gate') {
            const item = GATE_ITEMS[qIndex];
            return (
                <>
                    <div className="progress">
                        Question {qIndex + 1} of {GATE_ITEMS.length}
                    </div>
                    <div className="q">{item.q}</div>
                    <div className="opts">
                        {mcqOpts.map((o) => {
                            const picked = mcqPick?.text === o;
                            let cls = 'opt';
                            if (picked && mcqPick.status === 'right') cls += ' right';
                            if (picked && mcqPick.status === 'wrong') cls += ' wrong';
                            return (
                                <button
                                    key={o}
                                    type="button"
                                    className={cls}
                                    disabled={mcqLocked && !(picked && mcqPick.status === 'wrong')}
                                    onClick={() => handleGateAnswer(o)}
                                >
                                    {o}
                                </button>
                            );
                        })}
                    </div>
                    <Feedback feedback={feedback} />
                    {awaitingNext ? (
                        <div className="row" style={{ marginTop: 12 }}>
                            <button type="button" className="btn" onClick={runNext}>
                                {nextLabel}
                            </button>
                        </div>
                    ) : null}
                </>
            );
        }

        if (chamberKey === 'beats') {
            const [w] = beatWords[qIndex];
            return (
                <>
                    <div className="progress">
                        Word {qIndex + 1} of {beatWords.length}
                    </div>
                    <div className="clapzone">
                        <div className="bigword">{w}</div>
                        <div className="claps">
                            {claps ? `${'👏'.repeat(claps)}  ${claps}` : ''}
                        </div>
                        <div className="row" style={{ justifyContent: 'center' }}>
                            <button
                                type="button"
                                className="clapbtn"
                                aria-label="Clap"
                                disabled={beatsLocked}
                                onClick={() => {
                                    if (claps < 6) {
                                        setClaps((c) => c + 1);
                                        sClap();
                                    }
                                }}
                            >
                                👏
                            </button>
                        </div>
                        <div className="row" style={{ justifyContent: 'center' }}>
                            <button
                                type="button"
                                className="btn ghost"
                                disabled={beatsLocked}
                                onClick={() => setClaps(0)}
                            >
                                Reset
                            </button>
                            <button type="button" className="btn alt" disabled={beatsLocked} onClick={handleBeatsCheck}>
                                Check
                            </button>
                        </div>
                    </div>
                    <Feedback feedback={feedback} />
                    {awaitingNext ? (
                        <div className="row" style={{ marginTop: 12 }}>
                            <button type="button" className="btn" onClick={runNext}>
                                {nextLabel}
                            </button>
                        </div>
                    ) : null}
                </>
            );
        }

        if (chamberKey === 'sword') {
            const [w] = swordWords[qIndex];
            const letters = [...w];
            return (
                <>
                    <div className="progress">
                        Word {qIndex + 1} of {swordWords.length}
                    </div>
                    <div className="split">
                        {letters.map((c, k) => (
                            <span key={`${w}-${k}`} className="sylpart">
                                <span className="ch">{c}</span>
                                {k < letters.length - 1 ? (
                                    <button
                                        type="button"
                                        className={`cut${cutMarks[k + 1] ? ` ${cutMarks[k + 1]}` : ''}`}
                                        aria-label={`cut after ${c}`}
                                        disabled={Boolean(cutMarks[k + 1]) || swordLocked}
                                        onClick={() => handleSwordCut(k + 1)}
                                    />
                                ) : null}
                            </span>
                        ))}
                    </div>
                    <Feedback feedback={feedback} />
                    {awaitingNext ? (
                        <div className="row" style={{ marginTop: 12 }}>
                            <button type="button" className="btn" onClick={runNext}>
                                {nextLabel}
                            </button>
                        </div>
                    ) : null}
                </>
            );
        }

        if (chamberKey === 'shield') {
            const [s, word] = shieldItems[qIndex];
            return (
                <>
                    <div className="progress">
                        Syllable {qIndex + 1} of {shieldItems.length} · from the word <b>{word}</b>
                    </div>
                    <div className="center">
                        <div className="sylchip">{s}</div>
                    </div>
                    <div className="shields">
                        <button
                            type="button"
                            className="shield open"
                            disabled={shieldDisabled}
                            onClick={() => handleShieldPick('open')}
                        >
                            <div className="n">OPEN 🔓</div>
                            <div className="d">ends in a vowel · long sound</div>
                        </button>
                        <button
                            type="button"
                            className="shield closed"
                            disabled={shieldDisabled}
                            onClick={() => handleShieldPick('closed')}
                        >
                            <div className="n">CLOSED 🔒</div>
                            <div className="d">ends in a consonant · short sound</div>
                        </button>
                    </div>
                    <Feedback feedback={feedback} />
                    {awaitingNext ? (
                        <div className="row" style={{ marginTop: 12 }}>
                            <button type="button" className="btn" onClick={runNext}>
                                {nextLabel}
                            </button>
                        </div>
                    ) : null}
                </>
            );
        }

        return null;
    };

    const renderBoss = () => {
        if (bossFail) {
            return (
                <>
                    <Feedback
                        feedback={{
                            ok: false,
                            node: '💔 The monster knocked you back! Catch your breath and fight again, hero.',
                        }}
                    />
                    <div className="row" style={{ marginTop: 12 }}>
                        <button type="button" className="btn" onClick={startBossFight}>
                            Fight Again ⚔️
                        </button>
                    </div>
                </>
            );
        }

        const item = bossQuestions[bossQRound % bossQuestions.length];
        const hpPct = (bossHp / BOSS_MAX_HP) * 100;

        return (
            <>
                <div className="boss">
                    <div className={`monster${monsterHit ? ' hit' : ''}`}>👹</div>
                    <div className="hpwrap">
                        <div className="lbl">
                            <span>Monster of Confusion</span>
                            <span>
                                {bossHp}/{BOSS_MAX_HP}
                            </span>
                        </div>
                        <div className="hp">
                            <i style={{ width: `${hpPct}%` }} />
                        </div>
                    </div>
                </div>
                <div className="q">{item.q}</div>
                <div className="opts">
                    {mcqOpts.map((o) => {
                        const picked = mcqPick?.text === o;
                        let cls = 'opt';
                        if (picked && mcqPick.status === 'right') cls += ' right';
                        if (picked && mcqPick.status === 'wrong') cls += ' wrong';
                        if (mcqLocked && mcqPick?.reveal === o) cls += ' right';
                        return (
                            <button
                                key={o}
                                type="button"
                                className={cls}
                                disabled={mcqLocked}
                                onClick={() => handleBossAnswer(o)}
                            >
                                {o}
                            </button>
                        );
                    })}
                </div>
                <Feedback feedback={feedback} />
                {awaitingNext ? (
                    <div className="row" style={{ marginTop: 12 }}>
                        <button type="button" className="btn" onClick={runNext}>
                            {nextLabel}
                        </button>
                    </div>
                ) : null}
            </>
        );
    };

    return (
        <div className="psq">
            <div className="stripe" aria-hidden="true">
                <span className="s1" />
                <span className="s2" />
                <span className="s3" />
                <span className="s4" />
                <span className="s5" />
                <span className="s6" />
            </div>

            {confettiPieces.map((p) => (
                <div
                    key={p.id}
                    className="confetti"
                    style={{
                        left: `${p.left}vw`,
                        background: p.background,
                        animationDuration: `${p.duration}s`,
                        animationDelay: `${p.delay}s`,
                    }}
                />
            ))}

            <div className="wrap">
                <header className="topbar">
                    <span className="brand">⚔️ Syllable Sword Quest</span>
                    <div className="stats">
                        <span className="stat">
                            ✨ <span>{xp}</span>
                            <span className="xpbar">
                                <i style={{ width: `${xpPct}%` }} />
                            </span>
                        </span>
                        <span className="stat hearts">{heartsDisplay}</span>
                    </div>
                </header>

                <nav className="map" aria-label="Chamber map">
                    {CHAMBERS.map((c, i) => {
                        let nodeClass = 'node';
                        if (i < hudChamber) nodeClass += ' done';
                        else if (i === hudChamber) nodeClass += ' now';
                        return (
                            <div key={c.key} className={nodeClass}>
                                {i < hudChamber ? '✔' : c.icon} {c.label}
                            </div>
                        );
                    })}
                </nav>

                <main className="card">
                    {scene === 'intro' && (
                        <>
                            <div className="eyebrow">Mission 1 · Syllable Sword Quest</div>
                            <h1 className="title">Escape the Labyrinth!</h1>
                            <div className="intro-hero">
                                <div className="em">🌀</div>
                                <div className="story" style={{ margin: 0 }}>
                                    A <b>Monster of Confusion</b> has escaped from the Labyrinth! Its magic breaks words into pieces and scrambles their sounds. Only a brave English demigod can find the way through the twisting tunnels, unlock each chamber and defeat it with the <b>Syllable Sword</b>.
                                </div>
                            </div>
                            <div className="missionlist">
                                <div className="mi" style={{ borderColor: 'var(--blue)' }}>
                                    <b>🏛️ Demigod Gate</b> Prove you know Percy&apos;s world.
                                </div>
                                <div className="mi" style={{ borderColor: 'var(--orange)' }}>
                                    <b>👏 Echo Tunnel</b> Clap the beats in each word.
                                </div>
                                <div className="mi" style={{ borderColor: 'var(--green)' }}>
                                    <b>🗡️ Syllable Sword</b> Slice words into syllables.
                                </div>
                                <div className="mi" style={{ borderColor: 'var(--purple)' }}>
                                    <b>🛡️ Shield Hall</b> Open or closed syllable?
                                </div>
                                <div className="mi" style={{ borderColor: 'var(--navy)' }}>
                                    <b>👹 Final Battle</b> Defeat the Monster of Confusion!
                                </div>
                            </div>
                            <div className="row">
                                <input
                                    className="namein"
                                    placeholder="Type your hero name…"
                                    maxLength={20}
                                    value={nameDraft}
                                    onChange={(e) => setNameDraft(e.target.value)}
                                    onKeyDown={(e) => {
                                        if (e.key === 'Enter') beginGame();
                                    }}
                                />
                                <button type="button" className="btn" onClick={beginGame}>
                                    Enter the Labyrinth ⚔️
                                </button>
                            </div>
                            <p className="progress" style={{ marginTop: 12 }}>
                                You have 5 ❤️ lives in each chamber. Every correct answer earns XP!
                            </p>
                        </>
                    )}

                    {scene === 'chamber' && shell && (
                        <>
                            <div className="eyebrow">
                                Chamber {shell.idx + 1} of 5 · {shell.eyebrow}
                            </div>
                            <h2 className="title">{shell.title}</h2>
                            {shell.story ? <div className="story">{shell.story(hero)}</div> : null}
                            {shell.rule ? (
                                <div className="rule">
                                    💡 <div>{shell.rule}</div>
                                </div>
                            ) : null}
                            {renderChamberStage()}
                        </>
                    )}

                    {scene === 'bossIntro' && (
                        <>
                            <div className="eyebrow">Chamber 5 of 5 · Final Battle</div>
                            <h2 className="title">👹 The Monster of Confusion</h2>
                            <div className="story">
                                The ground shakes… The <b>Monster of Confusion</b> rises from the dark! It scrambles everything you have learned. Use <b>all your powers</b> (story, vowels, beats and syllables) to strike it with Riptide. Each right answer is a hit. Each mistake lets it strike back!
                            </div>
                            <div className="center" style={{ padding: '10px 0 4px' }}>
                                <div style={{ fontSize: 96 }}>👹</div>
                            </div>
                            <div className="row" style={{ justifyContent: 'center', marginTop: 12 }}>
                                <button type="button" className="btn" onClick={startBossFight}>
                                    Draw Riptide ⚔️
                                </button>
                            </div>
                        </>
                    )}

                    {scene === 'boss' && (
                        <>
                            <div className="eyebrow">Chamber 5 of 5 · Final Battle</div>
                            <h2 className="title">👹 Defeat the Monster!</h2>
                            {renderBoss()}
                        </>
                    )}

                    {scene === 'victory' && (
                        <div className="center">
                            <div className="eyebrow">Quest Complete</div>
                            <div className="badge">{victoryRank[0]}</div>
                            <h2 className="title">Victory, {hero}!</h2>
                            <p style={{ color: 'var(--muted)' }}>
                                The Monster of Confusion is defeated, and the words of the Labyrinth are whole again.
                            </p>
                            <div className="rank" style={{ marginTop: 8 }}>
                                {victoryRank[1]}
                            </div>
                            <div className="summary">
                                <div className="sum">
                                    <div className="v">{xp}</div>
                                    <div className="k">Total XP</div>
                                </div>
                                <div className="sum">
                                    <div className="v">{accuracy}%</div>
                                    <div className="k">First-try accuracy</div>
                                </div>
                                <div className="sum">
                                    <div className="v">5/5</div>
                                    <div className="k">Chambers cleared</div>
                                </div>
                            </div>
                            <div className="recap">
                                <b style={{ fontFamily: 'Fredoka,sans-serif', color: 'var(--navy)', fontSize: 16 }}>
                                    🧠 Powers you unlocked today
                                </b>
                                <ul>
                                    <li>
                                        <b>Vowels</b> are A, E, I, O, U. Every syllable needs a vowel sound.
                                    </li>
                                    <li>
                                        A <b>syllable</b> is one beat of a word: clap it out! (<i>ad · ven · ture</i> = 3)
                                    </li>
                                    <li>
                                        <b>Closed syllable</b>: ends in a consonant, short vowel (<i>mon</i>-ster, <i>nap</i>-kin)
                                    </li>
                                    <li>
                                        <b>Open syllable</b>: ends in a vowel, long vowel that says its name (<i>ti</i>-ger, <i>ze</i>-ro)
                                    </li>
                                </ul>
                            </div>
                            <p className="progress">🛡️ Next mission: <b>Sentence Shield</b>, defeating run-on sentences!</p>
                            <div className="row" style={{ justifyContent: 'center', marginTop: 14 }}>
                                <button type="button" className="btn" onClick={playAgain}>
                                    Play Again ↻
                                </button>
                            </div>
                        </div>
                    )}

                    <GameXpNote />
                </main>

                <p className="foot">
                    <b>Syllable Sword Quest</b> · Percy Jackson English adventure
                </p>
            </div>
        </div>
    );
}