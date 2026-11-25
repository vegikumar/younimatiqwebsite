<!DOCTYPE html>

<!-- saved from url=(0048)https://vector-desk-05577789.figma.site/#consult -->
<html lang="en"><head>

<link crossorigin="" href="{{ asset('robotics_assets/index-MQrRNkSM.css') }}" rel="stylesheet"/>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1, viewport-fit=cover" name="viewport"/>
<link rel="preload" as="image" href="{{ asset('images/minecraft_hero_1787217370958.webp') }}">
<meta content="{{ csrf_token() }}" name="csrf-token"/>
@include('partials.csrf-hydrate')
@include('partials.google-analytics')
@include('partials.meta-pixel')
@include('partials.microsoft-clarity')
{{-- mautic tracking disabled: emails.younimatiq.com has no DNS --}}
<!-- Fonts & Icons -->
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
<link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
<link rel="preload" as="font" type="font/woff2" href="{{ asset('fonts/material-symbols/material-symbols-outlined.woff2') }}" crossorigin>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet"/>
<!-- Scripts & Styles -->
  @vite(['resources/css/app.css', 'resources/js/marketing.js'])
<style>
    :root {
      /* Theme Base Colors */
      --color-primary: #0057bf;
      --color-primary-container: #006fef;
      --color-on-primary: #ffffff;
      --color-secondary: #3d6a00;
      --color-secondary-container: #b1f369;
      --color-background: #faf9ff;
      --color-on-background: #181b23;
      --color-surface-container-low: #f2f3fe;
      --color-surface-container-highest: #e0e2ed;
      --color-inverse-surface: #2d3039;
      --color-outline-variant: #c1c6d7;

      --blue: var(--color-primary);
      --blue-dark: var(--color-primary-container);
      --navy: #003d86;
      --ink: var(--color-on-background);
      --gold: #cca730;
      --green: var(--color-secondary);
      --cloud: var(--color-surface-container-low);
      --slate: #414754;
      --line: var(--color-outline-variant);
      --white: #ffffff;

      --radius: 0.5rem;
      --radius-sm: 0.5rem;
      --radius-lg: 0.5rem;
      --radius-xl: 0.5rem;

      --clay-card-shadow: 4px 5px 0 var(--color-surface-container-highest);
      --clay-card-hover-shadow: 6px 7px 0 var(--color-surface-container-highest);
      --clay-button-shadow: 3px 4px 0 #003d86;
      --clay-button-hover-shadow: 4px 5px 0 #003d86;
      --clay-button-enroll-shadow: 3px 4px 0 #046bd2;
      --clay-button-enroll-hover: 4px 5px 0 #046bd2;
      --brand-panel-shadow: 0 25px 50px -12px rgba(0, 87, 191, 0.28);
    }

    * {
      box-sizing: border-box;
    }

    html {
        scroll-padding-top: 80px;

      width: 100%;
      height: 100%;
    }

    body {
      font-family: 'Quicksand', system-ui, -apple-system, sans-serif;
      color: var(--ink);
      background: var(--color-background);
      -webkit-font-smoothing: antialiased;
      line-height: 1.5;
      margin: 0;
      padding: 0;
      position: relative;
      width: 100%;
      min-height: 100vh;
      overflow-x: clip;
    }

    body.modal-open {
      overflow: hidden !important;
    }

    h1, h2, h3, .font-head {
      font-family: 'Quicksand', system-ui, sans-serif;
    }

    .wrap {
      width: 100%;
      position: relative;
    }

    .px {
      padding-left: max(4%, calc((100% - 1440px) / 2));
      padding-right: max(4%, calc((100% - 1440px) / 2));
    }

    section {
      padding: 60px 0;
    }

    .eyebrow {
      font-size: 14px;
      font-weight: 700;
      letter-spacing: .12em;
      text-transform: uppercase;
      color: rgba(0, 87, 191, 0.72);
    }

    .h2 {
      font-size: 32px;
      font-weight: 700;
      color: var(--blue);
      line-height: 1.28;
    }

    .sub {
      color: var(--slate);
      font-size: 16px;
      margin-top: 12px;
    }

    /* Universal tactile click animation for all interactive buttons */
    button,
    [role="button"],
    input[type="submit"],
    input[type="button"],
    input[type="reset"],
    .btn,
    .icon-btn,
    .mclose,
    .tab-btn,
    .fab-3d {
      transition: transform 0.15s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.15s ease, background-color 0.15s ease !important;
    }

    button:active,
    [role="button"]:active,
    input[type="submit"]:active,
    input[type="button"]:active,
    input[type="reset"]:active,
    .btn:active,
    .icon-btn:active,
    .mclose:active,
    .tab-btn:active,
    .fab-3d:active {
      transform: scale(0.96) translateY(2px) !important;
      box-shadow: 0 0 0 transparent !important;
    }

    .btn-primary {
      background: var(--blue);
      color: #fff;
      box-shadow: var(--clay-button-shadow);
    }

    .btn-primary:hover {
      box-shadow: var(--clay-button-hover-shadow);
      transform: translateY(-1px);
    }

    .btn-gold-outline {
      background: linear-gradient(to right, #d9f99d, #86efac);
      color: #14532d;
      box-shadow: var(--clay-button-enroll-shadow);
    }

    .btn-gold-outline:hover {
      box-shadow: var(--clay-button-enroll-hover);
      transform: translateY(-1px);
    }

    .btn-block {
      width: 100%;
    }

    .link-arrow {
      font-weight: 700;
      font-size: 15px;
      color: var(--blue);
      display: inline-flex;
      align-items: center;
      gap: 6px;
      transition: gap 0.2s;
    }

    .link-arrow:hover {
      gap: 10px;
      color: var(--color-primary-container);
      text-decoration: underline;
    }

    /* Header */
    header {
      position: sticky;
      top: 0;
      z-index: 50;
      background: rgba(255, 255, 255, .95);
      backdrop-filter: blur(8px);
      border-bottom: 1px solid rgba(193, 198, 215, .3);
    }

    .desktop-nav {
      display: none;
      align-items: center;
      gap: 24px;
      font-weight: 600;
      font-size: 15px;
      color: var(--color-on-surface);
    }

    .desktop-nav a:hover {
      color: var(--color-primary-container);
    }

    @media (min-width: 900px) {
      .desktop-nav { display: flex; }
      #menuBtn { display: none; }
    }

    .header-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 16px max(4%, calc((100% - 1440px) / 2));
      height: 80px;
    }

    .logo-img {
      height: 56px;
      width: auto;
    }

    .header-actions {
      display: flex;
      align-items: center;
      gap: 16px;
    }

    .icon-btn {
      width: 42px;
      height: 42px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: transparent;
      color: var(--ink);
      border: none;
      font-size: 24px;
      cursor: pointer;
      transition: background 0.2s;
    }

    .btn-sm {
      padding: 10px 24px;
      font-size: 14px;
    }

    /* Hero */
    .hero {
      position: relative;
      overflow: hidden;
      background: var(--brand-gradient);
      color: #003d86;
      text-align: left;
    }

    .hero-content {
      position: relative;
      z-index: 2;
    }

    .hero .eyebrow,
    .hero-badge {
      color: #003d86;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: rgba(255, 255, 255, 0.65);
      border: 1px solid rgba(0, 87, 191, 0.25);
      padding: 6px 14px;
      border-radius: 999px;
      font-size: 13px;
      font-weight: 700;
      backdrop-filter: blur(8px);
    }

    .hero h1,
    .hero-title {
      font-size: clamp(2rem, 4vw, 3.25rem);
      font-weight: 800;
      line-height: 1.2;
      margin-top: 16px;
      color: #003d86;
      text-align: left;
    }

    .hero-title-accent {
      color: #1e5200;
    }

    .hero p.sub {
      color: #003d86;
      font-size: 1.05rem;
      margin-top: 14px;
      font-weight: 600;
      line-height: 1.6;
      text-align: left;
    }

    .see-how {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      padding: 0.5rem 1.25rem;
      font-size: 0.875rem;
      font-weight: 700;
      border-radius: var(--radius);
      color: #003d86 !important;
      border: 2px solid #003d86;
      background: rgba(255, 255, 255, 0.75);
      box-shadow: 3px 4px 0 #003d86;
      transition: all 0.15s ease;
      text-decoration: none;
    }

    .see-how:hover {
      background: rgba(255, 255, 255, 0.95);
      transform: translateY(-1px);
      box-shadow: 4px 5px 0 #003d86;
    }

    .trust-row {
      margin-top: 24px;
      display: inline-flex;
      align-items: center;
      justify-content: flex-start;
      gap: 12px;
      background: rgba(255, 255, 255, 0.65);
      border: 1px solid rgba(0, 87, 191, 0.25);
      padding: 10px 16px;
      border-radius: 14px;
      font-size: 13.5px;
      color: #003d86;
      backdrop-filter: blur(8px);
      font-weight: 600;
    }

    .stars {
      color: #f59e0b;
      font-size: 14px;
      letter-spacing: 1px;
    }

    .rating-num {
      font-weight: 800;
      font-size: 15px;
      color: #003d86;
    }

    /* Stat bar */
    .stat-bar {
      background: var(--cloud);
      padding: 40px 20px !important;
    }

    .stat-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 12px;
    }
    @media (min-width: 768px) {
      .stat-grid {
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
      }
    }

    .stat-chip {
      background: #fff;
      border-radius: var(--radius-lg);
      padding: 18px 12px;
      text-align: center;
      border: 1px solid var(--color-surface-container-highest);
      box-shadow: var(--clay-card-shadow);
      transition: all 0.15s ease;
    }
    @media (min-width: 768px) {
      .stat-chip {
        padding: 24px 20px;
      }
    }

    .stat-chip:hover {
      transform: translateY(-2px);
      box-shadow: var(--clay-card-hover-shadow);
    }

    .stat-chip i {
      color: var(--blue);
      font-size: 24px;
      margin-bottom: 12px;
    }

    .stat-chip .label {
      font-size: 14px;
      font-weight: 700;
      color: var(--ink);
    }

    /* Courses */
    .courses-head {
      text-align: center;
      margin-bottom: 40px;
      max-width: 600px;
      margin-left: auto;
      margin-right: auto;
    }

    .course-group {
      margin-top: 32px;
    }

    .course-group:first-of-type {
      margin-top: 0;
    }

    .course-group-label {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      font-size: 14px;
      font-weight: 700;
      color: var(--blue);
      margin-bottom: 16px;
      border-bottom: 2px solid var(--color-secondary-container);
      padding-bottom: 8px;
      width: max-content;
      margin-left: auto;
      margin-right: auto;
    }

    .course-grid {
      display: flex;
      flex-wrap: nowrap;
      overflow-x: auto;
      gap: 20px;
      padding-bottom: 24px;
      scrollbar-width: none;
    }
    .course-grid::-webkit-scrollbar {
      display: none;
    }
    @media (min-width: 1024px) {
      .course-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        overflow-x: visible;
        padding-bottom: 0;
      }
    }

    .course-card {
      border-radius: var(--radius-lg);
      padding: 24px;
      border: 1px solid var(--color-surface-container-highest);
      display: flex;
      flex-direction: column;
      gap: 12px;
      min-height: 200px;
      transition: all 0.15s ease;
      background: #fff;
      box-shadow: var(--clay-card-shadow);
      cursor: pointer;
      flex: 0 0 230px;
    }
    @media (min-width: 640px) {
      .course-card {
        flex: 0 0 280px;
      }
    }
    @media (min-width: 1024px) {
      .course-card {
        flex: auto;
        width: 100%;
      }
    }

    .course-card:hover {
      transform: translateY(-2px);
      box-shadow: var(--clay-card-hover-shadow);
    }

    .course-card .cicon {
      width: 48px;
      height: 48px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
      background: var(--cloud);
      color: var(--blue);
    }

    .course-card h3 {
      font-size: 18px;
      font-weight: 700;
      color: var(--ink);
      line-height: 1.25;
    }

    .course-card p {
      font-size: 14px;
      color: var(--slate);
      line-height: 1.5;
      flex: 1;
      font-weight: 500;
    }

    .course-card .explore {
      font-size: 14px;
      font-weight: 700;
      color: var(--blue);
      margin-top: 8px;
    }

    /* Why Younimatiq */
    .why {
      background: var(--color-inverse-surface);
      color: #fff;
    }

    .why .h2 {
      color: #fff;
    }

    .why .sub {
      color: rgba(255, 255, 255, .7);
    }

    .why-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 24px;
      margin-top: 40px;
    }

    .why-card {
      background: rgba(255, 255, 255, .05);
      border: 1px solid rgba(255, 255, 255, .1);
      border-radius: var(--radius-lg);
      padding: 32px;
    }

    .why-card .wicon {
      width: 56px;
      height: 56px;
      border-radius: 16px;
      background: rgba(0, 87, 191, .3);
      color: #60a5fa;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
      margin-bottom: 20px;
    }

    .why-card h3 {
      font-size: 20px;
      font-weight: 700;
      margin-bottom: 12px;
    }

    .why-card p {
      color: rgba(255, 255, 255, .7);
      font-size: 15px;
      line-height: 1.6;
    }

    /* Story */
    .story-grid {
      display: grid;
      grid-template-columns: 1fr;
      gap: 40px;
      align-items: center;
    }

    @media (min-width: 900px) {
      .story-grid {
        grid-template-columns: 1fr 1fr;
      }
    }

    .story-box {
      background: var(--cloud);
      border-radius: var(--radius-xl);
      padding: 40px;
      border: 1px solid var(--color-surface-container-highest);
    }

    /* Steps */
    .steps-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: 24px;
      margin-top: 40px;
    }

    .step-card {
      background: #fff;
      border-radius: var(--radius-lg);
      padding: 32px;
      border: 1px solid var(--color-surface-container-highest);
      box-shadow: var(--clay-card-shadow);
      position: relative;
    }

    .step-num {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      background: var(--blue);
      color: #fff;
      font-weight: 700;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 16px;
      margin-bottom: 20px;
    }

    /* FAQs */
    .faq-list {
      max-width: 800px;
      margin: 40px auto 0;
      display: flex;
      flex-direction: column;
      gap: 0;
    }

    .faq-item {
      background: transparent;
      border-radius: 0;
      border: none;
      border-bottom: 1px solid #e2e8f0;
      padding: 0;
      box-shadow: none;
      transition: all 0.3s ease;
    }

    .faq-item summary {
      font-weight: 700;
      font-size: 18px;
      color: #003d86;
      cursor: pointer;
      list-style: none;
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 20px 0;
      gap: 16px;
      font-family: var(--font-headline), 'Outfit', sans-serif;
    }

    .faq-item summary::-webkit-details-marker {
      display: none;
    }

    .faq-item .plus {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      background: #EAF0FE;
      color: #2F6DF2;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 14px;
      flex-shrink: 0;
      transition: transform 0.3s ease, background 0.3s ease, color 0.3s ease;
    }

    .faq-item[open] .plus {
      transform: rotate(45deg);
      background: #2F6DF2;
      color: #ffffff;
    }

    .faq-item p {
      margin-top: 0;
      padding-bottom: 20px;
      color: #475569;
      font-size: 16px;
      line-height: 1.6;
    }

    /* Mobile Menu Overlay */
    .menu-overlay {
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, .5);
      z-index: 100;
      display: none;
    }

    .menu-overlay.open {
      display: block;
    }

    .menu-panel {
      position: absolute;
      top: 0;
      right: 0;
      width: 280px;
      height: 100%;
      background: #fff;
      padding: 32px 24px;
      display: flex;
      flex-direction: column;
      gap: 20px;
    }

    .menu-panel a {
      font-weight: 700;
      font-size: 16px;
      color: var(--ink);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .mclose {
      align-self: flex-end;
      background: transparent;
      border: none;
      font-size: 24px;
      cursor: pointer;
    }

    /* Lead Modal Overlay */
    .lead-modal-overlay {
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.6);
      backdrop-filter: blur(4px);
      z-index: 120;
      display: none;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }

    .lead-modal-overlay.open {
      display: flex;
    }

    .lead-modal-panel {
      background: #fff;
      border-radius: var(--radius-xl);
      max-width: 600px;
      width: 100%;
      max-height: 90vh;
      overflow-y: auto;
      padding: 32px;
      position: relative;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    }

    .lead-modal-header h3 {
      font-size: 24px;
      font-weight: 700;
      color: var(--blue);
    }

    .lead-modal-header p {
      font-size: 14px;
      color: var(--slate);
      margin-top: 4px;
      margin-bottom: 24px;
    }

    .form-group {
      margin-bottom: 16px;
    }

    .form-group label {
      display: block;
      font-size: 14px;
      font-weight: 700;
      margin-bottom: 8px;
      color: var(--ink);
    }

    .form-control {
      width: 100%;
      padding: 10px 14px;
      border-radius: var(--radius);
      border: 1px solid var(--color-outline-variant);
      font-family: inherit;
      font-size: 14px;
    }

    .grade-grid {
      display: grid;
      grid-template-columns: repeat(6, 1fr);
      gap: 8px;
    }

    .grade-btn input {
      display: none;
    }

    .grade-btn span {
      display: flex;
      align-items: center;
      justify-content: center;
      height: 38px;
      border-radius: var(--radius);
      border: 1px solid var(--color-outline-variant);
      font-weight: 700;
      font-size: 14px;
      cursor: pointer;
    }

    .grade-btn input:checked + span {
      background: var(--blue);
      color: #fff;
      border-color: var(--blue);
    }

    .course-checkbox-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 8px;
    }

    .course-check {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 14px;
      cursor: pointer;
    }

    .form-row {
      display: flex;
      gap: 12px;
    }
  </style>
<style>
html {
    
    scroll-padding-top: 80px !important;
html { scroll-padding-top: 80px !important; }
</style>
<title>Minecraft Coding & Game Design Classes for Kids | Younimatiq</title>
<meta name="description" content="Learn coding and game design through Minecraft at Younimatiq. Interactive, live online classes teaching real programming skills to kids.">
<meta name="keywords" content="Minecraft coding classes, learn to code with Minecraft, game design for kids, online coding classes, Younimatiq Minecraft">
<meta property="og:title" content="Minecraft Coding & Game Design Classes for Kids | Younimatiq">
<meta property="og:description" content="Learn coding and game design through Minecraft at Younimatiq. Interactive, live online classes teaching real programming skills to kids.">
<meta property="og:type" content="website">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:site_name" content="{{ config('app.name', 'Younimatiq') }}">
<meta property="og:image" content="{{ \App\Support\SeoHelper::absoluteImageUrl('images/minecraft_hero_1787217370958.webp') }}">
<meta name="twitter:title" content="Minecraft Coding & Game Design Classes for Kids | Younimatiq">
<meta name="twitter:description" content="Learn coding and game design through Minecraft at Younimatiq. Interactive, live online classes teaching real programming skills to kids.">
<meta name="twitter:image" content="{{ \App\Support\SeoHelper::absoluteImageUrl('images/minecraft_hero_1787217370958.webp') }}">
<meta name="twitter:card" content="summary_large_image">
<meta content="index, follow" name="robots"/>
<link rel="canonical" href="{{ url('/minecraft') }}"/>
<x-schema-org type="WebPage" name="Younimatiq | Minecraft Program" description="Learn coding and game design through Minecraft at Younimatiq."  :breadcrumbs="[['name' => 'Home', 'url' => url('/')], ['name' => 'Minecraft', 'url' => url('/minecraft')]]" />
@include('partials.favicon')
@include('partials.anti_copy')
@include('partials.content-protection')
</head>
<body data-gr-ext-installed="" data-new-gr-c-s-check-loaded="14.1320.0">
<header>
<div class="header-row">
<a href="/">
<img loading="lazy" alt="Younimatiq logo" class="logo-img object-contain" loading="lazy" src="{{ asset('logo.webp') }}"/>
</a>
<nav class="desktop-nav">
<a href="#home">Home</a>
<a href="#curriculum">Curriculum</a>
<a href="#how-it-works">How It Works</a>
<a href="#courses">Courses</a>
<a href="#pricing">Pricing & Plans</a>
<a href="#reviews">Reviews</a>
<a href="#consult">Contact</a>
</nav>
<div class="header-actions">
<a class="btn btn-primary btn-sm" href="#leadModal" onclick="openLeadModal(event, 'minecraft')" style='\"cursor:pointer;\"'>Book a free consultation</a>
<button aria-label="Open menu" class="icon-btn" id="menuBtn"><i class="fa-solid fa-bars"></i></button>
</div>
</div>
</header>
<div class="menu-overlay" id="menuOverlay">
<nav class="menu-panel" x-data="{ coursesOpen: false }">
<button class="mclose" id="menuClose"><i class="fa-solid fa-xmark"></i></button>
<a href="#home" onclick="document.getElementById('menuOverlay').classList.remove('open')">Home <i class="fa-solid fa-chevron-right"></i></a>
<a href="#curriculum" onclick="document.getElementById('menuOverlay').classList.remove('open')">Curriculum <i class="fa-solid fa-chevron-right"></i></a>
<a href="#how-it-works" onclick="document.getElementById('menuOverlay').classList.remove('open')">How It Works <i class="fa-solid fa-chevron-right"></i></a>
<a href="#courses" onclick="document.getElementById('menuOverlay').classList.remove('open')">Courses <i class="fa-solid fa-chevron-right"></i></a>
<a href="#pricing" onclick="document.getElementById('menuOverlay').classList.remove('open')">Pricing & Plans <i class="fa-solid fa-chevron-right"></i></a>
<a href="#reviews" onclick="document.getElementById('menuOverlay').classList.remove('open')">Reviews <i class="fa-solid fa-chevron-right"></i></a>
<a href="#consult" onclick="document.getElementById('menuOverlay').classList.remove('open')">Contact <i class="fa-solid fa-chevron-right"></i></a>
<a class="btn btn-primary btn-block" href="#leadModal" onclick="openLeadModal(event, 'minecraft')" style="margin-top: 12px;">Book a free consultation</a>
</nav>
</div>
<div>
<div id="root"><div id="home"></div><div class="min-h-screen bg-white"><main><section class="relative min-h-screen flex items-center overflow-hidden" style="background: url('{{ asset('images/minecraft_hero_1787217370958.webp') }}') center/cover no-repeat; padding-top: 72px;"><div class="absolute inset-0 pointer-events-none" style="background-color: rgba(0, 0, 0, 0.8);"></div><div class="relative max-w-7xl mx-auto px-4 py-10 grid lg:grid-cols-2 gap-12 items-center z-10"><div style="animation: 0.8s ease-out 0s 1 normal both running fadeSlideUp;"><div class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold mb-6" style="background: rgba(34, 197, 94, 0.25); color: rgb(134, 239, 172); border: 1px solid rgba(34, 197, 94, 0.4);"><span>⛏️</span><span>Turn Screen Time Into Skill Time</span></div><h1 class="text-4xl md:text-5xl xl:text-6xl font-extrabold text-white leading-tight mb-6" style="font-family: Sora, sans-serif; letter-spacing: -0.02em;">Build, Code, and Create in <span style="color: rgb(74, 222, 128);">Minecraft</span>.</h1><p class="text-lg text-white leading-relaxed mb-8 max-w-lg" style="opacity: 0.9;">Younimatiq's Minecraft Lab is a live, 1:1 online studio where students learn logic, redstone engineering, and Java modding — turning their favorite game into a powerful learning engine.</p><div class="flex flex-wrap gap-4"><a class="px-7 py-4 rounded-full font-bold text-white bg-success hover:bg-tertiary border-transparent transition-all text-base" href="#courses">Explore Courses</a></div><div class="mt-10 inline-flex items-center gap-3 px-5 py-3 rounded-2xl" style="background: rgba(0, 0, 0, 0.3); backdrop-filter: blur(8px); border: 1px solid rgba(255, 255, 255, 0.2);"><div class="flex"><svg fill="#f59e0b" height="16" stroke="#f59e0b" stroke-width="2" viewbox="0 0 24 24" width="16"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg><svg fill="#f59e0b" height="16" stroke="#f59e0b" stroke-width="2" viewbox="0 0 24 24" width="16"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg><svg fill="#f59e0b" height="16" stroke="#f59e0b" stroke-width="2" viewbox="0 0 24 24" width="16"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg><svg fill="#f59e0b" height="16" stroke="#f59e0b" stroke-width="2" viewbox="0 0 24 24" width="16"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg><svg fill="#f59e0b" height="16" stroke="#f59e0b" stroke-width="2" viewbox="0 0 24 24" width="16"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg></div><span class="text-white font-semibold">4.9</span><span class="text-white text-sm" style="opacity: 0.8;">from 1,200+ parent reviews</span></div></div><div class="flex justify-center lg:justify-end items-center relative z-10 mt-8 lg:mt-0" style="mix-blend-mode: screen; animation: 0.8s ease-out 0.2s 1 normal both running fadeSlideUp;"><img loading="lazy" src="{{ asset('images/minecraft_character.webp') }}" alt="Minecraft Character" class="w-full max-w-lg object-contain drop-shadow-2xl" /></div></div></section><section class="py-20 md:py-24" style="background: rgb(248, 250, 252);">
<div class="w-full px-6 md:px-12">
<div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-12">
<div>
<div class="inline-block px-4 py-1.5 rounded-full text-sm font-semibold mb-4" style="background: rgb(239, 246, 255); color: rgb(37, 99, 235);">Course Catalog</div>
<h2 class="text-3xl md:text-4xl font-extrabold text-on-surface" id="courses" style="font-family: Sora, sans-serif; letter-spacing: -0.02em;">Pick Your Path in Minecraft</h2>
</div>
<a class="shrink-0 text-sm font-semibold text-on-secondary-container hover:text-on-secondary-container transition-colors underline underline-offset-2" href="{{ route('courses.category', 'minecraft-camp') }}">Explore Minecraft Courses →</a>
</div>
    
    @if($courses->count() > 0)
    <div id="coursesScrollTrack" style="overflow: hidden; position: relative;">
      <div id="coursesScrollInner" class="flex gap-6 pb-4" style="scrollbar-width: none; width: max-content;">
        @foreach($courses as $course)
        <div class="shrink-0 w-72 md:w-80 rounded-3xl overflow-hidden bg-white flex flex-col course-scroll-card" style="box-shadow: rgba(30, 58, 138, 0.08) 0px 2px 20px; transition: transform 0.3s, box-shadow 0.3s;">
          <div class="relative w-full" style="background: rgb(226, 232, 240);">
            <img loading="lazy" alt="{{ $course->title }}" class="w-full h-auto" src="{{ $course->thumbnail_url }}" style="max-height: 250px; object-fit: contain;"/>
            <div class="absolute top-3 left-3 px-3 py-1 rounded-full text-xs font-bold" style="background: rgb(239, 246, 255); color: rgb(37, 99, 235);">{{ $course->age_group }}</div>
          </div>
          <div class="p-6 flex flex-col flex-1">
            <h3 class="font-bold text-on-surface text-base mb-2" style="font-family: Sora, sans-serif;">{{ $course->title }}</h3>
            <p class="text-sm text-on-surface leading-relaxed flex-1">{!! Str::limit(strip_tags($course->description), 80) !!}</p>
            <a class="mt-5 block text-center py-3 rounded-full text-sm font-bold text-white transition-all hover:opacity-90" href="{{ route('courses.show', $course) }}" style="background: rgb(34, 197, 94);">View Course</a>
          </div>
        </div>
        @endforeach
        {{-- Duplicate cards for seamless infinite loop --}}
        @foreach($courses as $course)
        <div class="shrink-0 w-72 md:w-80 rounded-3xl overflow-hidden bg-white flex flex-col course-scroll-card" aria-hidden="true" style="box-shadow: rgba(30, 58, 138, 0.08) 0px 2px 20px; transition: transform 0.3s, box-shadow 0.3s;">
          <div class="relative w-full" style="background: rgb(226, 232, 240);">
            <img loading="lazy" alt="{{ $course->title }}" class="w-full h-auto" src="{{ $course->thumbnail_url }}" style="max-height: 250px; object-fit: contain;"/>
            <div class="absolute top-3 left-3 px-3 py-1 rounded-full text-xs font-bold" style="background: rgb(239, 246, 255); color: rgb(37, 99, 235);">{{ $course->age_group }}</div>
          </div>
          <div class="p-6 flex flex-col flex-1">
            <h3 class="font-bold text-on-surface text-base mb-2" style="font-family: Sora, sans-serif;">{{ $course->title }}</h3>
            <p class="text-sm text-on-surface leading-relaxed flex-1">{!! Str::limit(strip_tags($course->description), 80) !!}</p>
            <a class="mt-5 block text-center py-3 rounded-full text-sm font-bold text-white transition-all hover:opacity-90" href="{{ route('courses.show', $course) }}" style="background: rgb(34, 197, 94);">View Course</a>
          </div>
        </div>
        @endforeach
      </div>
    </div>
    <style>
      #coursesScrollTrack {
        -webkit-mask-image: linear-gradient(to right, transparent 0%, black 5%, black 95%, transparent 100%);
        mask-image: linear-gradient(to right, transparent 0%, black 5%, black 95%, transparent 100%);
      }
      .course-scroll-card:hover {
        transform: translateY(-4px);
        box-shadow: rgba(30, 58, 138, 0.18) 0px 8px 32px !important;
      }
    </style>
    <script>
    (function() {
      const track = document.getElementById('coursesScrollTrack');
      const inner = document.getElementById('coursesScrollInner');
      if (!track || !inner) return;

      let paused = false;
      let offset = 0;
      let raf;
      const speed = 0.6; // px per frame — slow, smooth

      function getHalfWidth() {
        return inner.scrollWidth / 2;
      }

      function step() {
        if (!paused) {
          offset += speed;
          const half = getHalfWidth();
          if (offset >= half) {
            offset = 0;
          }
          inner.style.transform = `translateX(-${offset}px)`;
        }
        raf = requestAnimationFrame(step);
      }

      track.addEventListener('mouseenter', () => { paused = true; });
      track.addEventListener('mouseleave', () => { paused = false; });
      track.addEventListener('touchstart', () => { paused = true; }, { passive: true });
      track.addEventListener('touchend', () => {
        setTimeout(() => { paused = false; }, 1200);
      }, { passive: true });

      raf = requestAnimationFrame(step);
    })();
    </script>
    @else
    <p class="text-center text-on-surface-variant">No robotics courses found.</p>
    @endif
  </div>
</section><section class="py-20 md:py-24 bg-white" x-data="{ videos: ['{{ asset('minecraft_videos/video1.mp4') }}','{{ asset('minecraft_videos/video2.mp4') }}','{{ asset('minecraft_videos/video3.mp4') }}','{{ asset('minecraft_videos/video4.mp4') }}','{{ asset('minecraft_videos/video5.mp4') }}'], currentIndex: 0, playNext() { this.currentIndex = (this.currentIndex + 1) % this.videos.length; this.$refs.mainVideo.load(); this.$refs.mainVideo.play(); } }"><div class="max-w-7xl mx-auto px-6"><div class="text-center mb-12"><div class="inline-block px-4 py-1.5 rounded-full text-sm font-semibold mb-4" style="background: rgb(254, 243, 199); color: rgb(217, 119, 6);">Maker Moments</div><h2 class="text-3xl md:text-4xl font-extrabold text-on-surface" style="font-family: Sora, sans-serif; letter-spacing: -0.02em;">See Younimatiq Creators in Action.</h2></div><div class="relative w-full rounded-3xl overflow-hidden mb-6 cursor-pointer group" style="aspect-ratio: 16 / 9; background: rgb(15, 23, 42);"><video :src="videos[currentIndex]" @ended="playNext()" autoplay="" class="w-full h-full object-contain bg-black" controls="" muted="" playsinline="" x-ref="mainVideo">Your browser does not support the video tag.</video><div class="absolute bottom-4 left-4 px-3 py-1.5 rounded-lg text-sm font-semibold text-white" style="background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(8px);">Younimatiq Student Showcase</div></div><div class="flex gap-4 overflow-x-auto pb-4 snap-x snap-mandatory" style="scrollbar-width: none; -ms-overflow-style: none;"><template :key="index" x-for="(video, index) in videos"><template x-if="currentIndex !== index"><div @click="currentIndex = index; $refs.mainVideo.load(); $refs.mainVideo.play();" class="relative rounded-2xl overflow-hidden cursor-pointer group shrink-0 snap-start w-32 sm:w-40 md:w-56 lg:w-64" style="aspect-ratio: 3 / 2; background: rgb(226, 232, 240);"><video :src="video + '#t=0.001'" class="w-full h-full object-cover" muted="" playsinline="" preload="metadata"></video><div class="absolute inset-0 flex items-center justify-center bg-black/30 group-hover:bg-transparent transition-colors"><i class="fa-solid fa-play text-white text-2xl"></i></div></div></template></template></div></section><section class="py-20 md:py-24" style="background: linear-gradient(135deg, rgb(240, 249, 255) 0%, rgb(240, 253, 244) 100%);"><div class="max-w-7xl mx-auto px-6"><div class="text-center mb-14"><div class="inline-block px-4 py-1.5 rounded-full text-sm font-semibold mb-4" style="background: rgb(220, 252, 231); color: rgb(22, 163, 74);">Why Choose Younimatiq</div><h2 class="text-3xl md:text-4xl font-extrabold text-on-surface" style="font-family: Sora, sans-serif; letter-spacing: -0.02em;">Built Different. On Purpose.</h2></div><div class="grid grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-10"><div class="bg-white rounded-3xl p-7 flex flex-col gap-3" style="box-shadow: rgba(30, 58, 138, 0.07) 0px 2px 20px; animation: 0.6s ease-out 0s 1 normal both running fadeSlideUp; transition: transform 0.3s;"><div class="text-4xl">🎯</div><h3 class="font-bold text-on-surface text-base" style="font-family: Sora, sans-serif;">1:1 Personalized Coaching</h3><p class="text-sm text-on-surface leading-relaxed">Certified expert tutors dedicated to your child — not group classes where kids get lost.</p></div><div class="bg-white rounded-3xl p-7 flex flex-col gap-3" style="box-shadow: rgba(30, 58, 138, 0.07) 0px 2px 20px; animation: 0.6s ease-out 0.12s 1 normal both running fadeSlideUp; transition: transform 0.3s; transform: translateY(0px);"><div class="text-4xl">🎓</div><h3 class="font-bold text-on-surface text-base" style="font-family: Sora, sans-serif;">Harvard & MIT Expert Curriculum</h3><p class="text-sm text-on-surface leading-relaxed">Rigorous, Common Core-aligned programs built by world-class educators.</p></div><div class="bg-white rounded-3xl p-7 flex flex-col gap-3" style="box-shadow: rgba(30, 58, 138, 0.07) 0px 2px 20px; animation: 0.6s ease-out 0.24s 1 normal both running fadeSlideUp; transition: transform 0.3s; transform: translateY(0px);"><div class="text-4xl">🗂️</div><h3 class="font-bold text-on-surface text-base" style="font-family: Sora, sans-serif;">Real Progress, Real Portfolio</h3><p class="text-sm text-on-surface leading-relaxed">Every course ends in a shareable project — tangible proof of your child's skills.</p></div><div class="bg-white rounded-3xl p-7 flex flex-col gap-3" style="box-shadow: rgba(30, 58, 138, 0.07) 0px 2px 20px; animation: 0.6s ease-out 0.36s 1 normal both running fadeSlideUp; transition: transform 0.3s;"><div class="text-4xl">🔄</div><h3 class="font-bold text-on-surface text-base" style="font-family: Sora, sans-serif;">Younimatiq Learning Guarantee</h3><p class="text-sm text-on-surface leading-relaxed">If a concept doesn't click, your child gets a free class replay — no questions asked.</p></div></div><div class="flex items-center justify-center gap-2 py-4 rounded-2xl" style="background: rgb(220, 252, 231);"><span class="text-xl">🌳</span><p class="text-sm font-semibold text-tertiary">For every 24 classes completed, Younimatiq plants a tree — because the next generation deserves a planet worth building for.</p></div></div></section><section class="py-20 md:py-24 bg-white"><div class="w-full px-6 md:px-12"><div class="text-center mb-12"><h2 class="text-3xl md:text-4xl font-extrabold text-on-surface" id="reviews" style="font-family: Sora, sans-serif; letter-spacing: -0.02em;">Parents Love Younimatiq</h2><p class="text-on-surface-variant mt-3">Real stories from real families around the world.</p></div>
<div id="reviewsScrollTrack" style="overflow: hidden; position: relative;">
<div id="reviewsScrollInner" style="display: flex; gap: 24px; width: max-content; padding-bottom: 8px;"><div class="shrink-0 rounded-3xl p-7 flex flex-col gap-4" style="width: 340px;" style="background: rgb(248, 250, 252); box-shadow: rgba(30, 58, 138, 0.06) 0px 2px 20px; animation: 0.6s ease-out 0s 1 normal both running fadeSlideUp;"><div class="flex"><svg fill="#f59e0b" height="16" stroke="#f59e0b" stroke-width="2" viewbox="0 0 24 24" width="16"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg><svg fill="#f59e0b" height="16" stroke="#f59e0b" stroke-width="2" viewbox="0 0 24 24" width="16"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg><svg fill="#f59e0b" height="16" stroke="#f59e0b" stroke-width="2" viewbox="0 0 24 24" width="16"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg><svg fill="#f59e0b" height="16" stroke="#f59e0b" stroke-width="2" viewbox="0 0 24 24" width="16"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg><svg fill="#f59e0b" height="16" stroke="#f59e0b" stroke-width="2" viewbox="0 0 24 24" width="16"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg></div><p class="text-on-surface leading-relaxed text-sm italic flex-1">"My daughter built her first Java Mod after just 4 sessions! The 1:1 coaching made all the difference — she's now obsessed with coding."</p><div class="flex items-center gap-3"><div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold text-sm shrink-0" style="background: rgb(37, 99, 235);">S</div><div><div class="font-semibold text-on-surface text-sm">Sarah M.</div><div class="text-xs text-on-surface-variant">Parent, Texas</div></div></div></div><div class="shrink-0 rounded-3xl p-7 flex flex-col gap-4" style="width: 340px; background: rgb(248, 250, 252); box-shadow: rgba(30, 58, 138, 0.06) 0px 2px 20px; animation: 0.6s ease-out 0.15s 1 normal both running fadeSlideUp;"><div class="flex"><svg fill="#f59e0b" height="16" stroke="#f59e0b" stroke-width="2" viewbox="0 0 24 24" width="16"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg><svg fill="#f59e0b" height="16" stroke="#f59e0b" stroke-width="2" viewbox="0 0 24 24" width="16"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg><svg fill="#f59e0b" height="16" stroke="#f59e0b" stroke-width="2" viewbox="0 0 24 24" width="16"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg><svg fill="#f59e0b" height="16" stroke="#f59e0b" stroke-width="2" viewbox="0 0 24 24" width="16"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg><svg fill="#f59e0b" height="16" stroke="#f59e0b" stroke-width="2" viewbox="0 0 24 24" width="16"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg></div><p class="text-on-surface leading-relaxed text-sm italic flex-1">"Younimatiq's Minecraft course gave my son real redstone engineering skills. He won the STEM fair with his automated farm. Worth every dollar."</p><div class="flex items-center gap-3"><div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold text-sm shrink-0" style="background: rgb(34, 197, 94);">J</div><div><div class="font-semibold text-on-surface text-sm">James K.</div><div class="text-xs text-on-surface-variant">Parent, Sydney</div></div></div></div><div class="shrink-0 rounded-3xl p-7 flex flex-col gap-4" style="width: 340px; background: rgb(248, 250, 252); box-shadow: rgba(30, 58, 138, 0.06) 0px 2px 20px; animation: 0.6s ease-out 0.3s 1 normal both running fadeSlideUp;"><div class="flex"><svg fill="#f59e0b" height="16" stroke="#f59e0b" stroke-width="2" viewbox="0 0 24 24" width="16"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg><svg fill="#f59e0b" height="16" stroke="#f59e0b" stroke-width="2" viewbox="0 0 24 24" width="16"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg><svg fill="#f59e0b" height="16" stroke="#f59e0b" stroke-width="2" viewbox="0 0 24 24" width="16"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg><svg fill="#f59e0b" height="16" stroke="#f59e0b" stroke-width="2" viewbox="0 0 24 24" width="16"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg><svg fill="#f59e0b" height="16" stroke="#f59e0b" stroke-width="2" viewbox="0 0 24 24" width="16"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg></div><p class="text-on-surface leading-relaxed text-sm italic flex-1">"The expert curriculum is legit rigorous but completely kid-friendly. My 9-year-old understands complex redstone concepts!"</p><div class="flex items-center gap-3"><div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold text-sm shrink-0" style="background: rgb(245, 158, 11);">P</div><div><div class="font-semibold text-on-surface text-sm">Priya R.</div><div class="text-xs text-on-surface-variant">Parent, Melbourne</div></div></div></div></div><!-- end reviewsScrollInner -->
</div><!-- end reviewsScrollTrack -->
<style>
  #reviewsScrollTrack {
    -webkit-mask-image: linear-gradient(to right, transparent 0%, black 6%, black 94%, transparent 100%);
    mask-image: linear-gradient(to right, transparent 0%, black 6%, black 94%, transparent 100%);
  }
</style>
<script>
(function() {
  const track = document.getElementById('reviewsScrollTrack');
  const inner = document.getElementById('reviewsScrollInner');
  if (!track || !inner) return;

  // Duplicate cards for seamless infinite loop
  const origCards = Array.from(inner.children);
  origCards.forEach(function(card) {
    const clone = card.cloneNode(true);
    clone.setAttribute('aria-hidden', 'true');
    inner.appendChild(clone);
  });

  let paused = false;
  let offset = 0;
  const speed = 0.4; // slow — gives users reading time

  function step() {
    if (!paused) {
      offset += speed;
      const half = inner.scrollWidth / 2;
      if (offset >= half) offset = 0;
      inner.style.transform = 'translateX(-' + offset + 'px)';
    }
    requestAnimationFrame(step);
  }

  track.addEventListener('mouseenter', function() { paused = true; });
  track.addEventListener('mouseleave', function() { paused = false; });
  track.addEventListener('touchstart', function() { paused = true; }, { passive: true });
  track.addEventListener('touchend', function() {
    setTimeout(function() { paused = false; }, 1500);
  }, { passive: true });

  requestAnimationFrame(step);
})();
</script>
<div class="mt-16 text-center"><p class="text-xs font-semibold uppercase tracking-widest text-outline">Trusted by families in 30+ countries</p></div></div></div></section><section class="py-20 md:py-24 rounded-[2rem] lg:rounded-[3rem] mx-4 lg:mx-8 my-12 overflow-hidden shadow-2xl" id="pricing" style="background: linear-gradient(135deg, #10b981 0%, #047857 100%);"><div class="w-full px-6 md:px-12"><div class="text-center mb-16"><div class="inline-block px-4 py-1.5 rounded-full text-sm font-semibold mb-4 text-white" style="background: rgba(255, 255, 255, 0.2); border: 1px solid rgba(255, 255, 255, 0.3);">Younimatiq • Minecraft Program</div><h2 class="text-4xl md:text-5xl font-extrabold text-white mb-6" style="font-family: Sora, sans-serif; letter-spacing: -0.02em;">Live, instructor-led Minecraft classes for ages 6–14.</h2><p class="text-lg text-secondary-container max-w-3xl mx-auto">1:1 Personalized classes, paced entirely to your child — build redstone circuits, write real code, and ship a mod with a dedicated instructor.</p></div><div data-currency-only="INR">
<div class="grid md:grid-cols-2 gap-8 max-w-4xl mx-auto">
  <div class="bg-white rounded-3xl p-8 flex flex-col shadow-2xl relative border border-outline-variant hover:border-secondary transition-colors">
    <div class="absolute top-4 right-4 flex flex-col items-end gap-0.5">
      <span class="text-xs font-semibold text-error line-through">₹1,000<span class="font-normal not-italic">/class</span></span>
      <span class="text-[10px] font-bold text-white bg-error rounded-full px-2 py-0.5 leading-none">SAVE 50%</span>
    </div>
    <div class="text-sm font-bold tracking-wider text-on-secondary-container mb-2 uppercase">Minecraft Program</div>
    <div class="flex items-baseline gap-2 mb-1">
      <span class="text-5xl font-extrabold text-on-surface">₹500</span>
      <span class="text-on-surface-variant font-medium">/ class</span>
    </div>
    <div class="mb-4">
      <span class="text-sm text-error line-through font-medium">was ₹1,000</span>
    </div>
    <p class="text-on-surface text-sm mb-6 mt-6">Full access to your child's track, taught by expert instructors.</p>
    <ul class="space-y-4 mb-8 flex-1">
      <li class="flex items-start gap-3 text-sm text-on-surface"><i class="fa-solid fa-check text-on-tertiary-container mt-0.5"></i> Group class</li>
      <li class="flex items-start gap-3 text-sm text-on-surface"><i class="fa-solid fa-check text-on-tertiary-container mt-0.5"></i> 1–2 live classes / week</li>
      <li class="flex items-start gap-3 text-sm text-on-surface"><i class="fa-solid fa-check text-on-tertiary-container mt-0.5"></i> Track-matched curriculum & projects</li>
      <li class="flex items-start gap-3 text-sm text-on-surface"><i class="fa-solid fa-check text-on-tertiary-container mt-0.5"></i> Progress reports after every class</li>
      <li class="flex items-start gap-3 text-sm text-on-surface"><i class="fa-solid fa-check text-on-tertiary-container mt-0.5"></i> Flexible reschedule, easy make-ups</li>
    </ul>
    <a class="block w-full text-center py-3.5 rounded-xl font-bold text-white transition-all bg-secondary hover:bg-secondary" href="#leadModal" onclick="openLeadModal(event, 'minecraft')">Get Started</a>
  </div>

  <div class="bg-white rounded-3xl p-8 flex flex-col shadow-2xl relative border-2 border-secondary">
    <div class="absolute -top-4 left-1/2 -translate-x-1/2 bg-secondary text-white text-xs font-bold px-4 py-1 rounded-full whitespace-nowrap">1:1 POPULAR</div>
    <div class="absolute top-4 right-4 flex flex-col items-end gap-0.5">
      <span class="text-xs font-semibold text-error line-through">₹1,200<span class="font-normal not-italic">/class</span></span>
      <span class="text-[10px] font-bold text-white bg-error rounded-full px-2 py-0.5 leading-none">SAVE 50%</span>
    </div>
    <div class="text-sm font-bold tracking-wider text-on-secondary-container mb-2 uppercase">1:1 Minecraft Program</div>
    <div class="flex items-baseline gap-2 mb-1">
      <span class="text-5xl font-extrabold text-on-surface">₹600</span>
      <span class="text-on-surface-variant font-medium">/ class</span>
    </div>
    <div class="mb-4">
      <span class="text-sm text-error line-through font-medium">was ₹1,200</span>
    </div>
    <p class="text-on-surface text-sm mb-6 mt-6">Full access to your child's track, with a dedicated instructor.</p>
    <ul class="space-y-4 mb-8 flex-1">
      <li class="flex items-start gap-3 text-sm text-on-surface"><i class="fa-solid fa-check text-on-tertiary-container mt-0.5"></i> 1:1 personalized class</li>
      <li class="flex items-start gap-3 text-sm text-on-surface"><i class="fa-solid fa-check text-on-tertiary-container mt-0.5"></i> 1–2 live classes / week</li>
      <li class="flex items-start gap-3 text-sm text-on-surface"><i class="fa-solid fa-check text-on-tertiary-container mt-0.5"></i> Track-matched curriculum & projects</li>
      <li class="flex items-start gap-3 text-sm text-on-surface"><i class="fa-solid fa-check text-on-tertiary-container mt-0.5"></i> Progress reports after every class</li>
      <li class="flex items-start gap-3 text-sm text-on-surface"><i class="fa-solid fa-check text-on-tertiary-container mt-0.5"></i> Flexible reschedule, easy make-ups</li>
    </ul>
    <a class="block w-full text-center py-3.5 rounded-xl font-bold text-white transition-all bg-secondary hover:bg-secondary" href="#leadModal" onclick="openLeadModal(event, 'minecraft')">Get Started</a>
  </div>
</div>
</div>
<div data-currency-except="INR">
<div class="grid md:grid-cols-3 gap-8">

{{-- Card 1: 12-Class --}}
<div class="bg-white rounded-3xl p-8 flex flex-col shadow-2xl relative">
  <div class="absolute top-4 right-4 flex flex-col items-end gap-0.5">
    <span class="text-xs font-semibold text-error line-through">@price(22, ['INR' => 1000])<span class="font-normal not-italic">/class</span></span>
    <span class="text-[10px] font-bold text-white bg-error rounded-full px-2 py-0.5 leading-none">SAVE 18%</span>
  </div>
  <div class="text-sm font-bold tracking-wider text-on-secondary-container mb-2 uppercase">12-Class Plan</div>
    <div class="flex items-baseline gap-2 mb-1">
      @price(18, [], 'text-5xl font-extrabold text-on-surface')
      <span class="text-on-surface-variant font-medium">/ class</span>
    </div>
    <div class="mb-4">
      <span class="text-sm text-error line-through font-medium">
        was @price(22)
      </span>
    </div>
    <div class="inline-block px-3 py-1 rounded-md text-xs font-semibold mb-6 bg-tertiary-fixed text-on-tertiary-container w-fit">12 classes total</div>
  <p class="text-on-surface text-sm mb-6">Full access to your child's track, taught by expert instructors.</p>
  <ul class="space-y-4 mb-8 flex-1">
    <li class="flex items-start gap-3 text-sm text-on-surface"><i class="fa-solid fa-check text-on-tertiary-container mt-0.5"></i> 1:1 with a dedicated instructor</li>
    <li class="flex items-start gap-3 text-sm text-on-surface"><i class="fa-solid fa-check text-on-tertiary-container mt-0.5"></i> 1–2 live classes / week</li>
    <li class="flex items-start gap-3 text-sm text-on-surface"><i class="fa-solid fa-check text-on-tertiary-container mt-0.5"></i> Track-matched curriculum & projects</li>
    <li class="flex items-start gap-3 text-sm text-on-surface"><i class="fa-solid fa-check text-on-tertiary-container mt-0.5"></i> Progress reports after every class</li>
    <li class="flex items-start gap-3 text-sm text-on-surface"><i class="fa-solid fa-check text-on-tertiary-container mt-0.5"></i> Flexible reschedule, easy make-ups</li>
  </ul>
  <a class="block w-full text-center py-3.5 rounded-xl font-bold text-white transition-all bg-secondary hover:bg-secondary" href="#leadModal" onclick="openLeadModal(event, 'minecraft')">Get Started</a>
</div>

{{-- Card 2: 24-Class (Most Popular) --}}
<div class="bg-white rounded-3xl p-8 flex flex-col shadow-2xl relative transform md:-translate-y-4 border-2 border-secondary">
  <div class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 bg-secondary text-white px-4 py-1 rounded-full text-xs font-bold tracking-wider uppercase">Most Popular</div>
  <div class="absolute top-4 right-4 flex flex-col items-end gap-0.5">
    <span class="text-xs font-semibold text-error line-through">@price(20, ['INR' => 1000])<span class="font-normal not-italic">/class</span></span>
    <span class="text-[10px] font-bold text-white bg-error rounded-full px-2 py-0.5 leading-none">SAVE 20%</span>
  </div>
  <div class="text-sm font-bold tracking-wider text-on-secondary-container mb-2 uppercase">24-Class Plan</div>
  <div class="flex items-baseline gap-2 mb-1">
    @price(16, [], 'text-5xl font-extrabold text-on-surface')
    <span class="text-on-surface-variant font-medium">/ class</span>
  </div>
  <div class="mb-4">
    <span class="text-sm text-error line-through font-medium">
      was @price(20)
    </span>
  </div>
  <div class="inline-block px-3 py-1 rounded-md text-xs font-semibold mb-6 bg-tertiary-fixed text-on-tertiary-container w-fit">24 classes total</div>
  <p class="text-on-surface text-sm mb-6">Full access to your child's track, taught by expert instructors.</p>
  <ul class="space-y-4 mb-8 flex-1">
    <li class="flex items-start gap-3 text-sm text-on-surface"><i class="fa-solid fa-check text-on-tertiary-container mt-0.5"></i> 1:1 with a dedicated instructor</li>
    <li class="flex items-start gap-3 text-sm text-on-surface"><i class="fa-solid fa-check text-on-tertiary-container mt-0.5"></i> 1–2 live classes / week</li>
    <li class="flex items-start gap-3 text-sm text-on-surface"><i class="fa-solid fa-check text-on-tertiary-container mt-0.5"></i> Track-matched curriculum & projects</li>
    <li class="flex items-start gap-3 text-sm text-on-surface"><i class="fa-solid fa-check text-on-tertiary-container mt-0.5"></i> Progress reports after every class</li>
    <li class="flex items-start gap-3 text-sm text-on-surface"><i class="fa-solid fa-check text-on-tertiary-container mt-0.5"></i> Flexible reschedule, easy make-ups</li>
  </ul>
  <a class="block w-full text-center py-3.5 rounded-xl font-bold text-white transition-all bg-secondary hover:bg-secondary" href="#leadModal" onclick="openLeadModal(event, 'minecraft')">Get Started</a>
</div>

{{-- Card 3: 48-Class --}}
<div class="bg-white rounded-3xl p-8 flex flex-col shadow-2xl relative">
  <div class="absolute top-4 right-4 flex flex-col items-end gap-0.5">
    <span class="text-xs font-semibold text-error line-through">@price(18, ['INR' => 1000])<span class="font-normal not-italic">/class</span></span>
    <span class="text-[10px] font-bold text-white bg-error rounded-full px-2 py-0.5 leading-none">SAVE 22%</span>
  </div>
  <div class="text-sm font-bold tracking-wider text-on-secondary-container mb-2 uppercase">48-Class Plan</div>
  <div class="flex items-baseline gap-2 mb-1">
    @price(14, [], 'text-5xl font-extrabold text-on-surface')
    <span class="text-on-surface-variant font-medium">/ class</span>
  </div>
  <div class="mb-4">
    <span class="text-sm text-error line-through font-medium">
      was @price(18)
    </span>
  </div>
  <div class="inline-block px-3 py-1 rounded-md text-xs font-semibold mb-6 bg-tertiary-fixed text-on-tertiary-container w-fit">48 classes total</div>
  <p class="text-on-surface text-sm mb-6">Full access to your child's track, taught by expert instructors.</p>
  <ul class="space-y-4 mb-8 flex-1">
    <li class="flex items-start gap-3 text-sm text-on-surface"><i class="fa-solid fa-check text-on-tertiary-container mt-0.5"></i> 1:1 with a dedicated instructor</li>
    <li class="flex items-start gap-3 text-sm text-on-surface"><i class="fa-solid fa-check text-on-tertiary-container mt-0.5"></i> 1–2 live classes / week</li>
    <li class="flex items-start gap-3 text-sm text-on-surface"><i class="fa-solid fa-check text-on-tertiary-container mt-0.5"></i> Track-matched curriculum & projects</li>
    <li class="flex items-start gap-3 text-sm text-on-surface"><i class="fa-solid fa-check text-on-tertiary-container mt-0.5"></i> Progress reports after every class</li>
    <li class="flex items-start gap-3 text-sm text-on-surface"><i class="fa-solid fa-check text-on-tertiary-container mt-0.5"></i> Flexible reschedule, easy make-ups</li>
  </ul>
  <a class="block w-full text-center py-3.5 rounded-xl font-bold text-white transition-all bg-secondary hover:bg-secondary" href="#leadModal" onclick="openLeadModal(event, 'minecraft')">Get Started</a>
</div>


</div>
</div></div></section><section class="py-24 relative overflow-hidden" style="background: #0f172a;">
    <!-- Abstract Background Glows -->
    <div class="absolute top-0 left-1/4 w-96 h-96 bg-secondary/10 rounded-full blur-[100px] pointer-events-none"></div>
    <div class="absolute bottom-0 right-1/4 w-96 h-96 bg-tertiary/10 rounded-full blur-[100px] pointer-events-none"></div>

    <div class="max-w-7xl mx-auto px-6 relative z-10">
        <div class="text-center mb-16">
            <div class="inline-block px-5 py-2 rounded-full text-sm font-bold tracking-wide mb-6 uppercase" style="background: rgba(34, 197, 94, 0.1); color: rgb(74, 222, 128); border: 1px solid rgba(34, 197, 94, 0.2);">
                Logic, Engineering & Modding
            </div>
            <h2 class="text-4xl md:text-5xl font-extrabold text-white mb-4" id="how-it-works" style="font-family: Sora, sans-serif; letter-spacing: -0.02em;">
                The <span style="color: #4ade80;">Creative</span> Path to Mastery
            </h2>
            <p class="text-outline max-w-2xl mx-auto text-base md:text-lg">
                We transform Minecraft from a game into a canvas for engineering. Discover how our students progress from basic logic to deploying complex Java mods.
            </p>
        </div>

        <!-- Creative Cards Layout -->
        <div class="flex overflow-x-auto md:grid md:grid-cols-2 lg:grid-cols-4 gap-6 hide-scrollbar" x-data="{ 
    req: null, 
    speed: 0.5, 
    accum: 0,
    init() { 
        if(window.innerWidth < 768) { 
            const step = () => { 
                if(!this.$el.matches(':hover') && !this.$el.matches(':active') && !this.$el.matches(':focus-within')) { 
                    this.accum += this.speed; 
                    if(this.accum >= 1) { 
                        this.$el.scrollLeft += Math.floor(this.accum); 
                        this.accum -= Math.floor(this.accum); 
                    } 
                    if(this.$el.scrollLeft >= this.$el.scrollWidth - this.$el.clientWidth - 1) { 
                        this.$el.scrollLeft = 0; 
                    } 
                } 
                this.req = requestAnimationFrame(step); 
            }; 
            this.req = requestAnimationFrame(step); 
        } 
    } 
}">
            
            <!-- Card 1 -->
            <div class="shrink-0 w-[85vw] md:w-auto group relative rounded-3xl overflow-hidden cursor-pointer aspect-[4/5] lg:aspect-auto lg:h-[480px] transform transition-all duration-700 hover:-translate-y-2 hover:shadow-[0_20px_40px_-15px_rgba(59,130,246,0.3)] border border-secondary/20">
                <div class="absolute inset-0 bg-on-surface-variant z-0"></div>
                <div class="absolute inset-0 transition-opacity duration-700 bg-cover bg-center mix-blend-screen" style="background-image: url('{{ asset("images/minecraft_hero_1787217370958.webp") }}'); filter: grayscale(100%) sepia(100%) hue-rotate(190deg) saturate(300%) opacity(0.4);"></div>
                <div class="absolute inset-0 bg-gradient-to-t from-outline via-outline/70 to-transparent z-10"></div>
                
                <div class="relative z-20 h-full flex flex-col justify-between p-8">
                    <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-2xl font-mono font-bold" style="background: rgba(59,130,246,0.1); border: 1px solid rgba(59,130,246,0.2); color: #60a5fa;">1</div>
                    <div>
                        <h3 class="text-2xl font-bold text-white mb-3 font-sora">Learn Logic</h3>
                        <p class="text-outline text-sm leading-relaxed">
                            Students master the basics of computational thinking, binary logic, and problem-solving through interactive games.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Card 2 -->
            <div class="shrink-0 w-[85vw] md:w-auto group relative rounded-3xl overflow-hidden cursor-pointer aspect-[4/5] lg:aspect-auto lg:h-[480px] transform transition-all duration-700 hover:-translate-y-2 hover:shadow-[0_20px_40px_-15px_rgba(168,85,247,0.3)] border border-primary/20" style="border-color: rgba(168,85,247,0.2);">
                <div class="absolute inset-0 bg-on-surface-variant z-0"></div>
                <div class="absolute inset-0 transition-opacity duration-700 bg-cover bg-center mix-blend-screen" style="background-image: url('{{ asset("images/minecraft_redstone_1787217484129.webp") }}'); filter: grayscale(100%) sepia(100%) hue-rotate(250deg) saturate(300%) opacity(0.4);"></div>
                <div class="absolute inset-0 bg-gradient-to-t from-outline via-outline/70 to-transparent z-10"></div>
                
                <div class="relative z-20 h-full flex flex-col justify-between p-8">
                    <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-2xl font-mono font-bold" style="background: rgba(168,85,247,0.1); border: 1px solid rgba(168,85,247,0.2); color: #c084fc;">2</div>
                    <div>
                        <h3 class="text-2xl font-bold text-white mb-3 font-sora">Build Redstone</h3>
                        <p class="text-outline text-sm leading-relaxed">
                            Translate logic into reality by constructing complex redstone circuits, automated farms, and engineered machines.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Card 3 -->
            <div class="shrink-0 w-[85vw] md:w-auto group relative rounded-3xl overflow-hidden cursor-pointer aspect-[4/5] lg:aspect-auto lg:h-[480px] transform transition-all duration-700 hover:-translate-y-2 hover:shadow-[0_20px_40px_-15px_rgba(249,115,22,0.3)] border border-warning/20" style="border-color: rgba(249,115,22,0.2);">
                <div class="absolute inset-0 bg-on-surface-variant z-0"></div>
                <div class="absolute inset-0 transition-opacity duration-700 bg-cover bg-center mix-blend-screen" style="background-image: url('{{ asset("images/minecraft_coding_1787217529940.webp") }}'); filter: grayscale(100%) sepia(100%) hue-rotate(10deg) saturate(300%) opacity(0.4);"></div>
                <div class="absolute inset-0 bg-gradient-to-t from-outline via-outline/70 to-transparent z-10"></div>
                
                <div class="relative z-20 h-full flex flex-col justify-between p-8">
                    <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-2xl font-mono font-bold" style="background: rgba(249,115,22,0.1); border: 1px solid rgba(249,115,22,0.2); color: #fb923c;">3</div>
                    <div>
                        <h3 class="text-2xl font-bold text-white mb-3 font-sora">Write Code</h3>
                        <p class="text-outline text-sm leading-relaxed">
                            Dive into the backend of Minecraft. Kids learn syntax and write actual Java code to modify game behavior.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Card 4 -->
            <div class="shrink-0 w-[85vw] md:w-auto group relative rounded-3xl overflow-hidden cursor-pointer aspect-[4/5] lg:aspect-auto lg:h-[480px] transform transition-all duration-700 hover:-translate-y-2 hover:shadow-[0_20px_40px_-15px_rgba(34,197,94,0.3)] border border-tertiary/20">
                <div class="absolute inset-0 bg-on-surface-variant z-0"></div>
                <div class="absolute inset-0 transition-opacity duration-700 bg-cover bg-center mix-blend-screen" style="background-image: url('{{ asset("images/minecraft_character.webp") }}'); filter: grayscale(100%) sepia(100%) hue-rotate(90deg) saturate(300%) opacity(0.5);"></div>
                <div class="absolute inset-0 bg-gradient-to-t from-outline via-outline/70 to-transparent z-10"></div>
                
                <div class="relative z-20 h-full flex flex-col justify-between p-8">
                    <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-2xl font-mono font-bold" style="background: rgba(34,197,94,0.1); border: 1px solid rgba(34,197,94,0.2); color: #4ade80;">4</div>
                    <div>
                        <h3 class="text-2xl font-bold text-white mb-3 font-sora">Deploy Mod</h3>
                        <p class="text-outline text-sm leading-relaxed">
                            Compile, test, and deploy custom mods to real worlds. Students build an impressive portfolio of creations.
                        </p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section><section class="pb-20 md:pb-24 pt-12" style="background: #fafbfc;"><div class="max-w-7xl mx-auto px-6"><h2 class="text-3xl md:text-4xl font-extrabold text-on-surface mb-10" style="font-family: Sora, sans-serif; letter-spacing: -0.02em;">Frequently asked questions</h2><div class="space-y-5"><div class="bg-white border border-outline-variant rounded-2xl overflow-hidden transition-all duration-300 shadow-sm" x-data="{ open: false }"><button @click="open = !open" class="w-full px-6 py-5 flex items-center justify-between text-left focus:outline-none"><span class="font-bold text-on-surface text-lg">Why 1:1 instead of group classes?</span><span class="text-on-secondary-container text-2xl font-bold ml-4 leading-none" x-text="open ? '×' : '+'">+</span></button><div style="display: none;" x-show="open"><div class="px-6 pb-6 text-on-surface leading-relaxed">Every Younimatiq class is one-on-one with a dedicated instructor, so the pace, project difficulty, and feedback are built entirely around your child — no waiting on slower classmates and no feeling rushed by faster ones.</div></div></div><div class="bg-white border border-outline-variant rounded-2xl overflow-hidden transition-all duration-300 shadow-sm" x-data="{ open: false }"><button @click="open = !open" class="w-full px-6 py-5 flex items-center justify-between text-left focus:outline-none"><span class="font-bold text-on-surface text-lg">Do I need to buy hardware separately?</span><span class="text-on-secondary-container text-2xl font-bold ml-4 leading-none" x-text="open ? '×' : '+'">+</span></button><div style="display: none;" x-show="open"><div class="px-6 pb-6 text-on-surface leading-relaxed">Explorer and Builder tracks use simulators for most lessons, so no hardware purchase is required to start. The Innovator track uses physical kits, available to purchase or rent separately.</div></div></div><div class="bg-white border border-outline-variant rounded-2xl overflow-hidden transition-all duration-300 shadow-sm" x-data="{ open: false }"><button @click="open = !open" class="w-full px-6 py-5 flex items-center justify-between text-left focus:outline-none"><span class="font-bold text-on-surface text-lg">Can I switch tracks later?</span><span class="text-on-secondary-container text-2xl font-bold ml-4 leading-none" x-text="open ? '×' : '+'">+</span></button><div style="display: none;" x-show="open"><div class="px-6 pb-6 text-on-surface leading-relaxed">Yes — you can move up (or down) a track as your child progresses, at the start of your next 24-Class Plan. Your instructor and personalized pacing stay the same.</div></div></div><div class="bg-white border border-outline-variant rounded-2xl overflow-hidden transition-all duration-300 shadow-sm" x-data="{ open: false }"><button @click="open = !open" class="w-full px-6 py-5 flex items-center justify-between text-left focus:outline-none"><span class="font-bold text-on-surface text-lg">What happens if we miss a class?</span><span class="text-on-secondary-container text-2xl font-bold ml-4 leading-none" x-text="open ? '×' : '+'">+</span></button><div style="display: none;" x-show="open"><div class="px-6 pb-6 text-on-surface leading-relaxed">If you miss a class, a recording of the session will be sent to you so your child doesn't fall behind. If anything in it isn't clear, one of our Master Teachers will step in to walk your child through it.</div></div></div></div></div></section><section class="py-20 md:py-24 relative overflow-hidden" style="background: linear-gradient(135deg, rgb(30, 58, 138) 0%, rgb(37, 99, 235) 100%);"><div class="absolute inset-0 pointer-events-none overflow-hidden"><div class="absolute -right-32 -top-32 w-96 h-96 rounded-full opacity-10" style="background: radial-gradient(circle, rgb(34, 197, 94), transparent 70%); animation: 8s ease-in-out 0s infinite normal none running floatOrb;"></div><div class="absolute -left-20 -bottom-20 w-72 h-72 rounded-full opacity-10" style="background: radial-gradient(circle, rgb(96, 165, 250), transparent 70%); animation: 12s ease-in-out 2s infinite normal none running floatOrb;"></div><div style="position: absolute; inset: 0px; overflow: hidden; pointer-events: none; opacity: 0.55;"><div style="position: absolute; left: 30px; top: 0px; display: flex; flex-direction: column; gap: 4px; animation: 4s linear 0s infinite normal none running dataRain; pointer-events: none; user-select: none;"><span style="color: rgb(74, 222, 128); font-size: 11px; font-family: monospace; line-height: 16px; animation: 0.8s ease-in-out 0s infinite normal none running bitFade;">1</span><span style="color: rgba(74, 222, 128, 0.3); font-size: 11px; font-family: monospace; line-height: 16px; animation: 0.9s ease-in-out 0.05s infinite normal none running bitFade;">0</span><span style="color: rgba(74, 222, 128, 0.3); font-size: 11px; font-family: monospace; line-height: 16px; animation: 1s ease-in-out 0.1s infinite normal none running bitFade;">1</span><span style="color: rgba(74, 222, 128, 0.3); font-size: 11px; font-family: monospace; line-height: 16px; animation: 1.1s ease-in-out 0.15s infinite normal none running bitFade;">1</span><span style="color: rgba(74, 222, 128, 0.3); font-size: 11px; font-family: monospace; line-height: 16px; animation: 1.2s ease-in-out 0.2s infinite normal none running bitFade;">0</span><span style="color: rgba(74, 222, 128, 0.3); font-size: 11px; font-family: monospace; line-height: 16px; animation: 1.3s ease-in-out 0.25s infinite normal none running bitFade;">0</span><span style="color: rgba(74, 222, 128, 0.3); font-size: 11px; font-family: monospace; line-height: 16px; animation: 1.4s ease-in-out 0.3s infinite normal none running bitFade;">1</span><span style="color: rgba(74, 222, 128, 0.3); font-size: 11px; font-family: monospace; line-height: 16px; animation: 1.5s ease-in-out 0.35s infinite normal none running bitFade;">0</span></div><div style="position: absolute; left: 80px; top: 0px; display: flex; flex-direction: column; gap: 4px; animation: 5.5s linear 1.5s infinite normal none running dataRain; pointer-events: none; user-select: none;"><span style="color: rgb(74, 222, 128); font-size: 11px; font-family: monospace; line-height: 16px; animation: 0.8s ease-in-out 0s infinite normal none running bitFade;">AI</span><span style="color: rgba(74, 222, 128, 0.3); font-size: 11px; font-family: monospace; line-height: 16px; animation: 0.9s ease-in-out 0.05s infinite normal none running bitFade;">ML</span><span style="color: rgba(74, 222, 128, 0.3); font-size: 11px; font-family: monospace; line-height: 16px; animation: 1s ease-in-out 0.1s infinite normal none running bitFade;">→</span><span style="color: rgba(74, 222, 128, 0.3); font-size: 11px; font-family: monospace; line-height: 16px; animation: 1.1s ease-in-out 0.15s infinite normal none running bitFade;">0</span><span style="color: rgba(74, 222, 128, 0.3); font-size: 11px; font-family: monospace; line-height: 16px; animation: 1.2s ease-in-out 0.2s infinite normal none running bitFade;">1</span><span style="color: rgba(74, 222, 128, 0.3); font-size: 11px; font-family: monospace; line-height: 16px; animation: 1.3s ease-in-out 0.25s infinite normal none running bitFade;">⬡</span><span style="color: rgba(74, 222, 128, 0.3); font-size: 11px; font-family: monospace; line-height: 16px; animation: 1.4s ease-in-out 0.3s infinite normal none running bitFade;">0</span><span style="color: rgba(74, 222, 128, 0.3); font-size: 11px; font-family: monospace; line-height: 16px; animation: 1.5s ease-in-out 0.35s infinite normal none running bitFade;">1</span></div><div style="position: absolute; left: 140px; top: 0px; display: flex; flex-direction: column; gap: 4px; animation: 4.8s linear 0.8s infinite normal none running dataRain; pointer-events: none; user-select: none;"><span style="color: rgb(74, 222, 128); font-size: 11px; font-family: monospace; line-height: 16px; animation: 0.8s ease-in-out 0s infinite normal none running bitFade;">∑</span><span style="color: rgba(74, 222, 128, 0.3); font-size: 11px; font-family: monospace; line-height: 16px; animation: 0.9s ease-in-out 0.05s infinite normal none running bitFade;">λ</span><span style="color: rgba(74, 222, 128, 0.3); font-size: 11px; font-family: monospace; line-height: 16px; animation: 1s ease-in-out 0.1s infinite normal none running bitFade;">π</span><span style="color: rgba(74, 222, 128, 0.3); font-size: 11px; font-family: monospace; line-height: 16px; animation: 1.1s ease-in-out 0.15s infinite normal none running bitFade;">0</span><span style="color: rgba(74, 222, 128, 0.3); font-size: 11px; font-family: monospace; line-height: 16px; animation: 1.2s ease-in-out 0.2s infinite normal none running bitFade;">1</span><span style="color: rgba(74, 222, 128, 0.3); font-size: 11px; font-family: monospace; line-height: 16px; animation: 1.3s ease-in-out 0.25s infinite normal none running bitFade;">0</span><span style="color: rgba(74, 222, 128, 0.3); font-size: 11px; font-family: monospace; line-height: 16px; animation: 1.4s ease-in-out 0.3s infinite normal none running bitFade;">∞</span><span style="color: rgba(74, 222, 128, 0.3); font-size: 11px; font-family: monospace; line-height: 16px; animation: 1.5s ease-in-out 0.35s infinite normal none running bitFade;">1</span></div><div style="position: absolute; left: 200px; top: 0px; display: flex; flex-direction: column; gap: 4px; animation: 6.2s linear 2.2s infinite normal none running dataRain; pointer-events: none; user-select: none;"><span style="color: rgb(74, 222, 128); font-size: 11px; font-family: monospace; line-height: 16px; animation: 0.8s ease-in-out 0s infinite normal none running bitFade;">0</span><span style="color: rgba(74, 222, 128, 0.3); font-size: 11px; font-family: monospace; line-height: 16px; animation: 0.9s ease-in-out 0.05s infinite normal none running bitFade;">1</span><span style="color: rgba(74, 222, 128, 0.3); font-size: 11px; font-family: monospace; line-height: 16px; animation: 1s ease-in-out 0.1s infinite normal none running bitFade;">⬢</span><span style="color: rgba(74, 222, 128, 0.3); font-size: 11px; font-family: monospace; line-height: 16px; animation: 1.1s ease-in-out 0.15s infinite normal none running bitFade;">0</span><span style="color: rgba(74, 222, 128, 0.3); font-size: 11px; font-family: monospace; line-height: 16px; animation: 1.2s ease-in-out 0.2s infinite normal none running bitFade;">1</span><span style="color: rgba(74, 222, 128, 0.3); font-size: 11px; font-family: monospace; line-height: 16px; animation: 1.3s ease-in-out 0.25s infinite normal none running bitFade;">0</span><span style="color: rgba(74, 222, 128, 0.3); font-size: 11px; font-family: monospace; line-height: 16px; animation: 1.4s ease-in-out 0.3s infinite normal none running bitFade;">1</span><span style="color: rgba(74, 222, 128, 0.3); font-size: 11px; font-family: monospace; line-height: 16px; animation: 1.5s ease-in-out 0.35s infinite normal none running bitFade;">0</span></div><div style="position: absolute; left: 260px; top: 0px; display: flex; flex-direction: column; gap: 4px; animation: 4.3s linear 0.3s infinite normal none running dataRain; pointer-events: none; user-select: none;"><span style="color: rgb(74, 222, 128); font-size: 11px; font-family: monospace; line-height: 16px; animation: 0.8s ease-in-out 0s infinite normal none running bitFade;">ƒ(x)</span><span style="color: rgba(74, 222, 128, 0.3); font-size: 11px; font-family: monospace; line-height: 16px; animation: 0.9s ease-in-out 0.05s infinite normal none running bitFade;">∇</span><span style="color: rgba(74, 222, 128, 0.3); font-size: 11px; font-family: monospace; line-height: 16px; animation: 1s ease-in-out 0.1s infinite normal none running bitFade;">0</span><span style="color: rgba(74, 222, 128, 0.3); font-size: 11px; font-family: monospace; line-height: 16px; animation: 1.1s ease-in-out 0.15s infinite normal none running bitFade;">1</span><span style="color: rgba(74, 222, 128, 0.3); font-size: 11px; font-family: monospace; line-height: 16px; animation: 1.2s ease-in-out 0.2s infinite normal none running bitFade;">β</span><span style="color: rgba(74, 222, 128, 0.3); font-size: 11px; font-family: monospace; line-height: 16px; animation: 1.3s ease-in-out 0.25s infinite normal none running bitFade;">0</span><span style="color: rgba(74, 222, 128, 0.3); font-size: 11px; font-family: monospace; line-height: 16px; animation: 1.4s ease-in-out 0.3s infinite normal none running bitFade;">1</span><span style="color: rgba(74, 222, 128, 0.3); font-size: 11px; font-family: monospace; line-height: 16px; animation: 1.5s ease-in-out 0.35s infinite normal none running bitFade;">μ</span></div><div style="position: absolute; left: 320px; top: 0px; display: flex; flex-direction: column; gap: 4px; animation: 5.1s linear 1.1s infinite normal none running dataRain; pointer-events: none; user-select: none;"><span style="color: rgb(74, 222, 128); font-size: 11px; font-family: monospace; line-height: 16px; animation: 0.8s ease-in-out 0s infinite normal none running bitFade;">1</span><span style="color: rgba(74, 222, 128, 0.3); font-size: 11px; font-family: monospace; line-height: 16px; animation: 0.9s ease-in-out 0.05s infinite normal none running bitFade;">0</span><span style="color: rgba(74, 222, 128, 0.3); font-size: 11px; font-family: monospace; line-height: 16px; animation: 1s ease-in-out 0.1s infinite normal none running bitFade;">θ</span><span style="color: rgba(74, 222, 128, 0.3); font-size: 11px; font-family: monospace; line-height: 16px; animation: 1.1s ease-in-out 0.15s infinite normal none running bitFade;">1</span><span style="color: rgba(74, 222, 128, 0.3); font-size: 11px; font-family: monospace; line-height: 16px; animation: 1.2s ease-in-out 0.2s infinite normal none running bitFade;">0</span><span style="color: rgba(74, 222, 128, 0.3); font-size: 11px; font-family: monospace; line-height: 16px; animation: 1.3s ease-in-out 0.25s infinite normal none running bitFade;">σ</span><span style="color: rgba(74, 222, 128, 0.3); font-size: 11px; font-family: monospace; line-height: 16px; animation: 1.4s ease-in-out 0.3s infinite normal none running bitFade;">1</span><span style="color: rgba(74, 222, 128, 0.3); font-size: 11px; font-family: monospace; line-height: 16px; animation: 1.5s ease-in-out 0.35s infinite normal none running bitFade;">0</span></div></div></div><div class="relative max-w-3xl mx-auto px-6 text-center"><div class="text-5xl mb-6" style="animation: 2s ease-in-out 0s infinite normal none running robotBob;">🚀</div><h 2="" class="text-3xl md:text-5xl font-extrabold text-white mb-5" id="consult" style="font-family: Sora, sans-serif; letter-spacing: -0.02em;">Ready to Turn Screen Time Into Skill Time?<p class="text-secondary-container text-lg mb-8">Book a free consultation and get a personalized AI & STEM Lab plan for your child — no commitment required.</p><a class="inline-block px-10 py-4 rounded-full font-bold text-white text-lg shadow-2xl transition-all hover:scale-105 mb-10" href="#leadModal" onclick="openLeadModal(event, 'minecraft')" style='\"cursor:pointer;\"'>Book Free Consultation</a><div class="flex flex-wrap items-center justify-center gap-6 text-sm text-secondary-container"><a class="flex items-center gap-2 hover:text-white transition-colors" href="mailto:support@younimatiq.com"><svg fill="none" height="18" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="18"><rect height="16" rx="2" width="20" x="2" y="4"></rect><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path></svg> support@younimatiq.com</a><a class="flex items-center gap-2 hover:text-white transition-colors" href="tel:+12027944843"><svg fill="none" height="18" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="18"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 2.18h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 9.91a16 16 0 0 0 6.08 6.08l.97-.97a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"></path></svg> US +1 202-794-4843</a><a class="flex items-center gap-2 hover:text-white transition-colors" href="tel:+61470214835"><svg fill="none" height="18" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="18"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 2.18h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 9.91a16 16 0 0 0 6.08 6.08l.97-.97a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"></path></svg> AU +61-470-214835</a><a class="flex items-center gap-2 hover:text-white transition-colors" href="https://wa.me/12027944843"><svg fill="currentColor" height="20" viewbox="0 0 24 24" width="20"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"></path></svg> WhatsApp</a></div></h></div></section><footer class="w-full bg-inverse-surface text-white pt-24 pb-12 px-4 md:px-8">
<div class="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-4 gap-12 mb-16">
<div class="space-y-6">
<img loading="lazy" alt="Younimatiq Logo" class="h-20 w-auto bg-white p-2 rounded-2xl shadow-sm object-contain" loading="lazy" src="{{ asset('logo.webp') }}"/>
<p class="text-surface-variant font-body-md">
                    Empowering children to explore, create, and learn through a galaxy of interactive adventures. 100% online, 100% engaged.
                </p>
<div class="flex flex-wrap gap-3">
<a class="w-9 h-9 rounded-full bg-white/10 text-white flex items-center justify-center hover:bg-white hover:text-[#1877F2] transition-colors" href="https://www.facebook.com/younimatiq" target="_blank">
<svg class="w-4 h-4" fill="currentColor" viewbox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.469h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.469h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"></path></svg>
</a>
<a class="w-9 h-9 rounded-full bg-white/10 text-white flex items-center justify-center hover:bg-white hover:text-black transition-colors" href="https://x.com/younimatiq/" target="_blank">
<svg class="w-4 h-4" fill="currentColor" viewbox="0 0 24 24"><path d="M18.901 1.153h3.68l-8.04 9.19L24 22.846h-7.406l-5.8-7.584-6.638 7.584H.474l8.6-9.83L0 1.154h7.594l5.243 6.932ZM17.61 20.644h2.039L6.486 3.24H4.298Z"></path></svg>
</a>
<a class="w-9 h-9 rounded-full bg-white/10 text-white flex items-center justify-center hover:bg-white hover:text-[#FF0000] transition-colors" href="https://www.youtube.com/@younimatiq" target="_blank">
<svg class="w-4 h-4" fill="currentColor" viewbox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.5 12 3.5 12 3.5s-7.505 0-9.377.55a3.016 3.016 0 0 0-2.122 2.136C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.55 9.376.55 9.376.55s7.505 0 9.377-.55a3.016 3.016 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"></path></svg>
</a>
<a class="w-9 h-9 rounded-full bg-white/10 text-white flex items-center justify-center hover:bg-white hover:text-[#E60023] transition-colors" href="https://www.pinterest.com/younimatiq/" target="_blank">
<svg class="w-4 h-4" fill="currentColor" viewbox="0 0 24 24"><path d="M12 0a12 12 0 0 0-4.37 23.17c-.07-.65-.13-1.66.03-2.37.14-.63 1-4.22 1-4.22s-.26-.52-.26-1.28c0-1.2.7-2.1 1.56-2.1.73 0 1.08.55 1.08 1.2 0 .74-.47 1.84-.71 2.87-.2.86.43 1.56 1.27 1.56 1.53 0 2.7-1.61 2.7-3.93 0-2.05-1.47-3.48-3.57-3.48-2.4 0-3.8 1.8-3.8 3.65 0 .74.28 1.53.64 1.96.07.08.08.16.06.24-.07.29-.23.94-.26 1.05-.04.14-.14.17-.29.1-1.07-.5-1.74-2.07-1.74-3.33 0-2.7 1.96-5.18 5.66-5.18 2.97 0 5.28 2.11 5.28 4.93 0 2.95-1.86 5.33-4.44 5.33-.87 0-1.69-.45-1.97-.99l-.54 2.05c-.19.74-.71 1.66-1.06 2.22A12 12 0 1 0 12 0z"></path></svg>
</a>
<a class="w-9 h-9 rounded-full bg-white/10 text-white flex items-center justify-center hover:bg-white hover:text-[#DD2A7B] transition-colors" href="https://www.instagram.com/younimatiq/" target="_blank">
<svg class="w-4 h-4" fill="currentColor" viewbox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881z"></path></svg>
</a>
<a class="w-9 h-9 rounded-full bg-white/10 text-white flex items-center justify-center hover:bg-white hover:text-black transition-colors" href="https://www.tiktok.com/@younimatiq?_r=1&_t=ZP-97ghss4xXWp" target="_blank">
<svg class="w-4 h-4" fill="currentColor" viewbox="0 0 24 24"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.01.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93v7.2c0 1.63-.51 3.25-1.48 4.56-1.57 2.14-4.21 3.39-6.9 3.19-2.68-.2-5.06-1.84-6.27-4.25-1.16-2.28-1.07-5.09.23-7.29 1.34-2.25 3.86-3.62 6.48-3.61.12 0 .23 0 .35.01v4.06c-1.4-.24-2.88-.04-4.06.74-.95.63-1.61 1.66-1.73 2.78-.17 1.65.92 3.17 2.47 3.65 1.56.49 3.31.02 4.39-1.16 1.06-1.15 1.5-2.76 1.5-4.32V.02h.93z"></path></svg>
</a>
<a class="w-9 h-9 rounded-full bg-white/10 text-white flex items-center justify-center hover:bg-white hover:text-[#0A66C2] transition-colors" href="https://www.linkedin.com/company/younimatiq/" target="_blank">
<svg class="w-4 h-4" fill="currentColor" viewbox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"></path></svg>
</a>
</div>
</div>
<div>
<h4 class="font-bold text-xl mb-6">Quick Links</h4>
<ul class="space-y-4 text-surface-variant font-medium">
<li><a class="hover:text-white transition-colors" href="#home">Home</a></li>
<li><a class="hover:text-white transition-colors" href="#curriculum">Curriculum</a></li>
<li><a class="hover:text-white transition-colors" href="#how-it-works">How It Works</a></li>
<li><a class="hover:text-white transition-colors" href="#courses">Courses</a></li>
<li><a class="hover:text-white transition-colors" href="#reviews">Reviews</a></li>
<li><a class="hover:text-white transition-colors" href="#consult">Contact</a></li>
</ul>
</div>
<div>
<h4 class="font-bold text-xl mb-6">Contact Us</h4>
<ul class="space-y-4 text-surface-variant font-medium">
<li class="flex items-center gap-3">
<span class="material-symbols-outlined text-primary text-[18px]">call</span>
<a class="hover:text-white transition-colors" href="tel:+12027944843">USA +1 (202) 794 4843</a>
</li>
<li class="flex items-center gap-3">
<span class="material-symbols-outlined text-primary text-[18px]">call</span>
<a class="hover:text-white transition-colors" href="tel:+61470214835">Australia +61470214835</a>
</li>
<li class="flex items-center gap-3">
<span class="material-symbols-outlined text-primary text-[18px]">mail</span>
<a class="hover:text-white transition-colors" href="mailto:support@younimatiq.com">support@younimatiq.com</a>
</li>
<li class="flex items-start gap-3">
<span class="material-symbols-outlined text-primary text-[18px] mt-1">near_me</span>
<span>Texas, USA</span>
</li>
</ul>
<a class="mt-6 w-full btn-enroll btn-block" href="https://wa.me/12027944843" target="_blank">
<span class="material-symbols-outlined">chat</span>
                    Chat on Whatsapp
                </a>
</div>
<div class="space-y-6">
<h4 class="font-bold text-xl">Join the Mission</h4>
<p class="text-surface-variant font-medium">Subscribe for tips and early access to new courses.</p>
<form action="{{ route('newsletter.subscribe') }}" class="relative flex items-center" method="POST">
                    @csrf
                    <input class="w-full bg-white/10 border border-white/20 rounded-[var(--radius)] py-3 pl-4 pr-14 text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary font-medium" name="email" placeholder="Email address" required="" type="email"/>
<button class="absolute right-2 top-1/2 -translate-y-1/2 btn-primary btn-icon hover:scale-105 transition-transform" type="submit"><span class="material-symbols-outlined text-sm">send</span></button>
</form>
<a class="w-full btn-primary btn-block" href="mailto:support@younimatiq.com">
<span class="material-symbols-outlined">mail</span>
                    Email Us
                </a>
</div>
</div>
<div class="max-w-7xl mx-auto pt-8 border-t border-white/10 flex flex-col md:flex-row justify-between items-center gap-4 text-surface-variant text-sm font-medium">
<p>© {{ date('Y') }} Younimatiq - Reach for the Stars!</p>
<div class="flex gap-5 md:p-8">
<a class="hover:text-white" href="{{ route('pages.show', 'privacy-policy') }}">Privacy</a>
<a class="hover:text-white" href="{{ route('pages.show', 'terms-of-service') }}">Terms</a>
<a class="hover:text-white" href="{{ route('pages.show', 'cookie-policy') }}">Cookies</a>
<a class="hover:text-white" href="{{ route('contact') }}">Contact</a>
<a class="hover:text-white" href="{{ route('sitemap') }}">Sitemap</a>
</div>
</div>
</footer>
<!-- FLOATING ACTION BUTTONS (Matching app.blade.php) -->
<!-- Green Initiative FAB -->
<div class="fixed bottom-6 left-8 z-[60]" x-data="{
        expanded: false,
        text: '',
        fullText: 'Grow a greener earth',
        cycle() {
            this.expanded = true;
            this.text = '';
            let i = 0;
            let typeInterval = setInterval(() => {
                if (i < this.fullText.length) {
                    this.text += this.fullText.charAt(i);
                    i++;
                } else {
                    clearInterval(typeInterval);
                    setTimeout(() => {
                        this.text = '';
                        this.expanded = false;
                    }, 2000);
                }
            }, 50);
        },
        init() {
            setTimeout(() => {
                this.cycle();
                setInterval(() => {
                    this.cycle();
                }, 8000);
            }, 5000);
        }
    }">
<button :class="expanded ? 'rounded-[var(--radius)] px-5' : 'rounded-full justify-center px-0'" class="bg-white border-2 border-secondary shadow-[3px_4px_0_theme(colors.secondary.DEFAULT)] flex items-center hover:-translate-y-[1px] hover:shadow-[4px_5px_0_theme(colors.secondary.DEFAULT)] active:translate-y-[3px] active:translate-x-[3px] active:shadow-[0_0_0_theme(colors.secondary.DEFAULT)] transition-all duration-300 ease-in-out overflow-hidden h-[56px] min-w-[56px]" onclick="document.getElementById('green-modal').classList.remove('hidden')">
<span class="text-2xl shrink-0 flex items-center justify-center relative -top-px">🌱</span>
<span :class="expanded ? 'ml-3 opacity-100' : 'ml-0 opacity-0 w-0'" class="font-bold text-secondary whitespace-nowrap overflow-hidden transition-all duration-300 flex items-center" x-text="text"></span>
</button>
</div>
<!-- WhatsApp + Chatbot FABs (3D clay style) -->
<div class="fixed bottom-8 right-8 z-[60] flex flex-col-reverse items-center gap-5">
<a aria-label="Chat on WhatsApp" class="fab-3d fab-3d--whatsapp" href="https://wa.me/12027944843" rel="noopener noreferrer" target="_blank">
<svg aria-hidden="true" class="w-8 h-8 relative -top-px" fill="currentColor" viewbox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
<path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.347-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.876 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"></path>
</svg>
</a>
<style>
@keyframes chatbot-shake-enlarge {
  0%, 100% { transform: scale(1) rotate(0deg); }
  10% { transform: scale(1.15) rotate(-10deg); }
  20% { transform: scale(1.15) rotate(10deg); }
  30% { transform: scale(1.15) rotate(-10deg); }
  40% { transform: scale(1.15) rotate(10deg); }
  50% { transform: scale(1) rotate(0deg); }
}
.chatbot-animate {
  animation: chatbot-shake-enlarge 2s infinite ease-in-out;
}
</style>
<button aria-label="Open chatbot" class="fab-3d fab-3d--chat" onclick="document.getElementById('chatbot-window').classList.toggle('hidden')" type="button">
<img loading="lazy" src="{{ asset('images/chatbot.webp') }}" alt="Chatbot" class="w-12 h-12 object-contain chatbot-animate" />
</button>
</div>
<!-- Chatbot Window -->
<div class="fixed bottom-44 right-8 z-[70] hidden w-[min(400px,calc(100vw-2rem))] bg-white rounded-2xl shadow-2xl border border-surface-container-high overflow-hidden animate-in slide-in-from-bottom-5" id="chatbot-window">
<div class="bg-primary p-4 text-white flex justify-between items-center">
<div class="flex items-center gap-2">
<img loading="lazy" src="{{ asset('images/chatbot.webp') }}" class="w-[52px] h-[52px] object-contain" alt="Chatbot" />
<h3 class="font-bold">Younimatiq Assistant</h3>
</div>
<button aria-label="Close chatbot" class="hover:text-surface-container transition-colors" onclick="document.getElementById('chatbot-window').classList.add('hidden')" type="button">
<span class="material-symbols-outlined">close</span>
</button>
</div>
<div class="h-[400px] bg-surface-container-low p-4 overflow-y-auto flex flex-col gap-3" id="chatbot-messages">
<div class="flex gap-2 max-w-[80%]">
<div class="w-8 h-8 rounded-full bg-white flex-shrink-0 flex items-center justify-center overflow-hidden"><img loading="lazy" src="{{ asset('images/chatbot.webp') }}" class="w-6 h-6 object-contain" alt="Chatbot" /></div>
<div class="bg-white p-3 rounded-2xl rounded-tl-none shadow-sm text-sm text-on-surface">Hello! I'm Younimatiq's virtual assistant. How can I help you today?</div>
</div>
</div>
<div class="p-3 bg-white border-t border-surface-container-high flex gap-2 items-center">
<input class="flex-1 px-4 py-2 bg-surface-container-low rounded-full outline-none focus:ring-1 focus:ring-primary text-sm" id="chatbot-input" onkeypress="if(event.key === 'Enter') sendChatbotMessage()" placeholder="Type a message..." type="text"/>
<button aria-label="Send message" class="w-10 h-10 rounded-full bg-primary text-white flex items-center justify-center hover:scale-105 active:scale-95 transition-transform shrink-0" onclick="sendChatbotMessage()" type="button"><span class="material-symbols-outlined text-sm">send</span></button>
</div>
</div>
<!-- Green Modal --><!-- Green Modal -->
<div class="fixed inset-0 z-[70] hidden flex items-center justify-center p-4" id="green-modal">
<div class="absolute inset-0 bg-on-background/40 backdrop-blur-sm" onclick="this.parentElement.classList.add('hidden')"></div>
<div class="relative bg-white rounded-2xl w-full max-w-md shadow-2xl animate-in zoom-in duration-300 overflow-hidden flex flex-col">
<!-- Header -->
<div class="bg-[#2c772b] text-white p-4 px-6 flex justify-between items-center">
<h3 class="text-xl font-extrabold flex items-center gap-2">🌳 Grow a Green Earth</h3>
<button class="text-white hover:opacity-80 transition-opacity" onclick="document.getElementById('green-modal').classList.add('hidden')">
<span class="material-symbols-outlined font-bold text-xl">close</span>
</button>
</div>
<!-- Body -->
<div class="p-6 space-y-5 text-[#2c3e2d] text-[15px] leading-relaxed">
<div class="bg-[#eef5ef] border-l-[3px] border-[#2c772b] p-4 rounded-r-lg">
<p>For every 24 classes completed, we plant a tree — turning your child's learning journey into real, growing impact on the planet.</p>
</div>
<p>At Younimatiq, sustainability isn't an afterthought — it's part of how we operate. We believe education and environmental responsibility can grow together.</p>
<div class="bg-[#fef9e8] border-l-[3px] border-[#f5b324] p-4 rounded-r-lg">
<p>We have also developed a <strong>5MW solar power plant</strong>, reflecting our commitment to clean energy at scale.</p>
</div>
<p>When your child completes their classes, they're not just learning — they're contributing to a greener planet. Every milestone is a tree in the ground, and every tree is a step toward a healthier Earth for their generation.</p>
<p>Thank you for being part of this journey with us.</p>
<div class="flex justify-end pt-2">
<button class="bg-[#2c772b] hover:bg-[#235e22] text-white font-bold py-2.5 px-8 rounded-full transition-colors" onclick="document.getElementById('green-modal').classList.add('hidden')">Got it</button>
</div>
</div>
</div>
</div>
<!-- LEAD CAPTURE MODAL -->
    @include('partials.lead_modal')


  <!-- JavaScript -->
<script>
    // Header Mobile Menu Toggle
    const menuBtn = document.getElementById('menuBtn');
    const menuClose = document.getElementById('menuClose');
    const menuOverlay = document.getElementById('menuOverlay');
    if (menuBtn && menuOverlay) {
      menuBtn.addEventListener('click', () => menuOverlay.classList.add('open'));
    }
    if (menuClose && menuOverlay) {
      menuClose.addEventListener('click', () => menuOverlay.classList.remove('open'));
      menuOverlay.addEventListener('click', (e) => { if (e.target === menuOverlay) menuOverlay.classList.remove('open'); });
    }

    // Single FAQ open logic
    document.querySelectorAll('.faq-item').forEach(item => {
      item.addEventListener('toggle', () => {
        if (item.open) {
          document.querySelectorAll('.faq-item').forEach(o => { if (o !== item) o.open = false; });
        }
      });
    });

    

    // Chatbot functionality
    async function sendChatbotMessage() {
      const input = document.getElementById('chatbot-input');
      const message = input.value.trim();
      if (!message) return;
      
      const messagesContainer = document.getElementById('chatbot-messages');
      
      messagesContainer.innerHTML += `
          <div class="flex gap-2 max-w-[80%] self-end flex-row-reverse">
              <div class="w-8 h-8 rounded-full bg-surface-container-high flex-shrink-0 flex items-center justify-center text-on-surface text-xs font-bold">U</div>
              <div class="bg-primary p-3 rounded-2xl rounded-tr-none shadow-sm text-sm text-white">${message}</div>
          </div>
      `;
      
      input.value = '';
      messagesContainer.scrollTop = messagesContainer.scrollHeight;
      
      try {
          const response = await fetch('{{ route("chatbot.ask") }}', {
              method: 'POST',
              headers: {
                  'Content-Type': 'application/json',
                  'X-CSRF-TOKEN': (window.__csrfToken || '{{ csrf_token() }}')
              },
              body: JSON.stringify({ message })
          });
          
          const data = await response.json();
          
          messagesContainer.innerHTML += `
              <div class="flex gap-2 max-w-[80%]">
                  <div class="w-8 h-8 rounded-full bg-white flex-shrink-0 flex items-center justify-center overflow-hidden"><img loading="lazy" src="{{ asset('images/chatbot.webp') }}" class="w-6 h-6 object-contain" alt="Chatbot" /></div>
                  <div class="bg-white p-3 rounded-2xl rounded-tl-none shadow-sm text-sm text-on-surface">${data.answer}</div>
              </div>
          `;
          messagesContainer.scrollTop = messagesContainer.scrollHeight;
      } catch (error) {
          console.error(error);
      }
    }

    // Chatbot functionality
    async function sendChatbotMessage() {
      const input = document.getElementById('chatbot-input');
      const message = input.value.trim();
      if (!message) return;
      
      const messagesContainer = document.getElementById('chatbot-messages');
      
      messagesContainer.innerHTML += `
          <div class="flex gap-2 max-w-[80%] self-end flex-row-reverse">
              <div class="w-8 h-8 rounded-full bg-surface-container-high flex-shrink-0 flex items-center justify-center text-on-surface text-xs font-bold">U</div>
              <div class="bg-primary p-3 rounded-2xl rounded-tr-none shadow-sm text-sm text-white">${message}</div>
          </div>
      `;
      
      input.value = '';
      messagesContainer.scrollTop = messagesContainer.scrollHeight;
      
      try {
          const response = await fetch('{{ route("chatbot.ask") }}', {
              method: 'POST',
              headers: {
                  'Content-Type': 'application/json',
                  'X-CSRF-TOKEN': (window.__csrfToken || '{{ csrf_token() }}')
              },
              body: JSON.stringify({ message })
          });
          
          const data = await response.json();
          
          messagesContainer.innerHTML += `
              <div class="flex gap-2 max-w-[80%]">
                  <div class="w-8 h-8 rounded-full bg-white flex-shrink-0 flex items-center justify-center overflow-hidden"><img loading="lazy" src="{{ asset('images/chatbot.webp') }}" class="w-6 h-6 object-contain" alt="Chatbot" /></div>
                  <div class="bg-white p-3 rounded-2xl rounded-tl-none shadow-sm text-sm text-on-surface">${data.answer}</div>
              </div>
          `;
          messagesContainer.scrollTop = messagesContainer.scrollHeight;
      } catch (error) {
          console.error(error);
      }
    }

    // Smooth scroll
    // Smooth scroll only on anchor link click
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      anchor.addEventListener('click', function (e) {
        const targetId = this.getAttribute('href');
        if (targetId && targetId !== '#') {
          const targetEl = document.querySelector(targetId);
          if (targetEl) {
            e.preventDefault();
            targetEl.scrollIntoView({ behavior: 'smooth' });
          }
        }
      });
    });
  </script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const targetId = this.getAttribute('href');
            if (targetId === '#' || targetId === '#leadModal') return;
            
            const targetEl = document.querySelector(targetId);
            if (targetEl) {
                e.preventDefault();
                const overlay = document.getElementById('menuOverlay');
                if (overlay) {
                    overlay.classList.remove('open');
                }
                
                // Temporarily add scroll-behavior: smooth to html just for this jump
                document.documentElement.style.scrollBehavior = 'smooth';
                
                // Account for 80px fixed header by scrolling window manually
                const targetPosition = targetEl.getBoundingClientRect().top + window.pageYOffset - 80;
                window.scrollTo({ top: targetPosition, behavior: 'smooth' });
                
                // Remove it immediately after so trackpad scrolling isn't ruined
                setTimeout(() => {
                    document.documentElement.style.scrollBehavior = 'auto';
                }, 1000);
            }
        });
    });
});
</script>
<style>
      div.grammarly-desktop-integration {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
        border: 0;
        -moz-user-select: none;
        -webkit-user-select: none;
        -ms-user-select:none;
        user-select:none;
      }

      div.grammarly-desktop-integration:before {
        content: attr(data-content);
      }
    </style>
<style>
div[id^="figma-community-published-banner"],
div[id*="figma-banner"],
div[class*="figma-banner"],
iframe[src*="figma"],
div:has(> a[href*="figma.com"]) { 
    display: none !important; 
    opacity: 0 !important;
    visibility: hidden !important;
    pointer-events: none !important;
    z-index: -9999 !important;
}
div:has(> button:contains("🤖")) { display: none !important; }
div:has(> span:contains("Book Free Consultation")) {
    background: var(--brand-gradient, linear-gradient(135deg, #003d86, #00b4d8)) !important;
    color: white !important;
}
[style*="background-color: rgba(77, 73, 252, 1)"], [style*="background: #4d49fc"] {
    background: var(--brand-gradient) !important;
}
</style>
<script>
(function() {
    const hideFigma = () => {
        document.querySelectorAll('div, a').forEach(el => {
            if (el.id && typeof el.id === 'string' && el.id.includes('figma')) {
                el.style.setProperty('display', 'none', 'important');
            }
            if (el.tagName === 'A' && el.href && (el.href.includes('figma.com') || el.href.includes('figma.site'))) {
                let parent = el.closest('div[style*="fixed"], div[style*="absolute"], div[style*="z-index"]');
                if (parent) {
                    parent.style.setProperty('display', 'none', 'important');
                    parent.style.setProperty('opacity', '0', 'important');
                } else {
                    el.style.setProperty('display', 'none', 'important');
                }
            }
            if (el.tagName === 'DIV' && el.innerText && el.innerText.includes('Made in Figma') && el.innerText.length < 25) {
                el.style.setProperty('display', 'none', 'important');
            }
        });
    };
    document.addEventListener('DOMContentLoaded', hideFigma);
    if (typeof MutationObserver !== 'undefined') {
        new MutationObserver(hideFigma).observe(document.body, { childList: true, subtree: true });
    }
})();
</script>
<style>
</style>
<!-- Group Card Removal Script -->

<script>
document.addEventListener("DOMContentLoaded", function() {
    document.querySelectorAll(".bg-white.rounded-3xl.p-8.flex.flex-col").forEach(card => {
        if (card.textContent.includes("Group Program") || card.textContent.includes("Group class")) {
            card.style.display = "none";
            let parent = card.parentElement;
            if (parent && parent.classList.contains("md:grid-cols-2")) {
                parent.classList.remove("md:grid-cols-2");
                parent.classList.add("md:grid-cols-1");
                parent.classList.remove("max-w-4xl");
                parent.classList.remove("max-w-5xl");
                parent.classList.add("max-w-md");
            }
        }
    });
});
</script>

</body>
</html>
