<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Younimatiq — Unslop preview (home)</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600&family=Instrument+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f7f4ee;
            --card: #fdfbf7;
            --raised: #efe9df;
            --ink: #1f1b16;
            --secondary: #5e554a;
            --muted: #7b7266;
            --hairline: rgba(31, 27, 22, 0.08);
            --accent: #5b21b6;
            --accent-soft: #ede9fe;
            --positive: #16a34a;
            --radius-control: 10px;
            --radius-card: 16px;
            --radius-panel: 20px;
            --ease: cubic-bezier(0.2, 0.8, 0.2, 1);
        }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            margin: 0;
            font-family: "Instrument Sans", system-ui, sans-serif;
            color: var(--ink);
            background: var(--bg);
            line-height: 1.5;
        }
        a { color: inherit; text-decoration: none; }
        img { max-width: 100%; display: block; }
        .wrap { width: min(1200px, calc(100% - 48px)); margin: 0 auto; }
        .preview-banner {
            background: var(--ink);
            color: #efe9de;
            font-size: 13px;
            padding: 10px 16px;
            text-align: center;
        }
        .preview-banner a { color: #c4b5fd; font-weight: 500; }
        header.site {
            position: sticky; top: 0; z-index: 20;
            backdrop-filter: blur(10px);
            background: rgba(247, 244, 238, 0.88);
            border-bottom: 1px solid var(--hairline);
        }
        .nav {
            display: flex; align-items: center; justify-content: space-between;
            min-height: 64px; gap: 24px;
        }
        .brand { display: flex; align-items: center; gap: 10px; font-weight: 600; font-size: 18px; }
        .brand img { width: 32px; height: 32px; }
        .nav-links { display: flex; gap: 24px; font-size: 15px; color: var(--secondary); font-weight: 500; }
        .nav-links a:hover { color: var(--ink); }
        .nav-actions { display: flex; align-items: center; gap: 16px; }
        .link-quiet { font-size: 15px; font-weight: 500; color: var(--secondary); }
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            height: 44px; padding: 0 20px; border-radius: var(--radius-control);
            font-size: 15px; font-weight: 500; border: 1px solid transparent;
            transition: background-color 160ms var(--ease), color 160ms var(--ease), border-color 160ms var(--ease);
        }
        .btn-primary { background: var(--accent); color: #fff; }
        .btn-primary:hover { background: #4c1d95; }
        .btn-secondary { background: var(--card); border-color: var(--hairline); color: var(--ink); }
        .btn-secondary:hover { background: var(--raised); }
        .btn-lg { height: 48px; padding: 0 24px; }

        .hero {
            position: relative;
            min-height: calc(100vh - 104px);
            display: grid;
            align-items: stretch;
            overflow: hidden;
            border-bottom: 1px solid var(--hairline);
        }
        .hero-scene {
            position: absolute; inset: 0;
            background:
                linear-gradient(90deg, rgba(247,244,238,0.94) 0%, rgba(247,244,238,0.78) 34%, rgba(247,244,238,0.18) 58%, rgba(247,244,238,0.05) 100%),
                url('/images/unslop-younimatiq-hero.webp') center/cover no-repeat;
        }
        .hero-inner {
            position: relative; z-index: 1;
            display: grid; grid-template-columns: 1.05fr 0.95fr; gap: 40px;
            align-items: end; padding: 64px 0 0;
            min-height: calc(100vh - 104px);
        }
        .hero-copy { max-width: 520px; padding-bottom: 72px; }
        .eyebrow {
            display: inline-flex; align-items: center; gap: 8px;
            font-size: 13px; font-weight: 500; color: var(--muted); margin-bottom: 20px;
        }
        .eyebrow i {
            width: 8px; height: 8px; border-radius: 999px; background: var(--accent);
            display: inline-block;
        }
        h1 {
            font-family: Fraunces, Georgia, serif;
            font-weight: 600; font-size: clamp(40px, 5.4vw, 64px);
            line-height: 1.05; letter-spacing: -0.02em; margin: 0 0 20px;
        }
        .lede { font-size: 18px; color: var(--secondary); margin: 0 0 28px; max-width: 42ch; }
        .cta-row { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 20px; }
        .trust { font-size: 14px; color: var(--muted); display: flex; gap: 16px; flex-wrap: wrap; }
        .trust strong { color: var(--ink); font-weight: 600; }

        .hero-panel {
            background: var(--card);
            border: 1px solid var(--hairline);
            border-radius: var(--radius-panel) var(--radius-panel) 0 0;
            padding: 24px;
            box-shadow: 0 -12px 40px rgba(31, 27, 22, 0.06);
            min-height: 420px;
        }
        .panel-label { font-size: 13px; color: var(--muted); margin-bottom: 8px; }
        .panel-title { font-size: 18px; font-weight: 600; margin: 0 0 20px; }
        .session {
            display: grid; gap: 12px;
        }
        .session-row {
            display: grid; grid-template-columns: 40px 1fr auto; gap: 12px; align-items: center;
            padding: 12px; border-radius: 12px; background: var(--bg); border: 1px solid var(--hairline);
        }
        .avatar {
            width: 40px; height: 40px; border-radius: 12px;
            background: var(--accent-soft); color: var(--accent);
            display: grid; place-items: center; font-weight: 600; font-size: 14px;
        }
        .session-row strong { display: block; font-size: 14px; font-weight: 600; }
        .session-row span { font-size: 13px; color: var(--muted); }
        .pill {
            font-size: 12px; font-weight: 500; color: var(--accent);
            background: var(--accent-soft); border-radius: 8px; padding: 6px 10px;
        }
        .progress {
            margin-top: 20px; padding: 16px; border-radius: 12px; border: 1px solid var(--hairline); background: #fff;
        }
        .progress-top { display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 10px; }
        .bar { height: 8px; border-radius: 999px; background: var(--raised); overflow: hidden; }
        .bar > i { display: block; height: 100%; width: 68%; background: var(--accent); border-radius: inherit; }

        section.block { padding: 80px 0; }
        .section-head { max-width: 560px; margin-bottom: 40px; }
        .section-head h2 {
            font-family: Fraunces, Georgia, serif; font-weight: 600;
            font-size: clamp(28px, 3vw, 40px); letter-spacing: -0.02em; margin: 0 0 12px;
        }
        .section-head p { margin: 0; color: var(--secondary); font-size: 17px; }

        .stats {
            display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px;
        }
        .stat {
            background: var(--card); border: 1px solid var(--hairline);
            border-radius: var(--radius-card); padding: 24px;
        }
        .stat b {
            font-variant-numeric: tabular-nums; font-size: 32px; font-weight: 600;
            display: block; margin-bottom: 8px;
        }
        .stat span { color: var(--muted); font-size: 14px; }

        .tracks {
            display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 24px; align-items: start;
        }
        .track-list { display: grid; gap: 8px; }
        .track {
            display: flex; align-items: center; justify-content: space-between;
            gap: 16px; padding: 16px 18px; border-radius: 12px;
            background: var(--card); border: 1px solid var(--hairline);
        }
        .track strong { font-weight: 600; font-size: 15px; }
        .track span { color: var(--muted); font-size: 13px; }
        .aside-card {
            background: var(--ink); color: #efe9de; border-radius: var(--radius-panel); padding: 28px;
        }
        .aside-card h3 { font-family: Fraunces, Georgia, serif; font-size: 28px; margin: 0 0 12px; font-weight: 600; }
        .aside-card p { color: #aaa398; margin: 0 0 24px; }
        .aside-card .btn-primary { background: #fff; color: var(--ink); }
        .aside-card .btn-primary:hover { background: #efe9de; }

        .steps { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
        .step { padding: 24px; border-radius: var(--radius-card); background: var(--card); border: 1px solid var(--hairline); }
        .step-n {
            width: 28px; height: 28px; border-radius: 8px; background: var(--accent-soft); color: var(--accent);
            display: grid; place-items: center; font-size: 13px; font-weight: 600; margin-bottom: 16px;
        }
        .step h3 { margin: 0 0 8px; font-size: 18px; font-weight: 600; }
        .step p { margin: 0; color: var(--secondary); font-size: 15px; }

        .reviews { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
        .review {
            background: var(--card); border: 1px solid var(--hairline); border-radius: var(--radius-card); padding: 24px;
        }
        .review q { display: block; font-size: 16px; color: var(--ink); quotes: none; margin-bottom: 20px; }
        .review-meta { display: flex; gap: 12px; align-items: center; }
        .review-meta strong { display: block; font-size: 14px; }
        .review-meta span { font-size: 13px; color: var(--muted); }

        footer.site {
            border-top: 1px solid var(--hairline); padding: 40px 0 64px; color: var(--muted); font-size: 14px;
        }
        .footer-row { display: flex; justify-content: space-between; gap: 24px; flex-wrap: wrap; }

        @media (max-width: 900px) {
            .wrap { width: min(100% - 32px, 1200px); }
            .nav-links { display: none; }
            .hero-inner { grid-template-columns: 1fr; padding-top: 40px; min-height: auto; }
            .hero-copy { padding-bottom: 32px; }
            .hero-scene {
                background:
                    linear-gradient(180deg, rgba(247,244,238,0.92) 0%, rgba(247,244,238,0.78) 42%, rgba(247,244,238,0.55) 100%),
                    url('/images/unslop-younimatiq-hero.webp') center/cover no-repeat;
            }
            .stats, .steps, .reviews, .tracks { grid-template-columns: 1fr; }
            .hero-panel { border-radius: var(--radius-panel); margin-bottom: 32px; min-height: 0; }
        }
    </style>
</head>
<body>
    <div class="preview-banner">
        Unslop preview only · live site unchanged ·
        <a href="/">Back to live home</a> ·
        <a href="/testdashboard">Admin dashboard preview</a>
    </div>

    <header class="site">
        <div class="wrap nav">
            <a class="brand" href="/test">
                <img src="/images/logo-mark.webp" alt="">
                Younimatiq
            </a>
            <nav class="nav-links" aria-label="Primary">
                <a href="/courses">Courses</a>
                <a href="/pricing">Pricing</a>
                <a href="/community">Community</a>
                <a href="/about">About</a>
            </nav>
            <div class="nav-actions">
                <a class="link-quiet" href="/login/student">Log in</a>
                <a class="btn btn-primary" href="/courses">Book a free trial class</a>
            </div>
        </div>
    </header>

    <main>
        <section class="hero">
            <div class="hero-scene" aria-hidden="true"></div>
            <div class="wrap hero-inner">
                <div class="hero-copy">
                    <div class="eyebrow"><i></i> Live 1:1 classes for kids</div>
                    <h1>Turn screen time into skill time</h1>
                    <p class="lede">
                        Younimatiq pairs your child with a background-checked tutor for Math, Coding, AI, Robotics, and Minecraft — so they build projects, not just watch videos
                    </p>
                    <div class="cta-row">
                        <a class="btn btn-primary btn-lg" href="/courses">Book a free trial class</a>
                        <a class="btn btn-secondary btn-lg" href="#how">See how it works</a>
                    </div>
                    <div class="trust">
                        <span><strong>4.9</strong> on Trustpilot</span>
                        <span><strong>170+</strong> students learning live</span>
                        <span>COPPA-conscious</span>
                    </div>
                </div>

                <aside class="hero-panel" aria-label="Live class preview">
                    <div class="panel-label">Today · America/Chicago</div>
                    <h2 class="panel-title">Radan’s Minecraft Math Quest</h2>
                    <div class="session">
                        <div class="session-row">
                            <div class="avatar">SB</div>
                            <div>
                                <strong>Sheher Banu</strong>
                                <span>Tutor · Geometry & number sense</span>
                            </div>
                            <span class="pill">Live in 12 min</span>
                        </div>
                        <div class="session-row">
                            <div class="avatar">R</div>
                            <div>
                                <strong>Radan · Grade 6</strong>
                                <span>9:00 AM CST · 45 min</span>
                            </div>
                            <span class="pill">1:1</span>
                        </div>
                    </div>
                    <div class="progress">
                        <div class="progress-top">
                            <span>Week 3 project</span>
                            <strong>68%</strong>
                        </div>
                        <div class="bar" aria-hidden="true"><i></i></div>
                    </div>
                </aside>
            </div>
        </section>

        <section class="block">
            <div class="wrap">
                <div class="stats">
                    <div class="stat"><b>170+</b><span>Students learning live</span></div>
                    <div class="stat"><b>40+</b><span>Live online courses</span></div>
                    <div class="stat"><b>8+</b><span>Subject tracks</span></div>
                    <div class="stat"><b>100%</b><span>Background-checked tutors</span></div>
                </div>
            </div>
        </section>

        <section class="block" style="padding-top:0">
            <div class="wrap">
                <div class="section-head">
                    <h2>One child. Many interests. One clear path</h2>
                    <p>Pick a track that fits what your child already loves, then grow into projects they can show</p>
                </div>
                <div class="tracks">
                    <div class="track-list">
                        <div class="track"><div><strong>Math Lab</strong><br><span>Grades 1–8 · Common Core aligned</span></div><span>1:1</span></div>
                        <div class="track"><div><strong>Minecraft &amp; Game Design</strong><br><span>Build, solve, and explain</span></div><span>1:1</span></div>
                        <div class="track"><div><strong>Coding &amp; AI Mastery</strong><br><span>Projects with responsible AI use</span></div><span>1:1</span></div>
                        <div class="track"><div><strong>Robotics &amp; 3D Design</strong><br><span>From sketch to print</span></div><span>1:1</span></div>
                    </div>
                    <div class="aside-card">
                        <h3>Start with a free trial class</h3>
                        <p>Meet a tutor, try a real session, then decide. No long commitment to begin</p>
                        <a class="btn btn-primary" href="/courses">Browse courses</a>
                    </div>
                </div>
            </div>
        </section>

        <section class="block" id="how" style="background:#efe9df; border-block:1px solid var(--hairline)">
            <div class="wrap">
                <div class="section-head">
                    <h2>How Younimatiq works</h2>
                    <p>Three quiet steps from first chat to weekly progress you can see</p>
                </div>
                <div class="steps">
                    <article class="step">
                        <div class="step-n">1</div>
                        <h3>Tell us about your child</h3>
                        <p>Age, grade, and what they care about — takes a couple of minutes</p>
                    </article>
                    <article class="step">
                        <div class="step-n">2</div>
                        <h3>Match with a tutor</h3>
                        <p>We pair pace and personality, then book a live 1:1 trial class</p>
                    </article>
                    <article class="step">
                        <div class="step-n">3</div>
                        <h3>Track real progress</h3>
                        <p>Projects, recordings, and weekly milestones on the family dashboard</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="block">
            <div class="wrap">
                <div class="section-head">
                    <h2>What parents notice first</h2>
                    <p>Quiet feedback from families already in live classes</p>
                </div>
                <div class="reviews">
                    <figure class="review">
                        <q>My son went from dreading math homework to asking for extra practice. The 1:1 pace made the difference</q>
                        <div class="review-meta">
                            <div class="avatar">JR</div>
                            <div><strong>Jenna R.</strong><span>Math · Grade 5</span></div>
                        </div>
                    </figure>
                    <figure class="review">
                        <q>We wanted less scrolling and more making. The Minecraft math track finally clicked for her</q>
                        <div class="review-meta">
                            <div class="avatar">AK</div>
                            <div><strong>Amina K.</strong><span>Minecraft Math · Grade 4</span></div>
                        </div>
                    </figure>
                    <figure class="review">
                        <q>Every class is recorded, tutors are checked, and I can see the project trail week by week</q>
                        <div class="review-meta">
                            <div class="avatar">DL</div>
                            <div><strong>Daniel L.</strong><span>Coding · Grade 7</span></div>
                        </div>
                    </figure>
                </div>
            </div>
        </section>
    </main>

    <footer class="site">
        <div class="wrap footer-row">
            <div>© {{ date('Y') }} Younimatiq · Unslop preview by Damir Sokolovsky’s method · not live</div>
            <div><a href="/">Live homepage</a> · <a href="/testdashboard">Dashboard preview</a></div>
        </div>
    </footer>
</body>
</html>
