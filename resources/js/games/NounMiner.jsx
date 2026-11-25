import { useState } from 'react';
import './styles/noun-miner.css';
import { GameXpNote, useGameXp } from './GameXp';

const ROUNDS = [
    { s: 'Tyler found a torch.', n: { Tyler: 'person', torch: 'thing' } },
    { s: 'Evan made a pickaxe.', n: { Evan: 'person', pickaxe: 'thing' } },
    { s: 'The creeper broke the base.', n: { creeper: 'mob', base: 'place' } },
    { s: 'Tobi put a torch in the cave.', n: { Tobi: 'person', torch: 'thing', cave: 'place' } },
    { s: 'Candace made a roof.', n: { Candace: 'person', roof: 'thing' } },
    { s: 'Tyler saw a pig and a sheep.', n: { Tyler: 'person', pig: 'animal', sheep: 'animal' } },
    { s: 'Grace hid in the house.', n: { Grace: 'person', house: 'place' } },
];
const PRAISE = ['Great mining!', 'Super job!', 'You found them all!', 'Diamond hero!'];

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

export default function NounMiner() {
    const { claim } = useGameXp();
    const [screen, setScreen] = useState('intro');
    const [round, setRound] = useState(0);
    const [gems, setGems] = useState(0);
    const [misses, setMisses] = useState(0);
    const [found, setFound] = useState([]);
    const [shaking, setShaking] = useState(null);
    const [feedback, setFeedback] = useState('');
    const [feedbackKind, setFeedbackKind] = useState('');

    const current = ROUNDS[round];
    const words = current ? current.s.replace(/\.$/, '').split(' ') : [];
    const total = current ? Object.keys(current.n).length : 0;
    const stars = misses <= 2 ? 3 : misses <= 6 ? 2 : 1;

    const start = () => {
        setRound(0);
        setGems(0);
        setMisses(0);
        setFound([]);
        setFeedback('');
        setFeedbackKind('');
        setScreen('play');
    };

    const tap = (word) => {
        if (screen !== 'play' || found.includes(word)) return;
        if (current.n[word] && !found.includes(word)) {
            const nextFound = [...found, word];
            setFound(nextFound);
            setGems((n) => n + 1);
            beep(660, 1320, 0.15);
            if (nextFound.length === total) {
                setFeedbackKind('good');
                setFeedback(PRAISE[round % PRAISE.length]);
            } else {
                setFeedbackKind('good');
                setFeedback(`Yes! "${word}" is a ${current.n[word]}. Find ${total - nextFound.length} more.`);
            }
            return;
        }
        setMisses((n) => n + 1);
        setShaking(word + Date.now());
        beep(220, 140, 0.2);
        setFeedbackKind('bad');
        setFeedback(`"${word}" is not a naming word. Is it a person, place, animal or thing?`);
    };

    return (
        <div className="ym-nm">
            <div className="wrap">
                <header>
                    <h1>NOUN MINER</h1>
                    <div className="tally" aria-live="polite"><span className="gem" aria-hidden="true" /><span>{gems}</span>&nbsp;diamonds</div>
                </header>
                <div className="ground" aria-hidden="true" />
                <main className="panel">
                    {screen === 'intro' && (
                        <>
                            <p className="task">Naming words are called <b>NOUNS</b>. They name a person, place, animal or thing.</p>
                            <div className="rule">
                                <div><b>PERSON</b>Tyler</div>
                                <div><b>PLACE</b>cave</div>
                                <div><b>ANIMAL</b>pig</div>
                                <div><b>THING</b>torch</div>
                            </div>
                            <div className="example">
                                <span className="lbl">EXAMPLE</span>
                                <p><u>Grace</u> drew a <u>map</u>.</p>
                                <p style={{ fontSize: 20 }}>Grace is a person. Map is a thing. So we mine 2 nouns!</p>
                            </div>
                            <p className="hint">Tap the stone blocks that are naming words. Each noun turns into a diamond!</p>
                            <div className="row"><button type="button" className="btn" onClick={start}>START MINING</button></div>
                        </>
                    )}
                    {screen === 'play' && (
                        <>
                            <div className="progress" aria-hidden="true">
                                {ROUNDS.map((_, i) => <span key={i} className={i < round ? 'done' : i === round ? 'now' : ''} />)}
                            </div>
                            <p className="task">Sentence {round + 1} of {ROUNDS.length}: find <b>{total} nouns</b>.</p>
                            <div className="sentence">
                                {words.map((word, i) => {
                                    const isFound = found.includes(word);
                                    const shake = shaking && shaking.startsWith(word) && !isFound;
                                    return (
                                        <button
                                            key={`${round}-${word}-${i}`}
                                            type="button"
                                            className={`block${isFound ? ' found' : ''}${shake ? ' wrong' : ''}`}
                                            onClick={() => tap(word)}
                                        >
                                            {word}
                                            {isFound ? <span className="tag">{current.n[word].toUpperCase()}</span> : null}
                                        </button>
                                    );
                                })}
                                <span className="punct">.</span>
                            </div>
                            <p className={`feedback ${feedbackKind}`} aria-live="polite">{feedback}</p>
                            {found.length === total ? (
                                <div className="row">
                                    <button
                                        type="button"
                                        className="btn"
                                        onClick={() => {
                                            if (round < ROUNDS.length - 1) {
                                                setRound((n) => n + 1);
                                                setFound([]);
                                                setFeedback('');
                                                setFeedbackKind('');
                                            } else {
                                                setScreen('done');
                                                beep(523, 1046, 0.4);
                                                claim(gems);
                                            }
                                        }}
                                    >
                                        {round === ROUNDS.length - 1 ? 'SEE MY DIAMONDS ➜' : 'NEXT ➜'}
                                    </button>
                                </div>
                            ) : null}
                        </>
                    )}
                    {screen === 'done' && (
                        <>
                            <p className="big">YOU MINED {gems} DIAMONDS!</p>
                            <GameXpNote />
                            <div className="stars" aria-label={`${stars} out of 3 stars`}>
                                {'★'.repeat(stars)}
                                <span style={{ color: '#3A3047' }}>{'★'.repeat(3 - stars)}</span>
                            </div>
                            <p className="task">You are a <b>Noun Miner</b>! Remember: names like <b>T</b>yler and <b>G</b>race start with a Capital letter.</p>
                            <div className="row">
                                <button type="button" className="btn" onClick={start}>PLAY AGAIN</button>
                                <button type="button" className="btn ghost" onClick={() => setScreen('intro')}>HOW TO PLAY</button>
                            </div>
                        </>
                    )}
                </main>
            </div>
        </div>
    );
}
