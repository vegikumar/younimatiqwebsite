<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Younimatiq — Unslop preview (admin dashboard)</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500&family=Instrument+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --deep: #0f0f10;
            --bg: #141415;
            --card: #19191b;
            --raised: #232325;
            --ink: #ededed;
            --secondary: #a1a1a6;
            --muted: #85858b;
            --hairline: rgba(237, 237, 237, 0.08);
            --accent: #a78bfa;
            --accent-ink: #ddd6fe;
            --positive: #4cc38a;
            --negative: #e5484d;
            --ease: cubic-bezier(0.2, 0.8, 0.2, 1);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh; display: flex; flex-direction: column;
            font-family: "Instrument Sans", system-ui, sans-serif;
            background: var(--bg); color: var(--ink);
        }
        a { color: inherit; text-decoration: none; }
        .preview-banner {
            background: #efe9de; color: #1f1b16; font-size: 13px; padding: 10px 16px; text-align: center;
        }
        .preview-banner a { color: #5b21b6; font-weight: 500; }
        .shell { display: grid; grid-template-columns: 260px 1fr; min-height: calc(100vh - 40px); }
        aside {
            background: var(--deep); border-right: 1px solid var(--hairline);
            padding: 20px 16px; display: flex; flex-direction: column; gap: 8px;
        }
        .brand { display: flex; align-items: center; gap: 10px; padding: 8px 8px 20px; font-weight: 600; }
        .brand img { width: 28px; height: 28px; }
        .search {
            display: flex; align-items: center; gap: 8px; height: 36px; padding: 0 12px;
            border-radius: 10px; background: var(--card); border: 1px solid var(--hairline);
            color: var(--muted); font-size: 14px; margin-bottom: 16px;
        }
        .search kbd {
            margin-left: auto; font-family: "IBM Plex Mono", monospace; font-size: 11px;
            border: 1px solid var(--hairline); border-radius: 6px; padding: 2px 6px; color: var(--secondary);
        }
        .nav-label {
            font-size: 12px; color: var(--muted); padding: 16px 8px 8px; font-weight: 500;
        }
        .nav a {
            display: flex; align-items: center; gap: 10px; height: 36px; padding: 0 10px;
            border-radius: 10px; color: var(--secondary); font-size: 14px; font-weight: 500;
        }
        .nav a svg { width: 16px; height: 16px; stroke: currentColor; fill: none; stroke-width: 1.5; }
        .nav a.active { background: var(--raised); color: var(--ink); }
        .nav a:hover { background: rgba(255,255,255,0.03); color: var(--ink); }
        .nav .count {
            margin-left: auto; font-family: "IBM Plex Mono", monospace; font-size: 12px; color: var(--muted);
        }
        .main { padding: 24px 32px 48px; overflow: auto; }
        .top {
            display: flex; align-items: end; justify-content: space-between; gap: 16px;
            margin-bottom: 24px; flex-wrap: wrap;
        }
        .top h1 { margin: 0; font-size: 28px; font-weight: 600; letter-spacing: -0.02em; }
        .top p { margin: 8px 0 0; color: var(--secondary); font-size: 14px; }
        .actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .btn {
            height: 36px; padding: 0 14px; border-radius: 10px; border: 1px solid var(--hairline);
            background: var(--card); color: var(--ink); font-size: 13px; font-weight: 500;
            display: inline-flex; align-items: center; gap: 8px;
            transition: background-color 160ms var(--ease);
        }
        .btn:hover { background: var(--raised); }
        .btn-primary { background: var(--ink); color: #141415; border-color: transparent; }
        .btn-primary:hover { background: #fff; }

        .filters {
            display: flex; gap: 12px; align-items: end; flex-wrap: wrap;
            padding: 16px; border: 1px solid var(--hairline); border-radius: 14px;
            background: var(--card); margin-bottom: 20px;
        }
        label { display: grid; gap: 6px; font-size: 12px; color: var(--muted); font-weight: 500; }
        input[type="date"] {
            height: 36px; border-radius: 10px; border: 1px solid var(--hairline);
            background: var(--raised); color: var(--ink); padding: 0 12px; font: inherit;
        }

        .stats {
            display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 20px;
        }
        .stat {
            background: var(--card); border: 1px solid var(--hairline); border-radius: 14px; padding: 20px;
        }
        .stat span { display: block; color: var(--muted); font-size: 13px; margin-bottom: 12px; }
        .stat b {
            display: block; font-size: 28px; font-weight: 600; font-variant-numeric: tabular-nums;
            letter-spacing: -0.02em;
        }
        .stat em {
            display: block; margin-top: 8px; font-style: normal; font-size: 12px;
            font-family: "IBM Plex Mono", monospace; color: var(--secondary);
        }
        .stat em.up { color: var(--positive); }

        .grid { display: grid; grid-template-columns: 1.7fr 1fr; gap: 16px; }
        .card {
            background: var(--card); border: 1px solid var(--hairline); border-radius: 16px; padding: 20px;
        }
        .card-head { display: flex; justify-content: space-between; align-items: baseline; gap: 12px; margin-bottom: 20px; }
        .card-head h2 { margin: 0; font-size: 16px; font-weight: 600; }
        .card-head p { margin: 0; color: var(--muted); font-size: 13px; }

        .chart {
            height: 300px; display: grid; grid-template-columns: repeat(14, 1fr);
            align-items: end; gap: 8px; padding-top: 12px;
            border-top: 1px solid var(--hairline);
        }
        .chart-col { display: grid; align-items: end; height: 100%; }
        .chart-bar {
            width: 100%; border-radius: 6px 6px 2px 2px; background: var(--raised);
            min-height: 8px;
        }
        .chart-bar.on { background: linear-gradient(180deg, #c4b5fd 0%, #7c3aed 100%); }
        .chart-label {
            margin-top: 8px; text-align: center; font-size: 10px; color: var(--muted);
            font-family: "IBM Plex Mono", monospace;
        }

        .list { display: grid; gap: 8px; }
        .row {
            display: grid; grid-template-columns: 1fr auto; gap: 12px; align-items: center;
            padding: 12px; border-radius: 12px; background: var(--bg); border: 1px solid var(--hairline);
        }
        .row strong { display: block; font-size: 14px; font-weight: 600; }
        .row span { font-size: 12px; color: var(--muted); }
        .amount {
            font-family: "IBM Plex Mono", monospace; font-size: 13px; font-variant-numeric: tabular-nums;
        }
        .mix { display: grid; gap: 12px; }
        .mix-item { display: grid; gap: 6px; }
        .mix-top { display: flex; justify-content: space-between; font-size: 13px; }
        .mix-top span:last-child { font-family: "IBM Plex Mono", monospace; color: var(--secondary); }
        .mix-bar { height: 6px; border-radius: 999px; background: var(--raised); overflow: hidden; }
        .mix-bar i { display: block; height: 100%; background: var(--accent); border-radius: inherit; }

        .table-wrap { margin-top: 16px; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th {
            text-align: left; font-weight: 500; color: var(--muted); font-size: 12px;
            padding: 0 12px 12px; border-bottom: 1px solid var(--hairline);
        }
        td { padding: 14px 12px; border-bottom: 1px solid var(--hairline); }
        tr:hover td { background: rgba(255,255,255,0.02); }
        .mono { font-family: "IBM Plex Mono", monospace; font-variant-numeric: tabular-nums; }
        .status {
            display: inline-flex; align-items: center; gap: 6px; font-size: 12px; color: var(--secondary);
        }
        .status i { width: 6px; height: 6px; border-radius: 999px; background: var(--positive); }

        @media (max-width: 980px) {
            .shell { grid-template-columns: 1fr; }
            aside { display: none; }
            .stats, .grid { grid-template-columns: 1fr 1fr; }
            .main { padding: 20px 16px 40px; }
        }
        @media (max-width: 640px) {
            .stats, .grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="preview-banner">
        Unslop preview only · live admin unchanged ·
        <a href="/admin/dashboard">Open live dashboard</a> ·
        <a href="/test">Home preview</a>
    </div>

    <div class="shell">
        <aside>
            <div class="brand">
                <img src="/images/logo-mark.webp" alt="">
                Younimatiq
            </div>
            <div class="search">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                Search
                <kbd>⌘K</kbd>
            </div>
            <div class="nav-label">Operations</div>
            <nav class="nav">
                <a class="active" href="#">
                    <svg viewBox="0 0 24 24"><path d="M4 11.5 12 4l8 7.5V20a1 1 0 0 1-1 1h-5v-6H10v6H5a1 1 0 0 1-1-1z"/></svg>
                    Dashboard
                </a>
                <a href="/admin/students">
                    <svg viewBox="0 0 24 24"><path d="M16 19v-1a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v1"/><circle cx="9.5" cy="7.5" r="3.5"/><path d="M19 19v-1a3.5 3.5 0 0 0-2.5-3.35"/><path d="M15.5 4.2a3.5 3.5 0 0 1 0 6.6"/></svg>
                    Students
                </a>
                <a href="/admin/enrollments">
                    <svg viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 0 6.5 22H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                    Enrollments
                </a>
                <a href="/admin/orders">
                    <svg viewBox="0 0 24 24"><rect x="3" y="6" width="18" height="12" rx="2"/><path d="M3 10h18"/><path d="M7 15h2"/></svg>
                    Orders
                </a>
                <a href="/admin/match-suggestions">
                    <svg viewBox="0 0 24 24"><path d="M8 12h8"/><path d="M12 8v8"/><circle cx="12" cy="12" r="9"/></svg>
                    Match inbox
                    <span class="count">7</span>
                </a>
                <a href="/admin/consultations">
                    <svg viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H8l-4 4V5a2 2 0 0 1 2-2h13a2 2 0 0 1 2 2z"/></svg>
                    Consultations
                </a>
            </nav>
            <div class="nav-label">People</div>
            <nav class="nav">
                <a href="/admin/tutors">
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 20a8 8 0 0 1 16 0"/></svg>
                    Tutors
                </a>
                <a href="/admin/courses">
                    <svg viewBox="0 0 24 24"><path d="M22 10 12 5 2 10l10 5 10-5z"/><path d="M6 12v5c3 2 9 2 12 0v-5"/></svg>
                    Courses
                </a>
            </nav>
        </aside>

        <main class="main">
            <div class="top">
                <div>
                    <h1>November 2026</h1>
                    <p>Revenue, people, and live sessions for daily decisions</p>
                </div>
                <div class="actions">
                    <a class="btn btn-primary" href="/admin/students">Students</a>
                    <a class="btn" href="/admin/match-suggestions">Match inbox</a>
                    <a class="btn" href="/admin/orders">Orders</a>
                </div>
            </div>

            <form class="filters" action="/admin/dashboard" method="get">
                <label>From
                    <input type="date" name="from" value="2026-11-01">
                </label>
                <label>To
                    <input type="date" name="to" value="2026-11-14">
                </label>
                <button class="btn btn-primary" type="submit">Apply</button>
            </form>

            <div class="stats">
                <div class="stat">
                    <span>Total revenue</span>
                    <b>$48,210</b>
                    <em class="up">$6,420 this month</em>
                </div>
                <div class="stat">
                    <span>Enrollments</span>
                    <b>312</b>
                    <em class="up">+18 this month</em>
                </div>
                <div class="stat">
                    <span>Active students</span>
                    <b>170</b>
                    <em>42 tutors online</em>
                </div>
                <div class="stat">
                    <span>Upcoming sessions</span>
                    <b>27</b>
                    <em>Next 7 days</em>
                </div>
            </div>

            <div class="grid">
                <section class="card">
                    <div class="card-head">
                        <div>
                            <h2>Revenue trend</h2>
                            <p>Last 14 days · completed payments</p>
                        </div>
                        <div class="amount">$12,840</div>
                    </div>
                    <div class="chart" role="img" aria-label="Revenue over the last 14 days">
                        @php
                            $bars = [42, 55, 38, 70, 48, 62, 80, 45, 58, 90, 66, 72, 84, 96];
                            $days = ['1','2','3','4','5','6','7','8','9','10','11','12','13','14'];
                        @endphp
                        @foreach ($bars as $i => $h)
                            <div class="chart-col">
                                <div class="chart-bar {{ $i === count($bars) - 1 ? 'on' : '' }}" style="height: {{ $h }}%"></div>
                                <div class="chart-label">{{ $days[$i] }}</div>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="card">
                    <div class="card-head">
                        <div>
                            <h2>Category mix</h2>
                            <p>Enrollments by track</p>
                        </div>
                    </div>
                    <div class="mix">
                        <div class="mix-item">
                            <div class="mix-top"><span>Minecraft Math</span><span>34%</span></div>
                            <div class="mix-bar"><i style="width:34%"></i></div>
                        </div>
                        <div class="mix-item">
                            <div class="mix-top"><span>Coding &amp; AI</span><span>26%</span></div>
                            <div class="mix-bar"><i style="width:26%"></i></div>
                        </div>
                        <div class="mix-item">
                            <div class="mix-top"><span>Math Lab</span><span>22%</span></div>
                            <div class="mix-bar"><i style="width:22%"></i></div>
                        </div>
                        <div class="mix-item">
                            <div class="mix-top"><span>Robotics</span><span>18%</span></div>
                            <div class="mix-bar"><i style="width:18%"></i></div>
                        </div>
                    </div>
                </section>
            </div>

            <div class="grid" style="margin-top:16px">
                <section class="card">
                    <div class="card-head">
                        <div>
                            <h2>Upcoming sessions</h2>
                            <p>Next live classes with assigned tutors</p>
                        </div>
                    </div>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Course</th>
                                    <th>When</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Radan</td>
                                    <td>Minecraft Math Quest</td>
                                    <td class="mono">Nov 14 · 9:00 AM CST</td>
                                    <td><span class="status"><i></i> Confirmed</span></td>
                                </tr>
                                <tr>
                                    <td>Ansh</td>
                                    <td>Tinkercad Halloween Lab</td>
                                    <td class="mono">Nov 15 · 10:30 PM IST</td>
                                    <td><span class="status"><i></i> Confirmed</span></td>
                                </tr>
                                <tr>
                                    <td>Mia Chen</td>
                                    <td>Python Creators</td>
                                    <td class="mono">Nov 16 · 4:00 PM EST</td>
                                    <td><span class="status"><i></i> Confirmed</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="card">
                    <div class="card-head">
                        <div>
                            <h2>Recent enrollments</h2>
                            <p>Latest completed checkouts</p>
                        </div>
                    </div>
                    <div class="list">
                        <div class="row">
                            <div>
                                <strong>erezafar1@yahoo.com</strong>
                                <span>Minecraft Math Quest · Grade 5–6</span>
                            </div>
                            <div class="amount">$0</div>
                        </div>
                        <div class="row">
                            <div>
                                <strong>parent@example.com</strong>
                                <span>AI Mastery · 8 classes</span>
                            </div>
                            <div class="amount">$320</div>
                        </div>
                        <div class="row">
                            <div>
                                <strong>family.lee@gmail.com</strong>
                                <span>Robotics Starter · 4 classes</span>
                            </div>
                            <div class="amount">$180</div>
                        </div>
                    </div>
                </section>
            </div>
        </main>
    </div>
</body>
</html>
