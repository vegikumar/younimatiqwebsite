import { useState } from 'react';
import { GameXpNote, useGameXp } from './GameXp';
import './styles/rebuild-the-base.css';

const ART = [
    ['....ff......', 10],
    ['.....rr.....', 9],
    ['....rnnr....', 8],
    ['...rnnnnr...', 7],
    ['..rrrrrrrr..', 6],
    ['.sssssssssst', 5],
    ['.swnnwwnnws.', 4],
    ['.swwwddwwws.', 3],
    ['tswnnddnnws.', 2],
    ['.ssssssssss.', 1],
    ['gggggggggggg', 0],
];
const QUESTIONS = [
    { k: 'NAMING WORDS', q: 'Which word is a <em>noun</em> (naming word)?', a: ['torch', 'run', 'happy'], c: 0, why: 'A torch is a thing, so it is a naming word.' },
    { k: 'CAPITAL LETTERS', q: 'Which name is written the right way?', a: ['grace', 'Grace', 'gRACE'], c: 1, why: 'A name starts with a Capital letter: Grace.' },
    { k: 'NEW WORDS', q: 'What does <em>rebuild</em> mean?', a: ['to build again', 'to run away', 'to go to sleep'], c: 0, why: 'Rebuild means to build something again after it breaks.' },
    { k: 'THE STORY', q: 'Who drew a smart plan on a map?', a: ['Evan', 'Tobi', 'Grace'], c: 2, why: 'Clever Grace drew the plan.' },
    { k: 'DESCRIBING WORDS', q: 'Which word tells us what the wall is like?<br>Tobi built a <em>___</em> wall.', a: ['tall', 'Tobi', 'wall'], c: 0, why: 'Tall is a describing word.' },
    { k: 'THE STORY', q: 'What did Tobi put all around the base?', a: ['cakes', 'torches', 'flowers'], c: 1, why: 'Tobi put torches around, because light keeps mobs away.' },
    { k: 'CAPITAL LETTERS', q: 'Fill the gap:<br><em>___</em> made new blocks at the crafting table.', a: ['evan', 'Evan'], c: 1, why: 'Evan is a name, so it needs a Capital E.' },
    { k: 'NEW WORDS', q: 'Friends who work together are a…', a: ['mob', 'torch', 'team'], c: 2, why: 'A team is friends who work together.' },
    { k: 'ORDER WORDS', q: 'Which word fits the last part of the story?<br><em>___</em>, Candace added a glass roof.', a: ['First', 'Finally'], c: 1, why: 'Finally tells us it happened at the end.' },
    { k: 'FEELINGS', q: 'The new base was bigger and stronger. How did the friends feel?', a: ['proud', 'sleepy', 'angry'], c: 0, why: 'They felt proud because they did something good.' },
];

const CELLS = ART.flatMap(([row, layer]) => [...row].map((ch) => ({ ch: ch === '.' ? 'e' : ch, layer })));

function beep(f1, f2, dur) {
    try {
        const ac = beep.ac || (beep.ac = new (window.AudioContext || window.webkitAudioContext)());
        const o = ac.createOscillator();
        const g = ac.createGain();
        o.type = 'square';
        o.frequency.setValueAtTime(f1, ac.currentTime);
        o.frequency.linearRampToValueAtTime(f2, ac.currentTime + dur);
        g.gain.setValueAtTime(0.06, ac.currentTime);
        g.gain.exponentialRampToValueAtTime(0.001, ac.currentTime + dur);
        o.connect(g).connect(ac.destination);
        o.start();
        o.stop(ac.currentTime + dur);
    } catch {
        /* sound is optional */
    }
}

export default function RebuildTheBase() {
    const { claim } = useGameXp();
    const [phase, setPhase] = useState('start');
    const [qi, setQi] = useState(0);
    const [built, setBuilt] = useState(0);
    const [tries, setTries] = useState(0);
    const [wrong, setWrong] = useState([]);
    const [right, setRight] = useState(false);
    const question = QUESTIONS[qi];

    const begin = () => {
        setQi(0);
        setBuilt(0);
        setTries(0);
        setWrong([]);
        setRight(false);
        setPhase('ask');
    };

    const pick = (i) => {
        if (right || wrong.includes(i)) return;
        if (i === question.c) {
            setRight(true);
            setBuilt((n) => n + 1);
            beep(523, 1046, 0.18);
            return;
        }
        setTries((n) => n + 1);
        setWrong((list) => [...list, i]);
        beep(220, 140, 0.2);
    };

    return (
        <div className="ym-rb">
            <div className="wrap">
                <header>
                    <h1>REBUILD THE BASE</h1>
                    <div className="count" aria-live="polite">Blocks: <b>{built}</b> / 10</div>
                </header>
                <div className="game">
                    <main className="panel">
                        {phase === 'start' && (
                            <>
                                <p className="kind">MEGA FRIENDSHIP BASE</p>
                                <p className="q">Oh no! The creeper went BOOM and broke the base.</p>
                                <p className="fb" style={{ color: 'var(--ink-soft)' }}>Answer 10 questions. Every right answer puts a new row of blocks on the base. Can you build it all, up to the flag?</p>
                                <button type="button" className="btn" onClick={begin}>START BUILDING</button>
                            </>
                        )}
                        {phase === 'ask' && (
                            <>
                                <p className="kind">QUESTION {qi + 1} · {question.k}</p>
                                <p className="q" dangerouslySetInnerHTML={{ __html: question.q }} />
                                <div className="answers">
                                    {question.a.map((text, i) => (
                                        <button
                                            key={text}
                                            type="button"
                                            className={`ans${right && i === question.c ? ' right' : ''}${wrong.includes(i) ? ' wrong' : ''}`}
                                            onClick={() => pick(i)}
                                        >
                                            {text}
                                        </button>
                                    ))}
                                </div>
                                <p className={`fb${right ? ' good' : wrong.length ? ' bad' : ''}`} aria-live="polite">
                                    {right ? `Yes! ${question.why}` : wrong.length ? 'Not this one. Try again!' : ''}
                                </p>
                                {right ? (
                                    <button
                                        type="button"
                                        className="btn"
                                        onClick={() => {
                                            if (qi < QUESTIONS.length - 1) {
                                                setQi((n) => n + 1);
                                                setWrong([]);
                                                setRight(false);
                                            } else {
                                                setPhase('done');
                                                beep(523, 1046, 0.45);
                                                claim(built);
                                            }
                                        }}
                                    >
                                        {qi === QUESTIONS.length - 1 ? 'SEE MY BASE ➜' : 'NEXT ➜'}
                                    </button>
                                ) : null}
                            </>
                        )}
                        {phase === 'done' && (
                            <>
                                <p className="big">YOU REBUILT THE BASE!</p>
                                <GameXpNote />
                                <p className="q">Tyler, Evan, Grace, Tobi and Candace say <em>thank you</em>! You are a great team player.</p>
                                <p className="fb good">{tries === 0 ? 'Perfect! Every answer right the first time.' : 'You never gave up, just like Tyler!'}</p>
                                <button type="button" className="btn" onClick={() => setPhase('start')}>BUILD AGAIN</button>
                            </>
                        )}
                    </main>
                    <aside className="scene" aria-label="The Mega Friendship Base">
                        <p>
                            {phase === 'done'
                                ? 'The Mega Friendship Base is ready!'
                                : built === 0
                                    ? 'The creeper broke the base! Answer questions to rebuild it.'
                                    : `Keep going! ${10 - built} rows to build.`}
                        </p>
                        <div className="base">
                            {CELLS.map((cell, i) => (
                                <div key={i} className={`cell ${cell.ch}${cell.layer === 0 || cell.layer <= built ? ' on' : ''}`} />
                            ))}
                        </div>
                    </aside>
                </div>
            </div>
        </div>
    );
}
