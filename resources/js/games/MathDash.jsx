import { useMemo, useState } from 'react';
import { GameXpNote, useGameXp } from './GameXp';
import './styles/math-dash.css';

const TOTAL = 10;
const LEVELS = [
    { id: 1, label: 'Grade 1: Add to 10' },
    { id: 2, label: 'Grade 2: Add & Take away to 20' },
    { id: 3, label: 'Grade 3: Times tables' },
];

function rnd(a, b) {
    return a + Math.floor(Math.random() * (b - a + 1));
}

function makeQuestion(level) {
    let a;
    let b;
    let answer;
    let text;

    if (level === 1) {
        a = rnd(0, 9);
        b = rnd(0, 10 - a);
        answer = a + b;
        text = `${a} + ${b}`;
    } else if (level === 2) {
        if (Math.random() < 0.5) {
            a = rnd(2, 15);
            b = rnd(1, 20 - a);
            answer = a + b;
            text = `${a} + ${b}`;
        } else {
            a = rnd(5, 20);
            b = rnd(1, a);
            answer = a - b;
            text = `${a} − ${b}`;
        }
    } else {
        a = rnd(2, 10);
        b = rnd(2, 10);
        answer = a * b;
        text = `${a} × ${b}`;
    }

    const opts = [answer];
    while (opts.length < 4) {
        const o = answer + rnd(-5, 5);
        if (o >= 0 && !opts.includes(o)) opts.push(o);
    }
    opts.sort(() => Math.random() - 0.5);

    return { text, answer, opts };
}

export default function MathDash() {
    const { claim } = useGameXp();
    const [screen, setScreen] = useState('start');
    const [level, setLevel] = useState(1);
    const [n, setN] = useState(0);
    const [score, setScore] = useState(0);
    const [locked, setLocked] = useState(false);
    const [picked, setPicked] = useState(null);
    const [question, setQuestion] = useState(() => makeQuestion(1));
    const [popKey, setPopKey] = useState(0);

    const stars = useMemo(() => {
        const filled = score >= 9 ? 3 : score >= 6 ? 2 : 1;
        return '★★★'.slice(0, filled) + '☆☆☆'.slice(0, 3 - filled);
    }, [score]);

    const begin = () => {
        setN(0);
        setScore(0);
        setLocked(false);
        setPicked(null);
        setQuestion(makeQuestion(level));
        setPopKey((k) => k + 1);
        setScreen('game');
    };

    const pick = (value) => {
        if (locked) return;
        setLocked(true);
        setPicked(value);
        const correct = value === question.answer;
        const nextScore = correct ? score + 1 : score;
        if (correct) setScore(nextScore);

        window.setTimeout(() => {
            const next = n + 1;
            if (next >= TOTAL) {
                setScore(nextScore);
                setScreen('end');
                claim(nextScore);
                return;
            }
            setN(next);
            setPicked(null);
            setLocked(false);
            setQuestion(makeQuestion(level));
            setPopKey((k) => k + 1);
        }, 700);
    };

    return (
        <div className="ym-md">
            <div className="wrap">
                {screen === 'start' ? (
                    <div className="card">
                        <h1>
                            Math <span>Dash</span>
                        </h1>
                        <p className="sub">Answer 10 quick sums. Can you get them all?</p>
                        <div className="levels">
                            {LEVELS.map((item) => (
                                <button
                                    key={item.id}
                                    type="button"
                                    className={`lvl${level === item.id ? ' on' : ''}`}
                                    onClick={() => setLevel(item.id)}
                                >
                                    {item.label}
                                </button>
                            ))}
                        </div>
                        <button type="button" className="btn" onClick={begin}>
                            Start!
                        </button>
                    </div>
                ) : null}

                {screen === 'game' ? (
                    <div className="card">
                        <div className="meta">
                            <span>
                                {n + 1} / {TOTAL}
                            </span>
                            <span>Score {score}</span>
                        </div>
                        <div className="bar">
                            <i style={{ width: `${(n / TOTAL) * 100}%` }} />
                        </div>
                        <div key={popKey} className="q pop">
                            {question.text}
                        </div>
                        <div className="opts">
                            {question.opts.map((option) => {
                                let cls = 'opt';
                                if (picked !== null) {
                                    if (option === question.answer) cls += ' right';
                                    else if (option === picked) cls += ' wrong';
                                }
                                return (
                                    <button key={`${popKey}-${option}`} type="button" className={cls} onClick={() => pick(option)}>
                                        {option}
                                    </button>
                                );
                            })}
                        </div>
                    </div>
                ) : null}

                {screen === 'end' ? (
                    <div className="card">
                        <h1>
                            Great <span>job!</span>
                        </h1>
                        <div className="stars">{stars}</div>
                        <p className="sub">
                            You got {score} out of {TOTAL} right!
                        </p>
                        <GameXpNote />
                        <button
                            type="button"
                            className="btn"
                            onClick={() => {
                                setScreen('start');
                                setPicked(null);
                                setLocked(false);
                            }}
                        >
                            Play again
                        </button>
                    </div>
                ) : null}
            </div>
        </div>
    );
}
