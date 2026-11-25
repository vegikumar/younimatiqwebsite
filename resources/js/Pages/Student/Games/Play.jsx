import { Head, Link } from '@inertiajs/react';
import { lazy, Suspense } from 'react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import { GameXpProvider } from '../../../games/GameXp';
import HtmlGamePlayer from '../../../games/HtmlGamePlayer';

const GAMES = {
    'friendship-quest': lazy(() => import('../../../games/FriendshipQuest')),
    'story-crafting-table': lazy(() => import('../../../games/StoryCraftingTable')),
    'rebuild-the-base': lazy(() => import('../../../games/RebuildTheBase')),
    'noun-miner': lazy(() => import('../../../games/NounMiner')),
    'word-mining-tylers-story': lazy(() => import('../../../games/WordMining')),
    'story-treasure-map': lazy(() => import('../../../games/StoryTreasureMap')),
    'math-dash': lazy(() => import('../../../games/MathDash')),
    'verb-detective': lazy(() => import('../../../games/VerbDetective')),
    'alien-verb-lab': lazy(() => import('../../../games/AlienVerbLab')),
    'labyrinth-grammar': lazy(() => import('../../../games/LabyrinthGrammar')),
    'percy-jackson-ela-class-1': lazy(() => import('../../../games/PercySyllableQuest')),
    'inequalities-in-class-game': lazy(() => import('../../../games/MissionCrocodileGate')),
    'treasure-of-algebra-island': lazy(() => import('../../../games/TreasureAlgebraIsland')),
    'inequalities-practice-worksheet': lazy(() => import('../../../games/InequalitiesPracticeWorksheet')),
    'power-up-snowed-in-house': lazy(() => import('../../../games/SnowedInHouse')),
    'algebra-world-quest': lazy(() => import('../../../games/AlgebraWorldQuest')),
};

export default function Play({ game, backUrl = '/student/activities', xp = null }) {
    const Game = GAMES[game.key];
    const isHtml = Boolean(game.html_play || game.embed_url);
    const adminPreview = String(backUrl || '').includes('/admin/');

    return (
        <DashboardLayout title={game.title}>
            <Head title={game.title}>
                <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Anton&family=Fredoka:wght@400;600&family=Lexend:wght@400;600;700&family=Montserrat:wght@500;700;800&family=Pirata+One&family=Pixelify+Sans:wght@500;700&family=Press+Start+2P&family=Silkscreen:wght@400;700&family=Baloo+2:wght@500;700;800&family=Cinzel:wght@600;800&family=Nunito:wght@500;700;800&display=swap" />
            </Head>
            <div className="mb-4">
                <Link href={backUrl} className="text-sm font-bold text-primary">← Back</Link>
            </div>
            {!isHtml && xp?.max ? (
                <p className="mb-4 text-sm font-bold text-on-surface-variant">
                    {xp.awarded
                        ? `${xp.points} XP already added from this game.`
                        : `Play and learn. Your points are added to XP once, up to ${xp.max}.`}
                </p>
            ) : null}
            {isHtml && adminPreview && game.embed_url ? (
                <iframe
                    title={game.title}
                    src={game.embed_url}
                    className="w-full min-h-[calc(100vh-8rem)] rounded-xl border border-outline-variant bg-white"
                />
            ) : isHtml ? (
                <HtmlGamePlayer game={game} xp={xp} />
            ) : Game ? (
                <GameXpProvider gameKey={game.key} already={xp}>
                    <Suspense fallback={<p className="p-6 font-bold">Opening the game…</p>}>
                        <Game />
                    </Suspense>
                </GameXpProvider>
            ) : (
                <p className="p-6">This game is not ready yet.</p>
            )}
        </DashboardLayout>
    );
}
