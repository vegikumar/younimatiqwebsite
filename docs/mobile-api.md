# Younimatiq Mobile API (v1)

Base URL: `https://younimatiq.com/api/v1`

All authenticated requests require header:
```
Authorization: Bearer {token}
Accept: application/json
```

## Response envelope

Success:
```json
{ "success": true, "message": "...", "data": { } }
```

Error:
```json
{ "success": false, "message": "...", "errors": { }, "trial_expired": true }
```

---

## Auth

### POST `/auth/login`
```json
{ "email": "student@example.com", "password": "secret", "device_name": "iPhone 15" }
```
Response `data`: `{ "token", "token_type": "Bearer", "user": { ... } }`

### POST `/auth/register`
Body: `name`, `email`, `password`, `password_confirmation`, optional `timezone`, `device_name`, `referral_code` (or `ref`). Attributes the new student to the referrer when the code is valid.
```json
{ "name": "Alex", "email": "alex@example.com", "password": "secret", "password_confirmation": "secret", "timezone": "Asia/Kolkata" }
```

### POST `/auth/logout` (auth required)

### GET `/auth/me` (auth required)

### GET `/auth/email/status` (auth required)
Response `data`: `{ "verified": true, "email": "..." }`

### POST `/auth/email/resend` (auth required, throttled)
Resends Laravel verification notification if not yet verified.

### POST `/auth/forgot-password`
```json
{ "email": "student@example.com" }
```

### POST `/auth/reset-password`
```json
{ "token": "...", "email": "...", "password": "...", "password_confirmation": "..." }
```

---

## Student (all require Bearer token + active student + trial access unless noted)

### GET `/student/trial-status`
Returns `{ has_purchased_course, trial_ends_at, trial_expired, seconds_remaining }`

### GET `/student/dashboard`
Returns stats, enrolled courses, upcoming sessions (enriched bookings), **demo_sessions** (enriched demos with Zoom/`can_join`), quizzes, assignments, leaderboard, tips.

### GET `/student/activities`
Hub aggregate for enrolled courses: `enrollments[]` (`id`, `course_id`, `course_title`, `progress`), `assignments[]` (`id`, `title`, `course_id`, `course_title`, `due_at`, `due_at_label`), `quizzes[]` (`id`, `title`, `course_id`, `course_title`).

### GET `/student/career`
Career pathways for grades 6–12. Response `data`: `grade`, `grade_band`, `grade_band_label`, `pathways[]` (`id`, `slug`, `title`, `tagline`, `description`, `icon`, `skill_tags[]`, `courses[]` with `id`/`title`/`slug`). Returns **403** for early grade band (grades 1–3) with the same grade fields in the error payload.

### GET `/student/announcements`
Published tutor announcements for enrolled courses (+ welcome posts). Response `data`: `unread_count`, `announcements[]` (`id`, `title`, `content`, `video_url`, `is_welcome_post`, `course_id`, `course_title`, `tutor_name`, `is_read`, `read_at`, `published_at`).

### POST `/student/announcements/{id}/read`
Marks an announcement as read for the current student. Response `data`: `{ unread_count, announcement_id }`.

### POST `/student/chatbot/ask`
```json
{ "message": "How do I join a class?" }
```
Response `data`: `{ "answer": "..." }` (keyword match against chatbot training). Throttled.

### GET `/student/catalog`
All published active courses (**Courses tab** catalog browse — not the Learn/enrolled list). Query: `search`, `category` (slug), `age_group` (slug), `sort` (`newest|title_asc|title_desc|price_asc|price_desc`), `page`, `per_page` (max 50).

Response `data.courses[]` is a **card** payload. Fields: `id`, `title`, `slug`, `description`, `thumbnail_url`, `video_url`, `price`, `per_class_price`, `age_group`, `age_label`, `course_type`, `is_camp`, `number_of_classes`, `categories[]`, `tutor`, `modules_count`, `is_enrolled`, `checkout_path`.
Pagination in `data.meta` (`current_page`, `last_page`, `per_page`, `total`). Clients should request successive `page` values for infinite scroll.

`thumbnail_url` and `video_url` are **absolute HTTPS** URLs when present (or YouTube). Path rules (`ApiAssetUrl`):

- Already `http(s)://` → unchanged (double `/secure-asset/secure-asset/` collapsed)
- Starts with `/storage/`, `/images/`, `/fonts/`, `/videos/`, `/build/`, `/secure-asset/` → `https://younimatiq.com` + that path (no re-prefix)
- Disk-relative like `courses/thumbnails/...` → `https://younimatiq.com/secure-asset/...`
- Null when missing — use a placeholder image, do not skip the card

**Promo vs lesson video:** catalog `video_url` is the marketing/hero clip for cards and Course Detail. Lesson videos are `modules[].content_url` on `GET /student/courses/{id}/player` after enrollment. Zoom modules also expose `zoom_join_url`, `join_url`, `can_join` when a meeting URL exists.

**Join on catalog CTA is not for Zoom.** Zoom `can_join` / `zoom_join_url` live on dashboard bookings, `GET /student/bookings`, and zoom-type player modules. Catalog detail CTA is enroll/continue only.

Example card (`data.courses[0]`):

```json
{
  "id": 12,
  "title": "Minecraft Coding Adventures",
  "slug": "minecraft-coding-adventures",
  "description": "Kids learn coding concepts by building in Minecraft.",
  "thumbnail_url": "https://younimatiq.com/secure-asset/courses/thumbnails/minecraft.jpg",
  "video_url": "https://www.youtube.com/watch?v=abcdefghijk",
  "price": 199.0,
  "per_class_price": 25.0,
  "age_group": "8-12",
  "age_label": "Ages 8–12",
  "course_type": "live",
  "is_camp": false,
  "number_of_classes": 8,
  "categories": [{ "id": 3, "name": "Minecraft", "slug": "minecraft" }],
  "tutor": {
    "id": 4,
    "name": "Alex Tutor",
    "avatar_url": "https://ui-avatars.com/api/?name=A&background=046bd2&color=fff"
  },
  "modules_count": 8,
  "is_enrolled": false,
  "checkout_path": "/student/checkout/12"
}
```

### GET `/student/catalog/filters`
Response `data`: `{ categories: [{id,name,slug}], age_groups: [{slug,name,label,age_range}], sorts: [{value,label}] }`

### GET `/student/catalog/{course}`
Rich catalog **detail** by **id or slug** (published + active only).

Do **not** use the list/card payload for this screen — always call this detail endpoint. List cards lack `importance`, `curriculum`, `faqs`, `reviews`, `cta`, etc.

`data.course` includes card fields plus:

| Field | Notes |
|-------|--------|
| `format` | Course format string |
| `welcome_message` | Intro text (nullable) |
| `welcome_video_url` | Intro video URL (nullable; YouTube or absolute file URL) |
| `importance` | Why this course matters (nullable) |
| `learning_outcomes[]` | Strings |
| `highlights[]` | Strings |
| `who_for[]` | Audience bullets |
| `requirements[]` | Prerequisites / gear |
| `curriculum[]` | `{ id, title, order }` — outline only (no lesson URLs) |
| `faqs[]` | `{ id, question, answer, category }` — up to 6 (answer may contain HTML) |
| `related_courses[]` | Up to 3 **card-shaped** courses (same age/category) |
| `reviews[]` | Up to 6: `{ id, rating, comment, author_name, created_at }` |
| `review_stats` | `{ count, average }` |
| `cta` | `{ label, action, course_id }` — `action` is `checkout` or `open_enrolled` |

Sticky CTA only: `checkout` → Checkout; `open_enrolled` → Learn hub. **Do not** add a session Join button on Course Detail.

Example `data.course` (detail):

```json
{
  "id": 12,
  "title": "Minecraft Coding Adventures",
  "slug": "minecraft-coding-adventures",
  "description": "Kids learn coding concepts by building in Minecraft.",
  "thumbnail_url": "https://younimatiq.com/secure-asset/courses/thumbnails/minecraft.jpg",
  "video_url": "https://younimatiq.com/secure-asset/courses/videos/minecraft-promo.mp4",
  "price": 199.0,
  "per_class_price": 25.0,
  "age_group": "8-12",
  "age_label": "Ages 8–12",
  "course_type": "live",
  "is_camp": false,
  "number_of_classes": 8,
  "categories": [{ "id": 3, "name": "Minecraft", "slug": "minecraft" }],
  "tutor": { "id": 4, "name": "Alex Tutor", "avatar_url": "https://example.com/avatar.png" },
  "modules_count": 8,
  "is_enrolled": false,
  "checkout_path": "/student/checkout/12",
  "format": "live",
  "welcome_message": "Welcome to Minecraft Coding!",
  "welcome_video_url": "https://www.youtube.com/watch?v=abcdefghijk",
  "importance": "Builds logic, creativity, and confidence through play.",
  "learning_outcomes": ["Write simple commands", "Build a redstone project"],
  "highlights": ["Live tutor", "Small groups"],
  "who_for": ["Learners in the Ages 8–12 range who enjoy creative, hands-on projects."],
  "requirements": ["A laptop or desktop with a stable internet connection"],
  "curriculum": [{ "id": 101, "title": "Getting started", "order": 1 }],
  "faqs": [{ "id": 1, "question": "How long is each class?", "answer": "<p>60 minutes.</p>", "category": "Classes" }],
  "related_courses": [],
  "reviews": [{ "id": 9, "rating": 5, "comment": "Loved it", "author_name": "Sam", "created_at": "2026-09-01T10:00:00+00:00" }],
  "review_stats": { "count": 1, "average": 5.0 },
  "cta": { "label": "Enroll / Checkout", "action": "checkout", "course_id": 12 }
}
```

When enrolled, `cta` is `{ "label": "Continue learning", "action": "open_enrolled", "course_id": 12 }`.
### GET `/student/courses?search=math`
Enrolled courses only.

### GET `/student/courses/{id}`
Enrolled hub. Returns `course`, `modules[]`, and `next_booking` (same booking shape as dashboard/list, or `null`).

### GET `/student/courses/{id}/player` (enrolled)
### POST `/student/courses/{id}/modules/{moduleId}/complete` (enrolled)
### GET/POST `/student/courses/{id}/schedule-wizard` (enrolled)
**GET** returns scheduling capacity + form options:
`course_id`, `course_title`, `paid_classes`, `existing_bookings`, `remaining`, `message`, `default_timezone`, `timezones[]`, `days[]` (`value` 0–6 Sun–Sat / Mon–Sun labels), `default_time`.

**POST** body:
```json
{
  "start_date": "2026-09-20",
  "days": [1, 3, 5],
  "times": { "1": "17:00", "3": "17:00", "5": "18:30" },
  "timezone": "Asia/Kolkata"
}
```
Creates up to `remaining` future `scheduled_class` bookings. Days are Carbon `dayOfWeek` ints (`0`=Sunday … `6`=Saturday).
### GET `/student/courses/{id}/skill-tree` (enrolled)
### GET/POST `/student/courses/{id}/portfolio` (enrolled)
- **GET** — `data.portfolios[]`: `id`, `project_name`, `project_url`, `screenshot_url`, `tutor_feedback`, `badge_earned`, `visibility` (`private|parents|public`), `status`, `created_at`
- **POST** — fields: `project_name` (or `title`), optional `project_url`, optional `screenshot_url`, optional image `file`

### PATCH `/student/courses/{id}/portfolio/{portfolio}` (enrolled)
Share / visibility update. Body: `{ "visibility": "private|parents|public" }`. Owner-only. Sets `status` to `draft` when private, `approved` otherwise.

### GET `/student/courses/{id}/certificate` (enrolled)
Returns metadata. When an uploaded certificate exists, `certificate_download_url` points at the API download route below (Bearer-friendly).

### GET `/student/courses/{id}/certificate/download` (enrolled)
Streams the uploaded certificate file (`Content-Disposition: attachment`). Requires Bearer token.

### GET `/student/assignments`
### GET `/student/assignments/{id}`
### POST `/student/assignments/{id}/submit`

Multipart fields for question-based assignments:
- `answers[{questionId}]` — MCQ option index or short answer text
- `answer_drawings[{questionId}]` — base64 PNG data URL
- `answer_files[{questionId}]` — image file upload

Legacy (no questions): `content`, `submission_file`

### GET `/student/quizzes`
### GET `/student/quizzes/{id}`
Returns quiz summary. If not yet passed, includes `questions[]` for the take screen. If already passed, `questions` is empty and take is blocked.

### POST `/student/quizzes/{id}/submit`
```json
{ "answers": { "q1abc": 0, "q2def": "Paris" } }
```
Response includes `attempt` (score, passed, earned_xp), `review[]` (per-question student vs correct), and updated quiz summary (`can_retry` when failed). Reattempts allowed until passed; 403 after pass.

### GET `/student/quizzes/{id}/results`
Latest attempt + full answer review for an accessible quiz.
Response `data`: `{ quiz, attempt: { score, passed, created_at }, review: [{ number, question, type, student_answer, correct_answer, is_correct, image_url? }] }`  
404 if no attempt yet.

### GET `/student/bookings`
Response `data`:
- `series[]` — **one entry per enrolled course**, collapsing that course's recurring classes into a single row (mirrors the admin "Course Bookings" tab):
  - `id` (representative booking), `course_id`, `course` `{id,title,slug,thumbnail_url}`, `tutor` `{id,name,avatar_url}`, `status`
  - `classes` — total classes for the course (falls back to the enrollment's `paid_classes_count` when nothing is scheduled yet)
  - `scheduled` — count of `scheduled_class` rows, `remaining` — how many are still in the future
  - `is_recurring` — true when at least one class is scheduled
  - `days[]` — distinct weekday ints (`0`=Sun … `6`=Sat) the classes fall on
  - `times` — `{weekday: "HH:mm"}` in the series timezone
  - `start_date` (`YYYY-MM-DD`), `timezone`, `next_class_at` (ISO8601 or null), `created_at`
- `bookings[]` — non-`scheduled_class` sessions only (enrollment placeholders, live sessions): `id`, `course`, `tutor` (+ `avatar_url`), `status`, `session_type`, `scheduled_at`, `created_at`, `zoom_join_url`, `zoom_passcode`, `can_join`, `join_window` `{opens_at,closes_at}`, `description`
- `classes[]` — the individual `scheduled_class` bookings (flat, sorted by `scheduled_at`), same shape as `bookings[]`. Use this to expand a series into its classes.
- `available_sessions[]` — bookable live sessions for enrolled courses: `id`, `title`, `course`, `tutor`, `start_time`, `duration_minutes`, `status`, `join_url`, `zoom_passcode`, `can_join`, `join_window`
- `demo_sessions[]` — trial/camp demos matched by student email: `id`, `session_type` (`demo`), `camp_name`, `course`, `tutor`, `status`, `scheduled_at`, `created_at`, `zoom_join_url`, `zoom_passcode`, `can_join`, `join_window`, `awaiting_schedule`

**Series timezone:** resolved from the `scheduled_class` rows' `parent_timezone` first (the placeholder row often has none), then the representative row, then the student's account timezone. Bare `UTC`/`GMT` values are treated as unset so the student's real zone wins instead of a hardcoded default.

**Mobile join window:** Join opens **15 minutes before** `scheduled_at` / `start_time` and closes **60 minutes after**. Show Join only when `can_join` is true (and URL present). Unscheduled demos have `awaiting_schedule: true` and no join.

Dashboard `demo_sessions` / `upcoming_sessions` use the same enriched shapes.

### GET `/student/bookings/{id}`
Single booking owned by the authenticated student (`data.booking`). Returns 404 if not owner.

### POST `/student/bookings`
```json
{ "live_session_id": 12 }
```
Creates a booking from an available live session (copies join URL + passcode). Returns formatted booking (201).

### POST `/student/bookings/{id}/cancel`
Owner-only. Cancels an upcoming booking when `status` is `active|scheduled|confirmed|pending` and `scheduled_at` is in the future (or null). Sets `status` to `cancelled`. Returns formatted booking.

### GET `/student/recordings`
List of class recordings available to the student (course + session metadata).

### POST `/student/recordings/{id}/request`
Request access to a recording.

### GET `/student/recordings/{id}/stream`
Stream a recording the student may access (Bearer).

### GET `/student/rewards`
XP, badges, and rewards gallery for the authenticated student.

### GET `/student/checkout/{courseId}?coupon=CODE&currency=USD&pricing_model=full|per_class`
Returns converted `pricing` for the selected currency (`original_price`, `final_price`, `discount_amount`, `per_class_price`, `amount` alias of final). Coupon applies on USD base before conversion.

Also returns scheduling metadata for enrollment:
- `number_of_classes` — classes included in the full course.
- `schedule.default_start_date`, `schedule.default_time`, `schedule.default_timezone`, `schedule.timezones[]` (`{value,label}`), `schedule.days[]` (`{value,label}`; `value` is Carbon `dayOfWeek`, 0=Sunday … 6=Saturday).

### POST `/student/checkout/{courseId}/razorpay`
### POST `/student/checkout/{courseId}/stripe`
### POST `/student/checkout/{courseId}/paypal`

Body (optional): `{ "coupon_code", "pricing_model": "full|per_class", "currency": "USD", "delivery_mode": "live|self-paced", "classes": 8, "schedule_start_date": "2026-10-20", "schedule_days": [1,3,5], "schedule_times": {"1":"17:00","3":"17:00","5":"18:30"}, "timezone": "Asia/Kolkata" }`

- `classes` — number of classes to purchase. When it differs from `number_of_classes` the price is computed per class. Recorded as the enrollment's `paid_classes_count`.
- `schedule_*` — optional recurring schedule. On payment success every purchased class is automatically booked as a `scheduled_class` on the selected weekdays/times, starting from `schedule_start_date`. When omitted, classes are recorded but not booked; the student can use the schedule wizard later.

Razorpay create returns `payment_id`, `order_id`, `amount` (major units), **`amount_subunit`** (paise/cents for `Razorpay.open`), `order: { id, amount: amount_subunit, currency }`, `razorpay_key`, and `deep_links`.
Stripe/PayPal create responses include `payment_id` and `deep_links.success` / `deep_links.cancel` (`younimatiq://payment/...`).
Stripe Checkout `success_url` / `cancel_url` hit `GET /student/payments/stripe-return` which completes payment then redirects into the app deep link.
PayPal create returns `approval_url` (+ `checkout_url` alias). PayPal return hits `GET /student/payments/paypal-return` (captures APPROVED orders) then deep-links with `payment_id` + `paypal_order_id`.

### GET `/student/custom-checkout/{course}` (signed)
### POST `/student/custom-checkout/{course}/razorpay|stripe|paypal` (signed)
Admin custom-price enroll. Same signed query as web `/custom-enroll/{course}`: `price`, `currency`, `expires`, `signature` (plus optional `classes`). Course may be id or slug. Response mirrors checkout `show` with `custom: true` and locked price, and includes `number_of_classes` + the `schedule` metadata block (`default_start_date`, `default_time`, `default_timezone`, `timezones[]`, `days[]`) so the app can render the recurring-schedule picker. Deep link: `younimatiq://custom-enroll/{slug}?price&currency&expires&signature` → app `/custom-checkout/:slug`.

The create endpoints accept the same optional scheduling body as the regular checkout: `{ "classes": 8, "delivery_mode": "live", "schedule_start_date": "2026-10-20", "schedule_days": [1,3,5], "schedule_times": {"1":"17:00"}, "timezone": "Asia/Kolkata" }`. Defaults to the signed `classes`. On payment success every class is booked as a `scheduled_class` across the selected weekdays/times (falls back to a single weekly slot built from `class_date`/`class_time` when those legacy fields are sent).

### POST `/student/payments/verify`
Complete enrollment after native payment (Razorpay / PayPal / Stripe session).

```json
{
  "payment_id": 123,
  "gateway": "razorpay",
  "razorpay_payment_id": "...",
  "razorpay_order_id": "...",
  "razorpay_signature": "..."
}
```

Stripe example: `{ "payment_id": 123, "gateway": "stripe", "session_id": "cs_..." }`  
PayPal example: `{ "payment_id": 123, "gateway": "paypal", "paypal_order_id": "..." }`

### POST `/student/payments/cancel`
```json
{ "payment_id": 123 }
```
Marks pending payment as failed.

### GET `/student/payments/stripe-return` (no Bearer)
Query: `status=success|cancel`, `payment_id`, `session_id` (Stripe). Used by Stripe Checkout redirect → deep link.

### GET `/student/payments/paypal-return` (no Bearer)
Query: `status=success|cancel`, `payment_id`, PayPal `token` (order id). Captures then deep-links.

### GET `/student/tips`
Assigned tip folders + loose tips (`tips_folders[]` with nested `tips[]`, `loose_tips[]`). Each tip: `id`, `title`, `type` (`video`|`file`), `content_url` (external/YouTube when absolute), `stream_url` (Bearer-auth stream).

### GET `/student/tips/{tip}/stream`
Streams private tip files or redirects absolute `content_url`. Require Bearer.
Streams tip video/file for assigned students (Bearer). Dashboard tip payloads use this API URL as `stream_url`.

### GET `/student/community`
`data.projects[]`: `id`, `title`, `author_name`, `category`, `project_url`, **`media_url`** (absolute), **`media_type`** (`video`|`image`), `views_count`, `likes_count`, `liked`.  
`data.testimonials[]`: `id`, `name`, `content`, `rating`, `avatar_url`.

### POST `/student/community/projects/{id}/view`
### POST `/student/community/projects/{id}/like`
### POST `/student/community/projects/{id}/comment`
Body: `{ "content": "Nice project!" }` (or equivalent comment field accepted by the controller).

### GET `/student/payment-history`

### GET/PUT/POST `/student/profile`
POST used for multipart avatar upload.

### GET/PUT `/student/settings`
```json
{ "password": "...", "password_confirmation": "...", "email_reminders": true, "email_marketing": false, "timezone": "UTC" }
```

### GET/POST `/student/reviews`
### GET/POST `/student/qa`
### GET `/student/wishlist`
### POST/DELETE `/student/wishlist/{courseId}`
### GET `/student/referrals`
`referral_code`, `share_url` (`/register/student?ref=`), `mobile_share_url` (`younimatiq://register?ref=`), `share_message`, `total_earned`, `referrals[]` (attributed only), `sibling_discounts[]`.

### POST `/student/device-tokens`
Body: `{ "token", "platform": "ios|android", "app_version?" }` — FCM device token. Required for mobile push when `FCM_SERVER_KEY` is set on the server.
### DELETE `/student/device-tokens`
Body: `{ "token" }`

### GET `/student/notifications`
Items include `id`, `type`, `title`, `body`, `action_url` (web path — map to app routes), `read_at`, `created_at`.
### POST `/student/notifications/{id}/read`
### POST `/student/notifications/read-all`

---

## Guest trial (no Bearer)

Public, throttled. Used by the trial-expired / book-free-session flows in the mobile app.

### GET `/guest/consultation-slots`
Same payload as the web consultation slots endpoint (`timezone`, `windows`, optional `by_weekday` when `group_by_weekday=1`). Query: `timezone`, optional `week_of` / `date`, optional `group_by_weekday`.

### POST `/guest/book-trial`
Creates a free trial `CampBooking` (same fields as web trial form):
```json
{
  "email": "parent@example.com",
  "phone": "+1...",
  "whatsapp_number": "+1...",
  "name_c1": "Alex",
  "age_c1": 10,
  "grade_c1": "5",
  "board_c1": "CBSE",
  "courses_c1": "Minecraft Coding",
  "scheduled_date": "2026-11-10",
  "scheduled_time": "17:00",
  "timezone": "Asia/Kolkata"
}
```
Response `201` `data`: `{ booking_id, camp_name, scheduled_at, zoom_join_url, timezone }`.

### POST `/guest/book-class`
Creates a paid/pending demo `CampBooking` (web `POST /book-class` fields):
`parent_name`, `camp_name`, `email`, `age`, `scheduled_date`, `scheduled_time`, optional child/phone/course/timezone.
Response `201` `data`: `{ booking_id, payment_status, amount, currency, scheduled_at, needs_payment, checkout_path }`.

### Guest camp checkout (`/guest/booking/{booking}`)

| Method | Path | Notes |
|--------|------|--------|
| GET | `/checkout` | Amount, gateway flags, booking summary |
| GET | `/` | Booking status snapshot |
| POST | `/razorpay` | Creates Razorpay order (`order_id`, `amount_subunit`, `razorpay_key`, …) or `{ free: true }` |
| POST | `/verify` | Body: `razorpay_order_id`, `razorpay_payment_id`, `razorpay_signature` → marks paid |
| POST | `/stripe` | `{ checkout_url, session_id }` — return deep-links `younimatiq://camp-checkout/{id}` |
| GET | `/stripe-return` | Completes Stripe then redirects into the app |
| POST | `/paypal` | Sandbox mock — marks paid immediately |

### Booking reschedule (auth student)

### GET `/student/bookings/{id}/available-slots`
Query: `date=YYYY-MM-DD`, optional `timezone`. Returns preferred window slots with `available` (tutor conflict + 24h notice).

### POST `/student/bookings/{id}/reschedule`
Body: `{ "scheduled_at": "ISO-8601" }` or `{ "scheduled_date", "scheduled_time", "timezone" }`.
Owner-only; `scheduled_class` (not `live_session`); ≥24h ahead; booking windows enforced; recreates Zoom when a join URL exists.

---

## User object (sample)

```json
{
  "id": 1,
  "name": "Alex",
  "email": "alex@example.com",
  "avatar_url": "https://...",
  "timezone": "Asia/Kolkata",
  "xp": 1200,
  "level": 3,
  "trial": {
    "has_purchased_course": false,
    "trial_ends_at": "2026-09-10T12:00:00+00:00",
    "trial_expired": false,
    "seconds_remaining": 604800
  }
}
```

---

## HTTP codes

| Code | Meaning |
|------|---------|
| 200 | Success |
| 201 | Created |
| 401 | Invalid/missing token |
| 403 | Forbidden / trial expired (`trial_expired: true`) |
| 422 | Validation error |

---

## Flutter integration notes

1. Store token in `flutter_secure_storage`.
2. On 401 → clear token, navigate to login.
3. On 403 with `trial_expired: true` → Trial Expired screen. Still allowed while expired (no purchase yet): `/student/catalog/*`, `/student/checkout/*`, `/student/payments/*` (verify/cancel), and `/student/trial-status` so the student can browse and complete enrollment.
4. Media: use absolute `thumbnail_url` / `video_url` / `content_url` as returned. Prefer `resolveMediaUrl` + Bearer headers for `/secure-asset/` paths (Sanctum is accepted on `/secure-asset/{path}` for private modules/assignments). For PDFs/documents, download with Bearer then open/share — bare `launchUrl` has no Authorization. Public `/storage/` and `/images/` usually work without auth.
5. Player Zoom modules: Join when URL present unless `can_join` is explicitly `false`. Bookings tab uses the timed `can_join` window (15m before → 60m after).
6. Brand assets: `{BASE_URL}/logo.png`
7. **Recurring classes flow (parity with web):**
   - Purchase: `CheckoutScreen` (`/checkout/:id`) sends `classes`, `schedule_start_date`, `schedule_days[]` (0=Sun…6=Sat), `schedule_times{day:"HH:mm"}`, `timezone` to the gateway POST. The backend creates one booking per class on those recurring days.
   - Already enrolled: `ScheduleWizardScreen` (`/courses/:id/schedule`) — `GET` shows `paid_classes`/`existing_bookings`/`remaining`, `POST` `{start_date, days[], times{day:"HH:mm"}, timezone}` generates the remaining classes.
   - Bookings tab renders `series[]` (one card per course: N classes, days, next class). Expand to list `classes[]`.
   - Timezone pickers preselect the account timezone, or guess from the device UTC offset when the API returns `UTC`/empty (see `mobile/lib/core/util/timezone.dart`).
8. Routes: `/catalog` (browse) → `/catalog/:id` (detail) → `/checkout/:id` (buy) or `/courses/:id/player` (enrolled). `/courses/:id/schedule` edits the recurring schedule.

