const express = require('express');
const app = express();
const http = require('http').Server(app);
const io = require('socket.io')(http, { cors: { origin: '*', methods: ['GET', 'POST'] } });
const { v4: uuidv4 } = require('uuid');
const path = require('path');
const crypto = require('crypto');
const mysql = require('mysql2/promise');
const { AccessToken, EgressClient, EncodedFileOutput, EncodedFileType } = require('livekit-server-sdk');
const multer = require('multer');
const fs = require('fs');

(function loadDotEnv() {
    try {
        const envPath = path.join(__dirname, '.env');
        if (!fs.existsSync(envPath)) return;
        for (const line of fs.readFileSync(envPath, 'utf8').split('\n')) {
            const m = line.match(/^([^#=]+)=(.*)$/);
            if (!m) continue;
            const key = m[1].trim();
            const val = m[2].trim().replace(/^["']|["']$/g, '');
            if (!process.env[key]) process.env[key] = val;
        }
    } catch (_) {}
})();

const PORT = process.env.PORT || 8000;
const MEET_PUBLIC_URL = process.env.MEET_PUBLIC_URL || 'https://younimatiq.com';
const MEET_API_SECRET = process.env.MEET_API_SECRET || 'younimatiq-meet-secret-change-me';
const LARAVEL_CONTACT_ALERT_URL = process.env.LARAVEL_CONTACT_ALERT_URL || 'https://younimatiq.com/webhooks/meet/contact-alert';
/** Server-side recording (LiveKit Egress) is OFF for website/school meetings unless explicitly enabled. */
const PLATFORM_RECORDING_ENABLED = String(process.env.PLATFORM_RECORDING_ENABLED || 'false').toLowerCase() === 'true';
const TURN_HOST = process.env.TURN_HOST || 'younimatiq.com';
const TURN_SECRET = process.env.TURN_SECRET || '';
const TURN_TTL_SEC = Math.max(300, Number(process.env.TURN_TTL_SEC || 21600));
const MEET_MAX_PARTICIPANTS_PER_ROOM = Math.max(2, Number(process.env.MEET_MAX_PARTICIPANTS_PER_ROOM || 6));
const LIVEKIT_ENABLED = String(process.env.LIVEKIT_ENABLED || 'true').toLowerCase() === 'true';
const LIVEKIT_API_KEY = process.env.LIVEKIT_API_KEY || 'younimatiq';
const LIVEKIT_API_SECRET = process.env.LIVEKIT_API_SECRET || '';
const LIVEKIT_WS_URL = process.env.LIVEKIT_WS_URL || 'wss://younimatiq.com';
const LIVEKIT_HTTP_URL = process.env.LIVEKIT_HTTP_URL || 'http://127.0.0.1:7880';

const egressClient = new EgressClient(
    LIVEKIT_HTTP_URL,
    LIVEKIT_API_KEY,
    LIVEKIT_API_SECRET || 'devsecret_12345678901234567890123'
);

const uploadDir = path.join(__dirname, 'uploads');
if (!fs.existsSync(uploadDir)) fs.mkdirSync(uploadDir, { recursive: true });

function isWritableDir(dir) {
    try { fs.accessSync(dir, fs.constants.W_OK); return true; } catch (_) { return false; }
}
const externalDrive = '/var/www/externaldrive';
const mntDir = fs.existsSync(externalDrive) && isWritableDir(externalDrive)
    ? externalDrive
    : uploadDir;
if (!fs.existsSync(mntDir)) fs.mkdirSync(mntDir, { recursive: true });

const storage = multer.diskStorage({
    destination: function (req, file, cb) {
        const orgName = (req.body.organization || 'younimatiq').replace(/[^a-zA-Z0-9_-]/g, '_');
        let dest = path.join(mntDir, orgName);
        try {
            fs.mkdirSync(dest, { recursive: true });
            cb(null, dest);
        } catch (e) {
            try {
                dest = path.join(uploadDir, orgName);
                fs.mkdirSync(dest, { recursive: true });
                cb(null, dest);
            } catch (e2) {
                cb(e2);
            }
        }
    },
    filename: function (req, file, cb) {
        const roomId = req.body.roomId || uuidv4();
        const ext = path.extname(file.originalname) || '.webm';
        cb(null, `${roomId}_${Date.now()}${ext}`);
    }
});
const upload = multer({ storage });

const db = mysql.createPool({
    host: process.env.MYSQL_HOST || 'localhost',
    user: process.env.MYSQL_USER || 'vjmeet_user',
    password: process.env.MYSQL_PASSWORD || 'password',
    database: process.env.MYSQL_DATABASE || 'vjmeet',
    waitForConnections: true,
    connectionLimit: 10,
    queueLimit: 0
});

db.getConnection()
    .then(async (conn) => {
        console.log('Connected to MySQL database');
        try { await conn.query('ALTER TABLE recordings ADD COLUMN organization VARCHAR(255) NULL AFTER room_id'); } catch (e) {}
        try { await conn.query('ALTER TABLE recordings ADD COLUMN file_size BIGINT NULL AFTER file_path'); } catch (e) {}
        try {
            await conn.query(`CREATE TABLE IF NOT EXISTS platform_rooms (
                room_id VARCHAR(64) PRIMARY KEY,
                organization VARCHAR(255) NULL,
                contact_guard TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )`);
        } catch (e) {}
        const platformRoomColumns = [
            ['title', 'VARCHAR(255) NULL AFTER organization'],
            ['passcode_hash', 'VARCHAR(64) NULL AFTER contact_guard'],
            ['passcode_required', 'TINYINT(1) NOT NULL DEFAULT 0 AFTER passcode_hash'],
            ['scheduled_at', 'DATETIME NULL AFTER passcode_required'],
            ['waiting_room', 'TINYINT(1) NOT NULL DEFAULT 1 AFTER scheduled_at'],
            ['locked', 'TINYINT(1) NOT NULL DEFAULT 0 AFTER waiting_room'],
            ['recording_enabled', 'TINYINT(1) NOT NULL DEFAULT 0 AFTER locked'],
            ['brand_name', 'VARCHAR(255) NULL AFTER recording_enabled'],
            ['logo_url', 'VARCHAR(500) NULL AFTER brand_name'],
            ['primary_color', 'VARCHAR(16) NULL AFTER logo_url'],
        ];
        for (const [col, def] of platformRoomColumns) {
            try { await conn.query(`ALTER TABLE platform_rooms ADD COLUMN ${col} ${def}`); } catch (e) {}
        }
        try {
            await conn.query(`CREATE TABLE IF NOT EXISTS attendance (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                room_id VARCHAR(64) NOT NULL,
                organization VARCHAR(255) NULL,
                user_id VARCHAR(128) NULL,
                user_name VARCHAR(255) NULL,
                role VARCHAR(32) NULL,
                joined_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                left_at TIMESTAMP NULL,
                duration_seconds INT NULL,
                INDEX (room_id),
                INDEX (joined_at)
            )`);
        } catch (e) {}
        try {
            await conn.query(`CREATE TABLE IF NOT EXISTS contact_alerts (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                room_id VARCHAR(64) NOT NULL,
                source VARCHAR(32) NOT NULL,
                actor_name VARCHAR(255) NULL,
                actor_role VARCHAR(32) NULL,
                shared_text TEXT NOT NULL,
                matches_json JSON NULL,
                primary_type VARCHAR(32) NULL,
                occurred_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX (room_id),
                INDEX (occurred_at)
            )`);
        } catch (e) {}
        conn.release();
    })
    .catch((err) => console.error('Failed to connect to MySQL:', err.message));

const roomHosts = new Map();
/** Rooms created via website API (/api/create-meeting) — contact sharing blocked */
const platformRooms = new Map();
/** roomId -> Set of socket.ids that have CC enabled (want to see captions) */
const roomCaptionViewers = new Map();
/** roomId -> cached platform_rooms settings */
const roomSettings = new Map();
/** roomId -> Map(socketId -> { socketId, userId, name, joinedAt }) for waiting-room approval */
const waitingRooms = new Map();
/** roomId -> collaborative whiteboard scene { objects: [] } */
const whiteboardRooms = new Map();

function captionsWantedInRoom(roomId) {
    const set = roomCaptionViewers.get(roomId);
    return !!(set && set.size > 0);
}

function setCaptionViewer(roomId, socketId, enabled) {
    if (!roomId || !socketId) return false;
    if (!roomCaptionViewers.has(roomId)) roomCaptionViewers.set(roomId, new Set());
    const set = roomCaptionViewers.get(roomId);
    if (enabled) set.add(socketId);
    else set.delete(socketId);
    if (set.size === 0) roomCaptionViewers.delete(roomId);
    return captionsWantedInRoom(roomId);
}

function emitCaptionsDemand(roomId) {
    if (!roomId) return;
    io.to(roomId).emit('captions-demand', { active: captionsWantedInRoom(roomId) });
}

async function markPlatformRoom(roomId, organization, extra = {}) {
    platformRooms.set(roomId, { organization: organization || null, contact_guard: true });
    roomSettings.delete(roomId);
    try {
        await db.query(
            'INSERT INTO platform_rooms (room_id, organization, contact_guard) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE organization = VALUES(organization), contact_guard = 1',
            [roomId, organization || null]
        );
        const patch = {};
        for (const key of ['title', 'passcode', 'passcode_hash', 'passcode_required', 'scheduled_at', 'waiting_room', 'locked', 'recording_enabled', 'brand_name', 'logo_url', 'primary_color']) {
            if (Object.prototype.hasOwnProperty.call(extra, key)) patch[key] = extra[key];
        }
        if (Object.keys(patch).length) await updateRoomSettings(roomId, patch);
    } catch (e) {
        console.warn('Could not persist platform room:', e.message);
    }
}

function normalizeSettings(row) {
    if (!row) return null;
    return {
        room_id: row.room_id,
        organization: row.organization || null,
        title: row.title || null,
        contact_guard: !!row.contact_guard,
        passcode_hash: row.passcode_hash || null,
        passcode_required: !!row.passcode_required,
        scheduled_at: row.scheduled_at || null,
        waiting_room: !!row.waiting_room,
        locked: !!row.locked,
        recording_enabled: !!row.recording_enabled,
        brand_name: row.brand_name || null,
        logo_url: row.logo_url || null,
        primary_color: row.primary_color || null,
    };
}

async function getRoomSettings(roomId) {
    if (!roomId) return null;
    if (roomSettings.has(roomId)) return roomSettings.get(roomId);
    try {
        const [rows] = await db.query('SELECT * FROM platform_rooms WHERE room_id = ? LIMIT 1', [roomId]);
        if (!rows.length) return null;
        const settings = normalizeSettings(rows[0]);
        roomSettings.set(roomId, settings);
        return settings;
    } catch (e) {
        return null;
    }
}

async function updateRoomSettings(roomId, patch) {
    if (!roomId || !patch) return null;
    const allowed = ['title', 'passcode_hash', 'passcode_required', 'scheduled_at', 'waiting_room', 'locked', 'recording_enabled', 'brand_name', 'logo_url', 'primary_color'];
    const sets = [];
    const params = [];
    for (const key of allowed) {
        if (!Object.prototype.hasOwnProperty.call(patch, key)) continue;
        sets.push(`${key} = ?`);
        params.push(patch[key]);
    }
    if (!sets.length) return getRoomSettings(roomId);
    params.push(roomId);
    await db.query(`UPDATE platform_rooms SET ${sets.join(', ')} WHERE room_id = ?`, params);
    roomSettings.delete(roomId);
    const settings = await getRoomSettings(roomId);
    if (settings) {
        platformRooms.set(roomId, { organization: settings.organization, contact_guard: settings.contact_guard });
    }
    return settings;
}

function hashPasscode(passcode) {
    return crypto.createHash('sha256').update(String(passcode)).digest('hex');
}

async function verifyRoomPasscode(roomId, passcode) {
    const settings = await getRoomSettings(roomId);
    if (!settings || !settings.passcode_required) return true;
    if (!passcode) return false;
    try {
        return crypto.timingSafeEqual(Buffer.from(hashPasscode(passcode)), Buffer.from(settings.passcode_hash || ''));
    } catch (_) {
        return false;
    }
}

function brandingFor(settings) {
    if (!settings) return null;
    if (!settings.brand_name && !settings.logo_url && !settings.primary_color) return null;
    return {
        name: settings.brand_name || null,
        logo_url: settings.logo_url || null,
        primary_color: settings.primary_color || null,
    };
}

function roomStatusPayload(roomId, settings, extra = {}) {
    return {
        roomId,
        host_present: hostPresent(roomId),
        contact_guard: !!(settings && settings.contact_guard),
        platform_managed: !!(settings && settings.contact_guard),
        recording_enabled: settings ? !!settings.recording_enabled : true,
        waiting_room: settings ? !!settings.waiting_room : false,
        locked: settings ? !!settings.locked : false,
        passcode_required: settings ? !!settings.passcode_required : false,
        title: settings ? settings.title : null,
        scheduled_at: settings ? settings.scheduled_at : null,
        branding: brandingFor(settings),
        captions_wanted: captionsWantedInRoom(roomId),
        ...extra,
    };
}

function emitToHosts(roomId, event, payload) {
    const hosts = roomHosts.get(roomId);
    if (!hosts) return;
    for (const socketId of hosts) io.to(socketId).emit(event, payload);
}

function waitingListFor(roomId) {
    const map = waitingRooms.get(roomId);
    if (!map) return [];
    return [...map.values()];
}

function emitWaitingList(roomId) {
    emitToHosts(roomId, 'waiting-list', waitingListFor(roomId));
}

function addWaitingEntry(roomId, socket, name) {
    if (!waitingRooms.has(roomId)) waitingRooms.set(roomId, new Map());
    waitingRooms.get(roomId).set(socket.id, {
        socketId: socket.id,
        userId: socket.userId || null,
        name: name || socket.userName || 'Guest',
        joinedAt: new Date().toISOString(),
    });
    emitWaitingList(roomId);
}

function removeWaitingEntry(roomId, socketId) {
    const map = waitingRooms.get(roomId);
    if (!map) return null;
    const entry = map.get(socketId) || null;
    if (entry) map.delete(socketId);
    if (map.size === 0) waitingRooms.delete(roomId);
    emitWaitingList(roomId);
    return entry;
}

const ATTENDANCE_WEBHOOK_URL = process.env.LARAVEL_ATTENDANCE_WEBHOOK_URL || 'https://younimatiq.com/webhooks/meet/attendance';

async function postMeetWebhook(url, payload) {
    if (!url) return;
    try {
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Meet-Secret': MEET_API_SECRET,
            },
            body: JSON.stringify(payload),
        });
        if (!res.ok) console.warn('Meet webhook failed:', url, res.status);
    } catch (e) {
        console.warn('Meet webhook error:', url, e.message);
    }
}

async function recordAttendanceJoin(socket, roomId, userId, userName, role, organization) {
    if (socket.attendanceId) return;
    try {
        const [result] = await db.query(
            'INSERT INTO attendance (room_id, organization, user_id, user_name, role) VALUES (?, ?, ?, ?, ?)',
            [roomId, organization || null, userId || null, userName || null, role || null]
        );
        socket.attendanceId = result.insertId;
        socket.attendanceJoinedAt = Date.now();
        postMeetWebhook(ATTENDANCE_WEBHOOK_URL, {
            event: 'participant-joined',
            room_id: roomId,
            organization: organization || null,
            user_id: userId || null,
            user_name: userName || null,
            role: role || 'student',
            joined_at: new Date().toISOString(),
        });
    } catch (e) {
        console.warn('Could not record attendance join:', e.message);
    }
}

async function recordAttendanceLeave(socket) {
    if (!socket.attendanceId) return;
    const attendanceId = socket.attendanceId;
    socket.attendanceId = null;
    const duration = socket.attendanceJoinedAt ? Math.max(0, Math.round((Date.now() - socket.attendanceJoinedAt) / 1000)) : null;
    try {
        await db.query('UPDATE attendance SET left_at = NOW(), duration_seconds = ? WHERE id = ?', [duration, attendanceId]);
        postMeetWebhook(ATTENDANCE_WEBHOOK_URL, {
            event: 'participant-left',
            attendance_id: attendanceId,
            room_id: socket.roomId || null,
            user_id: socket.userId || null,
            user_name: socket.userName || null,
            role: socket.isHost ? 'tutor' : 'student',
            left_at: new Date().toISOString(),
            duration_seconds: duration,
        });
    } catch (e) {
        console.warn('Could not record attendance leave:', e.message);
    }
}

async function isPlatformManagedRoom(roomId) {
    if (!roomId) return false;
    if (platformRooms.has(roomId)) return !!platformRooms.get(roomId).contact_guard;
    try {
        const [rows] = await db.query('SELECT contact_guard FROM platform_rooms WHERE room_id = ? LIMIT 1', [roomId]);
        if (rows.length) {
            const guarded = !!rows[0].contact_guard;
            platformRooms.set(roomId, { contact_guard: guarded });
            return guarded;
        }
    } catch (e) {}
    return false;
}

function hostTokenForRoom(roomId) {
    return crypto.createHmac('sha256', MEET_API_SECRET).update(`host:${roomId}`).digest('hex');
}

function isValidHostToken(roomId, token) {
    if (!roomId || !token) return false;
    const expected = hostTokenForRoom(roomId);
    try {
        return crypto.timingSafeEqual(Buffer.from(String(token)), Buffer.from(expected));
    } catch (_) {
        return false;
    }
}

function requireApiSecret(req, res, next) {
    const secret = req.header('X-Meet-Secret');
    if (!MEET_API_SECRET || secret !== MEET_API_SECRET) {
        return res.status(401).json({ error: 'Unauthorized' });
    }
    return next();
}

function hostPresent(roomId) {
    const hosts = roomHosts.get(roomId);
    return !!(hosts && hosts.size > 0);
}

app.use(express.static(path.join(__dirname, 'public')));
app.use(express.json());

app.get('/api/room', (req, res) => {
    const roomId = uuidv4();
    const hostToken = hostTokenForRoom(roomId);
    res.json({ roomId, host_token: hostToken });
});

/**
 * Time-limited TURN credentials (coturn use-auth-secret / REST API style).
 * Clients fetch this before creating RTCPeerConnections.
 */
function buildIceServers() {
    const iceServers = [
        { urls: ['stun:stun.l.google.com:19302', 'stun:stun1.l.google.com:19302'] },
        { urls: `stun:${TURN_HOST}:3478` },
    ];
    if (TURN_SECRET) {
        const username = String(Math.floor(Date.now() / 1000) + TURN_TTL_SEC);
        const credential = crypto.createHmac('sha1', TURN_SECRET).update(username).digest('base64');
        iceServers.push(
            {
                urls: [
                    `turn:${TURN_HOST}:3478?transport=udp`,
                    `turn:${TURN_HOST}:3478?transport=tcp`,
                ],
                username,
                credential,
            },
        );
    }
    return {
        iceServers,
        iceCandidatePoolSize: 10,
        bundlePolicy: 'max-bundle',
        rtcpMuxPolicy: 'require',
        ttl_sec: TURN_TTL_SEC,
        max_participants_per_room: MEET_MAX_PARTICIPANTS_PER_ROOM,
        turn_enabled: Boolean(TURN_SECRET),
    };
}

app.get('/api/ice-servers', (req, res) => {
    res.set('Cache-Control', 'no-store');
    res.json(buildIceServers());
});

/** Public Meet media config for the room UI */
app.get('/api/meet-config', (req, res) => {
    res.set('Cache-Control', 'no-store');
    res.json({
        media_backend: LIVEKIT_ENABLED && LIVEKIT_API_SECRET ? 'livekit' : 'mesh',
        livekit_url: LIVEKIT_WS_URL,
        livekit_enabled: LIVEKIT_ENABLED && !!LIVEKIT_API_SECRET,
        max_participants_per_room: MEET_MAX_PARTICIPANTS_PER_ROOM,
        turn_enabled: Boolean(TURN_SECRET),
    });
});

/**
 * Issue a LiveKit access token for a room participant.
 * Identity should match the Socket.IO user id so UI tiles stay in sync.
 */
app.post('/api/livekit-token', express.json(), async (req, res) => {
    try {
        if (!LIVEKIT_ENABLED || !LIVEKIT_API_SECRET) {
            return res.status(503).json({ error: 'LiveKit is not enabled on this server' });
        }
        const roomId = String(req.body.roomId || req.body.room_id || '').trim();
        const identity = String(req.body.identity || req.body.userId || '').trim();
        const name = String(req.body.name || identity || 'Guest').slice(0, 64);
        const isHost = !!req.body.isHost || !!req.body.is_host;
        const hostToken = String(req.body.host_token || req.body.hostToken || '');

        if (!roomId || !identity) {
            return res.status(400).json({ error: 'roomId and identity are required' });
        }
        if (isHost && !isValidHostToken(roomId, hostToken)) {
            return res.status(403).json({ error: 'Invalid host token' });
        }

        const at = new AccessToken(LIVEKIT_API_KEY, LIVEKIT_API_SECRET, {
            identity,
            name,
            ttl: '6h',
            metadata: JSON.stringify({ role: isHost ? 'host' : 'participant', roomId }),
        });
        at.addGrant({
            roomJoin: true,
            room: roomId,
            canPublish: true,
            canSubscribe: true,
            canPublishData: true,
            canUpdateOwnMetadata: true,
        });
        const token = await at.toJwt();
        res.json({
            token,
            url: LIVEKIT_WS_URL,
            room: roomId,
            identity,
            media_backend: 'livekit',
        });
    } catch (e) {
        console.error('livekit-token error:', e);
        res.status(500).json({ error: 'Failed to create LiveKit token' });
    }
});

function toBool(value, fallback = false) {
    if (value === undefined || value === null) return fallback;
    if (typeof value === 'boolean') return value;
    if (typeof value === 'number') return value !== 0;
    return ['true', '1', 'yes', 'on'].includes(String(value).toLowerCase());
}

app.get('/api/rooms/:roomId/status', async (req, res) => {
    const roomId = req.params.roomId;
    const settings = await getRoomSettings(roomId);
    res.json({
        ...roomStatusPayload(roomId, settings),
        host_count: roomHosts.get(roomId)?.size || 0,
    });
});

function meetingExtrasFromBody(body) {
    const extra = {};
    if (Object.prototype.hasOwnProperty.call(body, 'title')) extra.title = body.title ? String(body.title).slice(0, 255) : null;
    if (Object.prototype.hasOwnProperty.call(body, 'scheduled_at')) {
        const d = body.scheduled_at ? new Date(body.scheduled_at) : null;
        extra.scheduled_at = d && !isNaN(d.getTime()) ? d : null;
    }
    if (Object.prototype.hasOwnProperty.call(body, 'waiting_room')) extra.waiting_room = toBool(body.waiting_room, true) ? 1 : 0;
    if (Object.prototype.hasOwnProperty.call(body, 'locked')) extra.locked = toBool(body.locked, false) ? 1 : 0;
    if (Object.prototype.hasOwnProperty.call(body, 'recording_enabled')) extra.recording_enabled = toBool(body.recording_enabled, false) ? 1 : 0;
    if (Object.prototype.hasOwnProperty.call(body, 'brand_name')) extra.brand_name = body.brand_name ? String(body.brand_name).slice(0, 255) : null;
    if (Object.prototype.hasOwnProperty.call(body, 'logo_url')) extra.logo_url = body.logo_url ? String(body.logo_url).slice(0, 500) : null;
    if (Object.prototype.hasOwnProperty.call(body, 'primary_color')) extra.primary_color = body.primary_color ? String(body.primary_color).slice(0, 16) : null;
    if (Object.prototype.hasOwnProperty.call(body, 'passcode')) {
        if (body.passcode) {
            extra.passcode_hash = hashPasscode(body.passcode);
            extra.passcode_required = 1;
        } else {
            extra.passcode_hash = null;
            extra.passcode_required = 0;
        }
    }
    return extra;
}

async function maybeStartEgress(roomId, orgName, recordingEnabled) {
    if (!recordingEnabled) return;
    try {
        const orgPath = path.join(mntDir, orgName);
        if (!fs.existsSync(orgPath)) fs.mkdirSync(orgPath, { recursive: true });
        const fileOutput = new EncodedFileOutput({
            filepath: path.join(orgPath, `${roomId}.mp4`),
            fileType: EncodedFileType.MP4,
        });
        await egressClient.startRoomCompositeEgress(roomId, { file: fileOutput });
        console.log(`Egress started for room ${roomId} (Org: ${orgName})`);
    } catch (e) {
        console.error('Egress start failed. Ensure LiveKit Egress is running.', e.message || e);
    }
}

app.post('/api/create-meeting', requireApiSecret, async (req, res) => {
    try {
        const body = req.body || {};
        const { organization_id, organization_name } = body;
        if (!organization_name) {
            return res.status(400).json({ error: 'organization_name is required' });
        }

        const roomId = uuidv4();
        const orgName = String(organization_name).replace(/[^a-zA-Z0-9_-]/g, '_');
        const hostToken = hostTokenForRoom(roomId);
        const extra = meetingExtrasFromBody(body);
        if (!Object.prototype.hasOwnProperty.call(extra, 'waiting_room')) extra.waiting_room = 1;
        if (!Object.prototype.hasOwnProperty.call(extra, 'locked')) extra.locked = 0;
        if (!Object.prototype.hasOwnProperty.call(extra, 'recording_enabled')) {
            extra.recording_enabled = PLATFORM_RECORDING_ENABLED ? 1 : 0;
        }
        const recordingEnabled = !!extra.recording_enabled;

        await markPlatformRoom(roomId, orgName, extra);
        await maybeStartEgress(roomId, orgName, recordingEnabled);

        const settings = await getRoomSettings(roomId);
        const joinUrl = `${MEET_PUBLIC_URL}/room/${roomId}`;
        const startUrl = `${joinUrl}?role=host&host_token=${encodeURIComponent(hostToken)}`;

        res.json({
            success: true,
            roomId,
            id: roomId,
            organization: orgName,
            organization_id: organization_id || null,
            join_url: joinUrl,
            start_url: startUrl,
            host_token: hostToken,
            passcode: body.passcode || null,
            passcode_required: !!(settings && settings.passcode_required),
            recording_enabled: recordingEnabled,
            waiting_room: !!(settings && settings.waiting_room),
            locked: !!(settings && settings.locked),
            scheduled_at: settings ? settings.scheduled_at : null,
            title: settings ? settings.title : null,
            contact_guard: true,
            branding: brandingFor(settings),
            message: 'Meeting created.',
        });
    } catch (error) {
        console.error('Error creating meeting:', error);
        res.status(500).json({ error: 'Internal server error' });
    }
});

/** Mark an existing room as website/platform-managed (contact guard on) */
app.post('/api/mark-platform-room', requireApiSecret, async (req, res) => {
    try {
        const roomId = String(req.body.room_id || req.body.roomId || '').trim();
        if (!roomId) return res.status(400).json({ error: 'room_id is required' });
        const orgName = String(req.body.organization_name || req.body.organization || 'younimatiq')
            .replace(/[^a-zA-Z0-9_-]/g, '_');
        const extra = meetingExtrasFromBody(req.body || {});
        await markPlatformRoom(roomId, orgName, extra);
        const settings = await getRoomSettings(roomId);
        res.json({
            success: true,
            roomId,
            contact_guard: true,
            host_token: hostTokenForRoom(roomId),
            recording_enabled: !!(settings && settings.recording_enabled),
            waiting_room: !!(settings && settings.waiting_room),
            locked: !!(settings && settings.locked),
            passcode_required: !!(settings && settings.passcode_required),
            branding: brandingFor(settings),
        });
    } catch (error) {
        console.error('Error marking platform room:', error);
        res.status(500).json({ error: 'Internal server error' });
    }
});

app.get('/api/meetings/:roomId', requireApiSecret, async (req, res) => {
    const settings = await getRoomSettings(req.params.roomId);
    if (!settings) return res.status(404).json({ error: 'Not found' });
    res.json({
        success: true,
        meeting: {
            room_id: settings.room_id,
            organization: settings.organization,
            title: settings.title,
            scheduled_at: settings.scheduled_at,
            contact_guard: settings.contact_guard,
            passcode_required: settings.passcode_required,
            waiting_room: settings.waiting_room,
            locked: settings.locked,
            recording_enabled: settings.recording_enabled,
            branding: brandingFor(settings),
            host_present: hostPresent(settings.room_id),
            join_url: `${MEET_PUBLIC_URL}/room/${settings.room_id}`,
            start_url: `${MEET_PUBLIC_URL}/room/${settings.room_id}?role=host&host_token=${encodeURIComponent(hostTokenForRoom(settings.room_id))}`,
        },
    });
});

app.get('/api/meetings', requireApiSecret, async (req, res) => {
    try {
        const organization = req.query.organization;
        const organizationId = req.query.organization_id;
        const limit = Math.min(parseInt(req.query.limit || '100', 10) || 100, 500);
        let sql = 'SELECT * FROM platform_rooms WHERE 1=1';
        const params = [];
        if (organization) { sql += ' AND organization = ?'; params.push(organization); }
        sql += ' ORDER BY created_at DESC LIMIT ?';
        params.push(limit);
        const [rows] = await db.query(sql, params);
        res.json({
            success: true,
            organization_id: organizationId || null,
            meetings: rows.map((row) => ({
                room_id: row.room_id,
                organization: row.organization,
                title: row.title,
                scheduled_at: row.scheduled_at,
                contact_guard: !!row.contact_guard,
                passcode_required: !!row.passcode_required,
                waiting_room: !!row.waiting_room,
                locked: !!row.locked,
                recording_enabled: !!row.recording_enabled,
                branding: brandingFor(normalizeSettings(row)),
                host_present: hostPresent(row.room_id),
                created_at: row.created_at,
                join_url: `${MEET_PUBLIC_URL}/room/${row.room_id}`,
            })),
        });
    } catch (error) {
        console.error('Error listing meetings:', error);
        res.status(500).json({ error: 'Internal server error' });
    }
});

app.patch('/api/meetings/:roomId', requireApiSecret, async (req, res) => {
    try {
        const roomId = req.params.roomId;
        const existing = await getRoomSettings(roomId);
        if (!existing) return res.status(404).json({ error: 'Not found' });
        const extra = meetingExtrasFromBody(req.body || {});
        const settings = await updateRoomSettings(roomId, extra);
        if (Object.prototype.hasOwnProperty.call(extra, 'locked')) {
            io.to(roomId).emit('room-lock-status', { locked: !!extra.locked });
        }
        res.json({ success: true, meeting: settings });
    } catch (error) {
        console.error('Error updating meeting:', error);
        res.status(500).json({ error: 'Internal server error' });
    }
});

app.delete('/api/meetings/:roomId', requireApiSecret, async (req, res) => {
    try {
        const roomId = req.params.roomId;
        await db.query('DELETE FROM platform_rooms WHERE room_id = ?', [roomId]);
        roomSettings.delete(roomId);
        platformRooms.delete(roomId);
        waitingRooms.delete(roomId);
        res.json({ success: true });
    } catch (error) {
        console.error('Error deleting meeting:', error);
        res.status(500).json({ error: 'Internal server error' });
    }
});

app.get('/api/attendance', requireApiSecret, async (req, res) => {
    try {
        const roomId = req.query.room_id;
        const organization = req.query.organization;
        const limit = Math.min(parseInt(req.query.limit || '200', 10) || 200, 1000);
        let sql = 'SELECT * FROM attendance WHERE 1=1';
        const params = [];
        if (roomId) { sql += ' AND room_id = ?'; params.push(roomId); }
        if (organization) { sql += ' AND organization = ?'; params.push(organization); }
        sql += ' ORDER BY joined_at DESC LIMIT ?';
        params.push(limit);
        const [rows] = await db.query(sql, params);
        res.json({ success: true, attendance: rows });
    } catch (error) {
        console.error('Error listing attendance:', error);
        res.status(500).json({ error: 'Internal server error' });
    }
});

app.get('/api/contact-alerts', requireApiSecret, async (req, res) => {
    try {
        const roomId = req.query.room_id;
        const organization = req.query.organization;
        const limit = Math.min(parseInt(req.query.limit || '200', 10) || 200, 1000);
        let sql = `SELECT a.*, p.organization AS organization
                   FROM contact_alerts a
                   LEFT JOIN platform_rooms p ON p.room_id = a.room_id
                   WHERE 1=1`;
        const params = [];
        if (roomId) { sql += ' AND a.room_id = ?'; params.push(roomId); }
        if (organization) { sql += ' AND p.organization = ?'; params.push(organization); }
        sql += ' ORDER BY a.occurred_at DESC LIMIT ?';
        params.push(limit);
        const [rows] = await db.query(sql, params);
        const alerts = rows.map((row) => ({
            ...row,
            matches: (() => { try { return JSON.parse(row.matches_json || '[]'); } catch (_) { return []; } })(),
        }));
        res.json({ success: true, alerts });
    } catch (error) {
        console.error('Error listing contact alerts:', error);
        res.status(500).json({ error: 'Internal server error' });
    }
});

app.post('/api/recordings/upload', upload.single('video'), async (req, res) => {
    try {
        if (!req.file) return res.status(400).json({ error: 'No file uploaded' });

        const roomId = req.body.roomId || 'unknown';
        const hostToken = req.body.host_token || req.header('X-Host-Token');
        const apiSecret = req.header('X-Meet-Secret');
        const authorized = (apiSecret && apiSecret === MEET_API_SECRET) || isValidHostToken(roomId, hostToken);
        if (!authorized) {
            try { fs.unlinkSync(req.file.path); } catch (_) {}
            return res.status(401).json({ error: 'Unauthorized upload' });
        }

        const organization = (req.body.organization || 'younimatiq').replace(/[^a-zA-Z0-9_-]/g, '_');
        const roomSetting = await getRoomSettings(roomId);
        if (roomSetting && !roomSetting.recording_enabled) {
            try { fs.unlinkSync(req.file.path); } catch (_) {}
            return res.status(403).json({ error: 'Recording is disabled for this meeting' });
        }
        const absolutePath = req.file.path;
        const relativePath = path.relative(mntDir, absolutePath).split(path.sep).join('/');

        const [result] = await db.query(
            'INSERT INTO recordings (room_id, organization, file_path, file_size) VALUES (?, ?, ?, ?)',
            [roomId, organization, relativePath, req.file.size || null]
        );

        res.json({
            success: true,
            id: result.insertId,
            room_id: roomId,
            organization,
            path: relativePath,
            url: `${MEET_PUBLIC_URL}/api/recordings/${result.insertId}/file`,
        });
    } catch (error) {
        console.error('Error saving recording to DB:', error);
        res.status(500).json({ error: 'Internal server error' });
    }
});

app.get('/api/recordings', requireApiSecret, async (req, res) => {
    try {
        const roomId = req.query.room_id;
        const organization = req.query.organization;
        let sql = 'SELECT id, room_id, organization, file_path, file_size, created_at FROM recordings WHERE 1=1';
        const params = [];
        if (roomId) { sql += ' AND room_id = ?'; params.push(roomId); }
        if (organization) { sql += ' AND organization = ?'; params.push(organization); }
        sql += ' ORDER BY created_at DESC LIMIT 200';

        const [rows] = await db.query(sql, params);
        const recordings = rows.map((r) => ({
            id: r.id,
            room_id: r.room_id,
            organization: r.organization,
            file_path: r.file_path,
            file_size: r.file_size,
            created_at: r.created_at,
            url: `${MEET_PUBLIC_URL}/api/recordings/${r.id}/file`,
        }));
        res.json({ success: true, recordings });
    } catch (error) {
        console.error('Error listing recordings:', error);
        res.status(500).json({ error: 'Internal server error' });
    }
});

app.get('/api/recordings/:id/file', requireApiSecret, async (req, res) => {
    try {
        const [rows] = await db.query('SELECT * FROM recordings WHERE id = ? LIMIT 1', [req.params.id]);
        if (!rows.length) return res.status(404).json({ error: 'Not found' });

        const row = rows[0];
        let filePath = row.file_path;
        if (!path.isAbsolute(filePath)) filePath = path.join(mntDir, filePath);
        if (!fs.existsSync(filePath) && row.file_path.startsWith('/uploads/')) {
            filePath = path.join(__dirname, row.file_path);
        }
        if (!fs.existsSync(filePath)) return res.status(404).json({ error: 'File missing on disk' });

        res.setHeader('Content-Type', 'video/webm');
        res.sendFile(path.resolve(filePath));
    } catch (error) {
        console.error('Error serving recording:', error);
        res.status(500).json({ error: 'Internal server error' });
    }
});

app.delete('/api/recordings/:id', requireApiSecret, async (req, res) => {
    try {
        const [rows] = await db.query('SELECT * FROM recordings WHERE id = ? LIMIT 1', [req.params.id]);
        if (!rows.length) return res.status(404).json({ error: 'Not found' });

        const row = rows[0];
        let filePath = row.file_path;
        if (!path.isAbsolute(filePath)) filePath = path.join(mntDir, filePath);
        if (!fs.existsSync(filePath) && row.file_path && row.file_path.startsWith('/uploads/')) {
            filePath = path.join(__dirname, row.file_path);
        }
        if (filePath && fs.existsSync(filePath)) {
            try { fs.unlinkSync(filePath); } catch (e) { console.warn('Could not delete recording file:', e.message); }
        }
        await db.query('DELETE FROM recordings WHERE id = ?', [req.params.id]);
        res.json({ success: true });
    } catch (error) {
        console.error('Error deleting recording:', error);
        res.status(500).json({ error: 'Internal server error' });
    }
});

/** Block emails & phone numbers (any country) in class chat for students and hosts */
function containsContactPii(text) {
    return extractContactMatches(text).length > 0;
}

function extractContactMatches(text) {
    if (!text || typeof text !== 'string') return [];
    const matches = [];
    const emailRe = /[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/g;
    let m;
    const seen = new Set();
    while ((m = emailRe.exec(text)) !== null) {
        const v = m[0];
        if (!seen.has('email:' + v)) {
            seen.add('email:' + v);
            matches.push({ type: 'email', value: v });
        }
    }
    const phoneRe = /(?:\+|00)?\d[\d\s().-]{7,18}\d/g;
    while ((m = phoneRe.exec(text)) !== null) {
        const v = m[0].trim();
        const digits = v.replace(/\D/g, '');
        if (digits.length >= 8 && digits.length <= 15) {
            const key = 'phone:' + digits;
            if (!seen.has(key)) {
                seen.add(key);
                matches.push({ type: 'phone', value: v });
            }
        }
    }
    const linkRe = /(?:https?:\/\/)?(?:wa\.me\/[^\s]+|t\.me\/[^\s]+)|\b(?:whatsapp|telegram)\b/gi;
    while ((m = linkRe.exec(text)) !== null) {
        const v = m[0].trim();
        const key = 'link:' + v.toLowerCase();
        if (!seen.has(key)) {
            seen.add(key);
            matches.push({ type: 'messaging_link', value: v });
        }
    }
    return matches;
}

function maskContactPii(text) {
    let out = String(text);
    out = out.replace(/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/g, '[email blocked]');
    out = out.replace(/(?:\+|00)?\d[\d\s().-]{7,18}\d/g, '[phone blocked]');
    out = out.replace(/(?:https?:\/\/)?(?:wa\.me\/[^\s]+|t\.me\/[^\s]+)/gi, '[link blocked]');
    out = out.replace(/\b(?:whatsapp|telegram)\b/gi, '[link blocked]');
    return out;
}

function buildRoomStatus(roomId, extra = {}) {
    const guarded = platformRooms.has(roomId)
        ? !!platformRooms.get(roomId).contact_guard
        : null;
    const status = {
        roomId,
        host_present: hostPresent(roomId),
        captions_wanted: captionsWantedInRoom(roomId),
        ...extra,
    };
    // Prefer sync cache; async callers should pass contact_guard explicitly when known
    if (Object.prototype.hasOwnProperty.call(extra, 'contact_guard')) {
        status.platform_managed = !!extra.contact_guard;
    } else if (guarded !== null) {
        status.contact_guard = guarded;
        status.platform_managed = guarded;
    }
    return status;
}

const recentAlertKeys = new Map();

async function reportContactAlert({ roomId, source, actorName, actorRole, sharedText, matches }) {
    const list = matches && matches.length ? matches : extractContactMatches(sharedText);
    if (!list.length) return;
    const fingerprint = [roomId, source, actorName || '', list.map((x) => x.type + ':' + x.value).sort().join('|')].join('::');
    const now = Date.now();
    const prev = recentAlertKeys.get(fingerprint);
    if (prev && now - prev < 120000) return;
    recentAlertKeys.set(fingerprint, now);
    if (recentAlertKeys.size > 500) {
        for (const [k, t] of recentAlertKeys) {
            if (now - t > 120000) recentAlertKeys.delete(k);
        }
    }

    const occurredAt = new Date().toISOString();
    const primaryType = list[0].type;
    try {
        await db.query(
            'INSERT INTO contact_alerts (room_id, source, actor_name, actor_role, shared_text, matches_json, primary_type, occurred_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [roomId, source, actorName || null, actorRole || null, sharedText, JSON.stringify(list), primaryType, new Date()]
        );
    } catch (e) {
        console.warn('Could not persist contact alert locally:', e.message);
    }

    if (!LARAVEL_CONTACT_ALERT_URL) return;
    const roomSettingsForAlert = await getRoomSettings(roomId);
    try {
        const res = await fetch(LARAVEL_CONTACT_ALERT_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Meet-Secret': MEET_API_SECRET,
            },
            body: JSON.stringify({
                room_id: roomId,
                organization: roomSettingsForAlert ? roomSettingsForAlert.organization : null,
                source,
                actor_name: actorName || null,
                actor_role: actorRole || 'unknown',
                shared_text: sharedText,
                matches: list,
                occurred_at: occurredAt,
            }),
        });
        if (!res.ok) {
            console.warn('Laravel contact alert webhook failed:', res.status, await res.text().catch(() => ''));
        }
    } catch (e) {
        console.warn('Laravel contact alert webhook error:', e.message);
    }
}

app.get('/room/:room', (req, res) => {
    res.setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, proxy-revalidate');
    res.setHeader('Pragma', 'no-cache');
    res.setHeader('Expires', '0');
    res.sendFile(path.join(__dirname, 'public', 'room.html'));
});

io.on('connection', (socket) => {
    console.log('User connected:', socket.id);
    socket.userIds = [];
    socket.isHost = false;

    socket.on('lobby-wait', async (payload) => {
        const roomId = typeof payload === 'string' ? payload : (payload && (payload.roomId || payload.room_id));
        if (!roomId) return;
        const passcode = typeof payload === 'object' && payload ? payload.passcode : undefined;
        socket.roomId = roomId;
        socket.inLobby = true;
        socket.userId = (payload && payload.userId) || socket.userId || null;
        socket.userName = (payload && payload.name) || socket.userName || null;
        const settings = await getRoomSettings(roomId);
        socket.contactGuard = !!(settings && settings.contact_guard) || await isPlatformManagedRoom(roomId);

        if (settings && settings.locked) {
            socket.emit('room-locked', { locked: true });
            socket.emit('room-status', roomStatusPayload(roomId, settings));
            return;
        }
        if (!(await verifyRoomPasscode(roomId, passcode))) {
            socket.emit('passcode-required', { message: 'A valid passcode is required' });
            return;
        }

        socket.join(roomId);
        if (settings && settings.waiting_room) {
            addWaitingEntry(roomId, socket, socket.userName);
            socket.emit('waiting-pending', { roomId, name: socket.userName || 'Guest' });
        }
        socket.emit('room-status', roomStatusPayload(roomId, settings));
    });

    socket.on('join-room', async (roomId, userId, userName, isHost, isCamOn, isMicOn, hostToken, passcode) => {
        const settings = await getRoomSettings(roomId);
        socket.contactGuard = !!(settings && settings.contact_guard) || await isPlatformManagedRoom(roomId);
        const verifiedHost = !!isHost && isValidHostToken(roomId, hostToken);

        if (!verifiedHost) {
            if (settings && settings.locked) {
                socket.emit('room-locked', { locked: true });
                return;
            }
            if (!(await verifyRoomPasscode(roomId, passcode))) {
                socket.emit('passcode-required', { message: 'Invalid passcode' });
                return;
            }
            if (settings && settings.waiting_room && !socket.approved) {
                socket.roomId = roomId;
                socket.inLobby = true;
                socket.userId = userId || socket.userId;
                socket.userName = userName || socket.userName;
                socket.join(roomId);
                addWaitingEntry(roomId, socket, userName);
                socket.emit('waiting-pending', { roomId, name: userName || 'Guest' });
                return;
            }
        }

        // Soft cap: mesh WebRTC quality collapses past a few peers. Many rooms can still run in parallel.
        const roomSet = io.sockets.adapter.rooms.get(roomId);
        const liveCount = roomSet ? roomSet.size : 0;
        if (!verifiedHost && liveCount >= MEET_MAX_PARTICIPANTS_PER_ROOM) {
            socket.emit('toast', `This room is full (${MEET_MAX_PARTICIPANTS_PER_ROOM} participants). Start another class room for more students.`);
            socket.emit('room-locked', { locked: true, reason: 'capacity' });
            return;
        }

        socket.roomId = roomId;
        socket.inLobby = false;
        socket.userId = userId || socket.userId;
        socket.userName = userName || socket.userName;
        socket.approved = false;
        socket.join(roomId);
        socket.join(userId);

        if (!socket.userIds.includes(userId)) socket.userIds.push(userId);

        socket.isHost = verifiedHost;
        recordAttendanceJoin(socket, roomId, userId, userName, verifiedHost ? 'tutor' : 'student', settings ? settings.organization : null);

        if (verifiedHost) {
            if (!roomHosts.has(roomId)) roomHosts.set(roomId, new Set());
            roomHosts.get(roomId).add(socket.id);
            socket.to(roomId).emit('host-joined', { roomId, userId, userName });
            io.to(roomId).emit('room-status', roomStatusPayload(roomId, settings, { host_present: true }));
            emitWaitingList(roomId);
        } else {
            if (isHost && !verifiedHost) {
                socket.emit('host-denied', { message: 'Invalid host token' });
            }
            socket.emit('room-status', roomStatusPayload(roomId, settings));
        }

        socket.to(roomId).emit('user-connected', userId, userName, verifiedHost, isCamOn, isMicOn);
    });

    socket.on('set-lock', async (payload) => {
        if (!socket.roomId || !socket.isHost) return;
        const locked = !!(payload && (payload.locked ?? payload.lock));
        const settings = await updateRoomSettings(socket.roomId, { locked: locked ? 1 : 0 });
        io.to(socket.roomId).emit('room-lock-status', { locked });
        io.to(socket.roomId).emit('room-status', roomStatusPayload(socket.roomId, settings));
        socket.emit('toast', locked ? 'Class locked — new students cannot join.' : 'Class unlocked.');
    });

    socket.on('waiting-list-request', () => {
        if (!socket.roomId || !socket.isHost) return;
        socket.emit('waiting-list', waitingListFor(socket.roomId));
    });

    socket.on('waiting-approve', (payload) => {
        if (!socket.roomId || !socket.isHost) return;
        const socketId = typeof payload === 'string' ? payload : (payload && payload.socketId);
        const map = waitingRooms.get(socket.roomId);
        if (!map) return;
        const entry = map.get(socketId);
        if (!entry) return;
        map.delete(socketId);
        if (map.size === 0) waitingRooms.delete(socket.roomId);
        const target = io.sockets.sockets.get(socketId);
        if (target) {
            target.approved = true;
            target.inLobby = false;
            io.to(socketId).emit('waiting-approved', { roomId: socket.roomId });
        }
        emitWaitingList(socket.roomId);
    });

    socket.on('waiting-deny', (payload) => {
        if (!socket.roomId || !socket.isHost) return;
        const socketId = typeof payload === 'string' ? payload : (payload && payload.socketId);
        const map = waitingRooms.get(socket.roomId);
        if (!map) return;
        const entry = map.get(socketId);
        if (!entry) return;
        map.delete(socketId);
        if (map.size === 0) waitingRooms.delete(socket.roomId);
        io.to(socketId).emit('waiting-denied', { roomId: socket.roomId });
        emitWaitingList(socket.roomId);
    });

    socket.on('waiting-admit-all', () => {
        if (!socket.roomId || !socket.isHost) return;
        const map = waitingRooms.get(socket.roomId);
        if (!map) return;
        for (const [socketId] of map) {
            const target = io.sockets.sockets.get(socketId);
            if (target) {
                target.approved = true;
                target.inLobby = false;
                io.to(socketId).emit('waiting-approved', { roomId: socket.roomId });
            }
        }
        waitingRooms.delete(socket.roomId);
        emitWaitingList(socket.roomId);
    });

    socket.on('offer', (payload) => io.to(payload.target).emit('offer', payload));
    socket.on('answer', (payload) => io.to(payload.target).emit('answer', payload));
    socket.on('ice-candidate', (payload) => io.to(payload.target).emit('ice-candidate', payload));

    socket.on('toggle-video', (userId, isVideoOn) => {
        if (socket.roomId) socket.to(socket.roomId).emit('user-toggled-video', userId, isVideoOn);
    });
    socket.on('toggle-audio', (userId, isAudioOn) => {
        if (socket.roomId) socket.to(socket.roomId).emit('user-toggled-audio', userId, isAudioOn);
    });
    socket.on('screen-share', (userId, isSharing) => {
        if (socket.roomId) socket.to(socket.roomId).emit('user-screen-share', userId, isSharing);
    });
    socket.on('whiteboard-sync', (payload) => {
        const roomId = (payload && (payload.roomId || payload.room_id)) || socket.roomId;
        if (!roomId || !Array.isArray(payload && payload.objects)) return;
        // Soft cap scene size to keep sync lightweight
        const objects = payload.objects.slice(0, 800);
        whiteboardRooms.set(roomId, { objects, updatedAt: Date.now(), from: payload.from || socket.id });
        socket.to(roomId).emit('whiteboard-sync', {
            roomId,
            objects,
            from: payload.from || socket.id,
            name: payload.name || socket.userName || 'Guest',
        });
    });

    socket.on('whiteboard-request', (payload) => {
        const roomId = (payload && (payload.roomId || payload.room_id)) || socket.roomId;
        if (!roomId) return;
        const scene = whiteboardRooms.get(roomId) || { objects: [] };
        socket.emit('whiteboard-state', { roomId, objects: scene.objects || [] });
    });

    socket.on('chat-message', (message, senderName) => {
        if (!socket.roomId) return;
        const raw = typeof message === 'string' ? message : String(message || '');
        // Only website-created meetings block contact sharing
        if (socket.contactGuard && containsContactPii(raw)) {
            const matches = extractContactMatches(raw);
            reportContactAlert({
                roomId: socket.roomId,
                source: 'chat',
                actorName: senderName || 'Unknown',
                actorRole: socket.isHost ? 'tutor' : 'student',
                sharedText: raw,
                matches,
            });
            socket.emit('chat-message', '[Security] Emails and phone numbers are not allowed in class chat. An admin has been alerted.', 'System');
            socket.emit('chat-blocked', { reason: 'contact_info', matches });
            return;
        }
        socket.to(socket.roomId).emit('chat-message', raw, senderName);
    });

    socket.on('caption-preference', (payload) => {
        if (!socket.roomId) return;
        const enabled = !!(payload && payload.enabled);
        socket.wantsCaptions = enabled;
        setCaptionViewer(socket.roomId, socket.id, enabled);
        emitCaptionsDemand(socket.roomId);
        socket.emit('captions-demand', { active: captionsWantedInRoom(socket.roomId) });
    });

    socket.on('caption-final', (payload) => {
        if (!socket.roomId || !payload) return;
        const text = typeof payload.text === 'string' ? payload.text.trim() : '';
        if (!text) return;
        const senderName = payload.senderName || 'Participant';
        const userId = payload.userId || null;
        const someoneWantsCaptions = captionsWantedInRoom(socket.roomId) || !!payload.shareCaptions;

        if (socket.contactGuard && containsContactPii(text)) {
            const matches = extractContactMatches(text);
            reportContactAlert({
                roomId: socket.roomId,
                source: someoneWantsCaptions ? 'captions' : 'speech',
                actorName: senderName,
                actorRole: socket.isHost ? 'tutor' : 'student',
                sharedText: text,
                matches,
            });
            const masked = maskContactPii(text);
            if (someoneWantsCaptions) {
                socket.to(socket.roomId).emit('caption', {
                    userId,
                    senderName,
                    text: masked,
                    final: true,
                    blocked: true,
                });
            }
            socket.emit('speech-blocked', { reason: 'contact_info', matches });
            return;
        }

        if (someoneWantsCaptions) {
            socket.to(socket.roomId).emit('caption', {
                userId,
                senderName,
                text,
                final: true,
                blocked: false,
            });
        }
    });

    socket.on('caption-interim', (payload) => {
        if (!socket.roomId || !payload) return;
        if (!captionsWantedInRoom(socket.roomId) && !payload.shareCaptions) return;
        const text = typeof payload.text === 'string' ? payload.text.trim() : '';
        if (!text) return;
        let display = text;
        if (socket.contactGuard && containsContactPii(text)) {
            display = maskContactPii(text);
        }
        socket.to(socket.roomId).emit('caption', {
            userId: payload.userId || null,
            senderName: payload.senderName || 'Participant',
            text: display,
            final: false,
            blocked: false,
        });
    });

    socket.on('host-action', (payload) => {
        if (!socket.roomId || !socket.isHost) return;
        if (payload.target === 'all') socket.to(socket.roomId).emit('host-action', payload.action);
        else io.to(payload.target).emit('host-action', payload.action);
    });
    socket.on('hand-raised', (userId, isRaised) => {
        if (socket.roomId) socket.to(socket.roomId).emit('hand-raised', userId, isRaised);
    });

    socket.on('disconnect', async () => {
        console.log('User disconnected:', socket.id);
        if (socket.roomId) removeWaitingEntry(socket.roomId, socket.id);
        recordAttendanceLeave(socket);
        if (socket.roomId && socket.wantsCaptions) {
            setCaptionViewer(socket.roomId, socket.id, false);
            emitCaptionsDemand(socket.roomId);
        }
        if (socket.roomId && socket.userIds) {
            socket.userIds.forEach((uid) => socket.to(socket.roomId).emit('user-disconnected', uid));
        }
        if (socket.roomId && socket.isHost) {
            const hosts = roomHosts.get(socket.roomId);
            if (hosts) {
                hosts.delete(socket.id);
                if (hosts.size === 0) {
                    roomHosts.delete(socket.roomId);
                    io.to(socket.roomId).emit('host-left', { roomId: socket.roomId });
                    const settings = await getRoomSettings(socket.roomId);
                    io.to(socket.roomId).emit('room-status', roomStatusPayload(socket.roomId, settings, { host_present: false }));
                }
            }
        }
    });
});

app.use((err, req, res, next) => {
    console.error('Request error:', err.message || err);
    if (res.headersSent) return next(err);
    const status = err.code === 'LIMIT_FILE_SIZE' ? 413 : 400;
    res.status(status).json({ error: 'Upload failed', detail: err.message || 'Bad request' });
});

http.listen(PORT, () => {
    console.log(`Server is running on port ${PORT}`);
});
