# Younimatiq — Student Application Wireframes

**Document version:** 1.0  
**Date:** September 1, 2026  
**Product:** Younimatiq Student Portal (Web)  
**Base URL:** `https://younimatiq.com/student/*`  
**Audience:** Product, design, development, QA

---

## 1. Executive summary

The Younimatiq student application is a logged-in learning portal where students (and parents acting on their behalf) manage enrollments, attend live 1:1 classes, complete lessons, submit assignments, take quizzes, track progress, and handle payments.

This document defines **all required pages**, **navigation structure**, **low-fidelity wireframes**, and **key user flows** for the student application. It reflects the current Laravel implementation and marks gaps or recommended enhancements.

---

## 2. Application scope

### 2.1 In scope (student application)

| Area | Description |
|------|-------------|
| Authentication | Login, register, forgot/reset password, email verification |
| Dashboard | Home hub, stats, upcoming classes, quick actions |
| Learning | Course catalog (enrolled), course player, skill tree, certificates |
| Live classes | Schedule wizard, tutor bookings, Zoom join links |
| Assessments | Assignments, quizzes |
| Account | Profile, settings, payment history, reviews, Q&A |
| Commerce | Checkout, payment success/cancel, trial access |
| Engagement | Referrals, wishlist (optional nav), community link |

### 2.2 Out of scope (public marketing site)

Homepage, landing pages (`/minecraft`, `/math`, `/ai-robotics`), public course catalog (`/courses`), contact, blog — documented separately.

---

## 3. Information architecture (sitemap)

```
Younimatiq Student Application
│
├── AUTH (unauthenticated)
│   ├── /login/student
│   ├── /register/student
│   ├── /forgot-password
│   ├── /reset-password
│   └── /email/verify
│
├── STUDENT PORTAL (authenticated — /student/*)
│   ├── Dashboard ........................ /student/dashboard
│   ├── My Profile ....................... /student/profile
│   ├── Enrolled Courses ................. /student/courses
│   │   ├── Course Player ................ /student/courses/{id}/player
│   │   ├── Schedule Wizard .............. /student/courses/{id}/schedule-wizard
│   │   ├── Skill Tree & Portfolio ....... /student/courses/{id}/skill-tree
│   │   └── Certificate .................. /student/courses/{id}/certificate
│   ├── Assignments ...................... /student/assignments
│   ├── Quizzes .......................... /student/quizzes
│   │   └── Take Quiz .................... /student/quizzes/{id}/take
│   ├── Tutor Bookings ................... /student/bookings
│   ├── Payment History .................. /student/payment-history
│   ├── Reviews .......................... /student/reviews
│   ├── Q & A ............................ /student/qa
│   ├── Settings ......................... /student/settings
│   ├── Referrals ........................ /student/referrals
│   ├── Wishlist ......................... /student/wishlist
│   ├── Community ........................ /student/community
│   ├── Checkout ......................... /student/checkout/{course}
│   ├── Payment Success .................. /student/payment/success
│   ├── Payment Cancel ................... /student/payment/cancel
│   └── Trial Expired .................... /student/trial-expired
│
└── PUBLIC COURSE ACTIONS (linked from portal)
    ├── Browse courses ................... /courses
    ├── Course detail .................... /courses/{slug}
    └── Write review (public) ............ /courses/{slug}/review
```

---

## 4. Global layout shell

All authenticated student pages (except course player, checkout, certificate) use the **dashboard layout**.

### 4.1 Desktop wireframe (≥1024px)

```
┌──────────────────────────────────────────────────────────────────────────────┐
│ TOP BAR (optional mobile header)                              [🔔] [Avatar] │
├──────────────┬───────────────────────────────────────────────────────────────┤
│              │                                                               │
│  [Logo]      │  PAGE TITLE / BREADCRUMB                                      │
│              │                                                               │
│  ─────────   │  ┌─────────────────────────────────────────────────────────┐  │
│  Dashboard   │  │                                                         │  │
│  My Profile  │  │              MAIN CONTENT AREA                          │  │
│  Enrolled    │  │              (scrollable)                                 │  │
│  Assignments │  │                                                         │  │
│  Quizzes     │  │                                                         │  │
│  Bookings    │  │                                                         │  │
│  Payments    │  │                                                         │  │
│  Reviews     │  │                                                         │  │
│  Q & A       │  │                                                         │  │
│  Settings    │  │                                                         │  │
│              │  └─────────────────────────────────────────────────────────┘  │
│  ─────────   │                                                               │
│  [Log Out]   │                                                               │
│              │                                                               │
│  SIDEBAR     │                                                               │
│  ~240px      │                                                               │
└──────────────┴───────────────────────────────────────────────────────────────┘
```

### 4.2 Mobile wireframe (<1024px)

```
┌─────────────────────────────┐
│ [☰]  Younimatiq Student    [👤] │
├─────────────────────────────┤
│                             │
│     MAIN CONTENT            │
│     (full width)            │
│                             │
│                             │
├─────────────────────────────┤
│ Optional bottom nav (future)│
└─────────────────────────────┘

Hamburger → slide-over sidebar with same nav links
```

### 4.3 Design tokens (current)

| Element | Value |
|---------|-------|
| Primary color | `#0057bf` / `#046bd2` |
| Font (dashboard) | Inter, Nunito |
| Font (marketing/player) | Quicksand |
| Card style | Rounded 2xl, clay-card, soft borders |
| Icons | Material Symbols Outlined |

---

## 5. Page wireframes

---

### 5.1 Student Login

**Route:** `GET /login/student`  
**View:** `auth/login-student.blade.php`

```
┌────────────────────────────────────────────────────────────┐
│                                                            │
│   ┌──────────────────┐  ┌──────────────────────────┐  │
│   │                  │  │  [School icon]            │  │
│   │   Character      │  │  Welcome Back, Explorer!  │  │
│   │   illustration   │  │                           │  │
│   │   (animated)     │  │  Email [____________]     │  │
│   │                  │  │  Password [__________] 👁 │  │
│   │                  │  │  ☐ Remember me            │  │
│   │                  │  │  Forgot password?         │  │
│   │                  │  │                           │  │
│   │                  │  │  [    Sign In    ]        │  │
│   │                  │  │  New here? Register →     │  │
│   └──────────────────┘  └──────────────────────────┘  │
│                                                            │
└────────────────────────────────────────────────────────────┘
```

**Required elements:** Email, password, remember me, forgot password link, register link, error messages, CSRF protection.

---

### 5.2 Student Registration

**Route:** `GET /register/student`  
**View:** `auth/register-student.blade.php`

```
┌──────────────────────────────────────────┐
│  Become an Explorer                      │
│                                          │
│  Name [________________________]         │
│  Email [_______________________]         │
│  Password [____________________] 👁       │
│  Confirm Password [____________] 👁       │
│                                          │
│  [    Start your adventure    ]          │
│  Already have account? Login →         │
└──────────────────────────────────────────┘
```

---

### 5.3 Forgot / Reset Password

**Routes:** `/forgot-password`, `/reset-password`  
**Views:** `auth/forgot-password.blade.php`, `auth/reset-password.blade.php`

```
FORGOT PASSWORD                    RESET PASSWORD
┌─────────────────────────┐       ┌─────────────────────────┐
│ Enter your email        │       │ Email (readonly/hidden) │
│ [___________________]   │       │ New password            │
│ [ Send reset link ]     │       │ Confirm password        │
│ Back to login           │       │ [ Reset password ]      │
└─────────────────────────┘       └─────────────────────────┘
```

---

### 5.4 Student Dashboard (Home)

**Route:** `GET /student/dashboard`  
**View:** `student/dashboard.blade.php`

```
┌────────────────────────────────────────────────────────────────────────────┐
│ [TRIAL BANNER — if trial active]                                           │
│  Trial dashboard access | Countdown: 5d 12h 30m 15s | Enroll CTA          │
├────────────────────────────────────────────────────────────────────────────┤
│ ┌─────────────────────────────────────┐ ┌──────────────────────────────┐ │
│ │ Level 3 Explorer                    │ │ Leaderboard Score            │ │
│ │ Welcome back, Alex! 🚀              │ │ 1,250 XP                     │ │
│ │ Ready for your next adventure?      │ │ Global Rank #42 | Gold tier  │ │
│ └─────────────────────────────────────┘ └──────────────────────────────┘ │
├────────────────────────────────────────────────────────────────────────────┤
│ ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐                      │
│ │ Enrolled │ │ In Prog  │ │ Completed│ │ Avg %    │                      │
│ │    3     │ │    2     │ │    1     │ │   67%    │                      │
│ └──────────┘ └──────────┘ └──────────┘ └──────────┘                      │
├────────────────────────────────────────────────────────────────────────────┤
│ UPCOMING LIVE SESSIONS                                                     │
│ ┌──────────────────────────────────────────────────────────────────────┐ │
│ │ 📅 Minecraft Python | Tue 4:00 PM | Tutor: Sarah | [Join Zoom]       │ │
│ │ 📅 Grade 6 Math     | Thu 6:00 PM | Tutor: James | [Join Zoom]       │ │
│ └──────────────────────────────────────────────────────────────────────┘ │
│ Note: Join link active 15 min before class                               │
├────────────────────────────────────────────────────────────────────────────┤
│ CONTINUE LEARNING                                                          │
│ [Course card] [Course card] [Course card]  → horizontal scroll on mobile    │
├────────────────────────────────────────────────────────────────────────────┤
│ RECENT ACTIVITY / ASSIGNMENTS DUE / QUIZ REMINDERS (recommended)          │
└────────────────────────────────────────────────────────────────────────────┘
```

**Key actions:** Join live class, resume course, browse courses, view trial countdown.

---

### 5.5 My Profile

**Route:** `GET /student/profile`  
**View:** `student/profile.blade.php`

```
┌────────────────────────────────────────────────────────────────┐
│ My Profile 👤                                                  │
├────────────────────────────────────────────────────────────────┤
│ ┌────────┐  Alex Explorer                                      │
│ │ Avatar │  parent@email.com                                   │
│ │  📷    │  Member since Jan 2026                              │
│ └────────┘                                                     │
├────────────────────────────────────────────────────────────────┤
│ Full Name [________________________]                           │
│ Email     [________________________]                           │
│ Timezone  [Select timezone ▼]                                │
│ Avatar    [Upload image]                                       │
│                                                                │
│ [ Save Changes ]                                               │
└────────────────────────────────────────────────────────────────┘
```

---

### 5.6 Enrolled Courses

**Route:** `GET /student/courses`  
**View:** `student/courses.blade.php`

```
┌────────────────────────────────────────────────────────────────┐
│ My Courses                                                     │
│ Filter: [All] [In Progress] [Completed]                        │
├────────────────────────────────────────────────────────────────┤
│ ┌─────────────────┐ ┌─────────────────┐ ┌─────────────────┐ │
│ │ [thumbnail]     │ │ [thumbnail]     │ │ [thumbnail]     │ │
│ │ Minecraft Python│ │ Grade 6 Math    │ │ Robotics Lvl 3  │ │
│ │ ████████░░ 80%  │ │ ████░░░░░░ 40%  │ │ ██████████ 100% │ │
│ │ [Continue]      │ │ [Continue]      │ │ [Certificate]   │ │
│ │ [Skill Tree]    │ │ [Schedule]      │ │ [Review]        │ │
│ └─────────────────┘ └─────────────────┘ └─────────────────┘ │
├────────────────────────────────────────────────────────────────┤
│ Empty state: "No courses yet" → [Browse Courses]               │
└────────────────────────────────────────────────────────────────┘
```

**Per-card actions:** Continue learning (player), Schedule classes, Skill tree, Certificate (if complete).

---

### 5.7 Course Player (Immersive)

**Route:** `GET /student/courses/{course}/player`  
**View:** `student/course-player.blade.php`  
**Layout:** Full-screen (no sidebar)

```
┌────────────────────────────────────────────────────────────────────────────┐
│ ← My Courses    Course Title                    [Certificate] Instructor   │
├──────────────────────────────────────┬─────────────────────────────────────┤
│                                      │ CURRICULUM                          │
│                                      │ Unit 1: Introduction                │
│         VIDEO / TEXT / ZOOM          │   ✓ Lesson 1 — Welcome              │
│         CONTENT AREA                 │   ● Lesson 2 — Variables  ← active  │
│         (70% width desktop)          │   ○ Lesson 3 — Loops                │
│                                      │ Unit 2: Projects                    │
│                                      │   ○ Lesson 4 — Build a mod          │
│                                      │                                     │
│                                      │ [Mark Complete]                     │
└──────────────────────────────────────┴─────────────────────────────────────┘
```

**Content types:** Direct video, embedded YouTube/Vimeo, text lesson, Zoom live link, file download.

---

### 5.8 Schedule Wizard

**Route:** `GET /student/courses/{course}/schedule-wizard`  
**View:** `student/schedule_wizard.blade.php`

```
┌────────────────────────────────────────────────────────────────┐
│ Schedule Classes for {Course Title}                          │
│ You have 8 classes left to schedule                            │
├────────────────────────────────────────────────────────────────┤
│ Timezone        [America/New_York ▼]                           │
│ Start date      [📅 ____________]                              │
│                                                                │
│ Select days & times:                                           │
│ ☐ Monday    [10:00 AM ▼]                                       │
│ ☑ Wednesday [4:00 PM ▼]                                        │
│ ☐ Friday    [—]                                                │
│                                                                │
│ Preferred tutor (optional) [Any available ▼]                   │
│                                                                │
│ [ Preview schedule ]  [ Confirm & book sessions ]              │
└────────────────────────────────────────────────────────────────┘
```

---

### 5.9 Skill Tree & Portfolio

**Route:** `GET /student/courses/{course}/skill-tree`  
**View:** `student/skill-tree.blade.php`

```
┌────────────────────────────────────────────────────────────────┐
│ Visual Mastery Progress — Level 2 Builder                      │
│ Overall Mastery ████████░░ 72%                                 │
├────────────────────────────────────────────────────────────────┤
│ [ Skill Tree Nodes (8/12) ]  [ Project Portfolio (3) ]         │
├────────────────────────────────────────────────────────────────┤
│ SKILL TREE TAB:                                                │
│                                                                │
│     [✓ Logic] ──→ [✓ Loops] ──→ [● Functions] ──→ [🔒 Mods]  │
│           │              │                                     │
│     [✓ Variables]    [○ Arrays]                                │
│                                                                │
│ PORTFOLIO TAB:                                                 │
│ ┌──────────────┐ ┌──────────────┐                              │
│ │ Project img  │ │ Project img  │  [+ Add project]             │
│ │ Redstone farm│ │ Python bot   │                              │
│ └──────────────┘ └──────────────┘                              │
└────────────────────────────────────────────────────────────────┘
```

---

### 5.10 Certificate

**Route:** `GET /student/courses/{course}/certificate`  
**View:** `student/certificate.blade.php`  
**Layout:** Standalone print view

```
┌────────────────────────────────────────────────────────────────┐
│                                          [ Print / Save PDF ] │
│  ┌──────────────────────────────────────────────────────────┐ │
│  │              Younimatiq                                │ │
│  │         Certificate of Completion                        │ │
│  │                                                          │ │
│  │         This certifies that                              │ │
│  │              ALEX EXPLORER                               │ │
│  │    has successfully completed the course                 │ │
│  │         Minecraft Python Quest                           │ │
│  │                                                          │ │
│  │   Date: Aug 29, 2026    Instructor: Sarah M.             │ │
│  └──────────────────────────────────────────────────────────┘ │
└────────────────────────────────────────────────────────────────┘
```

---

### 5.11 Assignments

**Route:** `GET /student/assignments`  
**View:** `student/assignments.blade.php`

```
┌────────────────────────────────────────────────────────────────┐
│ My Assignments                                                 │
│ Tabs: [Pending] [Submitted] [Graded]                           │
├────────────────────────────────────────────────────────────────┤
│ ┌────────────────────────────────────────────────────────────┐ │
│ │ Minecraft Python — Build a redstone door                   │ │
│ │ Due: Sep 5, 2026 | Status: Pending                        │ │
│ │ Instructions: ...                                          │ │
│ │ Upload file [Choose file]  or paste link                   │ │
│ │ Notes [____________________________]                       │ │
│ │ [ Submit Assignment ]                                      │ │
│ └────────────────────────────────────────────────────────────┘ │
│ ┌────────────────────────────────────────────────────────────┐ │
│ │ Grade 6 Math — Fraction worksheet | Graded: 92% | Feedback │ │
│ └────────────────────────────────────────────────────────────┘ │
└────────────────────────────────────────────────────────────────┘
```

---

### 5.12 Quizzes

**Route:** `GET /student/quizzes`  
**View:** `student/quizzes/index.blade.php`

```
┌────────────────────────────────────────────────────────────────┐
│ My Quizzes                                                     │
├────────────────────────────────────────────────────────────────┤
│ Available                                                      │
│ ┌──────────────────────────────────────────────────────────┐   │
│ │ Variables Quiz | Minecraft Python | 10 questions       │   │
│ │ Time limit: 20 min                    [ Start Quiz ]     │   │
│ └──────────────────────────────────────────────────────────┘   │
│ Completed                                                      │
│ ┌──────────────────────────────────────────────────────────┐   │
│ │ Logic Basics | Score: 8/10 | Sep 1, 2026                 │   │
│ └──────────────────────────────────────────────────────────┘   │
└────────────────────────────────────────────────────────────────┘
```

---

### 5.13 Take Quiz (Full-screen flow)

**Route:** `GET /student/quizzes/{quiz}/take`  
**View:** `student/quizzes/take.blade.php`

```
┌────────────────────────────────────────────────────────────────┐
│ Variables Quiz                    Question 3 of 10    ⏱ 18:42  │
├────────────────────────────────────────────────────────────────┤
│ What is the output of print(2 + 3 * 2)?                        │
│                                                                │
│ ○ 10                                                           │
│ ● 8                                                            │
│ ○ 12                                                           │
│ ○ Error                                                        │
│                                                                │
│ [← Previous]                              [ Next →] [ Submit ]   │
└────────────────────────────────────────────────────────────────┘

RESULT SCREEN (after submit):
┌────────────────────────────────────────────────────────────────┐
│ Quiz Complete! Score: 8/10 (80%)                             │
│ [ Review answers ]  [ Back to quizzes ]                        │
└────────────────────────────────────────────────────────────────┘
```

---

### 5.14 Tutor Bookings

**Route:** `GET /student/bookings`  
**View:** `student/bookings.blade.php`

```
┌────────────────────────────────────────────────────────────────┐
│ Tutor Bookings 📅                                              │
├────────────────────────────────────────────────────────────────┤
│ AVAILABLE SESSIONS (open slots)                                │
│ ┌──────────────────────────────────────────────────────────┐   │
│ │ Live Session — Robotics | Mon 5:00 PM | 30 min           │   │
│ │                                        [ Book Session ]  │   │
│ └──────────────────────────────────────────────────────────┘   │
├────────────────────────────────────────────────────────────────┤
│ MY BOOKINGS                                                    │
│ Upcoming | Past                                                │
│ ┌──────────────────────────────────────────────────────────┐   │
│ │ Minecraft Python | Sep 3, 4:00 PM | Tutor: Sarah         │   │
│ │ Status: Confirmed              [Join Zoom] [Reschedule*] │   │
│ └──────────────────────────────────────────────────────────┘   │
│ *Reschedule via support / future self-service                  │
└────────────────────────────────────────────────────────────────┘
```

---

### 5.15 Checkout & Payment

**Route:** `GET /student/checkout/{course}`  
**View:** `student/checkout.blade.php`

```
┌────────────────────────────────────────────────────────────────────────────┐
│                        Secure Checkout                                     │
├────────────────────────────────────┬───────────────────────────────────────┤
│ Schedule Your Class                │ ORDER SUMMARY                         │
│                                    │ Course: Minecraft Python              │
│ Timezone [Select ▼]                │ Plan: 24-Class Plan                   │
│ Pricing: ○ Full  ● Per class       │ Subtotal: $384                        │
│ Coupon [________] [Apply]          │ Discount: -$50                        │
│                                    │ Total: $334                           │
│ Payment method:                    │                                       │
│ ○ Stripe (card)                    │ Includes:                             │
│ ○ PayPal                           │ • 1:1 live classes                    │
│ ○ Razorpay (INR)                   │ • Dedicated tutor                     │
│                                    │ • Progress reports                    │
│ Card details (Stripe Elements)     │                                       │
│ [________________]                 │ [ Complete Purchase ]                 │
└────────────────────────────────────┴───────────────────────────────────────┘
```

**Related routes:**
- `GET /student/payment/success` — confirmation + next steps (schedule wizard)
- `GET /student/payment/cancel` — payment cancelled, retry CTA

---

### 5.16 Payment History

**Route:** `GET /student/payment-history`  
**View:** `student/payment-history.blade.php`

```
┌────────────────────────────────────────────────────────────────┐
│ Payment History                                                │
├────────────────────────────────────────────────────────────────┤
│ Date       | Course            | Amount  | Status  | Receipt  │
│ Sep 1 2026 | Minecraft Python  | $334    | Paid    | [PDF]    │
│ Aug 15     | Grade 6 Math      | $192    | Paid    | [PDF]    │
└────────────────────────────────────────────────────────────────┘
```

---

### 5.17 Reviews

**Route:** `GET /student/reviews`  
**View:** `student/reviews.blade.php`

```
┌────────────────────────────────────────────────────────────────┐
│ My Reviews ⭐                                                   │
├────────────────────────────────────────────────────────────────┤
│ Write a review for a completed course:                         │
│ Course [Select ▼]  Rating ★★★★★  Comment [____________]       │
│ [ Submit Review ]                                              │
├────────────────────────────────────────────────────────────────┤
│ Your past reviews (editable)                                   │
└────────────────────────────────────────────────────────────────┘
```

---

### 5.18 Q & A

**Route:** `GET /student/qa`  
**View:** `student/qa.blade.php`

```
┌────────────────────────────────────────────────────────────────┐
│ Q & A Forum                                                    │
├────────────────────────────────────────────────────────────────┤
│ Ask a question:                                                │
│ Course [Select ▼]  Subject [________]                          │
│ Question [________________________________________]            │
│ [ Post Question ]                                              │
├────────────────────────────────────────────────────────────────┤
│ My questions & tutor answers                                   │
│ ┌──────────────────────────────────────────────────────────┐   │
│ │ Q: How do I install the mod?                           │   │
│ │ A: Follow step 3 in Lesson 5... — Tutor Sarah          │   │
│ └──────────────────────────────────────────────────────────┘   │
└────────────────────────────────────────────────────────────────┘
```

---

### 5.19 Settings

**Route:** `GET /student/settings`  
**View:** `student/settings.blade.php`

```
┌────────────────────────────────────────────────────────────────┐
│ Account Settings                                               │
├────────────────────────────────────────────────────────────────┤
│ Change Password                                                │
│ Current [________]  New [________]  Confirm [________]         │
│ [ Update Password ]                                            │
├────────────────────────────────────────────────────────────────┤
│ Notifications (recommended)                                    │
│ ☑ Email class reminders  ☑ Assignment due alerts              │
├────────────────────────────────────────────────────────────────┤
│ Danger zone: [ Delete account ] (future)                       │
└────────────────────────────────────────────────────────────────┘
```

---

### 5.20 Referrals

**Route:** `GET /student/referrals`  
**View:** `parent/referrals.blade.php`

```
┌────────────────────────────────────────────────────────────────┐
│ Referrals & Sibling Discounts                                  │
│ Total credits earned: $75                                      │
├──────────────────────────────┬─────────────────────────────────┤
│ YOUR REFERRAL CODE           │ SIBLING DISCOUNT                │
│ YOUNIMATIQ-ALEX-2026              │ 15% off 2nd child enrollment    │
│ [ Copy code ] [ Share ]      │ Auto-applied at checkout        │
├──────────────────────────────┴─────────────────────────────────┤
│ Referral history table                                         │
└────────────────────────────────────────────────────────────────┘
```

---

### 5.21 Wishlist

**Route:** `GET /student/wishlist`  
**View:** `student/wishlist.blade.php`

```
┌────────────────────────────────────────────────────────────────┐
│ My Wishlist ♥                                                  │
├────────────────────────────────────────────────────────────────┤
│ [Course card] [Course card]  → [Enroll now] [Remove]           │
│ Empty: Browse courses CTA                                      │
└────────────────────────────────────────────────────────────────┘
```

*Note: Route exists; add to sidebar nav if product wants it visible.*

---

### 5.22 Trial Expired

**Route:** `GET /student/trial-expired`  
**View:** `student/trial_expired.blade.php`

```
┌─────────────────────────────────────┐
│         ⏳ Trial Expired            │
│                                     │
│  Your 9-day trial has ended.        │
│  Enroll in a course for full access │
│                                     │
│  [ Browse Courses ]                 │
│  [ Back to Home ]                   │
│  [ Log Out ]                        │
│  Contact support link               │
└─────────────────────────────────────┘
```

---

## 6. User flows

### 6.1 New student — demo to enrolled

```
Book free class (marketing site)
    → Admin assigns demo / trial account created
    → Student registers or receives login
    → Dashboard (trial banner, 9-day access)
    → Browse /courses → Select course
    → Checkout (schedule + pay)
    → Payment success
    → Schedule wizard (book remaining classes)
    → Course player + live Zoom sessions
    → Assignments / quizzes / skill tree
    → Certificate on completion
```

### 6.2 Returning student — daily use

```
Login
    → Dashboard
    → See upcoming class → Join Zoom (15 min window)
    OR Continue learning → Course player
    OR Submit assignment / take quiz
```

### 6.3 Trial expiry flow

```
Trial countdown on dashboard
    → Trial expires
    → Redirect to /student/trial-expired
    → Browse courses → Checkout → Full access restored
```

---

## 7. Navigation matrix

| Page | Sidebar link | Mobile menu | Breadcrumb |
|------|-------------|-------------|------------|
| Dashboard | ✓ | ✓ | — |
| Profile | ✓ | ✓ | Dashboard › Profile |
| Enrolled Courses | ✓ | ✓ | Dashboard › Courses |
| Course Player | — | Back arrow | Courses › {title} |
| Assignments | ✓ | ✓ | Dashboard › Assignments |
| Quizzes | ✓ | ✓ | Dashboard › Quizzes |
| Bookings | ✓ | ✓ | Dashboard › Bookings |
| Payment History | ✓ | ✓ | Dashboard › Payments |
| Reviews | ✓ | ✓ | Dashboard › Reviews |
| Q & A | ✓ | ✓ | Dashboard › Q&A |
| Settings | ✓ | ✓ | Dashboard › Settings |
| Referrals | — (add link) | — | Dashboard › Referrals |
| Wishlist | — (add link) | — | Dashboard › Wishlist |
| Checkout | — | — | Courses › Checkout |

---

## 8. Responsive breakpoints

| Breakpoint | Layout behavior |
|------------|-----------------|
| `< 640px` | Single column, stacked cards, hamburger nav, full-width CTAs |
| `640–1023px` | 2-column grids, sidebar collapses to drawer |
| `≥ 1024px` | Fixed sidebar + main content, course player split view |
| `≥ 1280px` | Max-width container ~1600px on dashboard |

---

## 9. Component library (reusable UI)

| Component | Used on |
|-----------|---------|
| Stat card | Dashboard |
| Course card (progress bar) | Dashboard, Courses, Wishlist |
| Clay card | Profile, Skill tree, Referrals |
| Trial banner + countdown | Dashboard |
| Zoom join button | Dashboard, Bookings |
| Quiz question card | Take quiz |
| Payment method selector | Checkout |
| Empty state + CTA | Courses, Assignments, Wishlist |
| Toast / alert | All forms |
| Modal (lead/booking) | Marketing → student handoff |

---

## 10. API & data dependencies (per page)

| Page | Primary data |
|------|--------------|
| Dashboard | Enrollments, bookings, XP/stats, trial end date |
| Courses | Enrollments with progress % |
| Course player | Course modules, completion state |
| Bookings | LiveSession slots, Booking records, Zoom URLs |
| Checkout | Course pricing, coupons, Stripe/PayPal/Razorpay |
| Assignments | Assignment + submission status |
| Quizzes | Quiz list, attempts, scores |
| Skill tree | Nodes mastery, portfolio items |

---

## 11. Accessibility & UX requirements

- All forms: labels, focus states, keyboard navigation
- Video player: captions support (recommended)
- Color contrast: WCAG AA on primary buttons
- Timezone: always show local time with TZ label
- Live class: clear "Join" disabled state before window opens
- Mobile: touch targets ≥ 44px

---

## 12. Implementation status (current codebase)

| Page | Status | View file |
|------|--------|-----------|
| Login / Register | ✅ Built | `auth/login-student.blade.php` |
| Dashboard | ✅ Built | `student/dashboard.blade.php` |
| Profile | ✅ Built | `student/profile.blade.php` |
| Enrolled Courses | ✅ Built | `student/courses.blade.php` |
| Course Player | ✅ Built | `student/course-player.blade.php` |
| Schedule Wizard | ✅ Built | `student/schedule_wizard.blade.php` |
| Skill Tree | ✅ Built | `student/skill-tree.blade.php` |
| Certificate | ✅ Built | `student/certificate.blade.php` |
| Assignments | ✅ Built | `student/assignments.blade.php` |
| Quizzes | ✅ Built | `student/quizzes/*` |
| Bookings | ✅ Built | `student/bookings.blade.php` |
| Checkout | ✅ Built | `student/checkout.blade.php` |
| Payment History | ✅ Built | `student/payment-history.blade.php` |
| Reviews | ✅ Built | `student/reviews.blade.php` |
| Q & A | ✅ Built | `student/qa.blade.php` |
| Settings | ✅ Built | `student/settings.blade.php` |
| Referrals | ✅ Built | `parent/referrals.blade.php` |
| Wishlist | ✅ Built | `student/wishlist.blade.php` |
| Trial Expired | ✅ Built | `student/trial_expired.blade.php` |

### Recommended enhancements

1. Add **Referrals** and **Wishlist** to sidebar navigation
2. **Notifications center** in top bar (classes, grades, messages)
3. **Reschedule/cancel** self-service for bookings
4. **Parent/multi-child switcher** if one account manages multiple students
5. **Mobile bottom tab bar** (Home, Courses, Classes, Profile)
6. **Offline / low-bandwidth** mode for lesson text content

---

## 13. Page count summary

| Category | Pages |
|----------|-------|
| Authentication | 5 |
| Core portal | 11 |
| Course sub-pages | 4 |
| Assessments | 2 (+ quiz take) |
| Commerce | 3 |
| Account & engagement | 5 |
| **Total required** | **~30 screens** |

---

## 14. Document approval

| Role | Name | Date | Signature |
|------|------|------|-----------|
| Product Owner | | | |
| Design Lead | | | |
| Engineering Lead | | | |

---

*End of document — Younimatiq Student Application Wireframes v1.0*
