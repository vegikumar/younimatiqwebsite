import { useEffect, useState } from 'react';
import './styles/story-treasure-map.css';
import { GameXpNote, useGameXp } from './GameXp';

const STOPS = [
    { img: 'c4', title: 'The New House', who: [['👦👩', 'A boy and his mom', 1], ['🐉', 'A dragon', 0], ['👮', 'A police officer', 0]], where: [['🏫', 'A school', 0], ['🏠', 'A new house with boxes', 1], ['🏖️', 'A beach', 0]], next: [['🎮', 'He plays a game with friends'], ['😴', 'He goes to sleep'], ['🍰', 'He eats cake']], answer: "He plays a game with his friends! Let's go to stop 2!" },
    { img: 'c7', title: 'Into the Game', who: [['🐶', 'A dog', 0], ['👵', 'A grandma', 0], ['🧑', 'The boy', 1]], where: [['🧱', 'A world made of blocks', 1], ['🚗', 'In a car', 0], ['🌊', 'Under the sea', 0]], next: [['👫', 'He meets his friends'], ['🛒', 'He goes shopping'], ['📚', 'He reads a book']], answer: 'He meets his friends in the game world!' },
    { img: 'c35', title: 'The Campfire', who: [['🐟', 'Two fish', 0], ['👫', 'Two friends', 1], ['🤖', 'Two robots', 0]], where: [['🍳', 'A kitchen', 0], ['🚀', 'Space', 0], ['🌲🔥', 'A forest, by a fire', 1]], next: [['👻', 'Something scary comes'], ['🎂', 'A birthday party'], ['🌧️', 'It starts to rain']], answer: 'Something scary comes… look at stop 4!' },
    { img: 'c37', title: 'BOOM!', who: [['😱', 'Scared friends', 1], ['🐱', 'A cat', 0], ['🎅', 'Santa', 0]], where: [['🏊', 'A swimming pool', 0], ['🌲', 'The forest', 1], ['🏫', 'A classroom', 0]], next: [['🏃', 'They run away'], ['😴', 'They go to sleep'], ['🍕', 'They eat pizza']], answer: 'They run away — and keep going on their adventure!' },
    { img: 'c45', title: 'The Secret Room', who: [['🦁', 'A lion', 0], ['👑', 'A king', 0], ['🔥', 'Friends with torches', 1]], where: [['🕳️', 'A dark room under the ground', 1], ['☀️', 'A sunny park', 0], ['🏖️', 'A beach', 0]], next: [['🌀', 'They jump into a magic door'], ['🛁', 'They take a bath'], ['📺', 'They watch TV']], answer: 'They jump through a magic door to a new world!' },
    { img: 'c50', title: 'The Dragon', who: [['🐰', 'A bunny', 0], ['🐉', 'Friends and a big dragon', 1], ['🐠', 'A fish', 0]], where: [['🌌', 'The dark sky, on an island', 1], ['🏠', 'A house', 0], ['🌳', 'A garden', 0]], next: [['⚔️', 'They fight the dragon together'], ['🛏️', 'They go to bed'], ['🍦', 'They eat ice cream']], answer: 'They work together and beat the dragon! Now open the treasure! 🎁' },
];
const QS = [['who', '👤', 'WHO?', 'I can see…', 'Who can you see?'], ['where', '🌍', 'WHERE?', 'They are in…', 'Where are they?'], ['next', '⭐', 'WHAT NEXT?', 'I think…', 'What will happen next?']];
const PRAISE = ['Yes! Well done! 🌟', 'Super looking! ⭐', 'You got it! 🎉', 'Great job! 👏'];
const KEY = 'storyMapStars2';

function speak(text) {
    try {
        window.speechSynthesis.cancel();
        const utterance = new SpeechSynthesisUtterance(text);
        utterance.rate = 0.85;
        window.speechSynthesis.speak(utterance);
    } catch {
        /* read-aloud is optional */
    }
}

function loadGot() {
    try {
        return JSON.parse(localStorage.getItem(KEY) || '{}') || {};
    } catch {
        return {};
    }
}

export default function StoryTreasureMap() {
    const { claim } = useGameXp();
    const [got, setGot] = useState(loadGot);
    const [open, setOpen] = useState(null);
    const [treasure, setTreasure] = useState(false);
    const [shake, setShake] = useState(null);

    useEffect(() => {
        try {
            localStorage.setItem(KEY, JSON.stringify(got));
        } catch {
            /* storage can be blocked */
        }
    }, [got]);

    const count = Object.keys(got).length;
    const openTreasure = () => {
        setOpen(null);
        setTreasure(true);
        speak('You found the treasure! Hooray!');
        claim(count);
    };
    const stopDone = (i) => QS.every((q) => got[i + q[0]] !== undefined);
    const stop = open === null ? null : STOPS[open];

    const choose = (kind, index, choice) => {
        const key = `${open}${kind}`;
        if (kind === 'next') {
            setGot((prev) => ({ ...prev, [key]: index + 1 }));
            return;
        }
        if (choice[2]) {
            setGot((prev) => ({ ...prev, [key]: 1 }));
            return;
        }
        setShake(`${kind}-${index}`);
        window.setTimeout(() => setShake(null), 350);
    };

    return (
        <div className="ym-stm">
            <div className="map">
                <svg className="compass" viewBox="0 0 100 100" aria-hidden="true">
                    <circle cx="50" cy="50" r="44" fill="none" stroke="currentColor" strokeWidth="3" />
                    <polygon points="50,8 58,50 50,92 42,50" fill="currentColor" />
                    <polygon points="8,50 50,42 92,50 50,58" fill="currentColor" opacity=".5" />
                </svg>
                <header>
                    <h1>The Story Treasure Map</h1>
                    <p className="sub">Tap a picture. Look carefully. Then guess! 👀</p>
                    <div className="legend">
                        <span className="chip who">👤 WHO? "I can see…"</span>
                        <span className="chip where">🌍 WHERE? "They are in…"</span>
                        <span className="chip next">⭐ WHAT NEXT? "I think…"</span>
                    </div>
                    <div className="stars">⭐ {count} / 18 stars</div>
                </header>
                <div className="trail">
                    {STOPS.map((item, i) => (
                        <div key={item.title}>
                            {i > 0 ? (
                                <div className="conn">
                                    <svg viewBox="0 0 100 70" preserveAspectRatio="none">
                                        <path d={(i - 1) % 2 === 0 ? 'M25 0 C25 45, 75 25, 75 70' : 'M75 0 C75 45, 25 25, 25 70'} />
                                    </svg>
                                </div>
                            ) : null}
                            <div className={`stop${i % 2 ? ' right' : ''}${stopDone(i) ? ' done' : ''}`}>
                                <div className="thumb" role="button" tabIndex={0} aria-label={`Open stop ${i + 1}`} onClick={() => { setTreasure(false); setOpen(i); }} onKeyDown={(event) => { if (event.key === 'Enter') setOpen(i); }}>
                                    <img src={`/games/story/${item.img}.jpg`} alt={`Picture from the book: ${item.title}`} />
                                    <span className="tag">Tap me! 👆</span>
                                </div>
                                <div className="info">
                                    <span className="pin">{stopDone(i) ? '✓' : i + 1}</span>
                                    <h2>{item.title}</h2>
                                    <p>{stopDone(i) ? 'All 3 stars found! ⭐⭐⭐' : 'Who? Where? What next?'}</p>
                                    <button type="button" className="go" onClick={() => { setTreasure(false); setOpen(i); }}>{stopDone(i) ? 'Look again 👀' : "Let's guess! →"}</button>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
                <div className="treasure">
                    <div className="xmark">X</div>
                    <div className="chest" role="button" tabIndex={0} aria-label="Open the treasure chest" onClick={openTreasure} onKeyDown={(event) => { if (event.key === 'Enter') openTreasure(); }}>🎁</div>
                    <p className="sub">Tap the treasure at the end of the story!</p>
                </div>
                <button type="button" className="reset" onClick={() => setGot({})}>↺ Play again</button>
            </div>
            <div className={`modal${open !== null || treasure ? ' open' : ''}`} role="dialog" aria-modal="true" onClick={(event) => { if (event.target === event.currentTarget) { setOpen(null); setTreasure(false); } }}>
                <div className="sheet">
                    {treasure ? (
                        <>
                            <button type="button" className="close" aria-label="Close" onClick={() => setTreasure(false)}>✕</button>
                            <h3>🎉 You found the treasure!</h3>
                            <p className="lead">The friends beat the dragon. Everyone is happy. Fireworks! 🎆 You have {count} of 18 points.</p>
                            <GameXpNote />
                            <img className="big" src="/games/story/c69.jpg" alt="Fireworks over the town" />
                            <p className="lead" style={{ marginTop: 16 }}>Now tell the story to a friend: 🗣️</p>
                            <div className="story">
                                <div><span>First</span>🏠 new house</div>
                                <div><span>Then</span>🧱 game world</div>
                                <div><span>Next</span>💥 BOOM!</div>
                                <div><span>Finally</span>🐉 the dragon</div>
                            </div>
                            <div className="actions"><button type="button" className="btn" onClick={() => setTreasure(false)}>Back to the map 🗺️</button></div>
                        </>
                    ) : null}
                    {stop ? (
                        <>
                            <button type="button" className="close" aria-label="Close" onClick={() => setOpen(null)}>✕</button>
                            <h3>Stop {open + 1}: {stop.title}</h3>
                            <p className="lead">Look at the picture carefully… 👀</p>
                            <img className="big" src={`/games/story/${stop.img}.jpg`} alt={`Picture from the book: ${stop.title}`} />
                            {QS.map(([kind, icon, label, starter, spoken]) => {
                                const saved = got[`${open}${kind}`];
                                let feedback = '';
                                if (saved !== undefined) {
                                    if (kind === 'next') feedback = `✨ ${stop.answer}`;
                                    else {
                                        const right = stop[kind].find((choice) => choice[2]);
                                        feedback = `⭐ ${starter.replace('…', ' ')}${right[1].toLowerCase()}.`;
                                    }
                                }
                                return (
                                    <div className={`q ${kind}`} key={kind}>
                                        <div className="qhead">
                                            <h4>{label} {icon}</h4>
                                            <span className="starter">"{starter}"</span>
                                            <button type="button" className="say" aria-label="Read aloud" onClick={() => speak(`${spoken} ${stop[kind].map((choice) => choice[1]).join(', or, ')}`)}>🔊</button>
                                        </div>
                                        <div className="choices">
                                            {stop[kind].map((choice, index) => {
                                                const selected = kind === 'next' ? saved === index + 1 : saved && choice[2];
                                                return (
                                                    <button key={choice[1]} type="button" className={`choice${selected ? ' right' : ''}${shake === `${kind}-${index}` ? ' wrong' : ''}`} onClick={() => choose(kind, index, choice)}>
                                                        <span className="em">{choice[0]}</span>
                                                        <span>{choice[1]}</span>
                                                    </button>
                                                );
                                            })}
                                        </div>
                                        <p className={`feedback${feedback ? ' good' : shake && shake.startsWith(kind) ? ' try' : ''}`}>
                                            {feedback || (shake && shake.startsWith(kind) ? 'Hmm, look again! Try another one 🔍' : '')}
                                        </p>
                                    </div>
                                );
                            })}
                            <div className="actions">
                                {open < STOPS.length - 1 ? (
                                    <button type="button" className="btn" onClick={() => setOpen(open + 1)}>Next stop ➜</button>
                                ) : (
                                    <button type="button" className="btn" onClick={() => { setOpen(null); setTreasure(true); speak('You found the treasure! Hooray!'); }}>Open the treasure 🎁</button>
                                )}
                            </div>
                        </>
                    ) : null}
                </div>
            </div>
        </div>
    );
}
