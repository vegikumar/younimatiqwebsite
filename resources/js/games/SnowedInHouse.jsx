import { useEffect, useMemo, useRef, useState } from 'react';
import { GameXpNote, useGameXp } from './GameXp';
import './styles/snowed-in-house.css';

const MISSIONS = [
    { id: 'slice', name: 'Syllable Shovel', room: 'Kitchen', color: 'var(--blue)', rounds: 6 },
    { id: 'gate', name: 'Open or Closed?', room: 'Living room', color: 'var(--orange)', rounds: 10 },
    { id: 'shield', name: 'Sentence Shield', room: 'Bedroom', color: 'var(--pink)', rounds: 6 },
    { id: 'words', name: 'Word Explorer', room: 'Basement', color: 'var(--green)', rounds: 8 },
    { id: 'spell', name: 'Spell Check', room: 'Study', color: 'var(--purple)', rounds: 8 },
];

const SLICE = ['cab-in', 'fe-ver', 'bliz-zard', 'hol-i-day', 'fam-i-ly', 'e-lec-tric-i-ty', 'mys-te-ri-ous', 'fro-zen', 'shov-el', 'blan-ket', 'mit-ten', 'dis-ap-peared', 'ti-ny', 'ze-ro', 'des-per-ate', 'gen-er-ous'];
const GATE = [
    ['fe-ver', 0, true], ['fe-ver', 1, false], ['cab-in', 0, false], ['cab-in', 1, false],
    ['ze-ro', 0, true], ['ze-ro', 1, true], ['ti-ny', 0, true], ['ti-ny', 1, true],
    ['fro-zen', 0, true], ['fro-zen', 1, false], ['mo-ment', 0, true], ['mo-ment', 1, false],
    ['bas-ket', 0, false], ['mit-ten', 1, false], ['San-ta', 1, true], ['San-ta', 0, false],
    ['blan-ket', 0, false], ['nap-kin', 1, false], ['bliz-zard', 0, false], ['shov-el', 0, false],
];
const SPOT = [
    ['The power went off', 'the house got cold', 'so'],
    ['Manny flipped the switches', 'every room went dark', 'and'],
    ['Rodrick slept all day', 'Greg was bored', 'but'],
    ['The basement flooded', 'the family grabbed buckets', 'so'],
    ['Mom cooked the last pasta', 'everyone was hungry', 'and'],
    ['The heater broke', 'Mom found extra blankets', 'so'],
    ['Greg checked the elf', 'it was still watching him', 'and'],
    ['The snow melted', 'Greg finally went outside', 'and'],
    ['Rowley told the truth', 'Greg had to clean the wall', 'so'],
    ['Mom lit a candle', 'we all sat together', 'and'],
    ['Greg shovelled the church path', 'nobody knew it was him', 'but'],
    ['Dad was stuck at the hotel', 'we were stuck at home', 'but'],
];
const F = {
    runon: (a, b) => `${a} ${b}.`,
    splice: (a, b) => `${a}, ${b}.`,
    stop: (a, b) => `${a}. ${b[0].toUpperCase()}${b.slice(1)}.`,
    conj: (a, b, c) => `${a}, ${c} ${b}.`,
    semi: (a, b) => `${a}; ${b}.`,
};
const BANK = [
    ['blizzard', 'a heavy snowstorm with strong, freezing winds', 'The ____ kept us indoors for three whole days.'],
    ['stranded', 'stuck somewhere and unable to leave', 'Dad was ____ at a hotel across town.'],
    ['suspect', 'a person who might have done something wrong', 'Greg worried that he was the main ____.'],
    ['innocent', 'not guilty; did nothing wrong', 'Greg hoped everyone would believe he was ____.'],
    ['shiver', 'to shake because you are cold or scared', 'With no heating, we started to ____ under the blankets.'],
    ['desperate', 'in great need and ready to try anything', 'By day three, we were ____ for hot food.'],
    ['generous', 'happy to give or share with others', 'A ____ helper cleared the church car park.'],
    ['anonymous', 'unknown by name; nobody knows who did it', 'Nobody knew who the helper was, so the kind act was ____.'],
];
const DIARY = 'Day 2 of the {blizard|blizzard|blizard,blizzerd}. We are {totaly|totally|totaly,totallie} {strandid|stranded|strandid,straned} in the house. Mom says the heater is broken, so we all {shivver|shiver|shivver,shivur} under {blankits|blankets|blankits,blancets}. Rodrick says I am the main {suspekt|suspect|suspekt,suspeckt} for eating the last cookies, but I am {inocent|innocent|inocent,innosent}! By dinner time we were so {desprate|desperate|desprate,despirate} that we ate cereal with no milk.';

function shuffle(a) {
    const arr = a.slice();
    for (let i = arr.length - 1; i > 0; i -= 1) {
        const j = Math.floor(Math.random() * (i + 1));
        [arr[i], arr[j]] = [arr[j], arr[i]];
    }
    return arr;
}

function stars(score, max) {
    const r = max ? score / max : 0;
    const n = r >= 0.85 ? 3 : r >= 0.5 ? 2 : 1;
    return '★'.repeat(n) + '☆'.repeat(3 - n);
}

function Snowflakes() {
    const ref = useRef(null);
    useEffect(() => {
        const c = ref.current;
        if (!c || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return undefined;
        const ctx = c.getContext('2d');
        let W;
        let H;
        let raf;
        const fl = [];
        const size = () => {
            W = c.width = c.offsetWidth;
            H = c.height = c.offsetHeight;
        };
        size();
        window.addEventListener('resize', size);
        for (let k = 0; k < 40; k += 1) {
            fl.push({ x: Math.random() * 400, y: Math.random() * 600, r: 1 + Math.random() * 2.2, v: 0.3 + Math.random() * 0.7 });
        }
        const tick = () => {
            ctx.clearRect(0, 0, W, H);
            ctx.fillStyle = 'rgba(255,255,255,.75)';
            fl.forEach((f) => {
                f.y += f.v;
                f.x += Math.sin(f.y / 30) * 0.3;
                if (f.y > H) {
                    f.y = -5;
                    f.x = Math.random() * W;
                }
                ctx.beginPath();
                ctx.arc(f.x % W, f.y, f.r, 0, 7);
                ctx.fill();
            });
            raf = requestAnimationFrame(tick);
        };
        raf = requestAnimationFrame(tick);
        return () => {
            cancelAnimationFrame(raf);
            window.removeEventListener('resize', size);
        };
    }, []);
    return <canvas className="flakes" ref={ref} aria-hidden="true" />;
}

function MissionHead({ mission, index, round, score, how, children }) {
    return (
        <>
            <div className="phead">
                <div>
                    <div className="tag" style={{ color: mission.color }}>
                        Mission {index} · {mission.room} breaker
                    </div>
                    <h2>{mission.name}</h2>
                </div>
                <div className="pills">
                    <span className="pill">{round}</span>
                    <span className="pill">{score}</span>
                </div>
            </div>
            <p className="how">{how}</p>
            <div className="stage">{children}</div>
        </>
    );
}

function SliceMission({ mission, onComplete }) {
    const words = useMemo(() => shuffle(SLICE).slice(0, mission.rounds), [mission.rounds]);
    const [i, setI] = useState(0);
    const [score, setScore] = useState(0);
    const [pendingScore, setPendingScore] = useState(0);
    const [cuts, setCuts] = useState(() => new Set());
    const [checked, setChecked] = useState(false);
    const [fb, setFb] = useState({ cls: 'fb', text: '' });
    const [shake, setShake] = useState(false);

    if (i >= words.length) return null;
    const parts = words[i].split('-');
    const letters = parts.join('');
    const ans = new Set();
    let p = 0;
    parts.slice(0, -1).forEach((x) => {
        p += x.length;
        ans.add(p);
    });

    const toggle = (pos) => {
        if (checked) return;
        setCuts((prev) => {
            const next = new Set(prev);
            if (next.has(pos)) next.delete(pos);
            else next.add(pos);
            return next;
        });
    };

    const check = () => {
        const ok = cuts.size === ans.size && [...cuts].every((c) => ans.has(c));
        setChecked(true);
        const nextScore = ok ? score + 1 : score;
        setPendingScore(nextScore);
        if (ok) {
            setScore(nextScore);
            setFb({ cls: 'fb good', text: `Great digging! ${words[i]} has ${parts.length} syllables.` });
        } else {
            setFb({ cls: 'fb bad', text: `It splits as ${words[i]}. Green marks show the missing cuts.` });
            setShake(true);
            setTimeout(() => setShake(false), 350);
        }
    };

    const advance = () => {
        if (i === words.length - 1) onComplete(pendingScore || score);
        else {
            setI((x) => x + 1);
            setCuts(new Set());
            setChecked(false);
            setFb({ cls: 'fb', text: '' });
        }
    };

    return (
        <MissionHead
            mission={mission}
            index={1}
            round={`Round ${i + 1} of ${words.length}`}
            score={`${score} correct`}
            how="Tap the gaps between letters to dig each word into syllables. Tap again to undo. Every syllable needs a vowel sound. Stuck? Press Clap hint."
        >
            <div className={`slicer${shake ? ' shake' : ''}`}>
                {[...letters].map((ch, k) => (
                    <span key={`l-${k}`}>
                        <span>{ch}</span>
                        {k < letters.length - 1 ? (
                            <button
                                type="button"
                                className={`gap${cuts.has(k + 1) ? ' cut' : ''}${checked && cuts.has(k + 1) && !ans.has(k + 1) ? ' wrong' : ''}${checked && !cuts.has(k + 1) && ans.has(k + 1) ? ' missed' : ''}`}
                                data-p={k + 1}
                                aria-label={`Dig after letter ${k + 1}`}
                                disabled={checked}
                                onClick={() => toggle(k + 1)}
                            />
                        ) : null}
                    </span>
                ))}
            </div>
            <div className={fb.cls}>{fb.text}</div>
            {!checked ? (
                <div className="btns">
                    <button type="button" className="btn" onClick={() => setFb({ cls: 'fb', text: `Clap it: ${parts.length} beats` })}>
                        Clap hint
                    </button>
                    <button type="button" className="btn primary" onClick={check}>
                        Check
                    </button>
                </div>
            ) : (
                <button type="button" className="btn primary" onClick={advance}>
                    {i === words.length - 1 ? 'Switch the breaker on!' : 'Next'}
                </button>
            )}
        </MissionHead>
    );
}

function GateMission({ mission, onComplete }) {
    const items = useMemo(() => shuffle(GATE).slice(0, mission.rounds), [mission.rounds]);
    const [i, setI] = useState(0);
    const [score, setScore] = useState(0);
    const [picked, setPicked] = useState(null);
    const [pendingScore, setPendingScore] = useState(0);
    const [fb, setFb] = useState({ cls: 'fb', text: '' });
    const [shake, setShake] = useState(false);

    if (i >= items.length) return null;
    const [w, idx, open] = items[i];
    const parts = w.split('-');

    const choose = (isOpen) => {
        if (picked !== null) return;
        setPicked(isOpen);
        const syl = parts[idx];
        const nextScore = isOpen === open ? score + 1 : score;
        setPendingScore(nextScore);
        setScore(nextScore);
        if (isOpen === open) {
            setFb({ cls: 'fb good', text: `Yes! "${syl}" ends in a ${open ? "vowel, so it's open" : "consonant, so it's closed"}.` });
        } else {
            setFb({ cls: 'fb bad', text: `"${syl}" ends in a ${open ? "vowel, so it's OPEN" : "consonant, so it's CLOSED"}.` });
            setShake(true);
            setTimeout(() => setShake(false), 350);
        }
    };

    const advance = () => {
        if (i === items.length - 1) onComplete(pendingScore || score);
        else {
            setI((x) => x + 1);
            setPicked(null);
            setFb({ cls: 'fb', text: '' });
        }
    };

    return (
        <MissionHead
            mission={mission}
            index={2}
            round={`Round ${i + 1} of ${items.length}`}
            score={`${score} correct`}
            how="Look at the glowing syllable. If it ends in a vowel, it's OPEN (the vowel says its name). If it ends in a consonant, it's CLOSED (the vowel sounds short)."
        >
            <div className={`big-word${shake ? ' shake' : ''}`}>
                {parts.map((x, k) => (
                    <span key={k}>
                        {k > 0 ? <span className="dash">-</span> : null}
                        <span className={k === idx ? 'hi' : 'lo'}>{x}</span>
                    </span>
                ))}
            </div>
            {picked === null ? (
                <div className="btns">
                    <button type="button" className="btn open big" onClick={() => choose(true)}>
                        OPEN
                    </button>
                    <button type="button" className="btn closed big" onClick={() => choose(false)}>
                        CLOSED
                    </button>
                </div>
            ) : null}
            <div className={fb.cls}>{fb.text}</div>
            {picked !== null ? (
                <button type="button" className="btn primary" onClick={advance}>
                    {i === items.length - 1 ? 'Switch the breaker on!' : 'Next'}
                </button>
            ) : null}
        </MissionHead>
    );
}

function ShieldMission({ mission, onComplete }) {
    const items = useMemo(() => {
        const kinds = shuffle(['runon', 'runon', 'splice', 'splice', 'correct', 'runon']);
        return shuffle(SPOT)
            .slice(0, mission.rounds)
            .map((s, k) => ({ a: s[0], b: s[1], c: s[2], kind: kinds[k % kinds.length] }));
    }, [mission.rounds]);
    const [i, setI] = useState(0);
    const [score, setScore] = useState(0);
    const [named, setNamed] = useState(false);
    const [namedRight, setNamedRight] = useState(false);
    const [repairOpts, setRepairOpts] = useState(null);
    const [repairDone, setRepairDone] = useState(false);
    const [fb, setFb] = useState({ cls: 'fb', text: '' });
    const [shake, setShake] = useState(false);
    const [goodRepair, setGoodRepair] = useState('stop');

    if (i >= items.length) return null;
    const it = items[i];
    const shown = it.kind === 'runon' ? F.runon(it.a, it.b) : it.kind === 'splice' ? F.splice(it.a, it.b) : F.conj(it.a, it.b, it.c);

    const [pendingScore, setPendingScore] = useState(0);

    const nameIt = (kind) => {
        if (named) return;
        const right = kind === it.kind;
        setNamed(true);
        setNamedRight(right);
        const why = {
            correct: 'correct: it has a comma AND a joining word',
            runon: 'a run-on: two complete thoughts with nothing between them',
            splice: "a comma splice: a comma alone can't hold two sentences",
        }[it.kind];
        setFb({ cls: `fb ${right ? 'good' : 'bad'}`, text: `${right ? "Spotted! It's " : 'Look again. It\'s '}${why}.` });
        if (!right) {
            setShake(true);
            setTimeout(() => setShake(false), 350);
        }
        if (it.kind === 'correct') {
            const nextScore = right ? score + 1 : score;
            setScore(nextScore);
            setPendingScore(nextScore);
            setRepairDone(true);
            return;
        }
        const good = shuffle(['stop', 'conj', 'semi'])[0];
        setGoodRepair(good);
        setRepairOpts(
            shuffle([
                { t: F[good](it.a, it.b, it.c), ok: true },
                { t: F.splice(it.a, it.b), ok: false, why: "That's still a comma splice." },
                { t: F.runon(it.a, it.b), ok: false, why: "That's still a run-on." },
            ])
        );
    };

    const pickRepair = (o, idx) => {
        if (repairDone) return;
        setRepairDone(true);
        setRepairOpts((opts) => opts.map((x, k) => ({ ...x, show: true, picked: k === idx })));
        if (o.ok) {
            const nextScore = namedRight ? score + 1 : score;
            setScore(nextScore);
            setPendingScore(nextScore);
            setFb({
                cls: 'fb good',
                text: `Repaired with ${{ stop: 'a full stop', conj: 'a comma + joining word', semi: 'a semicolon' }[goodRepair]}!`,
            });
        } else {
            setPendingScore(score);
            setFb({ cls: 'fb bad', text: `${o.why} The green one is fixed.` });
        }
    };

    const advance = () => {
        if (i === items.length - 1) onComplete(pendingScore || score);
        else {
            setI((x) => x + 1);
            setNamed(false);
            setNamedRight(false);
            setRepairOpts(null);
            setRepairDone(false);
            setFb({ cls: 'fb', text: '' });
        }
    };

    return (
        <MissionHead
            mission={mission}
            index={3}
            round={`Round ${i + 1} of ${items.length}`}
            score={`${score} correct`}
            how="Is the sentence correct, a Run-on Monster (two sentences with nothing between them) or a Comma Splice Monster (two sentences joined by only a comma)? If it's a monster, pick the repair."
        >
            <div className="step">Step 1 · Name it</div>
            <div className={`sentence${shake ? ' shake' : ''}`}>{shown}</div>
            <div className="btns">
                <button type="button" className="btn" disabled={named} onClick={() => nameIt('correct')}>
                    Correct ✓
                </button>
                <button type="button" className="btn" disabled={named} onClick={() => nameIt('runon')}>
                    Run-on Monster
                </button>
                <button type="button" className="btn" disabled={named} onClick={() => nameIt('splice')}>
                    Comma Splice Monster
                </button>
            </div>
            <div className={fb.cls}>{fb.text}</div>
            {repairOpts ? (
                <div className="options">
                    <div className="step">Step 2 · Choose the repair</div>
                    {repairOpts.map((o, k) => (
                        <button
                            key={k}
                            type="button"
                            className={`opt${o.show && o.ok ? ' right' : ''}${o.show && o.picked && !o.ok ? ' wrongpick' : ''}`}
                            disabled={repairDone}
                            onClick={() => pickRepair(o, k)}
                        >
                            {o.t}
                        </button>
                    ))}
                </div>
            ) : null}
            {repairDone ? (
                <button type="button" className="btn primary" onClick={advance}>
                    {i === items.length - 1 ? 'Switch the breaker on!' : 'Next'}
                </button>
            ) : null}
        </MissionHead>
    );
}

function WordsMission({ mission, onComplete }) {
    const items = useMemo(
        () => shuffle(BANK).map((b, k) => ({ w: b[0], mean: b[1], gap: b[2], type: k % 2 ? 'gap' : 'mean' })),
        []
    );
    const [i, setI] = useState(0);
    const [score, setScore] = useState(0);
    const [answered, setAnswered] = useState(false);
    const [pendingScore, setPendingScore] = useState(0);
    const [fb, setFb] = useState({ cls: 'fb', text: '' });
    const opts = useMemo(() => {
        const it = items[i];
        if (!it) return [];
        return shuffle([it.w, ...shuffle(BANK.map((b) => b[0]).filter((x) => x !== it.w)).slice(0, 3)]);
    }, [i, items]);

    if (i >= items.length) return null;
    const it = items[i];

    const pick = (w) => {
        if (answered) return;
        setAnswered(true);
        const nextScore = w === it.w ? score + 1 : score;
        setPendingScore(nextScore);
        setScore(nextScore);
        if (w === it.w) {
            setFb({ cls: 'fb good', text: `Yes! "${it.w}" means ${it.mean}.` });
        } else {
            setFb({ cls: 'fb bad', text: `It's "${it.w}": ${it.mean}.` });
        }
    };

    const advance = () => {
        if (i === items.length - 1) onComplete(pendingScore || score);
        else {
            setI((x) => x + 1);
            setAnswered(false);
            setFb({ cls: 'fb', text: '' });
        }
    };

    return (
        <MissionHead
            mission={mission}
            index={4}
            round={`Round ${i + 1} of ${items.length}`}
            score={`${score} correct`}
            how="Explore the Word Bank! Some rounds give you a meaning to match. Others give you a diary sentence with a missing word. Pick the word that fits."
        >
            {it.type === 'mean' ? (
                <>
                    <div className="step">Which word means…</div>
                    <div className="sentence">&quot;{it.mean}&quot;</div>
                </>
            ) : (
                <>
                    <div className="step">Fill the gap</div>
                    <div className="sentence">
                        {it.gap.split('____').map((part, idx, arr) => (
                            <span key={idx}>
                                {part}
                                {idx < arr.length - 1 ? <span style={{ color: 'var(--purple)' }}>_____</span> : null}
                            </span>
                        ))}
                    </div>
                </>
            )}
            <div className="btns">
                {opts.map((o) => (
                    <button
                        key={o}
                        type="button"
                        className="btn"
                        disabled={answered}
                        style={
                            answered && o === it.w
                                ? { borderColor: 'var(--green)', background: '#EAF7E7' }
                                : answered && o !== it.w
                                  ? undefined
                                  : undefined
                        }
                        onClick={() => pick(o)}
                    >
                        {o}
                    </button>
                ))}
            </div>
            <div className={fb.cls}>{fb.text}</div>
            {answered ? (
                <button type="button" className="btn primary" onClick={advance}>
                    {i === items.length - 1 ? 'Switch the breaker on!' : 'Next'}
                </button>
            ) : null}
        </MissionHead>
    );
}

function SpellMission({ mission, onComplete }) {
    const tokens = useMemo(() => {
        const out = [];
        let n = 0;
        DIARY.split(/(\{[^}]+\})/).forEach((part) => {
            if (part.startsWith('{')) {
                const [bad, good, wrongs] = part.slice(1, -1).split('|');
                out.push({ type: 'bad', k: n, bad, good, wrongs: wrongs.split(',') });
                n += 1;
            } else {
                part.split(/(\s+)/).forEach((t) => {
                    if (/^\s+$/.test(t) || !t) out.push({ type: 'space', t });
                    else {
                        const m2 = t.match(/^([A-Za-z']+)(.*)$/);
                        if (m2) {
                            out.push({ type: 'ok', w: m2[1] });
                            if (m2[2]) out.push({ type: 'text', t: m2[2] });
                        } else out.push({ type: 'text', t });
                    }
                });
            }
        });
        return out;
    }, []);

    const [fixed, setFixed] = useState({});
    const [firstTry, setFirstTry] = useState(0);
    const [tried, setTried] = useState({});
    const [active, setActive] = useState(null);
    const [fb, setFb] = useState({ cls: 'fb', text: 'Find all 8 mistakes.' });
    const [picker, setPicker] = useState([]);
    const fixedCount = Object.keys(fixed).length;

    useEffect(() => {
        if (fixedCount === 8) {
            const t = setTimeout(() => onComplete(firstTry), 700);
            return () => clearTimeout(t);
        }
        return undefined;
    }, [fixedCount, firstTry, onComplete]);

    const clickWord = (tok) => {
        if (tok.type === 'bad' && fixed[tok.k]) return;
        if (tok.type === 'ok') {
            setActive(null);
            setPicker([]);
            setFb({ cls: 'fb', text: `"${tok.w}" is spelled correctly. Keep looking!` });
            return;
        }
        if (tok.type !== 'bad') return;
        setActive(tok.k);
        setFb({ cls: 'fb', text: `How do you spell "${tok.bad}" correctly?` });
        setPicker(shuffle([tok.good, ...tok.wrongs]));
    };

    const pickSpell = (s) => {
        const tok = tokens.find((t) => t.type === 'bad' && t.k === active);
        if (!tok) return;
        if (s === tok.good) {
            if (!tried[tok.k]) setFirstTry((n) => n + 1);
            setFixed((f) => ({ ...f, [tok.k]: true }));
            setFb({ cls: 'fb good', text: `Fixed: ${tok.good}!` });
            setPicker([]);
            setActive(null);
        } else {
            setTried((t) => ({ ...t, [tok.k]: true }));
            setFb({ cls: 'fb bad', text: 'Not quite. Clap it into beats and try again.' });
        }
    };

    return (
        <MissionHead
            mission={mission}
            index={5}
            round={`${fixedCount} of 8 fixed`}
            score={`${firstTry} first try`}
            how="This diary note was written in a hurry, and 8 words are spelled wrong. Tap a word you think is wrong, then pick the correct spelling. Use Clap and spell: say each beat and spell it."
        >
            <div className="diary">
                {tokens.map((tok, idx) => {
                    if (tok.type === 'space' || tok.type === 'text') return <span key={idx}>{tok.t}</span>;
                    if (tok.type === 'ok') {
                        return (
                            <button key={idx} type="button" className="wd" onClick={() => clickWord(tok)}>
                                {tok.w}
                            </button>
                        );
                    }
                    const isFixed = !!fixed[tok.k];
                    return (
                        <button
                            key={idx}
                            type="button"
                            className={`wd${isFixed ? ' fixed' : ''}${active === tok.k ? ' oops' : ''}`}
                            onClick={() => clickWord(tok)}
                        >
                            {isFixed ? tok.good : tok.bad}
                        </button>
                    );
                })}
            </div>
            <div className={fb.cls}>{fb.text}</div>
            <div className="picker">
                {picker.map((o) => (
                    <button key={o} type="button" className="btn" onClick={() => pickSpell(o)} disabled={tried[active] && o !== tokens.find((t) => t.k === active)?.good && false}>
                        {o}
                    </button>
                ))}
            </div>
        </MissionHead>
    );
}

function MissionComplete({ mission, score, allDone, nextMission, onReplay, onNext, onCert }) {
    return (
        <div className="stage" style={{ background: 'var(--bg)' }}>
            <div className="pop" style={{ display: 'grid', gap: 10, justifyItems: 'center' }}>
                <div className="stars">{stars(score, mission.rounds)}</div>
                <div className="big-word" style={{ fontSize: 28 }}>
                    {mission.room} lights are ON!
                </div>
                <p className="how">
                    You got {score} of {mission.rounds} correct. {score === mission.rounds ? 'Perfect power!' : 'Replay any time to earn more stars.'}
                </p>
                <div className="btns">
                    <button type="button" className="btn" onClick={onReplay}>
                        Replay this mission
                    </button>
                    {allDone ? (
                        <button type="button" className="btn primary" onClick={onCert}>
                            See your certificate
                        </button>
                    ) : (
                        <button type="button" className="btn primary" onClick={onNext}>
                            Next mission: {nextMission?.name}
                        </button>
                    )}
                </div>
            </div>
        </div>
    );
}

export default function SnowedInHouse() {
    const { claim } = useGameXp();
    const [name, setName] = useState('');
    const [started, setStarted] = useState(false);
    const [done, setDone] = useState({});
    const [scores, setScores] = useState({});
    const [view, setView] = useState('intro'); // intro | mission | complete | certificate
    const [missionId, setMissionId] = useState('slice');
    const [lastScore, setLastScore] = useState(0);
    const claimed = useRef(false);

    const mission = MISSIONS.find((m) => m.id === missionId) || MISSIONS[0];
    const doneCount = MISSIONS.filter((m) => done[m.id]).length;
    const allDone = MISSIONS.every((m) => done[m.id]);
    const nextMission = MISSIONS.find((m) => !done[m.id]);

    const openMission = (m) => {
        setMissionId(m.id);
        setView('mission');
    };

    const finishMission = (score) => {
        setDone((d) => ({ ...d, [mission.id]: true }));
        setScores((s) => ({ ...s, [mission.id]: Math.max(s[mission.id] || 0, score) }));
        setLastScore(score);
        setView('complete');
    };

    const showCert = () => {
        setView('certificate');
        if (!claimed.current) {
            claimed.current = true;
            const total = MISSIONS.reduce((s, m) => s + (scores[m.id] || 0), 0);
            const max = MISSIONS.reduce((s, m) => s + m.rounds, 0);
            const pts = Math.max(1, Math.round((total / max) * 20));
            claim(pts);
        }
    };

    const playAgain = () => {
        setDone({});
        setScores({});
        setLastScore(0);
        claimed.current = false;
        openMission(MISSIONS[0]);
    };

    const start = () => {
        setStarted(true);
        openMission(MISSIONS[0]);
    };

    return (
        <div className="ym-sih">
            <div className="stripe">
                <span className="s1" />
                <span className="s2" />
                <span className="s3" />
                <span className="s4" />
                <span className="s5" />
                <span className="s6" />
            </div>
            <div className="wrap">
                <header className="top">
                    <img src="/logo.webp" alt="YounimatiQ – Future-Fit Education" />
                    <div className="ttl">
                        <div className="eyebrow">Cabin Fever Mission Game</div>
                        <h1>Power Up the Snowed-In House</h1>
                    </div>
                </header>

                <div className="main">
                    <aside className="housecard" aria-label="House power status">
                        <Snowflakes />
                        <h2>The house is freezing!</h2>
                        <p>Each mission you finish switches one breaker back on and lights up a room.</p>
                        <div className="house">
                            <svg viewBox="0 0 300 230" role="img" aria-label="A snowy house with five rooms">
                                <rect x="0" y="205" width="300" height="25" fill="#EEF4FF" />
                                <polygon points="40,95 150,25 260,95" fill="#7B3FE4" />
                                <path d="M40 95 L150 25 L260 95 L250 99 L150 36 L50 99 Z" fill="#fff" />
                                <rect x="215" y="40" width="22" height="40" fill="#E5336E" />
                                <rect x="211" y="34" width="30" height="9" rx="3" fill="#fff" />
                                <rect x="55" y="95" width="190" height="112" fill="#2F7BEA" />
                                {[1, 2, 3, 4, 5].map((n) => {
                                    const pos = {
                                        1: [70, 108],
                                        2: [128, 108],
                                        3: [186, 108],
                                        4: [70, 156],
                                        5: [186, 156],
                                    }[n];
                                    return (
                                        <rect
                                            key={n}
                                            className={`win${done[MISSIONS[n - 1].id] ? ' on' : ''}`}
                                            x={pos[0]}
                                            y={pos[1]}
                                            width="44"
                                            height="34"
                                            rx="4"
                                            fill={done[MISSIONS[n - 1].id] ? '#FFD45C' : '#1B1450'}
                                        />
                                    );
                                })}
                                <rect x="133" y="150" width="34" height="57" rx="4" fill="#F7931E" />
                                <circle cx="160" cy="180" r="2.5" fill="#26197A" />
                            </svg>
                        </div>
                        <div className="thermo">
                            <div className="lab">
                                <span>Freezing</span>
                                <span>{doneCount} of 5 rooms warm</span>
                                <span>Cosy</span>
                            </div>
                            <div className="bar">
                                <div className="fill" style={{ width: `${(doneCount / 5) * 100}%` }} />
                            </div>
                        </div>
                        <div className="breakers">
                            {MISSIONS.map((m, k) => {
                                const isDone = !!done[m.id];
                                const unlocked = k === 0 || done[MISSIONS[k - 1].id] || isDone;
                                return (
                                    <button
                                        key={m.id}
                                        type="button"
                                        className={`brk${isDone ? ' done' : ''}`}
                                        disabled={!unlocked || !started}
                                        aria-label={`Mission ${k + 1}: ${m.name}, ${isDone ? 'complete' : unlocked ? 'ready' : 'locked'}`}
                                        onClick={() => openMission(m)}
                                    >
                                        <span className="sw" aria-hidden="true" />
                                        <span>
                                            <span className="nm">
                                                {k + 1}. {m.name}
                                            </span>
                                            <span className="rm">Breaker: {m.room}</span>
                                        </span>
                                        <span className="st">{isDone ? stars(scores[m.id] || 0, m.rounds) : unlocked ? 'Ready' : 'Locked'}</span>
                                    </button>
                                );
                            })}
                        </div>
                    </aside>

                    <section className="panel" aria-live="polite">
                        {view === 'intro' ? (
                            <div className="intro">
                                <div className="tag" style={{ color: 'var(--purple)' }}>
                                    Your mission
                                </div>
                                <h2 style={{ margin: 0, color: 'var(--navy)', fontSize: 26, textWrap: 'balance' }}>The blizzard is here and the power is OFF!</h2>
                                <p className="story">
                                    A huge snowstorm has buried the house. To make things worse, someone has flipped every breaker except one, and the rooms are getting colder by the minute. Use your
                                    English powers to switch each breaker back on: split words, fix sentences, learn new words and spell-check the diary. Light up all five rooms to win!
                                </p>
                                <div className="namefield">
                                    <label htmlFor="sih-nm">
                                        <b>Diary writer&apos;s name:</b>
                                    </label>
                                    <input id="sih-nm" maxLength={30} placeholder="Type your name" value={name} onChange={(e) => setName(e.target.value)} />
                                </div>
                                <button type="button" className="btn primary big" onClick={start}>
                                    Start the mission
                                </button>
                            </div>
                        ) : null}

                        {view === 'mission' && missionId === 'slice' ? <SliceMission key={`slice-${scores.slice || 0}-${Object.keys(done).length}`} mission={mission} onComplete={finishMission} /> : null}
                        {view === 'mission' && missionId === 'gate' ? <GateMission key={`gate-${Object.keys(done).length}`} mission={mission} onComplete={finishMission} /> : null}
                        {view === 'mission' && missionId === 'shield' ? <ShieldMission key={`shield-${Object.keys(done).length}`} mission={mission} onComplete={finishMission} /> : null}
                        {view === 'mission' && missionId === 'words' ? <WordsMission key={`words-${Object.keys(done).length}`} mission={mission} onComplete={finishMission} /> : null}
                        {view === 'mission' && missionId === 'spell' ? <SpellMission key={`spell-${Object.keys(done).length}`} mission={mission} onComplete={finishMission} /> : null}

                        {view === 'complete' ? (
                            <MissionComplete
                                mission={mission}
                                score={lastScore}
                                allDone={allDone}
                                nextMission={nextMission}
                                onReplay={() => openMission(mission)}
                                onNext={() => nextMission && openMission(nextMission)}
                                onCert={showCert}
                            />
                        ) : null}

                        {view === 'certificate' ? (
                            <div className="stage" style={{ background: 'var(--bg)' }}>
                                <div className="cert pop">
                                    <img src="/logo.webp" alt="YounimatiQ" />
                                    <div className="tag" style={{ color: 'var(--purple)' }}>
                                        Certificate of Achievement
                                    </div>
                                    <div className="big">Cabin Fever Champion</div>
                                    <div className="who">{name.trim() || 'Diary Writer'}</div>
                                    <p className="how">switched every breaker back on and warmed up the whole house using syllables, sentence repairs, new vocabulary and spelling.</p>
                                    <div className="stars">
                                        {stars(
                                            MISSIONS.reduce((s, m) => s + (scores[m.id] || 0), 0),
                                            MISSIONS.reduce((s, m) => s + m.rounds, 0)
                                        )}
                                    </div>
                                    <p className="how">
                                        {MISSIONS.reduce((s, m) => s + (scores[m.id] || 0), 0)} of {MISSIONS.reduce((s, m) => s + m.rounds, 0)} answers correct ·{' '}
                                        {new Date().toLocaleDateString(undefined, { day: 'numeric', month: 'long', year: 'numeric' })}
                                    </p>
                                </div>
                                <div className="btns">
                                    <button type="button" className="btn primary" onClick={playAgain}>
                                        Play again
                                    </button>
                                </div>
                                <GameXpNote />
                            </div>
                        ) : null}
                    </section>
                </div>
                <footer>YounimatiQ · Future-Fit Education · Practice game for the Diary of a Wimpy Kid: Cabin Fever lesson.</footer>
            </div>
        </div>
    );
}
