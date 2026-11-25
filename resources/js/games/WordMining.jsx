import { useMemo, useState } from 'react';
import './styles/word-mining.css';
import { GameXpNote, useGameXp } from './GameXp';

const LEVELS = [
    { w: 'HOUSE', img: 'c4', s: 'Tyler and his mom move into a new ___.' },
    { w: 'GAME', img: 'c7', s: 'Tyler clicks “Join” and jumps into the ___.' },
    { w: 'FOREST', img: 'c35', s: 'The friends sit by a campfire in the ___.' },
    { w: 'BOOM', img: 'c37', s: 'The green monster goes ___! Run!' },
    { w: 'TORCH', img: 'c45', s: 'Each friend holds a glowing ___ in the dark room.' },
    { w: 'DRAGON', img: 'c50', s: 'A huge black ___ flies in the sky.' },
    { w: 'FRIENDS', img: 'c69', s: 'Tyler finds out his game ___ go to his new school!' },
];
const ABC = 'ABCDEFGHIJKLMNOPRSTUVWY';

function shuffle(list) {
    const a = [...list];
    for (let i = a.length - 1; i > 0; i -= 1) {
        const j = Math.floor(Math.random() * (i + 1));
        [a[i], a[j]] = [a[j], a[i]];
    }
    return a;
}

function makeCells(word) {
    const cols = 5;
    const rows = word.length > 5 ? 4 : 3;
    const n = cols * rows;
    const fillers = ABC.split('').filter((c) => !word.includes(c));
    const cells = word.split('');
    while (cells.length < n) cells.push(fillers[Math.floor(Math.random() * fillers.length)]);
    return { cols, rows, cells: shuffle(cells).map((letter, index) => ({ id: index, letter, gone: false, crack: false, hint: false })) };
}

function loadProgress() {
    try {
        return JSON.parse(localStorage.getItem('wordMining') || '{}');
    } catch {
        return {};
    }
}

export default function WordMining() {
    const { claim } = useGameXp();
    const saved = useMemo(loadProgress, []);
    const [level, setLevel] = useState(Math.min(saved.done?.length || 0, LEVELS.length - 1));
    const [pos, setPos] = useState(0);
    const [gems, setGems] = useState(saved.gems || 0);
    const [done, setDone] = useState(saved.done || []);
    const [board, setBoard] = useState(() => makeCells(LEVELS[Math.min(saved.done?.length || 0, LEVELS.length - 1)].w));
    const [message, setMessage] = useState('Mine the letters in order! ⛏️');
    const [messageKind, setMessageKind] = useState('');
    const [shaking, setShaking] = useState(null);
    const [ghost, setGhost] = useState(null);
    const [startOpen, setStartOpen] = useState(true);
    const [winOpen, setWinOpen] = useState(false);
    const [finalOpen, setFinalOpen] = useState(false);

    const current = LEVELS[level];
    const blank = pos === 0
        ? '_ '.repeat(current.w.length).trim()
        : `${current.w.slice(0, pos).split('').join(' ')}${pos < current.w.length ? ` ${'_ '.repeat(current.w.length - pos).trim()}` : ''}`;

    const persist = (nextGems, nextDone) => {
        try {
            localStorage.setItem('wordMining', JSON.stringify({ gems: nextGems, done: nextDone }));
        } catch {
            /* storage can be blocked */
        }
    };

    const load = (index) => {
        setLevel(index);
        setPos(0);
        setBoard(makeCells(LEVELS[index].w));
        setMessage('Mine the letters in order! ⛏️');
        setMessageKind('');
        setGhost(null);
        setWinOpen(false);
        setFinalOpen(false);
    };

    const dig = (cell) => {
        if (cell.gone || winOpen) return;
        const need = current.w[pos];
        setBoard((rows) => ({ ...rows, cells: rows.cells.map((item) => ({ ...item, hint: false })) }));
        if (cell.letter === need) {
            setBoard((rows) => ({ ...rows, cells: rows.cells.map((item) => (item.id === cell.id ? { ...item, crack: true } : item)) }));
            window.setTimeout(() => {
                setBoard((rows) => ({ ...rows, cells: rows.cells.map((item) => (item.id === cell.id ? { ...item, crack: false, gone: true } : item)) }));
                setPos((currentPos) => {
                    const next = currentPos + 1;
                    if (next < current.w.length) {
                        setMessage(`⛏️ ${need}! Now find ${current.w[next]}…`);
                        setMessageKind('good');
                    } else {
                        const nextDone = done.includes(level) ? done : [...done, level];
                        const nextGems = gems + 1;
                        setDone(nextDone);
                        setGems(nextGems);
                        persist(nextGems, nextDone);
                        setMessage(`💎 You spelled ${current.w}!`);
                        setMessageKind('good');
                        window.setTimeout(() => setWinOpen(true), 500);
                    }
                    return next;
                });
            }, 140);
            return;
        }
        setShaking(cell.id);
        window.setTimeout(() => setShaking(null), 300);
        setMessage(`Not ${cell.letter}. Look for the letter ${need}! 🔍`);
        setMessageKind('try');
    };

    const hint = () => {
        const need = current.w[pos];
        if (!need) return;
        setBoard((rows) => {
            let marked = false;
            return {
                ...rows,
                cells: rows.cells.map((item) => {
                    const hint = !marked && !item.gone && item.letter === need;
                    if (hint) marked = true;
                    return { ...item, hint };
                }),
            };
        });
        setGhost(pos);
        setMessage(`💡 Find the glowing block: ${need}`);
        setMessageKind('good');
    };

    return (
        <div className="ym-wm">
            <div className="wrap">
                <header>
                    <h1>⛏️ Word Mining</h1>
                    <div className="hud">
                        <span className="pill">LEVEL {level + 1}/{LEVELS.length}</span>
                        <span className="pill">💎 {gems}</span>
                    </div>
                </header>
                <div className="world">
                    <div className="top">
                        <div className="pic">
                            <img src={`/games/story/${current.img}.jpg`} alt="Picture from Tyler's story" />
                            <span className="lvl">LEVEL {level + 1}</span>
                        </div>
                        <div className="clue">
                            <div className="lab"><span>READ THE CLUE</span></div>
                            <p className="sentence">{current.s.split('___')[0]}<span className="blank">{winOpen || pos === current.w.length ? current.w : blank}</span>{current.s.split('___')[1]}</p>
                            <div className="tools">
                                <button type="button" className="btn gold" onClick={hint}>💡 HINT</button>
                                <button type="button" className="btn" onClick={() => setStartOpen(true)}>❓ HOW</button>
                            </div>
                        </div>
                    </div>
                    <div className="hotbar">
                        {current.w.split('').map((letter, index) => (
                            <div key={letter + index} className={`slot${index === pos ? ' next' : ''}${index < pos ? ' fill' : ''}${ghost === index && index === pos ? ' ghost' : ''}`}>
                                {index < pos || ghost === index ? letter : ''}
                            </div>
                        ))}
                    </div>
                    <div className={`msg ${messageKind}`}>{message}</div>
                    <div className="mine" style={{ '--cols': board.cols }}>
                        {board.cells.map((cell) => {
                            const row = Math.floor(cell.id / board.cols);
                            const tone = row === 0 ? 'grass' : row === board.rows - 1 ? 'deep' : cell.id % 3 === 0 ? 'dirt' : '';
                            return (
                                <button
                                    key={cell.id}
                                    type="button"
                                    className={`block ${tone}${cell.gone ? ' gone' : ''}${cell.crack ? ' crack' : ''}${cell.hint ? ' hint' : ''}${shaking === cell.id ? ' shake' : ''}`}
                                    aria-label={`Block ${cell.letter}`}
                                    onClick={() => dig(cell)}
                                >
                                    <span>{cell.letter}</span>
                                </button>
                            );
                        })}
                    </div>
                    <div className="levels">
                        {LEVELS.map((item, index) => (
                            <button key={item.w} type="button" className={`lv${index === level ? ' cur' : ''}${done.includes(index) ? ' done' : ''}`} aria-label={`Level ${index + 1}`} onClick={() => load(index)}>{index + 1}</button>
                        ))}
                    </div>
                </div>
            </div>
            <div className={`overlay${startOpen ? ' open' : ''}`}>
                <div className="card">
                    <h2>WORD MINING<br />Tyler's Story</h2>
                    <p>Tyler moves to a new town… and goes on a big adventure with his friends! ⛏️</p>
                    <ol className="howto">
                        <li>👀 Look at the picture.</li>
                        <li>📖 Read the clue. What word is missing?</li>
                        <li>⛏️ Tap the blocks to mine the letters <b>in order</b>.</li>
                        <li>💎 Spell the word to win a diamond!</li>
                    </ol>
                    <button type="button" className="btn go" onClick={() => setStartOpen(false)}>▶ START MINING</button>
                </div>
            </div>
            <div className={`overlay${winOpen ? ' open' : ''}`}>
                <div className="card">
                    <h2>YOU MINED IT!</h2>
                    <div className="big-word">{current.w}</div>
                    <p>{current.s.replace('___', current.w.toLowerCase())}</p>
                    <p>+1 💎</p>
                    <button type="button" className="btn go" onClick={() => {
                        if (level < LEVELS.length - 1) load(level + 1);
                        else {
                            setFinalOpen(true);
                            claim(Math.min(gems, LEVELS.length));
                        }
                        setWinOpen(false);
                    }}>{level < LEVELS.length - 1 ? 'NEXT LEVEL ▶' : 'OPEN THE CHEST 🏆'}</button>
                </div>
            </div>
            <div className={`overlay${finalOpen ? ' open' : ''}`}>
                <div className="card">
                    <h2>🏆 ALL WORDS MINED!</h2>
                    <p>You found every word in Tyler's story!</p>
                    <GameXpNote />
                    <div className="chest">{LEVELS.map((item) => <span key={item.w} className="w">{item.w}</span>)}</div>
                    <p>Can you use each word in a sentence? 🗣️</p>
                    <button type="button" className="btn go" onClick={() => { setDone([]); setGems(0); persist(0, []); load(0); }}>↺ PLAY AGAIN</button>
                </div>
            </div>
        </div>
    );
}
