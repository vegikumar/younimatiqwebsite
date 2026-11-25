import { useCallback, useEffect, useRef, useState } from 'react';
import './styles/labyrinth-grammar.css';
import { GameXpNote, useGameXp } from './GameXp';

const RANKS = [
    [0, 'Rookie Demigod'],
    [100, 'Word Warrior'],
    [250, 'Sentence Guardian'],
    [400, 'Master Storyteller'],
    [600, 'English Demigod'],
];

const SLICE_WORDS = [
    'ro-bot', 'ho-tel', 'sun-set', 'mu-sic', 'nap-kin', 'cab-in', 'la-zy', 'ti-ger', 'rab-bit', 'cam-pus',
    'mon-ster', 'ad-ven-ture', 'fan-tas-tic', 'light-ning', 'mag-i-cal', 'lab-y-rinth', 'my-thol-o-gy',
    'mys-te-ri-ous', 'dan-ger-ous', 'ze-ro', 'pi-lot', 'bas-ket', 'dem-i-god', 'pro-phe-cy', 'Per-cy', 'hel-met',
];

const GATE_DECK = [
    ['ro-bot', 0, true], ['ro-bot', 1, false], ['ho-tel', 0, true], ['ho-tel', 1, false],
    ['mu-sic', 0, true], ['mu-sic', 1, false], ['sun-set', 0, false], ['nap-kin', 1, false],
    ['nap-kin', 0, false], ['ti-ger', 0, true], ['ze-ro', 0, true], ['ze-ro', 1, true],
    ['me-ter', 0, true], ['la-zy', 0, true], ['la-zy', 1, true], ['cab-in', 0, false],
    ['cab-in', 1, false], ['pi-lot', 0, true], ['pi-lot', 1, false], ['rab-bit', 0, false],
    ['cam-pus', 1, false], ['mon-ster', 0, false], ['fan-tas-tic', 1, false], ['light-ning', 0, false],
    ['o-pen', 0, true], ['o-pen', 1, false], ['ba-con', 0, true], ['si-lent', 0, true],
    ['si-lent', 1, false], ['tu-lip', 0, true], ['tu-lip', 1, false], ['he-ro', 0, true],
    ['he-ro', 1, true], ['hel-met', 0, false], ['my-thol-o-gy', 0, true], ['my-thol-o-gy', 1, false],
    ['bas-ket', 1, false], ['Per-cy', 1, true],
];

const SPOT_ITEMS = [
    ['Percy was tired', 'he continued fighting', 'but'],
    ['Annabeth solved the puzzle', 'Percy opened the door', 'and'],
    ['Hercules lifted the boulder', 'he threw it across the river', 'and'],
    ['Athena drew a map', 'Percy followed the path', 'and'],
    ['The cyclops roared', 'the campers hid behind the wall', 'so'],
    ['Zeus hurled lightning', 'the sky turned white', 'and'],
    ['Hermes ran quickly', 'he delivered the message', 'and'],
    ['The satyr played his flute', 'the monsters fell asleep', 'and'],
    ['Apollo shot an arrow', 'it landed in the target', 'and'],
    ['Artemis tracked the beast', 'she never made a sound', 'but'],
    ['The tunnel twisted left', 'the heroes followed it', 'so'],
    ['Grover heard a growl', 'he froze in fear', 'and'],
    ['The scorpion snapped its claws', 'Annabeth ducked behind a rock', 'so'],
    ['Rachel pointed to the exit', 'everyone ran towards the light', 'and'],
    ['The maze shifted again', 'the friends were separated', 'and'],
    ['Poseidon calmed the waves', 'the ship sailed home', 'and'],
    ['The gate opened', 'the heroes stepped inside', 'and'],
    ['Tyson was strong', 'he was also very kind', 'and'],
];

const MESSAGES = [
    { words: 'the minotaur is sleeping do not make a sound'.split(' '), allow: { 3: ['.', '!'], 9: ['.', '!'] }, model: 'The minotaur is sleeping. Do not make a sound!' },
    { words: 'run left the right tunnel is blocked'.split(' '), allow: { 1: ['.', '!', ';'], 6: ['.', '!'] }, model: 'Run left! The right tunnel is blocked.' },
    { words: 'the labyrinth was dangerous but the heroes kept going'.split(' '), allow: { 4: [','], 8: ['.', '!'] }, model: 'The Labyrinth was dangerous, but the heroes kept going.' },
    { words: 'watch out the floor is moving'.split(' '), allow: { 1: ['!', '.'], 5: ['!', '.'] }, model: 'Watch out! The floor is moving!' },
    { words: 'the gate opened so we ran inside'.split(' '), allow: { 2: [','], 6: ['.', '!'] }, model: 'The gate opened, so we ran inside.' },
    { words: 'Rachel found the exit everyone followed her'.split(' '), allow: { 3: ['.', ';', '!'], 6: ['.', '!'] }, model: 'Rachel found the exit. Everyone followed her.' },
];

const MARKS = ['', '.', ',', '!', ';', '?'];

function shuffle(arr) {
    const a = [...arr];
    for (let i = a.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [a[i], a[j]] = [a[j], a[i]];
    }
    return a;
}

export default function LabyrinthGrammar() {
    const { claim } = useGameXp();

    // Overall state
    const [xp, setXp] = useState(() => {
        try {
            return Number(localStorage.getItem('lab-xp')) || 0;
        } catch {
            return 0;
        }
    });
    const [bests, setBests] = useState(() => {
        try {
            return JSON.parse(localStorage.getItem('lab-bests') || '{}');
        } catch {
            return {};
        }
    });

    const [activeGame, setActiveGame] = useState('slicer'); // slicer | gate | spotter | repair
    const panelRef = useRef(null);

    const addXP = useCallback((pts) => {
        setXp((prev) => {
            const next = prev + pts;
            try {
                localStorage.setItem('lab-xp', next);
            } catch {}
            // Claim up to 20 XP in Younimatiq
            const earnedUnit = Math.min(20, Math.max(1, Math.round((next / 300) * 20)));
            claim(earnedUnit);
            return next;
        });
    }, [claim]);

    const saveBest = useCallback((game, score) => {
        setBests((prev) => {
            const old = prev[game] || 0;
            if (score > old) {
                const next = { ...prev, [game]: score };
                try {
                    localStorage.setItem('lab-bests', JSON.stringify(next));
                } catch {}
                return next;
            }
            return prev;
        });
    }, []);

    // Calculate current rank
    let currentRank = RANKS[0][1];
    let nextRankXP = RANKS[1][0];
    let prevRankXP = 0;
    for (let i = 0; i < RANKS.length; i++) {
        if (xp >= RANKS[i][0]) {
            currentRank = RANKS[i][1];
            prevRankXP = RANKS[i][0];
            nextRankXP = RANKS[i + 1] ? RANKS[i + 1][0] : null;
        }
    }
    const rankProgress = nextRankXP
        ? Math.min(100, Math.max(0, ((xp - prevRankXP) / (nextRankXP - prevRankXP)) * 100))
        : 100;

    const selectGame = (g) => {
        setActiveGame(g);
        panelRef.current?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    };

    return (
        <div className="ym-lab">
            <div className="wrap">
                <header className="top">
                    <div className="brand">
                        <div className="delta" aria-hidden="true">Δ</div>
                        <div>
                            <h1>Labyrinth Grammar Games</h1>
                            <p>The Battle of the Labyrinth · English powers practice</p>
                        </div>
                    </div>
                    <div className="xpbox">
                        <div>
                            <div className="xp">{xp} XP</div>
                            <div className="barwrap">
                                <div className="bar" style={{ width: `${rankProgress}%` }} />
                            </div>
                        </div>
                        <span className="rank">{currentRank}</span>
                    </div>
                </header>

                <p className="intro">
                    The Monster of Confusion is loose in the Labyrinth. Pick a challenge, earn XP and climb the ranks:
                    Word Warrior at 100 XP, Sentence Guardian at 250, Master Storyteller at 400 and English Demigod at 600.
                </p>

                <nav className="hub" aria-label="Choose a game">
                    <button
                        type="button"
                        className={`card${activeGame === 'slicer' ? ' active' : ''}`}
                        style={{ '--c': 'var(--torch)' }}
                        onClick={() => selectGame('slicer')}
                    >
                        <span className="tag">Syllable Sword · Mission 1</span>
                        <h2>Syllable Slicer</h2>
                        <p>Tap the gaps to slice each monster word into syllables.</p>
                        <span className="best">Best: {bests.slicer !== undefined ? `${bests.slicer} / 8` : '–'}</span>
                    </button>

                    <button
                        type="button"
                        className={`card${activeGame === 'gate' ? ' active' : ''}`}
                        style={{ '--c': 'var(--sea)' }}
                        onClick={() => selectGame('gate')}
                    >
                        <span className="tag">Syllable Sword · Mission 1</span>
                        <h2>Open or Closed Gate</h2>
                        <p>60-second rush. Is the glowing syllable open or closed?</p>
                        <span className="best">Best: {bests.gate !== undefined ? `${bests.gate} correct` : '–'}</span>
                    </button>

                    <button
                        type="button"
                        className={`card${activeGame === 'spotter' ? ' active' : ''}`}
                        style={{ '--c': 'var(--bronze)' }}
                        onClick={() => selectGame('spotter')}
                    >
                        <span className="tag">Sentence Shield · Mission 2</span>
                        <h2>Monster Spotter</h2>
                        <p>Name the monster (run-on or comma splice), then pick the repair.</p>
                        <span className="best">Best: {bests.spotter !== undefined ? `${bests.spotter} / 8` : '–'}</span>
                    </button>

                    <button
                        type="button"
                        className={`card${activeGame === 'repair' ? ' active' : ''}`}
                        style={{ '--c': 'var(--good)' }}
                        onClick={() => selectGame('repair')}
                    >
                        <span className="tag">Sentence Shield + Storyteller</span>
                        <h2>Fix Scrambled Message</h2>
                        <p>Add the punctuation so every warning is clear.</p>
                        <span className="best">Best: {bests.repair !== undefined ? `${bests.repair} / 6` : '–'}</span>
                    </button>
                </nav>

                <div ref={panelRef}>
                    {activeGame === 'slicer' && (
                        <SlicerGame
                            addXP={addXP}
                            saveBest={saveBest}
                            onOtherGame={() => selectGame('gate')}
                        />
                    )}
                    {activeGame === 'gate' && (
                        <GateGame
                            addXP={addXP}
                            saveBest={saveBest}
                            onOtherGame={() => selectGame('spotter')}
                        />
                    )}
                    {activeGame === 'spotter' && (
                        <SpotterGame
                            addXP={addXP}
                            saveBest={saveBest}
                            onOtherGame={() => selectGame('repair')}
                        />
                    )}
                    {activeGame === 'repair' && (
                        <RepairGame
                            addXP={addXP}
                            saveBest={saveBest}
                            onOtherGame={() => selectGame('slicer')}
                        />
                    )}
                </div>

                <footer>
                    Practice games for the class "Welcome to Percy Jackson Series: The Battle of the Labyrinth".
                </footer>
            </div>
        </div>
    );
}

/* =========================================================================
   GAME 1: Syllable Slicer
   ========================================================================= */
function SlicerGame({ addXP, saveBest, onOtherGame }) {
    const [words, setWords] = useState(() => shuffle(SLICE_WORDS).slice(0, 8));
    const [wordIdx, setWordIdx] = useState(0);
    const [cuts, setCuts] = useState(new Set());
    const [checked, setChecked] = useState(false);
    const [hinted, setHinted] = useState(false);
    const [feedback, setFeedback] = useState(null);
    const [score, setScore] = useState(0);
    const [done, setDone] = useState(false);
    const [shake, setShake] = useState(false);

    const currentWord = words[wordIdx];
    const parts = currentWord ? currentWord.split('-') : [];
    const letters = parts.join('');

    // Answer cut positions (after letter 1-based)
    const answerCuts = new Set();
    let pos = 0;
    parts.slice(0, -1).forEach((p) => {
        pos += p.length;
        answerCuts.add(pos);
    });

    const toggleCut = (p) => {
        if (checked) return;
        setCuts((prev) => {
            const next = new Set(prev);
            if (next.has(p)) next.delete(p);
            else next.add(p);
            return next;
        });
    };

    const handleHint = () => {
        setHinted(true);
        setFeedback({
            good: true,
            msg: `Clap it: ${parts.map(() => '👏').join(' ')} (${parts.length} beats)`,
        });
    };

    const handleCheck = () => {
        setChecked(true);
        const isCorrect = cuts.size === answerCuts.size && [...cuts].every((c) => answerCuts.has(c));

        if (isCorrect) {
            const pts = hinted ? 5 : 10;
            addXP(pts);
            setScore((s) => s + 1);
            setFeedback({
                good: true,
                msg: `Clean slice! "${currentWord}" has ${parts.length} syllables. +${pts} XP`,
            });
        } else {
            setShake(true);
            setTimeout(() => setShake(false), 350);
            setFeedback({
                good: false,
                msg: `Not quite. It splits as "${currentWord}" (${parts.length} syllables). Green marks show missed cuts.`,
            });
        }
    };

    const handleNext = () => {
        if (wordIdx < words.length - 1) {
            setWordIdx((n) => n + 1);
            setCuts(new Set());
            setChecked(false);
            setHinted(false);
            setFeedback(null);
        } else {
            const finalScore = score + (checked && cuts.size === answerCuts.size && [...cuts].every((c) => answerCuts.has(c)) ? 0 : 0);
            saveBest('slicer', finalScore);
            setDone(true);
        }
    };

    const handleRestart = () => {
        setWords(shuffle(SLICE_WORDS).slice(0, 8));
        setWordIdx(0);
        setCuts(new Set());
        setChecked(false);
        setHinted(false);
        setFeedback(null);
        setScore(0);
        setDone(false);
    };

    return (
        <section className="panel">
            <div className="panel-head">
                <h2>Syllable Slicer</h2>
                <div className="meta">
                    <span className="pill">Word {wordIdx + 1} of {words.length}</span>
                    <span className="pill">{score} sliced</span>
                </div>
            </div>
            <p className="how">
                Tap the gap between letters to swing your Syllable Sword. Tap again to undo. Every syllable needs a vowel sound.
            </p>

            <div className="stage">
                {!done ? (
                    <>
                        <div className={`slicer${shake ? ' shake' : ''}`}>
                            {Array.from(letters).map((ch, idx) => {
                                const gapPos = idx + 1;
                                const isLast = idx === letters.length - 1;
                                const isCut = cuts.has(gapPos);
                                const isAnswer = answerCuts.has(gapPos);
                                let gapClass = 'gap';
                                if (isCut) gapClass += ' cut';
                                if (checked) {
                                    if (isCut && !isAnswer) gapClass += ' wrong';
                                    if (!isCut && isAnswer) gapClass += ' missed';
                                }

                                return (
                                    <span key={idx} style={{ display: 'inline-flex', alignItems: 'center' }}>
                                        <span className="ch">{ch}</span>
                                        {!isLast && (
                                            <button
                                                type="button"
                                                className={gapClass}
                                                disabled={checked}
                                                onClick={() => toggleCut(gapPos)}
                                                aria-label={`Cut after letter ${gapPos}`}
                                            />
                                        )}
                                    </span>
                                );
                            })}
                        </div>

                        {feedback && (
                            <div className={`feedback ${feedback.good ? 'good' : 'bad'}`}>
                                {feedback.msg}
                            </div>
                        )}

                        <div className="btns">
                            {!checked ? (
                                <>
                                    <button type="button" className="btn" onClick={handleHint} disabled={hinted}>
                                        Clap hint 👏
                                    </button>
                                    <button type="button" className="btn primary" onClick={handleCheck}>
                                        Check my slices
                                    </button>
                                </>
                            ) : (
                                <button type="button" className="btn primary" onClick={handleNext}>
                                    {wordIdx === words.length - 1 ? 'See results' : 'Next word ➜'}
                                </button>
                            )}
                        </div>
                    </>
                ) : (
                    <div className="done">
                        <div className="big">{score} / {words.length}</div>
                        <p>
                            {score >= 6
                                ? 'Syllable Sword mastered! You can break any monster word into beats.'
                                : 'Good fight! Clap the words slowly and try again to sharpen your sword.'}
                        </p>
                        <GameXpNote />
                        <div className="btns">
                            <button type="button" className="btn primary" onClick={handleRestart}>
                                Play again
                            </button>
                            <button type="button" className="btn" onClick={onOtherGame}>
                                Next challenge ➜
                            </button>
                        </div>
                    </div>
                )}
            </div>
        </section>
    );
}

/* =========================================================================
   GAME 2: Open or Closed Gate (60-sec rush)
   ========================================================================= */
function GateGame({ addXP, saveBest, onOtherGame }) {
    const [running, setRunning] = useState(false);
    const [timeLeft, setTimeLeft] = useState(60);
    const [deck, setDeck] = useState(() => shuffle(GATE_DECK));
    const [cardIdx, setCardIdx] = useState(0);
    const [score, setScore] = useState(0);
    const [streak, setStreak] = useState(0);
    const [feedback, setFeedback] = useState(null);
    const [done, setDone] = useState(false);

    const currentCard = deck[cardIdx] || deck[0];
    const parts = currentCard ? currentCard[0].split('-') : [];
    const targetSylIndex = currentCard ? currentCard[1] : 0;
    const isTargetOpen = currentCard ? currentCard[2] : false;

    // Timer countdown
    useEffect(() => {
        if (!running || timeLeft <= 0) return;
        const timer = setInterval(() => {
            setTimeLeft((t) => {
                if (t <= 1) {
                    clearInterval(timer);
                    setRunning(false);
                    setDone(true);
                    return 0;
                }
                return t - 1;
            });
        }, 1000);
        return () => clearInterval(timer);
    }, [running, timeLeft]);

    // Save best when done
    useEffect(() => {
        if (done) {
            saveBest('gate', score);
        }
    }, [done, score, saveBest]);

    const handleAnswer = useCallback((userSaidOpen) => {
        if (!running || timeLeft <= 0) return;
        const isRight = userSaidOpen === isTargetOpen;
        const syl = parts[targetSylIndex];

        if (isRight) {
            const nextStreak = streak + 1;
            setStreak(nextStreak);
            setScore((s) => s + 1);
            const pts = 5 + (nextStreak >= 5 ? 5 : 0);
            addXP(pts);
            setFeedback({
                good: true,
                msg: `Correct! "${syl}" is ${isTargetOpen ? 'OPEN (ends in a vowel)' : 'CLOSED (ends in a consonant)'}. +${pts} XP`,
            });
        } else {
            setStreak(0);
            setFeedback({
                good: false,
                msg: `Look at the end letter: "${syl}" ends in a ${isTargetOpen ? 'vowel (OPEN)' : 'consonant (CLOSED)'}.`,
            });
        }

        // Advance to next card
        setCardIdx((i) => {
            if (i >= deck.length - 1) {
                setDeck(shuffle(GATE_DECK));
                return 0;
            }
            return i + 1;
        });
    }, [running, timeLeft, isTargetOpen, parts, targetSylIndex, streak, addXP, deck.length]);

    // Keyboard support (O / C)
    useEffect(() => {
        if (!running) return;
        const onKeyDown = (e) => {
            if (e.key.toLowerCase() === 'o') handleAnswer(true);
            else if (e.key.toLowerCase() === 'c') handleAnswer(false);
        };
        window.addEventListener('keydown', onKeyDown);
        return () => window.removeEventListener('keydown', onKeyDown);
    }, [running, handleAnswer]);

    const handleStart = () => {
        setTimeLeft(60);
        setScore(0);
        setStreak(0);
        setCardIdx(0);
        setDeck(shuffle(GATE_DECK));
        setFeedback(null);
        setDone(false);
        setRunning(true);
    };

    return (
        <section className="panel">
            <div className="panel-head">
                <h2>Open or Closed Gate</h2>
                <div className="meta">
                    <span className="pill">{score} correct</span>
                    <span className="pill">Streak: {streak}</span>
                </div>
            </div>
            <p className="how">
                The Labyrinth gate shows one glowing syllable. If it ends in a vowel, it is <b>OPEN</b> (long vowel).
                If it ends in a consonant, it is <b>CLOSED</b> (short vowel). Press <b>O</b> or <b>C</b> on keyboard or tap below!
            </p>

            <div className="stage">
                {!running && !done ? (
                    <div>
                        <p style={{ marginBottom: 16 }}>Ready, demigod? The 60-second timer starts when you press Start.</p>
                        <button type="button" className="btn primary big" onClick={handleStart}>
                            Start the 60 seconds ⚡
                        </button>
                    </div>
                ) : running ? (
                    <>
                        <div className="timer">
                            <div style={{ width: `${(timeLeft / 60) * 100}%` }} />
                        </div>
                        <div className="small">{timeLeft} seconds left</div>

                        <div className="gate-word">
                            {parts.map((p, k) => (
                                <span key={k}>
                                    <span className={k === targetSylIndex ? 'hi' : 'lo'}>{p}</span>
                                    {k < parts.length - 1 && <span className="dash">-</span>}
                                </span>
                            ))}
                        </div>

                        <div className="btns">
                            <button type="button" className="btn open big" onClick={() => handleAnswer(true)}>
                                OPEN [O]
                            </button>
                            <button type="button" className="btn closed big" onClick={() => handleAnswer(false)}>
                                CLOSED [C]
                            </button>
                        </div>

                        {feedback && (
                            <div className={`feedback ${feedback.good ? 'good' : 'bad'}`}>
                                {feedback.msg}
                            </div>
                        )}
                    </>
                ) : (
                    <div className="done">
                        <div className="big">{score} correct</div>
                        <p>
                            {score >= 15
                                ? 'The gate swings wide open for a true Syllable Sword master!'
                                : "Time's up! Look at the last letter of each syllable and try to beat your score."}
                        </p>
                        <GameXpNote />
                        <div className="btns">
                            <button type="button" className="btn primary" onClick={handleStart}>
                                Try again
                            </button>
                            <button type="button" className="btn" onClick={onOtherGame}>
                                Next challenge ➜
                            </button>
                        </div>
                    </div>
                )}
            </div>
        </section>
    );
}

/* =========================================================================
   GAME 3: Monster Spotter (Sentence Shield)
   ========================================================================= */
const FORMS = {
    runon: (a, b) => `${a} ${b.toLowerCase()}.`,
    splice: (a, b) => `${a}, ${b.toLowerCase()}.`,
    stop: (a, b) => `${a}. ${b[0].toUpperCase() + b.slice(1)}.`,
    conj: (a, b, c) => `${a}, ${c} ${b.toLowerCase()}.`,
    semi: (a, b) => `${a}; ${b.toLowerCase()}.`,
};

function SpotterGame({ addXP, saveBest, onOtherGame }) {
    const [items, setItems] = useState(() => {
        const kinds = shuffle(['runon', 'runon', 'runon', 'splice', 'splice', 'splice', 'correct', 'correct']);
        return shuffle(SPOT_ITEMS).slice(0, 8).map((s, k) => ({
            a: s[0],
            b: s[1],
            c: s[2],
            kind: kinds[k],
        }));
    });
    const [idx, setIdx] = useState(0);
    const [step, setStep] = useState(1); // 1 = name monster, 2 = pick repair
    const [pickedKind, setPickedKind] = useState(null);
    const [repairOpts, setRepairOpts] = useState([]);
    const [pickedRepair, setPickedRepair] = useState(null);
    const [feedback, setFeedback] = useState(null);
    const [score, setScore] = useState(0);
    const [done, setDone] = useState(false);
    const [shake, setShake] = useState(false);

    const currentItem = items[idx];
    const sentenceText = currentItem
        ? currentItem.kind === 'runon'
            ? FORMS.runon(currentItem.a, currentItem.b)
            : currentItem.kind === 'splice'
            ? FORMS.splice(currentItem.a, currentItem.b)
            : FORMS.conj(currentItem.a, currentItem.b, currentItem.c)
        : '';

    const handlePickKind = (kind) => {
        if (pickedKind) return;
        setPickedKind(kind);
        const isRight = kind === currentItem.kind;
        const label = {
            correct: 'correct (it has a comma AND a joining word)',
            runon: 'a run-on monster (two complete thoughts with no punctuation)',
            splice: 'a comma splice monster (a comma alone cannot join sentences)',
        }[currentItem.kind];

        if (isRight) {
            addXP(10);
            setFeedback({ good: true, msg: `Spotted! It is ${label}. +10 XP` });
        } else {
            setShake(true);
            setTimeout(() => setShake(false), 350);
            setFeedback({ good: false, msg: `Look again. It is ${label}.` });
        }

        if (currentItem.kind === 'correct') {
            if (isRight) setScore((s) => s + 1);
            setStep('next');
        } else {
            // Setup repair options
            const goodFormKey = shuffle(['stop', 'conj', 'semi'])[0];
            const opts = shuffle([
                { t: FORMS[goodFormKey](currentItem.a, currentItem.b, currentItem.c), ok: true, form: goodFormKey },
                { t: FORMS.splice(currentItem.a, currentItem.b), ok: false, why: "That's still a comma splice!" },
                { t: FORMS.runon(currentItem.a, currentItem.b), ok: false, why: "That's still a run-on sentence!" },
            ]);
            setRepairOpts(opts);
            setStep(2);
        }
    };

    const handlePickRepair = (opt) => {
        if (pickedRepair) return;
        setPickedRepair(opt);
        if (opt.ok) {
            addXP(10);
            if (pickedKind === currentItem.kind) setScore((s) => s + 1);
            setFeedback({
                good: true,
                msg: `Repaired! You fixed it with ${
                    { stop: 'a full stop', conj: 'a comma + coordinating conjunction', semi: 'a semicolon' }[opt.form]
                }. +10 XP`,
            });
        } else {
            setFeedback({
                good: false,
                msg: `${opt.why} The green highlighted one is the correct repair.`,
            });
        }
        setStep('next');
    };

    const handleNext = () => {
        if (idx < items.length - 1) {
            setIdx((n) => n + 1);
            setStep(1);
            setPickedKind(null);
            setRepairOpts([]);
            setPickedRepair(null);
            setFeedback(null);
        } else {
            saveBest('spotter', score);
            setDone(true);
        }
    };

    const handleRestart = () => {
        const kinds = shuffle(['runon', 'runon', 'runon', 'splice', 'splice', 'splice', 'correct', 'correct']);
        setItems(
            shuffle(SPOT_ITEMS).slice(0, 8).map((s, k) => ({
                a: s[0],
                b: s[1],
                c: s[2],
                kind: kinds[k],
            }))
        );
        setIdx(0);
        setStep(1);
        setPickedKind(null);
        setRepairOpts([]);
        setPickedRepair(null);
        setFeedback(null);
        setScore(0);
        setDone(false);
    };

    return (
        <section className="panel">
            <div className="panel-head">
                <h2>Monster Spotter</h2>
                <div className="meta">
                    <span className="pill">Sentence {idx + 1} of {items.length}</span>
                    <span className="pill">{score} defeated</span>
                </div>
            </div>
            <p className="how">
                Read the sentence. Is it correct, a Run-on Monster, or a Comma Splice Monster? If it is a monster, choose the repair that fixes it!
            </p>

            <div className="stage">
                {!done ? (
                    <>
                        <div className="step">Step 1 · Name It</div>
                        <div className={`sentence${shake ? ' shake' : ''}`}>{sentenceText}</div>

                        <div className="btns">
                            <button
                                type="button"
                                className="btn ok"
                                disabled={Boolean(pickedKind)}
                                onClick={() => handlePickKind('correct')}
                            >
                                Correct ✓
                            </button>
                            <button
                                type="button"
                                className="btn"
                                disabled={Boolean(pickedKind)}
                                onClick={() => handlePickKind('runon')}
                            >
                                Run-on Monster
                            </button>
                            <button
                                type="button"
                                className="btn"
                                disabled={Boolean(pickedKind)}
                                onClick={() => handlePickKind('splice')}
                            >
                                Comma Splice Monster
                            </button>
                        </div>

                        {feedback && (
                            <div className={`feedback ${feedback.good ? 'good' : 'bad'}`}>
                                {feedback.msg}
                            </div>
                        )}

                        {step === 2 && (
                            <div style={{ marginTop: 10, width: '100%', maxWidth: 560 }}>
                                <div className="step" style={{ textAlign: 'center', marginBottom: 8 }}>
                                    Step 2 · Choose the repair
                                </div>
                                <div className="options">
                                    {repairOpts.map((opt, oIdx) => {
                                        let cls = 'opt';
                                        if (pickedRepair) {
                                            if (opt.ok) cls += ' right';
                                            else if (opt === pickedRepair) cls += ' wrongpick';
                                        }
                                        return (
                                            <button
                                                key={oIdx}
                                                type="button"
                                                className={cls}
                                                disabled={Boolean(pickedRepair)}
                                                onClick={() => handlePickRepair(opt)}
                                            >
                                                {opt.t}
                                            </button>
                                        );
                                    })}
                                </div>
                            </div>
                        )}

                        {step === 'next' && (
                            <div className="btns" style={{ marginTop: 14 }}>
                                <button type="button" className="btn primary" onClick={handleNext}>
                                    {idx === items.length - 1 ? 'See results' : 'Next sentence ➜'}
                                </button>
                            </div>
                        )}
                    </>
                ) : (
                    <div className="done">
                        <div className="big">{score} / {items.length}</div>
                        <p>
                            {score >= 6
                                ? 'Sentence Shield raised! No grammar monster gets past you.'
                                : 'Keep your shield up. Find the two thoughts first, then check what sits between them.'}
                        </p>
                        <GameXpNote />
                        <div className="btns">
                            <button type="button" className="btn primary" onClick={handleRestart}>
                                Play again
                            </button>
                            <button type="button" className="btn" onClick={onOtherGame}>
                                Next challenge ➜
                            </button>
                        </div>
                    </div>
                )}
            </div>
        </section>
    );
}

/* =========================================================================
   GAME 4: Fix the Scrambled Message
   ========================================================================= */
function RepairGame({ addXP, saveBest, onOtherGame }) {
    const [msgs, setMsgs] = useState(() => [MESSAGES[0], ...shuffle(MESSAGES.slice(1)).slice(0, 5)]);
    const [msgIdx, setMsgIdx] = useState(0);
    const [slots, setSlots] = useState(() => MESSAGES[0].words.map(() => ''));
    const [tries, setTries] = useState(0);
    const [checked, setChecked] = useState(false);
    const [slotResults, setSlotResults] = useState({});
    const [feedback, setFeedback] = useState(null);
    const [score, setScore] = useState(0);
    const [done, setDone] = useState(false);
    const [shake, setShake] = useState(false);

    const currentMsg = msgs[msgIdx];

    const cycleSlot = (k) => {
        if (checked && feedback?.good) return;
        setSlots((prev) => {
            const next = [...prev];
            const curIdx = MARKS.indexOf(next[k] || '');
            next[k] = MARKS[(curIdx + 1) % MARKS.length];
            return next;
        });
    };

    const handleClear = () => {
        setSlots(currentMsg.words.map(() => ''));
        setSlotResults({});
        setFeedback(null);
    };

    const handleCheck = () => {
        const nextTries = tries + 1;
        setTries(nextTries);

        const newResults = {};
        let wrongCount = 0;

        slots.forEach((val, k) => {
            const allowed = currentMsg.allow[k];
            const isOk = allowed ? allowed.includes(val) : val === '';
            if (!isOk) {
                newResults[k] = 'bad';
                wrongCount++;
            } else if (val) {
                newResults[k] = 'goodslot';
            }
        });

        setSlotResults(newResults);
        setChecked(true);

        if (wrongCount === 0) {
            const pts = nextTries === 1 ? 15 : 8;
            addXP(pts);
            setScore((s) => s + 1);
            setFeedback({
                good: true,
                msg: `Message clear! Every thought has its boundary. +${pts} XP`,
            });
        } else {
            setShake(true);
            setTimeout(() => setShake(false), 350);
            if (nextTries >= 3) {
                setFeedback({
                    good: false,
                    msg: `One way to fix it: "${currentMsg.model}"`,
                });
            } else {
                setFeedback({
                    good: false,
                    msg: `${wrongCount} box${wrongCount === 1 ? '' : 'es'} need fixing (shown in red). Find where each thought ends.${
                        nextTries === 2 ? ' One more try before the solution is shown.' : ''
                    }`,
                });
            }
        }
    };

    const handleNext = () => {
        if (msgIdx < msgs.length - 1) {
            const nextIdx = msgIdx + 1;
            setMsgIdx(nextIdx);
            setSlots(msgs[nextIdx].words.map(() => ''));
            setTries(0);
            setChecked(false);
            setSlotResults({});
            setFeedback(null);
        } else {
            saveBest('repair', score);
            setDone(true);
        }
    };

    const handleRestart = () => {
        const newMsgs = [MESSAGES[0], ...shuffle(MESSAGES.slice(1)).slice(0, 5)];
        setMsgs(newMsgs);
        setMsgIdx(0);
        setSlots(newMsgs[0].words.map(() => ''));
        setTries(0);
        setChecked(false);
        setSlotResults({});
        setFeedback(null);
        setScore(0);
        setDone(false);
    };

    return (
        <section className="panel">
            <div className="panel-head">
                <h2>Fix the Scrambled Message</h2>
                <div className="meta">
                    <span className="pill">Message {msgIdx + 1} of {msgs.length}</span>
                    <span className="pill">{score} fixed</span>
                </div>
            </div>
            <p className="how">
                The monster stripped out every boundary. Tap a box between words to cycle through punctuation:
                full stop (.), comma (,), exclamation (!), semicolon (;), question mark (?), then empty again.
            </p>

            <div className="stage">
                {!done ? (
                    <>
                        <div className="small">Scrambled warning #{msgIdx + 1}</div>

                        <div className={`msg${shake ? ' shake' : ''}`}>
                            {currentMsg.words.map((w, k) => {
                                const isCapital = k === 0 || ['.', '!', '?'].includes(slots[k - 1]);
                                const isProper = /^[A-Z]/.test(w);
                                const displayWord = isCapital && !isProper ? w[0].toUpperCase() + w.slice(1) : w;
                                const markVal = slots[k] || '';
                                let slotCls = `slot${markVal ? ' filled' : ''}`;
                                if (slotResults[k]) slotCls += ` ${slotResults[k]}`;

                                return (
                                    <span key={k} style={{ display: 'inline-flex', alignItems: 'center', gap: 3 }}>
                                        <span>{displayWord}</span>
                                        <button
                                            type="button"
                                            className={slotCls}
                                            disabled={checked && feedback?.good}
                                            onClick={() => cycleSlot(k)}
                                            aria-label={`Punctuation after ${w}`}
                                        >
                                            {markVal}
                                        </button>
                                    </span>
                                );
                            })}
                        </div>

                        {feedback && (
                            <div className={`feedback ${feedback.good ? 'good' : 'bad'}`}>
                                {feedback.msg}
                            </div>
                        )}

                        <div className="btns">
                            {(!checked || !feedback?.good) && tries < 3 ? (
                                <>
                                    <button type="button" className="btn" onClick={handleClear}>
                                        Clear all
                                    </button>
                                    <button type="button" className="btn primary" onClick={handleCheck}>
                                        Check the message
                                    </button>
                                </>
                            ) : (
                                <button type="button" className="btn primary" onClick={handleNext}>
                                    {msgIdx === msgs.length - 1 ? 'See results' : 'Next message ➜'}
                                </button>
                            )}
                        </div>
                    </>
                ) : (
                    <div className="done">
                        <div className="big">{score} / {msgs.length}</div>
                        <p>
                            {score >= 5
                                ? 'Every warning is clear. The camp is safe, Master Storyteller!'
                                : 'Some messages are still tangled. Find each complete thought, then put up a boundary.'}
                        </p>
                        <GameXpNote />
                        <div className="btns">
                            <button type="button" className="btn primary" onClick={handleRestart}>
                                Play again
                            </button>
                            <button type="button" className="btn" onClick={onOtherGame}>
                                First challenge ➜
                            </button>
                        </div>
                    </div>
                )}
            </div>
        </section>
    );
}

