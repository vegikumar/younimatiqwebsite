import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import './styles/treasure-algebra-island.css';
import { GameXpNote, useGameXp } from './GameXp';

const COL = ['var(--blue)', 'var(--orange)', 'var(--green)', 'var(--purple)', 'var(--pink)'];
const KEY = 'yq-algebra-island-v2';

const STOPS_RAW = [
    {
        name: 'Word Jungle',
        skill: 'What is an expression?',
        intro:
            "Squawk! Welcome, explorer! An EXPRESSION is a math phrase made of numbers, letters and operations, like 8p + 3. It has NO equals sign. We use expressions every day to describe costs, scores and amounts.",
        ido: {
            ctx: 'The jungle pizza shack sells pizzas for <b>$8 each</b>, plus a <b>$3 delivery fee</b>. How do we write the cost of <b>p</b> pizzas?',
            steps: [
                { mx: 'p', say: 'We don’t know how many pizzas yet, so I use a letter: p.' },
                { mx: '8 × p = 8p', say: 'Each pizza costs $8, so p pizzas cost 8p. The 8 is called the coefficient.' },
                { mx: '8p + 3', say: 'Add the $3 delivery fee. The 3 never changes, so it is the constant.' },
                { mx: '8p + 3   (no = sign!)', say: 'There is no equals sign, so 8p + 3 is an expression, a math phrase.' },
            ],
        },
        we: {
            ctx: 'Movie night! Tickets cost <b>$6 each</b>, and a big popcorn costs <b>$4</b>. Let’s write the total cost together.',
            steps: [
                {
                    q: 'Step 1: What changes depending on how many friends come?',
                    opts: ['The popcorn price', 'The number of tickets, t', 'The ticket price', 'The $4'],
                    a: 1,
                    show: 't = number of tickets',
                    h: ['Which amount can be different each time?', 'Popcorn is always $4.', 'The number of tickets changes, so we call it t.'],
                    why: 'The number of tickets can change, so it is our letter, t.',
                },
                {
                    q: 'Step 2: What is the cost of <b>t</b> tickets?',
                    opts: ['6 + t', '6t', 't − 6', '4t'],
                    a: 1,
                    show: 'tickets cost 6t',
                    h: ['Each ticket is $6.', '2 tickets = 6 × 2, 3 tickets = 6 × 3…', 't tickets = 6 × t = 6t'],
                    why: '$6 for each ticket gives 6 × t = 6t.',
                },
                {
                    q: 'Step 3: Now add the popcorn. What is the full expression?',
                    opts: ['6t + 4', '6t = 4', '10t', '6 + 4t'],
                    a: 0,
                    show: 'total = 6t + 4',
                    h: ['Add $4 to the ticket cost.', '6t plus 4.', '6t + 4 (no equals sign needed).'],
                    why: 'Ticket cost plus popcorn: 6t + 4.',
                },
            ],
        },
        you: [
            {
                t: 'choice',
                q: 'At the jungle game stall, which one is an <b>expression</b> (not an equation)?',
                opts: ['2m + 1 = 9', '4y − 7', 'x = 5', '3 + 4 = 7'],
                a: 1,
                h: ['Look for the equals sign.', 'An expression has no = sign.', '4y − 7 is the only one without =.'],
                why: '4y − 7 has no equals sign, so it is an expression.',
            },
            {
                t: 'choice',
                q: 'Riya has <b>n</b> stickers. Her friend gives her <b>6 more</b>. Which expression shows how many she has now?',
                opts: ['6n', 'n − 6', 'n + 6', 'n = 6'],
                a: 2,
                h: ['Is she getting more or fewer stickers?', '"6 more" means add 6.', 'n + 6'],
                why: 'She gets 6 more, so we add: n + 6.',
            },
            {
                t: 'choice',
                q: 'A jungle taxi charges <span class="m">5x + 3</span> dollars for <b>x</b> km. Which number is the <b>constant</b> (the fixed fee)?',
                opts: ['5', 'x', '3', '+'],
                a: 2,
                h: ['A constant stands alone.', '5 is stuck to x, so it changes with the distance.', '3 is charged every ride, no matter what.'],
                why: '3 is the fixed fee that never changes, so it is the constant.',
            },
        ],
    },
    {
        name: 'Mystery Box Cave',
        skill: 'What is a variable?',
        intro:
            "Inside this cave are mystery boxes. A VARIABLE is a letter, like x, that stands for a number we don't know yet. Like a mystery box, it can hold different numbers at different times.",
        ido: {
            ctx: 'A treasure bag holds <b>m</b> marbles, and there are <b>3</b> loose marbles beside it. Total marbles: <span class="m">m + 3</span>.',
            box: true,
            steps: [
                { mx: 'm', say: 'm is the variable. It is the mystery box, because we don’t know how many marbles are in the bag.' },
                { mx: 'If m = 5:  5 + 3 = 8', say: 'When the bag holds 5 marbles, I put 5 in place of m and get 8.' },
                { mx: 'If m = 10:  10 + 3 = 13', say: 'If the bag holds 10, the total is 13. The variable changed, and so did the answer!' },
                { mx: '3 stays 3', say: 'The 3 loose marbles never change. That is the constant.' },
            ],
        },
        we: {
            ctx: 'A cave bus charges <span class="m">2k + 1</span> dollars, where <b>k</b> is the number of km. How much is a <b>4 km</b> ride?',
            steps: [
                {
                    q: 'Step 1: Which part is the variable?',
                    opts: ['2', 'k', '1', '+'],
                    a: 1,
                    show: 'variable: k = 4 km',
                    h: ['A variable is a letter.', 'Which part changes with each trip?', 'k'],
                    why: 'k is the letter that changes with each trip.',
                },
                {
                    q: 'Step 2: Put 4 in place of k. What is <b>2 × 4</b>?',
                    opts: ['6', '8', '24', '42'],
                    a: 1,
                    show: '2 × 4 = 8',
                    h: ['2k means 2 × k.', 'k is 4.', '2 × 4 = 8'],
                    why: '2k with k = 4 is 2 × 4 = 8.',
                },
                {
                    q: 'Step 3: Add the 1. What does the ride cost?',
                    opts: ['$8', '$9', '$7', '$10'],
                    a: 1,
                    show: '8 + 1 = $9',
                    h: ['8 + 1 = ?', 'Add the fixed $1.', '$9'],
                    why: '8 + 1 = 9, so the ride costs $9.',
                },
            ],
        },
        you: [
            {
                t: 'choice',
                q: 'A shop sells juice for <span class="m">7y − 2</span> dollars (with a $2 coupon). Which part is the <b>variable</b>?',
                opts: ['7', 'y', '2', '−'],
                a: 1,
                box: true,
                h: ['A variable is a letter.', 'Which part could be any number?', 'y'],
                why: 'y is the letter standing for an unknown number of juices.',
            },
            {
                t: 'num',
                q: 'The mystery box holds <b>5</b> gold coins. What is <span class="m">□ + 3</span>?',
                pre: '□ + 3 =',
                ans: 8,
                box: 5,
                h: ['Put 5 into the box.', '5 + 3 = ?', '8'],
                why: 'With 5 in the box: 5 + 3 = 8 coins.',
            },
            {
                t: 'num',
                q: 'In a video game you score <span class="m">3x + 2</span> points: 3 per coin plus a 2-point bonus. You collect <b>x = 4</b> coins. What is your score?',
                pre: '3x + 2 =',
                ans: 14,
                h: ['3x means 3 × x.', '3 × 4 = 12.', '12 + 2 = 14.'],
                why: '3 × 4 = 12, then 12 + 2 = 14 points.',
            },
        ],
    },
    {
        name: 'Like-Terms Market',
        skill: 'Operations on expressions',
        intro:
            'At the market, like terms are like fruit: apples go with apples, bananas go with bananas. You can only add or subtract terms with the SAME letter. Multiply or divide EVERY term.',
        ido: {
            ctx: 'Fruit stall! Basket 1 has <span class="m">3a + 2b</span> (apples and bananas). Basket 2 has <span class="m">2a + b</span>. How much fruit altogether?',
            steps: [
                { mx: '(3a + 2b) + (2a + b)', say: 'I write both baskets with a plus sign between them.' },
                { mx: '3a + 2a = 5a', say: 'Apples go with apples: 3a + 2a = 5a.' },
                { mx: '2b + b = 3b', say: 'Bananas go with bananas: 2b + 1b = 3b.' },
                { mx: '5a + 3b', say: '5a and 3b are different fruit, so they stay apart. Done!' },
            ],
        },
        we: {
            ctx: 'You buy two snack packs: <span class="m">(6x + 5)</span> and <span class="m">(2x − 3)</span> after a $3 discount. Let’s add them together.',
            steps: [
                {
                    q: 'Step 1: Group the <b>x</b>-terms. What is <b>6x + 2x</b>?',
                    opts: ['8x', '12x', '4x', '8'],
                    a: 0,
                    show: '6x + 2x = 8x',
                    h: ['Same letter, so add the numbers in front.', '6 + 2 = 8.', '8x'],
                    why: '6x + 2x = 8x.',
                },
                {
                    q: 'Step 2: Now the numbers. What is <b>5 − 3</b>?',
                    opts: ['8', '2', '−2', '15'],
                    a: 1,
                    show: '5 − 3 = 2',
                    h: ['The second pack has −3.', '5 − 3 = ?', '2'],
                    why: '5 − 3 = 2.',
                },
                {
                    q: 'Step 3: Put it together. What is the answer?',
                    opts: ['8x + 2', '10x', '8x − 2', '2x + 8'],
                    a: 0,
                    show: 'answer: 8x + 2',
                    h: ['x-terms first, then the number.', '8x and 2.', '8x + 2'],
                    why: '8x + 2. These are unlike terms, so they stay separate.',
                },
            ],
        },
        you: [
            {
                t: 'choice',
                q: '<b>Subtract:</b> You had <span class="m">9y + 4</span> sweets and gave away <span class="m">3y + 1</span>. What is left?',
                opts: ['6y + 5', '12y + 5', '6y + 3', '6y − 3'],
                a: 2,
                h: ['The minus sign takes away EVERY term in the bracket.', '9y − 3y = 6y.', '4 − 1 = 3.'],
                why: '9y − 3y = 6y and 4 − 1 = 3, so 6y + 3.',
            },
            {
                t: 'choice',
                q: '<b>Multiply:</b> You pack <b>3 gift bags</b>, each holding <span class="m">2x + 5</span> items. Total items?',
                opts: ['6x + 5', '5x + 8', '6x + 15', '6x + 8'],
                a: 2,
                h: ['The 3 multiplies EVERY term.', '3 × 2x = 6x.', '3 × 5 = 15.'],
                why: '3(2x + 5) = 6x + 15.',
            },
            {
                t: 'choice',
                q: '<b>Divide:</b> <span class="m">12m + 8</span> stickers are shared equally among <b>4 friends</b>. How many does each get?',
                opts: ['3m + 8', '3m + 2', '12m + 2', '8m + 4'],
                a: 1,
                h: ['Divide EACH term by 4.', '12m ÷ 4 = 3m.', '8 ÷ 4 = 2.'],
                why: '(12m + 8) ÷ 4 = 3m + 2.',
            },
            {
                t: 'choice',
                q: '<b>Simplify:</b> A trader got <span class="m">4a</span> apples, then <span class="m">3a</span> more, then sold <span class="m">2a</span>.',
                opts: ['9a', '5a', 'a', '7a'],
                a: 1,
                h: ['All terms have a, so combine them.', '4 + 3 = 7.', '7 − 2 = 5.'],
                why: '4a + 3a − 2a = 5a.',
            },
        ],
    },
    {
        name: 'Balance Bridge',
        skill: 'Single-step equations',
        intro:
            'This rope bridge only holds if both sides balance! An equation is like a balance scale. To find x, do the OPPOSITE operation to BOTH sides so the scale stays level.',
        ido: {
            ctx: 'Kabir had some money, <b>x</b>. His grandma gave him <b>$7</b>, and now he has <b>$15</b>. How much did he start with?',
            scale: ['x + 7', '15'],
            steps: [
                { mx: 'x + 7 = 15', say: 'I write it as an equation. Both sides balance.' },
                { mx: '+7 is stuck to x', say: 'What is being done to x? 7 is added.' },
                { mx: 'x + 7 − 7 = 15 − 7', say: 'The opposite of +7 is −7. I do it to BOTH sides so the bridge stays level.' },
                { mx: 'x = 8', say: 'Kabir started with $8. Check: 8 + 7 = 15 ✓' },
            ],
        },
        we: {
            ctx: '5 friends share a pizza bill <b>equally</b>. The total is <b>$35</b>. Solve <span class="m">5m = 35</span> to find each share.',
            scale: ['5m', '35'],
            steps: [
                {
                    q: 'Step 1: What is being done to m?',
                    opts: ['+ 5', '× 5', '÷ 5', '− 5'],
                    a: 1,
                    show: '5m means 5 × m',
                    h: ['5m is 5 and m written together.', 'A number next to a letter means multiply.', '× 5'],
                    why: '5m means 5 × m.',
                },
                {
                    q: 'Step 2: What is the opposite of × 5?',
                    opts: ['+ 5', '− 5', '÷ 5', '× 5 again'],
                    a: 2,
                    show: 'divide both sides by 5',
                    h: ['Which operation undoes multiplying?', 'Multiply and divide are opposites.', '÷ 5'],
                    why: 'Dividing undoes multiplying, so divide both sides by 5.',
                },
                {
                    q: 'Step 3: What is <b>35 ÷ 5</b>? Each friend pays…',
                    opts: ['$5', '$6', '$7', '$30'],
                    a: 2,
                    show: 'm = $7',
                    h: ['5 × ? = 35', 'Count in 5s: 5, 10, 15…', '7'],
                    why: '35 ÷ 5 = 7, so each friend pays $7. Check: 5 × 7 = 35 ✓',
                },
            ],
        },
        you: [
            {
                t: 'num',
                q: 'Zara spent <b>$9</b> at the fair and now has <b>$4</b> left. Solve <span class="m">y − 9 = 4</span> to find what she started with.',
                pre: 'y =',
                ans: 13,
                scale: ['y − 9', '4'],
                h: ['9 is subtracted from y.', 'The opposite of −9 is +9.', '4 + 9 = 13.'],
                why: 'Add 9 to both sides: y = 13. Check: 13 − 9 = 4 ✓',
            },
            {
                t: 'num',
                q: 'A pizza is shared by <b>3</b> friends and each gets <b>6</b> slices. Solve <span class="m">n ÷ 3 = 6</span> to find the total slices.',
                pre: 'n =',
                ans: 18,
                scale: ['n ÷ 3', '6'],
                h: ['n is divided by 3.', 'The opposite of ÷ 3 is × 3.', '6 × 3 = 18.'],
                why: 'Multiply both sides by 3: n = 18. Check: 18 ÷ 3 = 6 ✓',
            },
            {
                t: 'num',
                q: 'You save the same amount each week for <b>4 weeks</b> and have <b>$28</b>. Solve <span class="m">4w = 28</span>.',
                pre: 'w =',
                ans: 7,
                scale: ['4w', '28'],
                h: ['4w means 4 × w.', 'Divide both sides by 4.', '28 ÷ 4 = 7.'],
                why: 'Divide both sides by 4: w = 7 dollars a week.',
            },
        ],
    },
    {
        name: 'Treasure Vault',
        skill: 'Two-step equations',
        intro:
            'The treasure vault has a double lock! Two-step equations need two moves. Undo in REVERSE order, like taking off your shoes before your socks: undo the + or − first, then the × or ÷.',
        ido: {
            ctx: 'A fun fair charges a <b>$5 entry fee</b> plus <b>$3 per ride</b>. Mia spends <b>$26</b>. How many rides did she take?',
            scale: ['5 + 3r', '26'],
            steps: [
                { mx: '5 + 3r = 26', say: 'r is the number of rides. Entry plus rides equals what she spent.' },
                { mx: '3r = 26 − 5 = 21', say: 'Lock 1: undo the + 5 first. Subtract 5 from both sides.' },
                { mx: 'r = 21 ÷ 3 = 7', say: 'Lock 2: undo the × 3. Divide both sides by 3.' },
                { mx: 'r = 7 rides', say: 'Check: 5 + 3 × 7 = 5 + 21 = 26 ✓' },
            ],
        },
        we: {
            ctx: 'A gym charges <b>$5 to join</b> plus <b>$2 per visit</b>. Leo paid <b>$17</b>. Solve <span class="m">2x + 5 = 17</span> together.',
            scale: ['2x + 5', '17'],
            steps: [
                {
                    q: 'Step 1: Which lock do we open first?',
                    opts: ['Divide by 2 first', 'Subtract 5 first', 'Add 5 first', 'Multiply by 2'],
                    a: 1,
                    show: 'subtract 5 from both sides',
                    h: ['Shoes before socks: undo the last thing done.', '+ 5 was added last.', 'Subtract 5 first.'],
                    why: 'Undo the + 5 first by subtracting 5 from both sides.',
                },
                {
                    q: 'Step 2: What is <b>17 − 5</b>? So 2x = …',
                    opts: ['22', '12', '10', '11'],
                    a: 1,
                    show: '2x = 12',
                    h: ['17 − 5 = ?', 'Count back 5 from 17.', '12'],
                    why: '17 − 5 = 12, so 2x = 12.',
                },
                {
                    q: 'Step 3: Divide by 2. How many visits?',
                    opts: ['6', '24', '10', '5'],
                    a: 0,
                    show: 'x = 6 visits',
                    h: ['12 ÷ 2 = ?', 'Half of 12.', '6'],
                    why: '12 ÷ 2 = 6 visits. Check: 2 × 6 + 5 = 17 ✓',
                },
            ],
        },
        you: [
            {
                t: 'num',
                q: 'You buy <b>3</b> packs of cards and use a <b>$4</b> coupon. You pay <b>$11</b>. Solve <span class="m">3y − 4 = 11</span> for the price of one pack.',
                pre: 'y =',
                ans: 5,
                scale: ['3y − 4', '11'],
                h: ['Undo the − 4 first by adding 4.', '3y = 15.', '15 ÷ 3 = 5.'],
                why: '3y − 4 = 11 → 3y = 15 → y = $5.',
            },
            {
                t: 'num',
                q: 'A taxi charges <b>$3</b> plus <b>$2 per km</b>. The ride cost <b>$15</b>. Solve <span class="m">3 + 2k = 15</span>.',
                pre: 'k =',
                ans: 6,
                scale: ['3 + 2k', '15'],
                h: ['Subtract the $3 first.', '2k = 12.', '12 ÷ 2 = 6.'],
                why: '3 + 2k = 15 → 2k = 12 → k = 6 km.',
            },
            {
                t: 'num',
                q: 'Final lock (tricky!): <span class="m">5 − 2p = 1</span>',
                pre: 'p =',
                ans: 2,
                scale: ['5 − 2p', '1'],
                h: ['Subtract 5 from both sides first.', '−2p = −4.', 'Divide by −2.'],
                why: '5 − 2p = 1 → −2p = −4 → p = 2. Check: 5 − 4 = 1 ✓',
            },
        ],
    },
];

const STOPS = STOPS_RAW.map((s) => ({
    ...s,
    qs: s.we.steps.map((x) => ({ ...x, t: 'choice', we: true })).concat(s.you),
}));

const MAX = STOPS.reduce((a, s) => a + s.qs.length * 3, 0);
const PUZZLE_COUNT = STOPS.reduce((a, s) => a + s.qs.length, 0);

const WIN_LINES = [
    'You found the Word Jungle map piece! Expressions are math phrases with no equals sign.',
    'Cave cleared! You know a variable is a letter that can stand for different numbers.',
    'Market day success! Like terms combine; unlike terms stay apart.',
    'The bridge is steady! Undo the same operation on both sides of an equation.',
    'The vault is open! Two-step equations unwind in reverse order.',
];

function loadRes() {
    try {
        const r = JSON.parse(localStorage.getItem(KEY) || 'null');
        if (r && r.res) return r.res;
    } catch {
        /* ignore */
    }
    return {};
}

function saveRes(res) {
    try {
        localStorage.setItem(KEY, JSON.stringify({ res }));
    } catch {
        /* ignore */
    }
}

function Parrot() {
    return (
        <svg viewBox="0 0 52 52" aria-hidden="true">
            <circle cx="26" cy="26" r="25" fill="#E6F2FF" />
            <path d="M14 44 C10 30 16 14 28 12 C38 11 42 20 40 28 C38 38 30 44 14 44Z" fill="#73B64B" />
            <path d="M18 42 C18 34 22 28 30 28 L26 44Z" fill="#3F7BE0" />
            <path d="M36 18 C44 18 46 24 42 28 C40 24 38 22 36 22Z" fill="#F5C64F" />
            <circle cx="32" cy="19" r="3.2" fill="#fff" />
            <circle cx="33" cy="19" r="1.6" fill="#241A7A" />
            <path d="M22 12 C20 6 26 4 28 10" fill="#E65E8C" />
        </svg>
    );
}

function IslandSVG() {
    return (
        <svg className="isle" viewBox="0 0 210 190" role="img" aria-label="Treasure island with a palm tree and a chest marked x">
            <rect x="0" y="120" width="210" height="70" rx="18" fill="#3F7BE0" />
            <path d="M0 140 q15 -8 30 0 t30 0 t30 0 t30 0 t30 0 t30 0 t30 0" fill="none" stroke="#9CC0F5" strokeWidth="4" />
            <ellipse cx="105" cy="128" rx="88" ry="26" fill="#F5D98A" stroke="#241A7A" strokeWidth="3" />
            <path d="M120 124 C118 90 124 62 134 40" fill="none" stroke="#8A5A2B" strokeWidth="9" strokeLinecap="round" />
            <path
                d="M134 40 C110 30 92 40 86 52 C102 44 118 44 134 40Z M134 40 C150 22 170 22 182 32 C166 32 150 36 134 40Z M134 40 C126 18 136 6 148 4 C142 16 138 28 134 40Z M134 40 C156 40 172 52 174 66 C162 54 148 46 134 40Z"
                fill="#73B64B"
                stroke="#241A7A"
                strokeWidth="2.5"
                strokeLinejoin="round"
            />
            <rect x="56" y="100" width="44" height="28" rx="5" fill="#C2620F" stroke="#241A7A" strokeWidth="3" />
            <path d="M56 108 h44" stroke="#241A7A" strokeWidth="3" />
            <rect x="74" y="104" width="8" height="10" rx="2" fill="#F5C64F" stroke="#241A7A" strokeWidth="2" />
            <text x="150" y="146" fontFamily="'Baloo 2',sans-serif" fontWeight="800" fontSize="30" fill="#C23A6B">
                x
            </text>
            <circle cx="40" cy="40" r="18" fill="#F5C64F" />
        </svg>
    );
}

function BoxSVG({ value }) {
    const label = value !== undefined ? String(value) : '?';
    const right = value !== undefined ? '+ 3' : '= any number';
    return (
        <svg viewBox="0 0 300 110" width="300" role="img" aria-label={value !== undefined ? `Mystery box holding ${value}` : 'Mystery box'}>
            <rect x="40" y="20" width="80" height="70" rx="10" fill="#7B3CC6" stroke="#241A7A" strokeWidth="3" />
            <path d="M40 40 h80" stroke="#241A7A" strokeWidth="3" />
            <text x="80" y="76" textAnchor="middle" fontFamily="'Baloo 2',sans-serif" fontWeight="800" fontSize="30" fill="#fff">
                {label}
            </text>
            <text x="160" y="68" fontFamily="'Baloo 2',sans-serif" fontWeight="800" fontSize="30" fill="#241A7A">
                {right}
            </text>
        </svg>
    );
}

function ScaleSVG({ left, right, level }) {
    const ang = level ? 0 : -8;
    return (
        <svg viewBox="0 0 360 170" width="360" role="img" aria-label={`Balance scale: ${left} on the left, ${right} on the right${level ? ', balanced' : ''}`}>
            <path d="M180 40 L160 150 h40 Z" fill="#8A5A2B" stroke="#241A7A" strokeWidth="3" />
            <rect x="130" y="148" width="100" height="12" rx="4" fill="#8A5A2B" stroke="#241A7A" strokeWidth="3" />
            <g className="beam" style={{ transform: `rotate(${ang}deg)` }}>
                <rect x="30" y="34" width="300" height="10" rx="5" fill="#241A7A" />
                <line x1="60" y1="44" x2="60" y2="70" stroke="#241A7A" strokeWidth="3" />
                <line x1="300" y1="44" x2="300" y2="70" stroke="#241A7A" strokeWidth="3" />
                <rect x="8" y="70" width="104" height="44" rx="12" fill="#2F6BD6" stroke="#241A7A" strokeWidth="3" />
                <text x="60" y="100" textAnchor="middle" fontFamily="Nunito,sans-serif" fontWeight="800" fontSize="19" fill="#fff">
                    {left}
                </text>
                <rect x="248" y="70" width="104" height="44" rx="12" fill="#C2620F" stroke="#241A7A" strokeWidth="3" />
                <text x="300" y="100" textAnchor="middle" fontFamily="Nunito,sans-serif" fontWeight="800" fontSize="19" fill="#fff">
                    {right}
                </text>
            </g>
            <circle cx="180" cy="39" r="8" fill={level ? '#73B64B' : '#F5C64F'} stroke="#241A7A" strokeWidth="3" />
        </svg>
    );
}

function Phases({ cur }) {
    const labels = [
        ['I DO', 'Pico shows you'],
        ['WE DO', 'Solve it together'],
        ['YOU DO', 'On your own'],
    ];
    return (
        <div className="phases" aria-label="Learning steps">
            {labels.map((p, i) => (
                <span key={p[0]} className={`ph${i === cur ? ' on' : ''}${i < cur ? ' done' : ''}`} title={p[1]}>
                    {p[0]} · {p[1]}
                </span>
            ))}
        </div>
    );
}

function HtmlBlock({ className, html }) {
    return <div className={className} dangerouslySetInnerHTML={{ __html: html }} />;
}

export default function TreasureAlgebraIsland() {
    const { claim } = useGameXp();
    const [screen, setScreen] = useState('intro');
    const [st, setSt] = useState(0);
    const [q, setQ] = useState(0);
    const [res, setRes] = useState(loadRes);
    const [tries, setTries] = useState(0);
    const [hints, setHints] = useState(0);
    const [done, setDone] = useState(false);
    const [pick, setPick] = useState(null);
    const [val, setVal] = useState('');
    const [msg, setMsg] = useState(null);
    const [ds, setDs] = useState(0);
    const ansRef = useRef(null);
    const xpClaimed = useRef(false);

    const totalCoins = useMemo(() => Object.values(res).reduce((a, b) => a + b, 0), [res]);

    const stopDone = useCallback(
        (i) => STOPS[i].qs.every((_, j) => res[`${i}-${j}`]),
        [res],
    );

    const stopCoins = useCallback(
        (i) => STOPS[i].qs.reduce((a, _, j) => a + (res[`${i}-${j}`] || 0), 0),
        [res],
    );

    const openStop = useCallback((i) => i === 0 || stopDone(i - 1), [stopDone]);

    const mapPieces = useMemo(() => STOPS.filter((_, i) => stopDone(i)).length, [stopDone]);

    const persistRes = useCallback((next) => {
        setRes(next);
        saveRes(next);
    }, []);

    const fresh = useCallback(() => {
        setTries(0);
        setHints(0);
        setDone(false);
        setPick(null);
        setVal('');
        setMsg(null);
    }, []);

    const startQs = useCallback(() => {
        const qs = STOPS[st].qs;
        let nextQ = 0;
        for (let j = 0; j < qs.length; j++) {
            if (!res[`${st}-${j}`]) {
                nextQ = j;
                break;
            }
        }
        setQ(nextQ);
        fresh();
        setScreen('question');
    }, [st, res, fresh]);

    useEffect(() => {
        if (screen !== 'end' || xpClaimed.current) return;
        xpClaimed.current = true;
        const earned = Math.min(20, Math.max(1, Math.round((totalCoins / MAX) * 20)));
        claim(earned);
    }, [screen, totalCoins, claim]);

    useEffect(() => {
        if (screen !== 'question' || done || msg) return;
        const Q = STOPS[st]?.qs[q];
        if (Q?.t === 'num') {
            ansRef.current?.focus({ preventScroll: true });
        }
    }, [screen, st, q, done, msg]);

    const check = () => {
        const Q = STOPS[st].qs[q];
        let ok;
        if (Q.t === 'choice') {
            if (pick === null) {
                setMsg({ type: 'hint', text: 'Pick an answer first, explorer!' });
                return;
            }
            ok = pick === Q.a;
        } else {
            const v = parseFloat(String(val).trim().replace('−', '-'));
            if (Number.isNaN(v)) {
                setMsg({ type: 'hint', text: 'Type a number in the box first.' });
                return;
            }
            ok = v === Q.ans;
        }
        if (ok) {
            setDone(true);
            const c = Math.max(1, 3 - hints - tries);
            const k = `${st}-${q}`;
            const next = { ...res, [k]: Math.max(res[k] || 0, c) };
            persistRes(next);
            setMsg({ type: 'ok', text: `+${c} gold coin${c > 1 ? 's' : ''}! ${Q.why}` });
        } else {
            setTries((t) => t + 1);
            setMsg({
                type: 'no',
                text: Q.scale
                    ? 'The bridge wobbles! Plug your answer back into the equation. Do both sides match?'
                    : 'Not quite! Read it again, or ask Pico for a hint.',
            });
        }
    };

    const resetAll = () => {
        persistRes({});
        setScreen('intro');
    };

    const s = STOPS[st];
    const Q = screen === 'question' ? s.qs[q] : null;
    const weN = s?.we?.steps?.length ?? 0;

    let rank = 'Brave Deckhand';
    if (totalCoins / MAX >= 0.9) rank = 'Legendary Captain';
    else if (totalCoins / MAX >= 0.7) rank = 'First Mate';
    else if (totalCoins / MAX >= 0.5) rank = 'Treasure Hunter';

    return (
        <div className="tai">
            <div className="wrap">
                <header>
                    <div className="brand">YounimatiQ Explorers</div>
                    <div className="hud">
                        <span className="chip" title="Gold coins">
                            <span className="coin" aria-hidden="true" />
                            {totalCoins} / {MAX}
                        </span>
                        <span className="chip" title="Map pieces found">
                            Map {mapPieces} / 5
                        </span>
                    </div>
                </header>

                <main className="screen">
                    {screen === 'intro' ? (
                        <>
                            <section className="hero pop">
                                <div>
                                    <div className="eyebrow">YounimatiQ Explorers · Algebra Mission</div>
                                    <h1>
                                        Treasure of <span>Algebra Island</span>
                                    </h1>
                                    <p>
                                        Long ago, Captain Coefficient hid her gold on Algebra Island and tore the treasure map into <b>5 pieces</b>. Each
                                        piece is guarded by a math puzzle.
                                    </p>
                                    <p>
                                        Sail from Word Jungle to the Treasure Vault, solve <b>{PUZZLE_COUNT} puzzles</b>, and collect as many{' '}
                                        <b>gold coins</b> as you can.
                                    </p>
                                    <div className="row" style={{ marginTop: 12 }}>
                                        <button type="button" className="btn" onClick={() => setScreen('map')}>
                                            Set sail!
                                        </button>
                                        {totalCoins ? (
                                            <button type="button" className="btn ghost" onClick={resetAll}>
                                                Start over
                                            </button>
                                        ) : null}
                                    </div>
                                </div>
                                <IslandSVG />
                            </section>
                            <div className="guide">
                                <Parrot />
                                <div>
                                    <div className="who">PICO THE PARROT · YOUR GUIDE</div>
                                    <p>
                                        At every stop I solve one first (<b>I do</b>), then we solve one together step by step (<b>We do</b>), then it’s
                                        your turn (<b>You do</b>). Every answer is worth up to <b>3 gold coins</b>. You can ask me for a hint, but each hint
                                        costs a coin, and so does a wrong answer. Think first, then answer!
                                    </p>
                                </div>
                            </div>
                        </>
                    ) : null}

                    {screen === 'map' ? (
                        <>
                            <div className="guide pop">
                                <Parrot />
                                <div>
                                    <div className="who">PICO</div>
                                    <p>
                                        {totalCoins
                                            ? 'Nice sailing! Head to the next glowing stop on the trail.'
                                            : 'Here is the island trail. Our first stop is Word Jungle!'}
                                    </p>
                                </div>
                            </div>
                            <nav className="trail" aria-label="Island trail">
                                <span className="x">X marks the spot</span>
                                <ol>
                                    {STOPS.map((stop, i) => {
                                        const o = openStop(i);
                                        const d = stopDone(i);
                                        const cur = o && !d;
                                        const c = stopCoins(i);
                                        return (
                                            <li key={stop.name}>
                                                <button
                                                    type="button"
                                                    className={`stop${cur ? ' cur' : ''}`}
                                                    disabled={!o}
                                                    aria-label={`Stop ${i + 1}: ${stop.name}${o ? '' : ' (locked)'}`}
                                                    onClick={() => {
                                                        setSt(i);
                                                        setScreen('brief');
                                                    }}
                                                >
                                                    <span className="dot" style={{ background: COL[i] }}>
                                                        {i + 1}
                                                    </span>
                                                    <span className="nm">{stop.name}</span>
                                                    <span className="st">
                                                        {d ? `${c}/${stop.qs.length * 3} coins` : o ? 'Ready!' : 'Locked'}
                                                    </span>
                                                </button>
                                            </li>
                                        );
                                    })}
                                </ol>
                            </nav>
                            {STOPS.every((_, i) => stopDone(i)) ? (
                                <div className="row">
                                    <button type="button" className="btn" onClick={() => setScreen('end')}>
                                        Open the treasure report
                                    </button>
                                </div>
                            ) : null}
                        </>
                    ) : null}

                    {screen === 'brief' ? (
                        <section className="card pop">
                            <div className="top">
                                <span className="tag" style={{ background: COL[st] }}>
                                    Stop {st + 1} · {s.skill}
                                </span>
                            </div>
                            <h2 style={{ fontSize: 'clamp(28px,5vw,40px)' }}>{s.name}</h2>
                            <div className="guide">
                                <Parrot />
                                <div>
                                    <div className="who">PICO EXPLAINS</div>
                                    <p>{s.intro}</p>
                                </div>
                            </div>
                            <div className="actions">
                                <button type="button" className="btn ghost" onClick={() => setScreen('map')}>
                                    Back to the trail
                                </button>
                                <button
                                    type="button"
                                    className="btn"
                                    onClick={() => {
                                        setDs(0);
                                        setScreen('ido');
                                    }}
                                >
                                    Watch Pico solve one (I do)
                                </button>
                                {stopCoins(st) ? (
                                    <button type="button" className="btn ghost" onClick={startQs}>
                                        Skip to the puzzles
                                    </button>
                                ) : null}
                            </div>
                        </section>
                    ) : null}

                    {screen === 'ido' ? (
                        <section className="card pop">
                            <div className="top">
                                <span className="tag" style={{ background: COL[st] }}>
                                    Stop {st + 1} · {s.name}
                                </span>
                            </div>
                            <Phases cur={0} />
                            <HtmlBlock className="ctx" html={s.ido.ctx} />
                            {s.ido.scale ? (
                                <div className="vis">
                                    <ScaleSVG left={s.ido.scale[0]} right={s.ido.scale[1]} level={ds >= s.ido.steps.length - 1} />
                                </div>
                            ) : null}
                            {s.ido.box ? (
                                <div className="vis">
                                    <BoxSVG value={ds >= 1 ? 5 : undefined} />
                                </div>
                            ) : null}
                            <ol className="steps">
                                {s.ido.steps.slice(0, ds + 1).map((step, i) => (
                                    <li key={step.mx} className="pop">
                                        <span className="n">{i + 1}</span>
                                        <div>
                                            <div className="mx">{step.mx}</div>
                                            <div className="say">Pico: {step.say}</div>
                                        </div>
                                    </li>
                                ))}
                            </ol>
                            <div className="actions">
                                <button
                                    type="button"
                                    className="btn ghost"
                                    onClick={() => {
                                        if (ds > 0) setDs(ds - 1);
                                        else setScreen('brief');
                                    }}
                                >
                                    Back
                                </button>
                                {ds < s.ido.steps.length - 1 ? (
                                    <button type="button" className="btn" onClick={() => setDs(ds + 1)}>
                                        Show next step ({ds + 2} of {s.ido.steps.length})
                                    </button>
                                ) : (
                                    <button
                                        type="button"
                                        className="btn"
                                        onClick={() => {
                                            setQ(0);
                                            fresh();
                                            setScreen('question');
                                        }}
                                    >
                                        Now let’s do one together (We do)
                                    </button>
                                )}
                            </div>
                        </section>
                    ) : null}

                    {screen === 'question' && Q ? (
                        <section className="card pop">
                            <div className="top">
                                <span className="tag" style={{ background: COL[st] }}>
                                    Stop {st + 1} · {s.name}
                                </span>
                                <div className="prog" aria-label={`Puzzle ${q + 1} of ${s.qs.length}`}>
                                    {s.qs.map((_, j) => (
                                        <i key={j} className={res[`${st}-${j}`] ? 'done' : j === q ? 'now' : ''} />
                                    ))}
                                </div>
                            </div>
                            <Phases cur={Q.we ? 1 : 2} />
                            {Q.we ? (
                                <>
                                    <HtmlBlock className="ctx" html={s.we.ctx} />
                                    {(() => {
                                        const prev = s.we.steps.slice(0, q).map((x) => x.show).concat(done && Q.show ? [Q.show] : []);
                                        if (!prev.length) return null;
                                        return (
                                            <div className="work">
                                                <span className="lab">OUR WORKING SO FAR</span>
                                                {prev.map((t) => (
                                                    <span key={t}>{t}</span>
                                                ))}
                                            </div>
                                        );
                                    })()}
                                </>
                            ) : null}
                            <HtmlBlock className="q" html={Q.q} />
                            {(() => {
                                const sc = Q.scale || (Q.we && s.we.scale);
                                if (sc) {
                                    const level = Q.we ? done && q === weN - 1 : done;
                                    return (
                                        <div className="vis">
                                            <ScaleSVG left={sc[0]} right={sc[1]} level={level} />
                                        </div>
                                    );
                                }
                                if (Q.box !== undefined && Q.box !== true) {
                                    return (
                                        <div className="vis">
                                            <BoxSVG value={Q.box} />
                                        </div>
                                    );
                                }
                                if (Q.box === true) {
                                    return (
                                        <div className="vis">
                                            <BoxSVG />
                                        </div>
                                    );
                                }
                                return null;
                            })()}
                            {Q.t === 'choice' ? (
                                <div className="opts" role="group" aria-label="Answer choices">
                                    {Q.opts.map((o, k) => {
                                        let c = '';
                                        if (done && k === Q.a) c = 'right';
                                        else if (msg?.type === 'no' && pick === k) c = 'wrong';
                                        else if (pick === k) c = 'sel';
                                        return (
                                            <button
                                                key={o}
                                                type="button"
                                                className={`opt ${c}`.trim()}
                                                disabled={done}
                                                onClick={() => {
                                                    setPick(k);
                                                    if (msg?.type === 'no') setMsg(null);
                                                }}
                                            >
                                                {o}
                                            </button>
                                        );
                                    })}
                                </div>
                            ) : (
                                <div className="ansrow">
                                    <label htmlFor="tai-ans">{Q.pre}</label>
                                    <input
                                        ref={ansRef}
                                        id="tai-ans"
                                        inputMode="numeric"
                                        autoComplete="off"
                                        placeholder="?"
                                        value={val}
                                        disabled={done}
                                        onChange={(e) => setVal(e.target.value)}
                                        onKeyDown={(e) => {
                                            if (e.key === 'Enter') check();
                                        }}
                                    />
                                </div>
                            )}
                            {msg ? (
                                <div className={`fb ${msg.type} pop`} role="status">
                                    {msg.text}
                                </div>
                            ) : null}
                            <div className="actions">
                                {done ? (
                                    <>
                                        <button type="button" className="btn ghost" onClick={() => setScreen('map')}>
                                            Island trail
                                        </button>
                                        <button
                                            type="button"
                                            className="btn"
                                            onClick={() => {
                                                if (q === s.qs.length - 1) {
                                                    setScreen('win');
                                                } else {
                                                    setQ(q + 1);
                                                    fresh();
                                                }
                                            }}
                                        >
                                            {q === s.qs.length - 1
                                                ? 'Grab the map piece'
                                                : Q.we && q === weN - 1
                                                  ? 'Your turn! (You do)'
                                                  : Q.we
                                                    ? 'Next step'
                                                    : 'Next puzzle'}
                                        </button>
                                    </>
                                ) : (
                                    <>
                                        <button
                                            type="button"
                                            className="btn hint"
                                            disabled={Q.h.length - hints <= 0}
                                            onClick={() => {
                                                setMsg({ type: 'hint', text: `Pico's hint ${hints + 1}: ${Q.h[hints]}` });
                                                setHints((h) => h + 1);
                                            }}
                                        >
                                            {Q.h.length - hints > 0
                                                ? `Ask Pico for a hint (${Q.h.length - hints} left)`
                                                : 'No hints left'}
                                        </button>
                                        <button type="button" className="btn" onClick={check}>
                                            Check answer
                                        </button>
                                    </>
                                )}
                            </div>
                        </section>
                    ) : null}

                    {screen === 'win' ? (
                        <section className="card end pop">
                            <span className="tag" style={{ background: COL[st] }}>
                                Map piece {st + 1} of 5 found
                            </span>
                            <h2 style={{ fontSize: 'clamp(28px,5vw,40px)' }}>{s.name} complete!</h2>
                            <div className="coins">
                                {stopCoins(st)} / {s.qs.length * 3} coins
                            </div>
                            <div className="guide" style={{ textAlign: 'left', maxWidth: 600 }}>
                                <Parrot />
                                <div>
                                    <div className="who">PICO</div>
                                    <p>{WIN_LINES[st]}</p>
                                </div>
                            </div>
                            <div className="row" style={{ justifyContent: 'center' }}>
                                <button
                                    type="button"
                                    className="btn ghost"
                                    onClick={() => {
                                        setDs(0);
                                        setScreen('ido');
                                    }}
                                >
                                    Replay this stop
                                </button>
                                <button
                                    type="button"
                                    className="btn"
                                    onClick={() => setScreen(st === STOPS.length - 1 ? 'end' : 'map')}
                                >
                                    {st === STOPS.length - 1 ? 'Open the treasure chest' : 'Back to the trail'}
                                </button>
                            </div>
                        </section>
                    ) : null}

                    {screen === 'end' ? (
                        <section className="card end pop">
                            <span className="tag" style={{ background: 'var(--orange)' }}>
                                Treasure found
                            </span>
                            <h2 style={{ fontSize: 'clamp(26px,5vw,38px)' }}>You found Captain Coefficient&apos;s gold!</h2>
                            <div className="coins">
                                {totalCoins} / {MAX} gold coins
                            </div>
                            <div>Your explorer title</div>
                            <div className="rank">{rank}</div>
                            <div className="log">
                                {STOPS.map((stop, i) => (
                                    <div key={stop.name}>
                                        <span style={{ color: COL[i] }}>●</span> {stop.name}
                                        <br />
                                        {stopCoins(i)}/{stop.qs.length * 3}
                                    </div>
                                ))}
                            </div>
                            <p style={{ maxWidth: '54ch', color: 'var(--mute)', margin: 0 }}>
                                You mastered expressions, variables, operations on expressions, and single- and two-step equations. Replay any stop to
                                collect every coin!
                            </p>
                            <GameXpNote />
                            <div className="row" style={{ justifyContent: 'center' }}>
                                <button type="button" className="btn ghost" onClick={() => setScreen('map')}>
                                    Island trail
                                </button>
                                <button type="button" className="btn" onClick={resetAll}>
                                    Play again from the start
                                </button>
                            </div>
                        </section>
                    ) : null}
                </main>

                <p className="foot">© YounimatiQ · Future-Fit Education · Algebra: Expressions and Equations</p>
            </div>
        </div>
    );
}