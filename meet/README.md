# Younimatiq Meet (self-hosted)

Runs from **`meet/app`** on this same server (PM2 → `127.0.0.1:8000`).  
Laravel creates rooms over HTTP; join links open the Meet UI in the browser.

## Live layout

| Piece | Role |
|---|---|
| `meet/app/server.js` | Express API + Socket.IO signaling + WebRTC mesh rooms |
| `meet/app/public/room.html` | Browser meeting UI |
| MySQL `vjmeet` | Rooms / attendance / recordings metadata |
| nginx | Proxies Meet paths on `https://younimatiq.com` (and `meet.younimatiq.com` when DNS exists) |
| PM2 `younimatiq-meet` | Keeps Node alive |

Laravel does **not** embed the UI. It POSTs to `/api/create-meeting` and stores join/start URLs on bookings.

## How Younimatiq connects

```
Admin sets meeting_provider = meet
        │
        ▼
ZoomService::createMeeting()  ──if meet──►  MeetService::createMeeting()
        │                                         │
        │                                         ▼
        │                              POST https://younimatiq.com/api/create-meeting
        │                              Header: X-Meet-Secret
        ▼
Booking.zoom_join_url / zoom_start_url / meeting_provider / meeting_room_id
        │
        ▼
Student Join / Tutor Host → https://younimatiq.com/room/<id>
```

## Self-hosted options (your own, free) — pick one

| Option | What it is | When to use |
|---|---|---|
| **1. Local Meet from `meet/` (default now)** | Node app in this repo, PM2 on :8000, nginx on your domain | You want **your own** Meet, zero monthly SaaS, one server |
| **2. Dedicated subdomain later** | Same app, `meet.younimatiq.com` → :8000 (nginx already prepared) | After you add a DNS **A** (or CNAME) for `meet` → this server IP |
| **3. Embed Meet UI inside Laravel/Inertia** | Rewrite rooms into PHP/React | Only if you want one codebase — months of work; media still needs Node/WebRTC |
| **4. Zoom only (already built)** | Keep `meeting_provider=zoom` | Paid Zoom; not “your own” Meet |

**Use option 1.** It is already your product, free to run on this VPS, and Laravel only flips a provider switch.

Optional later (still free/self-hosted, not required for classes): turn on LiveKit egress in `meet/app` if you want server-side cloud recording. Browser local recording works without it.

## Admin steps

1. Admin → **API & Gateway Settings**
2. Select **Younimatiq Meet**
3. Base URL `https://younimatiq.com`, org `younimatiq`, API secret (same as `MEET_API_SECRET` in `.env`)
4. Save → Integrations → **Test Meet**
5. Assign / schedule a class → join link should be `younimatiq.com/room/...`

## Ops

```bash
cd /var/www/younimatiqwebsite/meet/app
pm2 status younimatiq-meet
pm2 logs younimatiq-meet --lines 50
curl -s http://127.0.0.1:8000/api/ice-servers
systemctl status coturn --no-pager
```

Env file: `meet/app/.env` (`MEET_PUBLIC_URL`, `MEET_API_SECRET`, MySQL, `TURN_*`).  
Laravel: `MEET_BASE_URL`, `MEET_API_SECRET`, `MEET_APP_ROOT=/var/www/younimatiqwebsite/meet/app`.

### Performance (self-hosted LiveKit SFU)

| Layer | Current setup |
|---|---|
| Media | **LiveKit SFU** (Docker `younimatiq-livekit`, host network) — mesh kept as fallback |
| Signaling UI | Socket.IO (waiting room, chat, host controls, contact guard) |
| NAT traversal | **Self-hosted coturn** on `younimatiq.com:3478` (UDP + TCP) |
| Capture / publish | 720p @ 30fps + simulcast layers; adaptiveStream + dynacast |
| LiveKit ports | TCP `7880` (proxied `/rtc`), TCP `7881`, UDP `50000–60000` |
| Parallel rooms | Many rooms via SFU (auto-create) |
| Per-room soft cap | `MEET_MAX_PARTICIPANTS_PER_ROOM=20` |

```bash
docker ps --filter name=younimatiq-livekit
curl -s https://younimatiq.com/api/meet-config
systemctl status coturn --no-pager
```

**Expected capacity on this 4-vCPU / 8GB box:** many parallel tutoring classes with stable HD. Scale CPU/bandwidth before huge webinars.

### When DNS for meet.younimatiq.com is ready

1. Add **A** record `meet` → `89.117.148.221` (this server)
2. `certbot certonly --webroot -w /var/www/html -d meet.younimatiq.com`
3. Point `MEET_PUBLIC_URL` / Laravel base URL to `https://meet.younimatiq.com` and restart PM2
