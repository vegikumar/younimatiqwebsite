// ===== INIT =====
const params = new URLSearchParams(window.location.search);
let MY_NAME = params.get('name') || localStorage.getItem('vjmeet-name') || '';
const ROOM_ID = window.location.pathname.split('/').pop();
const MY_ID = Math.random().toString(36).slice(2);
const HOST_TOKEN = params.get('host_token') || localStorage.getItem('vjmeet-host-token-' + ROOM_ID) || '';
if (params.get('host_token')) {
  localStorage.setItem('vjmeet-host-token-' + ROOM_ID, params.get('host_token'));
}
if (params.get('role') === 'host' || localStorage.getItem('vjmeet-host-' + ROOM_ID) === 'true') {
  if (HOST_TOKEN) {
    localStorage.setItem('vjmeet-host-' + ROOM_ID, 'true');
    localStorage.setItem('vjmeet-host-token-' + ROOM_ID, HOST_TOKEN);
  }
}
const amIHost = localStorage.getItem('vjmeet-host-' + ROOM_ID) === 'true' && !!localStorage.getItem('vjmeet-host-token-' + ROOM_ID);
let hostPresent = amIHost;
/** true only for meetings created from the website (Younimatiq) API */
let contactGuardEnabled = false;
let recordingEnabled = true;
let waitingRoomEnabled = false;
let roomLocked = false;
let passcodeRequired = false;
let roomPasscode = '';
let roomBranding = null;
let roomTitle = null;
let captionsEnabled = false;
let captionsDemanded = false;
let speechRecognition = null;
let speechWatchActive = false;
let speechRestartTimer = null;
let lastCaptionHideTimer = null;
// Strip role / host_token from the address bar after elevating host
try {
  const cleanRoleUrl = new URL(window.location.href);
  let dirty = false;
  ['role', 'host_token', 'name'].forEach((k) => {
    if (cleanRoleUrl.searchParams.has(k)) { cleanRoleUrl.searchParams.delete(k); dirty = true; }
  });
  if (dirty) window.history.replaceState({}, document.title, cleanRoleUrl.toString());
} catch (_) {}

// Populate name input if available
const joinNameInput = document.getElementById('join-name-input');
if (joinNameInput && MY_NAME) joinNameInput.value = MY_NAME;

const socket = io({
  transports: ['websocket', 'polling'],
  upgrade: true,
  rememberUpgrade: true,
  reconnection: true,
  reconnectionAttempts: 12,
  timeout: 20000,
});

// Self-hosted coturn ICE config (loaded before media). Fallback STUN only until fetch succeeds.
let rtcConfig = {
  iceServers: [
    { urls: ['stun:stun.l.google.com:19302', 'stun:stun1.l.google.com:19302'] },
    { urls: 'stun:younimatiq.com:3478' },
  ],
  iceCandidatePoolSize: 10,
  bundlePolicy: 'max-bundle',
  rtcpMuxPolicy: 'require',
};
let iceReady = null;

const MEDIA_VIDEO = {
  facingMode: undefined, // filled at call time
  width: { ideal: 1280, max: 1920 },
  height: { ideal: 720, max: 1080 },
  frameRate: { ideal: 30, max: 30 },
};
const MEDIA_AUDIO = {
  echoCancellation: true,
  noiseSuppression: true,
  autoGainControl: true,
  sampleRate: 48000,
  channelCount: 1,
};
/** Target encode bitrates for tutoring quality without melting uplink */
const VIDEO_MAX_BITRATE = 1_500_000; // 1.5 Mbps camera
const SCREEN_MAX_BITRATE = 2_500_000; // 2.5 Mbps screen share
const AUDIO_MAX_BITRATE = 64_000; // 64 kbps Opus

function videoConstraints(extra = {}) {
  return {
    facingMode: currentFacingMode,
    width: MEDIA_VIDEO.width,
    height: MEDIA_VIDEO.height,
    frameRate: MEDIA_VIDEO.frameRate,
    ...extra,
  };
}

async function loadIceServers() {
  try {
    const res = await fetch('/api/ice-servers', { cache: 'no-store' });
    if (!res.ok) throw new Error('ice http ' + res.status);
    const data = await res.json();
    if (data && Array.isArray(data.iceServers) && data.iceServers.length) {
      rtcConfig = {
        iceServers: data.iceServers,
        iceCandidatePoolSize: data.iceCandidatePoolSize || 10,
        bundlePolicy: data.bundlePolicy || 'max-bundle',
        rtcpMuxPolicy: data.rtcpMuxPolicy || 'require',
      };
    }
  } catch (e) {
    console.warn('ICE config fetch failed; using STUN fallback', e);
  }
}

iceReady = loadIceServers();
// Refresh TURN credentials before they expire (default TTL 6h → refresh every 4h)
setInterval(() => { iceReady = loadIceServers(); }, 4 * 60 * 60 * 1000);

/** 'livekit' (SFU) or 'mesh' (legacy P2P). Loaded from /api/meet-config. */
let mediaBackend = 'livekit';
let livekitUrl = 'wss://younimatiq.com';
let meetConfigReady = (async () => {
  try {
    const res = await fetch('/api/meet-config', { cache: 'no-store' });
    if (!res.ok) return;
    const cfg = await res.json();
    if (cfg.media_backend === 'mesh' || cfg.livekit_enabled === false) mediaBackend = 'mesh';
    else mediaBackend = 'livekit';
    if (cfg.livekit_url) livekitUrl = cfg.livekit_url;
  } catch (e) {
    console.warn('meet-config fetch failed; defaulting to livekit', e);
  }
})();

function usingLiveKit() {
  return mediaBackend === 'livekit' && window.YouniLiveKit;
}

async function connectLiveKitMedia() {
  if (!usingLiveKit()) return;
  try {
    await window.YouniLiveKit.connect({
      roomId: ROOM_ID,
      identity: MY_ID,
      name: MY_NAME || 'Guest',
      isHost: amIHost,
      hostToken: HOST_TOKEN || localStorage.getItem('vjmeet-host-token-' + ROOM_ID) || '',
    });
  } catch (e) {
    console.error('LiveKit connect failed', e);
    showToast('HD media failed — retrying mesh fallback');
    mediaBackend = 'mesh';
  }
}

async function applySenderBitrates(peer, { screen = false } = {}) {
  try {
    const senders = peer.getSenders();
    for (const sender of senders) {
      if (!sender.track) continue;
      const params = sender.getParameters();
      if (!params.encodings || !params.encodings.length) {
        params.encodings = [{}];
      }
      if (sender.track.kind === 'video') {
        params.encodings[0].maxBitrate = screen ? SCREEN_MAX_BITRATE : VIDEO_MAX_BITRATE;
        params.encodings[0].maxFramerate = screen ? 15 : 30;
        // Prefer resolution over frame rate when bandwidth drops
        params.degradationPreference = screen ? 'maintain-resolution' : 'balanced';
      } else if (sender.track.kind === 'audio') {
        params.encodings[0].maxBitrate = AUDIO_MAX_BITRATE;
      }
      await sender.setParameters(params);
    }
  } catch (e) {
    console.warn('setParameters bitrate failed', e);
  }
}

// ===== STATE =====
let localStream = null;
let screenStream = null;
let mediaRecorder = null;
let recordedChunks = [];
let isRecording = false;
let isMicOn = true;
let isCamOn = true;
let isScreenSharing = false;
let isHandRaised = false;
let panelOpen = null; // 'chat' | 'participants' | null
let layout = 'grid'; // 'grid' | 'spotlight'
let unreadCount = 0;
let timerInterval = null;
let startTime = Date.now();
let participants = {}; // id -> { name, stream, pc, isMuted, isCamOff }
let noiseFilter = null;
let audioCtx = null;
let blurEnabled = false;
let currentFacingMode = 'user';

const peers = {}; // peerId -> RTCPeerConnection
const iceQueues = {}; // peerId -> array of ICE candidates

// ===== DOM REFS =====
const videoGrid = document.getElementById('video-grid');
const sidePanel = document.getElementById('side-panel');
const micBtn = document.getElementById('mic-btn');
const cameraBtn = document.getElementById('camera-btn');
const flipCamBtn = document.getElementById('flip-cam-btn');
const screenBtn = document.getElementById('screen-btn');
const wbBtn = document.getElementById('wb-btn');
const recordBtn = document.getElementById('record-btn');
const handBtn = document.getElementById('hand-btn');
const leaveBtn = document.getElementById('leave-btn');
const chatBtn = document.getElementById('chat-btn');
const peopleBtn = document.getElementById('people-btn');
let youniWhiteboard = null;
if (window.YouniWhiteboard && typeof window.YouniWhiteboard.create === 'function') {
  youniWhiteboard = window.YouniWhiteboard.create({
    socket,
    roomId: ROOM_ID,
    myId: MY_ID,
    myName: MY_NAME || 'Guest',
  });
}
if (wbBtn && youniWhiteboard) {
  wbBtn.addEventListener('click', () => {
    const open = youniWhiteboard.toggle();
    wbBtn.classList.toggle('active', !!open);
    if (typeof showToast === 'function') {
      showToast(open ? 'Whiteboard open — everyone in the room can draw' : 'Back to class video');
    }
  });
}
const layoutBtn = document.getElementById('layout-btn');
const chatInput = document.getElementById('chat-input');
const sendBtn = document.getElementById('send-btn');
const chatMessages = document.getElementById('chat-messages');
const participantsList = document.getElementById('participants-list');
const participantCount = document.getElementById('participant-count');
const callTimer = document.getElementById('call-timer');
const cInfoTime = document.getElementById('cinfo-time');
const recIndicator = document.getElementById('rec-indicator');
const leaveModal = document.getElementById('leave-modal');
const toast = document.getElementById('toast');
const infoPanel = document.getElementById('info-panel');
const meetingTitle = document.getElementById('meeting-title');

// Chat Popup Refs
const chatPopup = document.getElementById('chat-popup');
const chatPopupSender = document.getElementById('chat-popup-sender');
const chatPopupMsg = document.getElementById('chat-popup-msg');
const chatPopupInput = document.getElementById('chat-popup-input');
const chatPopupSend = document.getElementById('chat-popup-send');
const chatPopupClose = document.getElementById('chat-popup-close');

// ===== SETUP =====
document.title = `VJMeet · ${ROOM_ID}`;
meetingTitle.textContent = `Room: ${ROOM_ID.slice(0, 8)}…`;
document.getElementById('info-room-id').textContent = ROOM_ID;
const cleanUrl = new URL(window.location.href); cleanUrl.searchParams.delete('name'); cleanUrl.searchParams.delete('role'); document.getElementById('info-link').textContent = cleanUrl.toString(); window.history.replaceState({}, document.title, cleanUrl.toString());

function applyRoomBrand(isPlatform, branding) {
  if (branding !== undefined) roomBranding = branding;
  const brand = isPlatform ? 'younimatiq' : 'vjmeet';
  const label = (roomBranding && roomBranding.name) || (isPlatform ? 'Younimatiq' : 'VJMeet');
  const v = '20260918';
  const setHref = (id, path) => {
    const el = document.getElementById(id);
    if (el) el.setAttribute('href', path);
  };
  setHref('favicon-ico', `/brand/${brand}/favicon.ico?v=${v}`);
  setHref('favicon-32', `/brand/${brand}/favicon-32x32.png?v=${v}`);
  setHref('favicon-apple', `/brand/${brand}/apple-touch-icon.png?v=${v}`);
  if (isPlatform) {
    const svg = document.getElementById('favicon-svg');
    if (svg) svg.remove();
  } else {
    let svg = document.getElementById('favicon-svg');
    if (!svg) {
      svg = document.createElement('link');
      svg.id = 'favicon-svg';
      svg.rel = 'icon';
      svg.type = 'image/svg+xml';
      document.head.appendChild(svg);
    }
    svg.href = `/brand/vjmeet/favicon.svg?v=${v}`;
  }
  const logoText = document.getElementById('brand-logo-text');
  if (logoText) logoText.textContent = label;
  const logoBox = document.querySelector('.logo-sm');
  if (logoBox) {
    if (roomBranding && roomBranding.logo_url) {
      logoBox.innerHTML = '<img src="' + escHtml(roomBranding.logo_url) + '" alt="" style="width:100%;height:100%;object-fit:contain;border-radius:8px;" />';
    }
  }
  if (roomBranding && roomBranding.primary_color) {
    try { document.documentElement.style.setProperty('--primary', roomBranding.primary_color); } catch (_) {}
  }
  document.title = `${label} · ${ROOM_ID}`;
  if (roomTitle && meetingTitle) meetingTitle.textContent = roomTitle;
  const appName = document.querySelector('meta[name="application-name"]');
  if (appName) appName.setAttribute('content', label);
  const appleTitle = document.querySelector('meta[name="apple-mobile-web-app-title"]');
  if (appleTitle) appleTitle.setAttribute('content', label);
}

function applyRecordingPolicy() {
  const allowed = !!amIHost && !!recordingEnabled;
  if (!allowed && isRecording) stopRecording();
  if (recordBtn) recordBtn.style.display = allowed ? '' : 'none';
}

// Detect website vs normal Meet ASAP (before join) so favicon is correct
(async function detectRoomBrandEarly() {
  try {
    const res = await fetch('/api/rooms/' + encodeURIComponent(ROOM_ID) + '/status');
    if (!res.ok) return;
    const data = await res.json();
    applyRoomStatusData(data);
  } catch (_) {}
})();

function applyRoomStatusData(data) {
  if (!data) return;
  const platform = !!(data.contact_guard || data.platform_managed);
  contactGuardEnabled = platform;
  if (typeof data.recording_enabled !== 'undefined') recordingEnabled = !!data.recording_enabled;
  if (typeof data.waiting_room !== 'undefined') waitingRoomEnabled = !!data.waiting_room;
  if (typeof data.locked !== 'undefined') roomLocked = !!data.locked;
  if (typeof data.passcode_required !== 'undefined') passcodeRequired = !!data.passcode_required;
  if (data.title) roomTitle = data.title;
  applyRoomBrand(platform, data.branding || null);
  applyRecordingPolicy();
  const passInput = document.getElementById('join-passcode-input');
  if (passInput && passcodeRequired && !amIHost) passInput.style.display = 'block';
  updateWaitingForHostUI();
}

// Timer
timerInterval = setInterval(() => {
  const s = Math.floor((Date.now() - startTime) / 1000);
  const mm = String(Math.floor(s / 60)).padStart(2, '0');
  const ss = String(s % 60).padStart(2, '0');
  const t = `${mm}:${ss}`;
  callTimer.textContent = t;
  cInfoTime.textContent = t;
}, 1000);

// ===== LOCAL MEDIA =====
async function initMedia() {
  const permModal = document.getElementById('permission-modal');
  if (permModal) permModal.style.display = 'none';
  
  if (localStream) {
    localStream.getTracks().forEach(track => track.stop());
  }
  
  try {
    await iceReady;
    localStream = await navigator.mediaDevices.getUserMedia({
      video: videoConstraints(),
      audio: { ...MEDIA_AUDIO },
    });
    
    // Apply initial mic/cam state
    localStream.getAudioTracks().forEach(t => t.enabled = isMicOn);
    localStream.getVideoTracks().forEach(t => t.enabled = isCamOn);
    
    addLocalTile();
    joinRoom();
  } catch (err) {
    if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
      if (permModal) permModal.style.display = 'flex';
      return;
    }
    showToast('⚠️ Camera access failed. Trying audio-only.');
    try {
      localStream = await navigator.mediaDevices.getUserMedia({
        audio: { ...MEDIA_AUDIO },
      });
      localStream.getAudioTracks().forEach(t => t.enabled = isMicOn);
      addLocalTile();
      joinRoom();
    } catch (errAudio) {
      if (errAudio.name === 'NotAllowedError' || errAudio.name === 'PermissionDeniedError') {
        if (permModal) permModal.style.display = 'flex';
        return;
      }
      showToast('⚠️ No camera or microphone found. Joining as viewer.');
      localStream = new MediaStream();
      addLocalTile();
      joinRoom();
    }
  }
}

if (document.getElementById('btn-retry-permissions')) {
  document.getElementById('btn-retry-permissions').addEventListener('click', initMedia);
}

function addLocalTile() {
  const tile = createTile(MY_ID, MY_NAME, true, amIHost);
  const video = tile.querySelector('video');
  if (localStream.getVideoTracks().length > 0) {
    video.srcObject = localStream;
  } else {
    showNoVideo(tile, MY_NAME);
  }
  videoGrid.appendChild(tile);
  updateGridLayout();
  addParticipantItem(MY_ID, MY_NAME, amIHost);
}

let hasJoinedRoom = false;
async function joinRoom() {
  await meetConfigReady;
  const token = HOST_TOKEN || localStorage.getItem('vjmeet-host-token-' + ROOM_ID) || '';
  socket.emit('join-room', ROOM_ID, MY_ID, MY_NAME, amIHost, isCamOn, isMicOn, token, roomPasscode);
  hasJoinedRoom = true;
  // Restore CC preference after (re)join so others keep sharing captions
  socket.emit('caption-preference', { enabled: !!captionsEnabled });
  updateWaitingForHostUI();
  syncSpeechWatch();

  let myTile = document.getElementById('tile-' + MY_ID);
  if (!myTile) {
    myTile = createTile(MY_ID, MY_NAME, true, amIHost);
    videoGrid.appendChild(myTile);
    updateGridLayout();
    addParticipantItem(MY_ID, MY_NAME, true);
    if (localStream) {
      const video = myTile.querySelector('video');
      video.srcObject = localStream;
      video.muted = true;
      if (isCamOn) showVideo(myTile); else showNoVideo(myTile, MY_NAME);
    }
  }

  // SFU media path (parallel rooms + higher quality). Socket.IO stays for lobby/chat/host.
  await connectLiveKitMedia();
}

// ===== PRE-JOIN TOGGLES =====
const pjMicBtn = document.getElementById('prejoin-mic-btn');
const pjCamBtn = document.getElementById('prejoin-cam-btn');

if (pjMicBtn) {
  pjMicBtn.addEventListener('click', () => {
    isMicOn = !isMicOn;
    pjMicBtn.querySelector('.pj-mic-on').style.display = isMicOn ? '' : 'none';
    pjMicBtn.querySelector('.pj-mic-off').style.display = isMicOn ? 'none' : '';
    micBtn.classList.toggle('active', isMicOn);
    micBtn.classList.toggle('off', !isMicOn);
    micBtn.querySelector('.icon-on').style.display = isMicOn ? '' : 'none';
    micBtn.querySelector('.icon-off').style.display = isMicOn ? 'none' : '';
  });
}

if (pjCamBtn) {
  pjCamBtn.addEventListener('click', () => {
    isCamOn = !isCamOn;
    pjCamBtn.querySelector('.pj-cam-on').style.display = isCamOn ? '' : 'none';
    pjCamBtn.querySelector('.pj-cam-off').style.display = isCamOn ? 'none' : '';
    cameraBtn.classList.toggle('active', isCamOn);
    cameraBtn.classList.toggle('off', !isCamOn);
    cameraBtn.querySelector('.icon-on').style.display = isCamOn ? '' : 'none';
    cameraBtn.querySelector('.icon-off').style.display = isCamOn ? 'none' : '';
  });
}

// ===== WEBRTC (mesh fallback; skipped when LiveKit SFU is active) =====
socket.on('user-connected', async (userId, userName, isHost, isPeerCamOn, isPeerMicOn) => {
  const n = userName || 'Guest';
  participants[userId] = { name: n, isHost, isCamOn: isPeerCamOn, isMicOn: isPeerMicOn };
  if (!userId.endsWith('-screen')) {
    addParticipantItem(userId, n, isHost);
    showNotification(`👋 ${n} joined`);
  }

  // LiveKit attaches remote tracks via TrackSubscribed — do not create mesh peers
  if (usingLiveKit()) return;
  
  const peer = createPeer(userId);
  peers[userId] = peer;
  
  if (!document.getElementById(`tile-${userId}`)) {
    const tile = createTile(userId, n, false, isHost);
    videoGrid.appendChild(tile);
    updateGridLayout();
    if (userId.endsWith('-screen')) {
       togglePin(userId);
    }
    if (isPeerCamOn === false) {
       showNoVideo(tile, n);
    }
  }
  
  try {
    const offer = await peer.createOffer({ offerToReceiveAudio: true, offerToReceiveVideo: true });
    await peer.setLocalDescription(offer);
    socket.emit('offer', { target: userId, caller: MY_ID, callerName: MY_NAME, isHost: amIHost, isCamOn: isCamOn, isMicOn: isMicOn, sdp: offer });
  } catch(e) { console.error(e); }

  if (isScreenSharing) {
    const sPeer = createScreenPeer(userId);
    screenPeers[userId] = sPeer;
    try {
      const sOffer = await sPeer.createOffer();
      await sPeer.setLocalDescription(sOffer);
      socket.emit('offer', { target: userId, caller: MY_ID + '-screen', callerName: MY_NAME + "'s Screen", isHost: false, sdp: sOffer });
    } catch(e) { console.error(e); }
  }
});

socket.on('offer', async (payload) => {
  if (usingLiveKit()) return;
  let isForScreen = payload.target === MY_ID + '-screen';
  if (!isForScreen && payload.target !== MY_ID) return;

  if (payload.callerName && !participants[payload.caller]) {
    participants[payload.caller] = { name: payload.callerName, isHost: payload.isHost || false, isCamOn: payload.isCamOn, isMicOn: payload.isMicOn };
    if (!payload.caller.endsWith('-screen')) {
      addParticipantItem(payload.caller, payload.callerName, payload.isHost || false);
    }
  } else if (participants[payload.caller]) {
    participants[payload.caller].isCamOn = payload.isCamOn;
    participants[payload.caller].isMicOn = payload.isMicOn;
    const tile = document.getElementById(`tile-${payload.caller}`);
    if (tile && payload.isCamOn === false) {
      showNoVideo(tile, participants[payload.caller].name);
    }
  }
  
  let targetPeers = isForScreen ? screenPeers : peers;
  let peer = targetPeers[payload.caller];
  if (!peer) {
    peer = isForScreen ? createScreenPeer(payload.caller) : createPeer(payload.caller);
    targetPeers[payload.caller] = peer;
  }
  
  try {
    await peer.setRemoteDescription(new RTCSessionDescription(payload.sdp));
    if (peer.iceQueue) {
      for (let cand of peer.iceQueue) { await peer.addIceCandidate(new RTCIceCandidate(cand)).catch(console.error); }
      peer.iceQueue = [];
    }
    const answer = await peer.createAnswer();
    await peer.setLocalDescription(answer);
    socket.emit('answer', { target: payload.caller, caller: payload.target, sdp: answer });
  } catch(e) { console.error(e); }
});

socket.on('answer', async (payload) => {
  if (usingLiveKit()) return;
  let isForScreen = payload.target === MY_ID + '-screen';
  if (!isForScreen && payload.target !== MY_ID) return;
  let targetPeers = isForScreen ? screenPeers : peers;
  const peer = targetPeers[payload.caller];
  if (peer) {
    await peer.setRemoteDescription(new RTCSessionDescription(payload.sdp)).catch(e=>console.error(e));
    if (peer.iceQueue) {
      for (let cand of peer.iceQueue) { await peer.addIceCandidate(new RTCIceCandidate(cand)).catch(console.error); }
      peer.iceQueue = [];
    }
  }
});

socket.on('ice-candidate', async (payload) => {
  if (usingLiveKit()) return;
  let isForScreen = payload.target === MY_ID + '-screen';
  if (!isForScreen && payload.target !== MY_ID) return;
  let targetPeers = isForScreen ? screenPeers : peers;
  const peer = targetPeers[payload.caller];
  if (peer) {
    if (peer.remoteDescription && peer.remoteDescription.type) {
      await peer.addIceCandidate(new RTCIceCandidate(payload.candidate)).catch(e => console.error(e));
    } else {
      peer.iceQueue = peer.iceQueue || [];
      peer.iceQueue.push(payload.candidate);
    }
  }
});

socket.on('user-disconnected', (userId) => {
  if (peers[userId]) {
    peers[userId].close();
    delete peers[userId];
  }
  delete participants[userId];
  const tile = document.getElementById('tile-' + userId);
  if (tile) {
    if (tile.classList.contains('pinned')) layout = 'grid';
    tile.remove();
  }
  updateGridLayout();
  removeParticipantItem(userId);
  
  const screenId = userId + '-screen';
  if (peers[screenId]) {
    peers[screenId].close();
    delete peers[screenId];
  }
  const screenTile = document.getElementById('tile-' + screenId);
  if (screenTile) {
    if (screenTile.classList.contains('pinned')) layout = 'grid';
    screenTile.remove();
  }
});

function createPeer(userId) {
  const peer = new RTCPeerConnection(rtcConfig);
  const isScreen = userId.endsWith('-screen');
  if (localStream && !isScreen) {
    localStream.getTracks().forEach(track => peer.addTrack(track, localStream));
  }
  peer.onicecandidate = (e) => {
    if (e.candidate) {
      socket.emit('ice-candidate', { target: userId, caller: MY_ID, candidate: e.candidate });
    }
  };
  peer.onconnectionstatechange = () => {
    if (peer.connectionState === 'failed') {
      try { peer.restartIce(); } catch (_) {}
    }
  };
  // Apply encode ceilings after negotiation starts
  peer.onnegotiationneeded = () => { applySenderBitrates(peer); };
  setTimeout(() => applySenderBitrates(peer), 400);
  peer.ontrack = (e) => {
    let tile = document.getElementById('tile-' + userId);
    let peerName = participants[userId] ? participants[userId].name : "Participant";
    let isPeerHost = participants[userId] ? participants[userId].isHost : false;
    
    if (!tile) {
      tile = createTile(userId, peerName, false, isPeerHost);
      videoGrid.appendChild(tile);
      updateGridLayout();
    } else {
      const nameDiv1 = tile.querySelector('.tile-no-video-name');
      const nameDiv2 = tile.querySelector('.tile-name');
      if (nameDiv1 && peerName !== "Participant") nameDiv1.textContent = peerName;
      if (nameDiv2 && peerName !== "Participant") nameDiv2.textContent = peerName;
    }
    
    const video = tile.querySelector('video');
    
    if (e.streams && e.streams.length > 0) {
      video.srcObject = e.streams[0];
    } else {
      let stream = video.srcObject;
      if (!stream) {
        stream = new MediaStream();
      }
      
      if (e.track && !stream.getTracks().includes(e.track)) {
        stream.addTrack(e.track);
      }
      
      // Force the video element to re-evaluate the tracks (fixes black screen bug)
      video.srcObject = new MediaStream(stream.getTracks());
    }
    
    video.play().catch(console.error);
    video.onloadedmetadata = () => {
      video.play().catch(console.error);
    };
    showVideo(tile);
  };
  return peer;
}

function createScreenPeer(userId) {
  const peer = new RTCPeerConnection(rtcConfig);
  if (typeof screenStream !== 'undefined' && screenStream) {
    screenStream.getTracks().forEach(track => peer.addTrack(track, screenStream));
  }
  peer.onicecandidate = (e) => {
    if (e.candidate) {
      socket.emit('ice-candidate', { target: userId, caller: MY_ID + '-screen', candidate: e.candidate });
    }
  };
  peer.onconnectionstatechange = () => {
    if (peer.connectionState === 'failed') {
      try { peer.restartIce(); } catch (_) {}
    }
  };
  setTimeout(() => applySenderBitrates(peer, { screen: true }), 400);
  return peer;
}


// ===== TILE FACTORY =====
const AVATAR_COLORS = [
  'linear-gradient(135deg,#6366f1,#8b5cf6)',
  'linear-gradient(135deg,#06b6d4,#0ea5e9)',
  'linear-gradient(135deg,#f43f5e,#ec4899)',
  'linear-gradient(135deg,#f59e0b,#f97316)',
  'linear-gradient(135deg,#10b981,#14b8a6)',
  'linear-gradient(135deg,#8b5cf6,#a78bfa)',
];
function colorForId(id) {
  let h = 0;
  for (let c of id) h = (h * 31 + c.charCodeAt(0)) & 0xffffffff;
  return AVATAR_COLORS[Math.abs(h) % AVATAR_COLORS.length];
}

function createTile(peerId, name, isLocal, isHost = false) {
  const tile = document.createElement('div');
  tile.className = 'video-tile';
  if (peerId.endsWith('-screen')) {
    tile.classList.add('screen-share');
  }
  tile.id = `tile-${peerId}`;
  tile.dataset.peerId = peerId;

  const video = document.createElement('video');
  video.autoplay = true;
  video.playsInline = true;
  if (isLocal) {
    video.muted = true;
    video.style.transform = 'scaleX(-1)'; // Mirror local camera (normal behavior)
  } else {
    video.autoPictureInPicture = true;
    if (typeof video.setSinkId === 'function' && typeof currentSpeakerId !== 'undefined' && currentSpeakerId !== 'default') {
      video.setSinkId(currentSpeakerId).catch(e => console.warn('setSinkId error', e));
    }
  }
  tile.appendChild(video);

  // No-video fallback (hidden by default)
  const noVid = document.createElement('div');
  noVid.className = 'tile-no-video';
  noVid.style.display = 'none';
  const av = document.createElement('div');
  av.className = 'tile-avatar-big';
  av.style.background = colorForId(peerId);
  av.textContent = name.charAt(0).toUpperCase();
  const nn = document.createElement('div');
  nn.className = 'tile-no-video-name';
  nn.textContent = name;
  noVid.appendChild(av);
  noVid.appendChild(nn);
  tile.appendChild(noVid);

  // Overlay
  const overlay = document.createElement('div');
  overlay.className = 'tile-overlay';
  const label = document.createElement('div');
  label.className = 'tile-name-label';
  label.id = `label-${peerId}`;
  label.textContent = isLocal ? `${name} (You)` : name;
  overlay.appendChild(label);
  tile.appendChild(overlay);

  if (isHost) {
    const badge = document.createElement('div');
    badge.className = 'tile-host-badge';
    badge.textContent = 'HOST';
    tile.appendChild(badge);
  }

  tile.addEventListener('dblclick', () => togglePin(peerId));
  return tile;
}

function showNoVideo(tile, name) {
  const video = tile.querySelector('video');
  const noVid = tile.querySelector('.tile-no-video');
  video.style.display = 'none';
  noVid.style.display = 'flex';
}
function showVideo(tile) {
  const video = tile.querySelector('video');
  const noVid = tile.querySelector('.tile-no-video');
  video.style.display = '';
  noVid.style.display = 'none';
}

const mobileMQ = window.matchMedia('(max-width: 768px)');

// True when the page should use the phone-style full-screen video layout.
// Falls back to a heuristic for Android WebViews that render a wide (desktop)
// viewport on a physically small touch screen.
function isMobileLayout() {
  if (mobileMQ.matches) return true;
  try {
    const coarse = window.matchMedia('(pointer: coarse)').matches;
    const screenMin = Math.min(window.screen?.width || 9999, window.screen?.height || 9999);
    if (coarse && screenMin <= 820 && window.innerWidth > screenMin + 80) return true;
  } catch (_) {}
  return false;
}

function updateGridLayout() {
  const children = Array.from(videoGrid.children);
  const count = children.length;
  videoGrid.className = 'video-grid';
  updateParticipantCount();

  if (count === 0) return;

  if (layout === 'spotlight' && count > 1) { 
    videoGrid.classList.add('spotlight'); 
    children.forEach(child => {
      child.style.width = '';
      child.style.height = '';
    });
    return; 
  }

  const areaWidth = videoGrid.clientWidth;
  const areaHeight = videoGrid.clientHeight;
  const gap = 8;
  const aspect = 16 / 9;

  // ---- Mobile / narrow screens: make the frames fill the screen ----
  // Videos stretch to fill each cell (object-fit: cover) so there is no
  // empty "desktop-like" letterboxing on phones. Columns are chosen so the
  // cells stay close to a landscape shape, which keeps cropping minimal.
  if (isMobileLayout() && areaWidth > 0 && areaHeight > 0) {
    videoGrid.classList.add('fill');
    let cols;
    if (count === 1) cols = 1;
    else if (count <= 2) cols = areaHeight >= areaWidth ? 1 : 2;
    else cols = Math.max(1, Math.round(Math.sqrt((count * areaWidth) / (areaHeight * aspect))));
    cols = Math.min(cols, count);
    const rows = Math.ceil(count / cols);
    const cellWidth = (areaWidth - (cols - 1) * gap) / cols;
    const cellHeight = (areaHeight - (rows - 1) * gap) / rows;
    children.forEach(child => {
      child.style.width = `${Math.max(1, Math.floor(cellWidth))}px`;
      child.style.height = `${Math.max(1, Math.floor(cellHeight))}px`;
    });
    return;
  }
  videoGrid.classList.remove('fill');

  let bestWidth = 0;
  let bestHeight = 0;
  
  for (let cols = 1; cols <= count; cols++) {
    const rows = Math.ceil(count / cols);
    let cellWidth = (areaWidth - (cols - 1) * gap) / cols;
    let cellHeight = cellWidth / aspect;

    if (cellHeight * rows + (rows - 1) * gap > areaHeight) {
      cellHeight = (areaHeight - (rows - 1) * gap) / rows;
      cellWidth = cellHeight * aspect;
    }

    if (cellWidth > bestWidth) {
      bestWidth = cellWidth;
      bestHeight = cellHeight;
    }
  }

  // Optional: cap single user size so it doesn't get ridiculously large
  if (count === 1) {
    if (bestWidth > areaWidth * 0.9) {
       bestWidth = areaWidth * 0.9;
       bestHeight = bestWidth / aspect;
    }
    if (bestHeight > areaHeight * 0.9) {
       bestHeight = areaHeight * 0.9;
       bestWidth = bestHeight * aspect;
    }
  }

  children.forEach(child => {
    child.style.width = `${Math.floor(bestWidth)}px`;
    child.style.height = `${Math.floor(bestHeight)}px`;
  });
}

window.addEventListener('resize', () => {
  requestAnimationFrame(updateGridLayout);
});

// ===== LIVE RESPONSIVE METRICS =====
// Keep the video area sized to the *actually visible* viewport so it never
// overflows behind mobile browser toolbars / notches, and recompute the grid
// whenever anything changes size (panel open, orientation, control bar wrap).
function syncLayoutMetrics() {
  const root = document.documentElement;
  const vv = window.visualViewport;
  const appH = Math.round((vv && vv.height) ? vv.height : window.innerHeight);
  root.style.setProperty('--app-h', appH + 'px');
  const topbarEl = document.getElementById('topbar');
  const controlsEl = document.getElementById('controls-bar');
  if (topbarEl) root.style.setProperty('--topbar-h', topbarEl.offsetHeight + 'px');
  if (controlsEl) root.style.setProperty('--controls-h', controlsEl.offsetHeight + 'px');
  updateGridLayout();
}

window.addEventListener('resize', syncLayoutMetrics);
window.addEventListener('orientationchange', () => setTimeout(syncLayoutMetrics, 250));
window.addEventListener('load', syncLayoutMetrics);
if (window.visualViewport) {
  window.visualViewport.addEventListener('resize', syncLayoutMetrics);
  window.visualViewport.addEventListener('scroll', syncLayoutMetrics);
}

(function observeLayout() {
  if (!window.ResizeObserver) return;
  let raf = null;
  const schedule = () => { cancelAnimationFrame(raf); raf = requestAnimationFrame(updateGridLayout); };
  const area = document.querySelector('.video-area');
  const controlsEl = document.getElementById('controls-bar');
  if (area) new ResizeObserver(schedule).observe(area);
  if (controlsEl) new ResizeObserver(() => { cancelAnimationFrame(raf); raf = requestAnimationFrame(syncLayoutMetrics); }).observe(controlsEl);
})();

requestAnimationFrame(syncLayoutMetrics);

function updateParticipantCount() {
  const count = videoGrid.children.length;
  participantCount.innerHTML = `<svg viewBox="0 0 16 16" fill="none" width="12" height="12"><path d="M10.5 7A3.5 3.5 0 1 0 7 3.5 3.5 3.5 0 0 0 10.5 7zM7 9.5A6.5 6.5 0 0 0 .5 16h13A6.5 6.5 0 0 0 7 9.5z" fill="currentColor" opacity="0.7"/></svg>${count}`;
}

function togglePin(peerId) {
  const tile = document.getElementById(`tile-${peerId}`);
  if (!tile) return;
  if (tile.classList.contains('pinned')) {
    tile.classList.remove('pinned');
    if (videoGrid.firstChild !== tile) videoGrid.prepend(tile);
    layout = 'grid';
  } else {
    document.querySelectorAll('.video-tile.pinned').forEach(t => t.classList.remove('pinned'));
    tile.classList.add('pinned');
    videoGrid.prepend(tile);
    layout = 'spotlight';
  }
  updateGridLayout();
}

// ===== SOCKET EVENTS =====
socket.on('disconnect', () => {
  if (hasJoinedRoom) showToast('⚠️ Connection lost. Reconnecting...');
});

socket.on('connect', () => {
  if (hasJoinedRoom && localStream && MY_NAME) {
    // Clear old peers since server will establish new connections
    for (let id in peers) {
      if (peers[id]) peers[id].close();
      delete peers[id];
      delete iceQueues[id];
      const tile = document.getElementById(`tile-${id}`);
      if (tile) tile.remove();
      const item = document.getElementById(`pitem-${id}`);
      if (item) item.remove();
    }
    participants = {};
    updateGridLayout();
    updateParticipantCount();

    joinRoom();
    showToast('✅ Reconnected to server');
  }
});





function syncUserState(userId, micOn, camOn) {
  if (micOn !== undefined) {
    const label = document.getElementById(`label-${userId}`);
    if (label) {
      const existing = label.querySelector('.muted-icon');
      if (!micOn && !existing) {
        const icon = document.createElement('span');
        icon.className = 'muted-icon';
        icon.innerHTML = `<svg viewBox="0 0 16 16" fill="none" width="12" height="12"><path d="M13 8A5 5 0 0 1 3 8M8 11v3M3 3l10 10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>`;
        label.appendChild(icon);
      } else if (micOn && existing) { existing.remove(); }
    }
    const pBadge = document.querySelector(`#pmic-${userId}`);
    if (pBadge) pBadge.style.opacity = micOn ? '0.3' : '1';
  }
  if (camOn !== undefined) {
    if (participants[userId]) participants[userId].isCamOn = camOn;
    const tile = document.getElementById(`tile-${userId}`);
    if (tile) {
      if (participants[userId]?.isScreenSharing) {
        showVideo(tile);
      } else {
        if (camOn) showVideo(tile); else showNoVideo(tile, participants[userId]?.name || '');
      }
    }
  }
}





function handleHostAction(action) {
  if (action === 'mute') {
    if (isMicOn) micBtn.click();
    showToast('The host muted your microphone');
  } else if (action === 'turn-off-cam') {
    if (isCamOn) cameraBtn.click();
    showToast('The host turned off your camera');
  } else if (action === 'remove') {
    window.location.href = '/?removed=1';
  } else if (action === 'end-meeting') {
    window.location.href = '/?ended=1';
  }
}
socket.on('host-action', handleHostAction);

socket.on('hand-raised', (userId, isRaised) => {
  const name = participants[userId]?.name || 'Someone';
  const tile = document.getElementById(`tile-${userId}`);
  if (tile) {
    let h = tile.querySelector('.tile-hand-icon');
    if (isRaised) { if (!h) { h = document.createElement('div'); h.className = 'tile-hand-icon'; h.textContent = '✋'; tile.appendChild(h); } }
    else { if (h) h.remove(); }
  }
  if (isRaised && amIHost) {
    showToast(`✋ ${name} raised their hand`);
  }
  if (isRaised) {
    sendPushNotification('Hand Raised', `${name} raised their hand.`);
  }
});

socket.on('chat-message', (msg, sender) => {
  appendChat(sender, msg, false);
  if (panelOpen !== 'chat') {
    unreadCount++;
    document.getElementById('unread-badge').textContent = unreadCount;
    document.getElementById('unread-badge').style.display = 'inline';
    document.getElementById('chat-unread').style.display = 'block';
    
    showChatPopup(sender, msg);
  }
  
  if (panelOpen !== 'chat' || document.hidden) {
    sendPushNotification(`New message from ${sender}`, msg);
  }
});

socket.on('user-toggled-video', (userId, isOn) => {
  if (participants[userId]) participants[userId].isCamOn = isOn;
  const tile = document.getElementById(`tile-${userId}`);
  if (!tile) return;
  if (isOn) showVideo(tile); else showNoVideo(tile, participants[userId]?.name || '');
});

socket.on('user-screen-share', (userId, isSharing) => {
  if (!isSharing) {
    const screenId = userId + '-screen';
    if (peers[screenId]) {
      peers[screenId].close();
      delete peers[screenId];
    }
    const tile = document.getElementById(`tile-${screenId}`);
    if (tile) {
      if (tile.classList.contains('pinned')) layout = 'grid';
      tile.remove();
    }
    updateGridLayout();
  }
});


// ===== KEEP ALIVE & WAKE LOCK =====
let wakeLock = null;
async function requestWakeLock() {
  try {
    if ('wakeLock' in navigator) {
      wakeLock = await navigator.wakeLock.request('screen');
      wakeLock.addEventListener('release', () => console.log('Wake Lock released'));
    }
  } catch (err) {
    console.error('Wake Lock error:', err);
  }
}

document.addEventListener('visibilitychange', async () => {
  if (document.visibilityState === 'visible') {
    if (wakeLock !== null) requestWakeLock();
  }
});

let keepAliveAudio = null;
function startKeepAlive() {
  if (!keepAliveAudio) {
    keepAliveAudio = new (window.AudioContext || window.webkitAudioContext)();
    const silentOsc = keepAliveAudio.createOscillator();
    const silentGain = keepAliveAudio.createGain();
    silentGain.gain.value = 0;
    silentOsc.connect(silentGain);
    silentGain.connect(keepAliveAudio.destination);
    silentOsc.start();
  }
}

// ===== START =====
let mediaStarted = false;
let waitingInLobby = false;
let lobbyPollTimer = null;

function startClassMedia() {
  if (mediaStarted) return;
  mediaStarted = true;
  waitingInLobby = false;
  if (lobbyPollTimer) {
    clearInterval(lobbyPollTimer);
    lobbyPollTimer = null;
  }
  updateWaitingForHostUI();
  initMedia().then(() => syncSpeechWatch()).catch(() => syncSpeechWatch());
  startKeepAlive();
  requestWakeLock();
}

function enterLobbyWait() {
  waitingInLobby = true;
  mediaStarted = false;
  updateWaitingForHostUI();
  socket.emit('lobby-wait', { roomId: ROOM_ID, userId: MY_ID, name: MY_NAME, passcode: roomPasscode });
  if (lobbyPollTimer) clearInterval(lobbyPollTimer);
  lobbyPollTimer = setInterval(async () => {
    try {
      const res = await fetch('/api/rooms/' + encodeURIComponent(ROOM_ID) + '/status');
      if (!res.ok) return;
      const data = await res.json();
      applyRoomStatusData(data);
      hostPresent = !!data.host_present;
      updateWaitingForHostUI();
      if (hostPresent && !waitingRoomEnabled && !roomLocked) startClassMedia();
    } catch (_) {}
  }, 3000);
}

document.getElementById('btn-enter-room').addEventListener('click', () => {
  if ('Notification' in window && Notification.permission === 'default') {
    Notification.requestPermission();
  }
  const nameInput = document.getElementById('join-name-input');
  if (nameInput) {
    const val = nameInput.value.trim();
    if (val) {
      MY_NAME = val;
      localStorage.setItem('vjmeet-name', MY_NAME);
    }
  }
  if (!MY_NAME) MY_NAME = 'Guest';

  const passInput = document.getElementById('join-passcode-input');
  const joinError = document.getElementById('join-error');
  if (passInput && passInput.style.display !== 'none' && !amIHost) {
    const code = passInput.value.trim();
    if (!code) {
      if (joinError) { joinError.textContent = 'Enter the meeting passcode.'; joinError.style.display = 'block'; }
      return;
    }
    roomPasscode = code;
  }
  if (joinError) joinError.style.display = 'none';

  sessionStorage.setItem('vjmeet-joined-' + ROOM_ID, 'true');
  document.getElementById('join-overlay').style.display = 'none';

  if (amIHost) {
    startClassMedia();
  } else {
    // Hard lobby: no camera/mic/WebRTC until teacher is present
    enterLobbyWait();
  }
});

if (amIHost) {
  const enterBtn = document.getElementById('btn-enter-room');
  if (enterBtn) enterBtn.textContent = 'Start Meeting (Host)';
}

function ensureWaitingBanner() {
  let el = document.getElementById('waiting-host-banner');
  if (!el) {
    el = document.createElement('div');
    el.id = 'waiting-host-banner';
    el.style.cssText = 'display:none;position:fixed;inset:0;z-index:80;background:rgba(15,23,42,.92);color:#fff;align-items:center;justify-content:center;flex-direction:column;gap:12px;text-align:center;padding:24px;';
    el.innerHTML = '<div id="waiting-title" style="font-size:18px;font-weight:700;"></div><div id="waiting-sub" style="font-size:13px;opacity:.8;"></div>';
    document.body.appendChild(el);
  }
  return el;
}

function updateWaitingForHostUI() {
  const banner = ensureWaitingBanner();
  const title = document.getElementById('waiting-title');
  const sub = document.getElementById('waiting-sub');
  if (roomLocked) {
    if (title) title.textContent = 'Class is locked';
    if (sub) sub.textContent = 'The teacher has locked this class. Please try again later.';
  } else if (waitingRoomEnabled) {
    if (title) title.textContent = 'Waiting for teacher approval…';
    if (sub) sub.textContent = 'You will join automatically once the teacher admits you.';
  } else {
    if (title) title.textContent = 'Waiting for the teacher to start the class…';
    if (sub) sub.textContent = 'Your camera and microphone stay off until the teacher joins.';
  }
  if (!amIHost && (waitingInLobby || !hostPresent) && !mediaStarted) {
    banner.style.display = 'flex';
  } else {
    banner.style.display = 'none';
  }
}

socket.on('room-status', (payload) => {
  hostPresent = !!(payload && payload.host_present);
  applyRoomStatusData(payload);
  if (payload && typeof payload.captions_wanted !== 'undefined') {
    captionsDemanded = !!payload.captions_wanted;
  }
  updateWaitingForHostUI();
  if (!amIHost && hostPresent && waitingInLobby && !waitingRoomEnabled && !roomLocked) startClassMedia();
  syncSpeechWatch();
});

socket.on('waiting-pending', () => {
  waitingInLobby = true;
  updateWaitingForHostUI();
});

socket.on('waiting-approved', () => {
  waitingInLobby = false;
  showToast('Teacher admitted you — joining now');
  startClassMedia();
});

socket.on('waiting-denied', () => {
  showToast('The teacher did not admit you.');
  setTimeout(() => { window.location.href = '/?denied=1'; }, 1200);
});

socket.on('room-locked', (payload) => {
  roomLocked = !!(payload && payload.locked);
  updateWaitingForHostUI();
  if (!amIHost && !mediaStarted) showToast('This class is locked by the teacher.');
});

socket.on('room-lock-status', (payload) => {
  roomLocked = !!(payload && payload.locked);
  updateWaitingForHostUI();
  updateLockButton();
});

socket.on('passcode-required', (payload) => {
  passcodeRequired = true;
  const passInput = document.getElementById('join-passcode-input');
  if (passInput) passInput.style.display = 'block';
  const joinError = document.getElementById('join-error');
  if (joinError) { joinError.textContent = (payload && payload.message) || 'A valid passcode is required.'; joinError.style.display = 'block'; }
  document.getElementById('join-overlay').style.display = 'flex';
});

socket.on('toast', (msg) => { if (msg) showToast(msg); });

socket.on('waiting-list', (list) => { renderWaitingList(list); });
socket.on('host-joined', () => {
  hostPresent = true;
  updateWaitingForHostUI();
  if (!amIHost) {
    showToast('Teacher joined — class is starting');
    if (waitingInLobby) startClassMedia();
  }
});
socket.on('host-left', () => {
  hostPresent = false;
  updateWaitingForHostUI();
  if (!amIHost) showToast('Teacher left the meeting');
});

if (MY_NAME && sessionStorage.getItem('vjmeet-joined-' + ROOM_ID) === 'true') {
  document.getElementById('btn-enter-room').click();
}

// ===== MIC TOGGLE =====
micBtn.addEventListener('click', async () => {
  isMicOn = !isMicOn;
  if (localStream) {
    localStream.getAudioTracks().forEach(t => t.enabled = isMicOn);
  }
  if (usingLiveKit()) {
    try { await window.YouniLiveKit.setMicEnabled(isMicOn); } catch (e) { console.warn(e); }
  }
  micBtn.classList.toggle('active', isMicOn);
  micBtn.classList.toggle('off', !isMicOn);
  micBtn.querySelector('.icon-on').style.display = isMicOn ? '' : 'none';
  micBtn.querySelector('.icon-off').style.display = isMicOn ? 'none' : '';
  socket.emit('toggle-audio', MY_ID, isMicOn);
  syncSpeechWatch();
});

// ===== CAMERA TOGGLE =====
cameraBtn.addEventListener('click', async () => {
  isCamOn = !isCamOn;
  if (localStream) {
    localStream.getVideoTracks().forEach(t => t.enabled = isCamOn);
  }
  if (usingLiveKit()) {
    try { await window.YouniLiveKit.setCamEnabled(isCamOn); } catch (e) { console.warn(e); }
  }
  cameraBtn.classList.toggle('active', isCamOn);
  cameraBtn.classList.toggle('off', !isCamOn);
  cameraBtn.querySelector('.icon-on').style.display = isCamOn ? '' : 'none';
  cameraBtn.querySelector('.icon-off').style.display = isCamOn ? 'none' : '';
  const tile = document.getElementById('tile-' + MY_ID);
  if (tile) { isCamOn ? showVideo(tile) : showNoVideo(tile, MY_NAME); }
  socket.emit('toggle-video', MY_ID, isCamOn);
});

// ===== FLIP CAMERA =====
if (flipCamBtn) {
  flipCamBtn.addEventListener('click', async () => {
    if (!localStream) return;
    currentFacingMode = currentFacingMode === 'user' ? 'environment' : 'user';
    
    try {
      const stream = await navigator.mediaDevices.getUserMedia({
        video: videoConstraints()
      });
      
      const newVideoTrack = stream.getVideoTracks()[0];
      const oldVideoTrack = localStream.getVideoTracks()[0];
      
      if (oldVideoTrack) {
        oldVideoTrack.stop();
        localStream.removeTrack(oldVideoTrack);
      }
      localStream.addTrack(newVideoTrack);
      
      if (!isCamOn) {
        newVideoTrack.enabled = false;
      }

      if (usingLiveKit()) {
        try { await window.YouniLiveKit.replaceCameraTrack(newVideoTrack); } catch (e) { console.warn(e); }
      } else if (!isScreenSharing) {
        Object.values(peers).forEach(pc => {
          if (pc.__videoSender) {
            pc.__videoSender.replaceTrack(newVideoTrack);
          }
        });
      }

      if (!isScreenSharing) {
        const tile = document.getElementById('tile-' + MY_ID);
        if (tile) {
          const video = tile.querySelector('video');
          video.srcObject = localStream;
          video.style.transform = currentFacingMode === 'user' ? 'scaleX(-1)' : 'none';
          if (isCamOn) showVideo(tile); else showNoVideo(tile, MY_NAME);
        }
      }
    } catch (err) {
      console.error('Flip camera failed:', err);
      showToast('Failed to flip camera.');
      currentFacingMode = currentFacingMode === 'user' ? 'environment' : 'user';
    }
  });
}

// ===== SCREEN SHARE =====
let screenPeers = {};

screenBtn.addEventListener('click', async () => {
  if (isScreenSharing) { stopScreenShare(); return; }
  if (usingLiveKit()) {
    try {
      await window.YouniLiveKit.setScreenShareEnabled(true);
      isScreenSharing = true;
      screenBtn.classList.add('active');
      socket.emit('screen-share', MY_ID, true);
    } catch (e) {
      console.error(e);
      showToast('Screen share failed or was cancelled');
    }
    return;
  }
  try {
    screenStream = await navigator.mediaDevices.getDisplayMedia({ video: true, audio: true });
    const screenTrack = screenStream.getVideoTracks()[0];
    screenTrack.onended = () => stopScreenShare();
    
    isScreenSharing = true;
    screenBtn.classList.add('active');
    
    const tile = createTile(MY_ID + '-screen', MY_NAME + "'s Screen", true, false);
    const video = tile.querySelector('video');
    video.srcObject = screenStream;
    video.style.transform = 'none'; // don't mirror
    videoGrid.appendChild(tile);
    updateGridLayout();
    
    togglePin(MY_ID + '-screen');
    
    socket.emit('join-room', ROOM_ID, MY_ID + '-screen', MY_NAME + "'s Screen", false);
  } catch(e) { console.error(e); }
});

async function stopScreenShare() {
  if (!isScreenSharing) return;
  isScreenSharing = false;
  screenBtn.classList.remove('active');

  if (usingLiveKit()) {
    try { await window.YouniLiveKit.setScreenShareEnabled(false); } catch (_) {}
    const lkTile = document.getElementById('tile-' + MY_ID + '-screen');
    if (lkTile) {
      if (lkTile.classList.contains('pinned')) layout = 'grid';
      lkTile.remove();
    }
    updateGridLayout();
    socket.emit('screen-share', MY_ID, false);
    return;
  }
  
  if (screenStream) screenStream.getTracks().forEach(t => t.stop());
  screenStream = null;
  
  for (let id in screenPeers) {
    if (screenPeers[id]) screenPeers[id].close();
  }
  screenPeers = {};
  
  const tile = document.getElementById('tile-' + MY_ID + '-screen');
  if (tile) {
    if (tile.classList.contains('pinned')) layout = 'grid';
    tile.remove();
  }
  updateGridLayout();
  
  socket.emit('screen-share', MY_ID, false);
}

// ===== RECORDING =====
applyRecordingPolicy();
recordBtn.addEventListener('click', () => { isRecording ? stopRecording() : startRecording(); });

let recordingStream = null;
let recordingAudioCtx = null;
let recordingDest = null;

async function startRecording() {
  if (!recordingEnabled) {
    showToast('Recording is disabled for this class.');
    return;
  }
  try {
    // Request the entire screen (monitor) and system audio
    recordingStream = await navigator.mediaDevices.getDisplayMedia({ 
      video: { displaySurface: 'monitor' }, 
      audio: true 
    });
  } catch (err) {
    console.error('Recording cancelled or failed', err);
    showToast('Recording cancelled.');
    return;
  }

  recordedChunks = [];
  recordingAudioCtx = new AudioContext();
  recordingDest = recordingAudioCtx.createMediaStreamDestination();

  // Mix system audio from the screen share if the user allowed it
  if (recordingStream.getAudioTracks().length > 0) {
    recordingAudioCtx.createMediaStreamSource(recordingStream).connect(recordingDest);
  }

  // Mix the user's microphone audio into the recording
  if (localStream && localStream.getAudioTracks().length > 0) {
    recordingAudioCtx.createMediaStreamSource(localStream).connect(recordingDest);
  }

  // Mix audio from all remote participants already in the call
  Object.values(peers).forEach(pc => {
    pc.getReceivers().forEach(receiver => {
      if (receiver.track && receiver.track.kind === 'audio') {
        try {
          const stream = new MediaStream([receiver.track]);
          recordingAudioCtx.createMediaStreamSource(stream).connect(recordingDest);
        } catch (e) { console.warn('Error connecting peer audio', e); }
      }
    });
  });

  const mixed = new MediaStream([
    ...recordingStream.getVideoTracks(),
    ...recordingDest.stream.getAudioTracks()
  ]);

  try { mediaRecorder = new MediaRecorder(mixed, { mimeType: 'video/webm;codecs=vp9,opus' }); }
  catch { mediaRecorder = new MediaRecorder(mixed); }

  mediaRecorder.ondataavailable = e => { if (e.data.size > 0) recordedChunks.push(e.data); };
  
  mediaRecorder.onstop = () => {
    if (recordingAudioCtx) recordingAudioCtx.close();
    if (recordingStream) recordingStream.getTracks().forEach(t => t.stop());
    const blob = new Blob(recordedChunks, { type: 'video/webm' });
    const fileName = 'VJMeet-' + ROOM_ID.slice(0,8) + '-' + new Date().toISOString().slice(0,19).replace(/:/g,'-') + '.webm';
    
    // Per-meeting recording policy.
    if (!recordingEnabled) {
      recordedChunks = [];
      showToast('Recording is disabled for this class.');
      return;
    }

    // 1. Upload to server
    const formData = new FormData();
    formData.append('video', blob, fileName);
    formData.append('roomId', ROOM_ID);
    formData.append('organization', 'younimatiq');
    formData.append('host_token', HOST_TOKEN || localStorage.getItem('vjmeet-host-token-' + ROOM_ID) || '');
    
    showToast('Uploading recording to server...');
    fetch('/api/recordings/upload', {
       method: 'POST',
       body: formData
    }).then(res => res.json()).then(data => {
       console.log('Recording uploaded:', data);
       showToast('Recording saved to server!');
    }).catch(err => {
       console.error('Upload failed', err);
       showToast('Failed to save recording to server.');
    });
    
    // 2. Download locally
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = fileName;
    a.click();
  };

  // If the user clicks "Stop sharing" on the browser UI, stop the recording
  recordingStream.getVideoTracks()[0].onended = () => {
    if (isRecording) stopRecording();
  };

  mediaRecorder.start(1000);
  isRecording = true; 
  recordBtn.classList.add('on-rec'); 
  recIndicator.style.display = 'flex';
  showToast('Recording entire screen started');
}

function stopRecording() {
  if (mediaRecorder && mediaRecorder.state !== 'inactive') mediaRecorder.stop();
  if (recordingStream) recordingStream.getTracks().forEach(t => t.stop());
  isRecording = false; 
  recordBtn.classList.remove('on-rec'); 
  recIndicator.style.display = 'none';
}

// ===== RAISE HAND =====
handBtn.addEventListener('click', () => {
  isHandRaised = !isHandRaised;
  handBtn.classList.toggle('active', isHandRaised);
  const tile = document.getElementById('tile-' + MY_ID);
  if (tile) {
    let h = tile.querySelector('.tile-hand-icon');
    if (isHandRaised) { if (!h) { h = document.createElement('div'); h.className = 'tile-hand-icon'; h.textContent = '✋'; tile.appendChild(h); } }
    else { if (h) h.remove(); }
  }
  socket.emit('hand-raised', MY_ID, isHandRaised);
});

// ===== LEAVE =====
leaveBtn.addEventListener('click', () => { leaveModal.style.display = 'flex'; });
document.getElementById('leave-cancel').addEventListener('click', () => { leaveModal.style.display = 'none'; });
document.getElementById('leave-confirm').addEventListener('click', async () => {
  if (amIHost) {
    socket.emit('host-action', { target: 'all', action: 'end-meeting' });
  }
  clearInterval(timerInterval);
  if (isRecording) stopRecording();
  if (usingLiveKit()) {
    try { await window.YouniLiveKit.disconnect(); } catch (_) {}
  }
  if (localStream) localStream.getTracks().forEach(t => t.stop());
  Object.values(peers).forEach(pc => pc.close());
  window.location.href = '/';
});

// ===== CHAT PANEL =====
function openPanel(name) {
  panelOpen = name;
  sidePanel.style.display = 'flex';
  document.getElementById('panel-chat').style.display = name === 'chat' ? 'flex' : 'none';
  document.getElementById('panel-participants').style.display = name === 'participants' ? 'flex' : 'none';
  document.getElementById('tab-chat').classList.toggle('active', name === 'chat');
  document.getElementById('tab-participants').classList.toggle('active', name === 'participants');
  chatBtn.classList.toggle('active', name === 'chat');
  peopleBtn.classList.toggle('active', name === 'participants');
  if (name === 'chat') {
    unreadCount = 0;
    document.getElementById('unread-badge').style.display = 'none';
    document.getElementById('chat-unread').style.display = 'none';
    chatMessages.scrollTop = chatMessages.scrollHeight;
    chatInput.focus();
  }
  setTimeout(updateGridLayout, 50);
}
function closePanel() {
  panelOpen = null; sidePanel.style.display = 'none';
  chatBtn.classList.remove('active'); peopleBtn.classList.remove('active');
  setTimeout(updateGridLayout, 50);
}
chatBtn.addEventListener('click', () => { panelOpen === 'chat' ? closePanel() : openPanel('chat'); });
peopleBtn.addEventListener('click', () => { panelOpen === 'participants' ? closePanel() : openPanel('participants'); });
document.querySelectorAll('.panel-tab').forEach(tab => { tab.addEventListener('click', () => openPanel(tab.dataset.panel)); });

function containsContactPii(text) {
  return extractContactMatches(text).length > 0;
}

function extractContactMatches(text) {
  if (!text) return [];
  const matches = [];
  const seen = new Set();
  const emailRe = /[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/g;
  let m;
  while ((m = emailRe.exec(text)) !== null) {
    const key = 'email:' + m[0];
    if (!seen.has(key)) { seen.add(key); matches.push({ type: 'email', value: m[0] }); }
  }
  const phoneRe = /(?:\+|00)?\d[\d\s().-]{7,18}\d/g;
  while ((m = phoneRe.exec(text)) !== null) {
    const v = m[0].trim();
    const digits = v.replace(/\D/g, '');
    if (digits.length >= 8 && digits.length <= 15) {
      const key = 'phone:' + digits;
      if (!seen.has(key)) { seen.add(key); matches.push({ type: 'phone', value: v }); }
    }
  }
  const linkRe = /(?:https?:\/\/)?(?:wa\.me\/[^\s]+|t\.me\/[^\s]+)|\b(?:whatsapp|telegram)\b/gi;
  while ((m = linkRe.exec(text)) !== null) {
    const v = m[0].trim();
    const key = 'link:' + v.toLowerCase();
    if (!seen.has(key)) { seen.add(key); matches.push({ type: 'messaging_link', value: v }); }
  }
  return matches;
}

function maskContactPiiLocal(text) {
  return String(text || '')
    .replace(/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/g, '[email blocked]')
    .replace(/(?:\+|00)?\d[\d\s().-]{7,18}\d/g, '[phone blocked]')
    .replace(/(?:https?:\/\/)?(?:wa\.me\/[^\s]+|t\.me\/[^\s]+)/gi, '[link blocked]')
    .replace(/\b(?:whatsapp|telegram)\b/gi, '[link blocked]');
}

function sendChat() {
  const msg = chatInput.value.trim(); if (!msg) return;
  if (contactGuardEnabled && containsContactPii(msg)) {
    // Server blocks relay + alerts admin + emits System message to sender
    socket.emit('chat-message', msg, MY_NAME);
    chatInput.value = '';
    return;
  }
  socket.emit('chat-message', msg, MY_NAME);
  appendChat(MY_NAME, msg, true);
  chatInput.value = '';
}
sendBtn.addEventListener('click', sendChat);
chatInput.addEventListener('keydown', e => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendChat(); } });

function appendChat(sender, msg, isOwn) {
  const welcome = chatMessages.querySelector('.chat-welcome'); if (welcome) welcome.remove();
  const d = document.createElement('div'); d.className = 'chat-msg' + (isOwn ? ' own' : '');
  const now = new Date();
  const t = now.getHours().toString().padStart(2,'0') + ':' + now.getMinutes().toString().padStart(2,'0');
  d.innerHTML = '<div class="chat-msg-header"><span class="chat-msg-name">' + escHtml(sender) + '</span><span class="chat-msg-time">' + t + '</span></div><div class="chat-msg-bubble">' + escHtml(msg) + '</div>';
  chatMessages.appendChild(d);
  chatMessages.scrollTop = chatMessages.scrollHeight;
}

const emojiPicker = document.getElementById('emoji-picker');
document.getElementById('emoji-btn').addEventListener('click', e => { e.stopPropagation(); emojiPicker.style.display = emojiPicker.style.display === 'none' ? 'flex' : 'none'; });
emojiPicker.querySelectorAll('span').forEach(em => { em.addEventListener('click', () => { chatInput.value += em.textContent; emojiPicker.style.display = 'none'; chatInput.focus(); }); });
document.addEventListener('click', () => { emojiPicker.style.display = 'none'; });

// ===== CHAT POPUP =====
let chatPopupTimeout;

function showChatPopup(sender, msg) {
  if (chatPopup) {
    chatPopup.classList.remove('hiding');
    chatPopupSender.textContent = sender;
    chatPopupMsg.textContent = msg;
    chatPopup.style.display = 'flex';
    if (chatPopupInput) chatPopupInput.focus();
    
    resetChatPopupTimeout();
  }
}

function resetChatPopupTimeout() {
  clearTimeout(chatPopupTimeout);
  chatPopupTimeout = setTimeout(closeChatPopup, 8000);
}

function closeChatPopup() {
  if (chatPopup) {
    chatPopup.classList.add('hiding');
    setTimeout(() => {
      chatPopup.style.display = 'none';
      chatPopup.classList.remove('hiding');
    }, 200);
  }
}

if (chatPopupClose) chatPopupClose.addEventListener('click', closeChatPopup);

function sendQuickReply() {
  if (!chatPopupInput) return;
  const msg = chatPopupInput.value.trim();
  if (!msg) return;
  if (contactGuardEnabled && containsContactPii(msg)) {
    socket.emit('chat-message', msg, MY_NAME);
    chatPopupInput.value = '';
    closeChatPopup();
    return;
  }
  socket.emit('chat-message', msg, MY_NAME);
  appendChat(MY_NAME, msg, true);
  chatPopupInput.value = '';
  closeChatPopup();
}

if (chatPopupSend) chatPopupSend.addEventListener('click', sendQuickReply);
if (chatPopupInput) {
  chatPopupInput.addEventListener('input', () => clearTimeout(chatPopupTimeout));
  chatPopupInput.addEventListener('keydown', e => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      sendQuickReply();
    }
  });
}

// ===== PARTICIPANTS =====
function addParticipantItem(id, name, isHost) {
  name = name || 'Guest';
  const item = document.createElement('div');
  item.className = 'participant-item';
  item.id = `pitem-${id}`;
  
  let hostActions = '';
  if (amIHost && id !== MY_ID) {
    hostActions = `
      <div style="display:flex; gap:4px; margin-left: auto; flex-wrap: wrap; justify-content: flex-end;">
        <button onclick="socket.emit('host-action', {target:'${id}', action:'mute'})" style="background:rgba(255,255,255,0.1); border:1px solid var(--border); color:var(--text); border-radius:4px; padding:2px 4px; font-size:10px; cursor:pointer;" title="Mute Mic">Mute</button>
        <button onclick="socket.emit('host-action', {target:'${id}', action:'turn-off-cam'})" style="background:rgba(255,255,255,0.1); border:1px solid var(--border); color:var(--text); border-radius:4px; padding:2px 4px; font-size:10px; cursor:pointer;" title="Turn Off Camera">Cam Off</button>
        <button onclick="socket.emit('host-action', {target:'${id}', action:'remove'})" style="background:var(--danger); border:none; color:white; border-radius:4px; padding:2px 4px; font-size:10px; cursor:pointer;" title="Remove User">Kick</button>
      </div>
    `;
  }
  
  item.innerHTML = '<div class="p-avatar" style="background:' + colorForId(id) + '">' + name.charAt(0).toUpperCase() + '</div><div class="p-name" style="' + (amIHost && id !== MY_ID ? 'flex:0 auto; margin-right:8px;' : '') + '">' + escHtml(name) + (id === MY_ID ? ' (You)' : '') + '</div><div class="p-badges">' + (isHost ? '<span class="p-badge host">HOST</span>' : '') + '<span class="p-badge muted" id="pmic-' + id + '" style="opacity:0.3"></span></div>' + hostActions;
  participantsList.appendChild(item);
  updateParticipantCount();
}
function removeParticipantItem(id) { const el = document.getElementById('pitem-' + id); if (el) el.remove(); updateParticipantCount(); }

// ===== HOST CONTROLS / WAITING ROOM =====
const hostControls = document.getElementById('host-controls');
if (hostControls && amIHost) hostControls.style.display = 'block';

function updateLockButton() {
  const btn = document.getElementById('lock-room-btn');
  if (!btn) return;
  btn.textContent = roomLocked ? 'Unlock class' : 'Lock class';
  btn.style.background = roomLocked ? 'rgba(244,63,94,0.15)' : 'rgba(255,255,255,0.08)';
  btn.style.borderColor = roomLocked ? 'rgba(244,63,94,0.35)' : 'var(--border)';
  btn.style.color = roomLocked ? '#fb7185' : 'var(--text)';
}

function renderWaitingList(list) {
  const wrap = document.getElementById('waiting-wrap');
  const el = document.getElementById('waiting-list');
  if (!wrap || !el) return;
  if (!Array.isArray(list) || list.length === 0) {
    wrap.style.display = 'none';
    el.innerHTML = '';
    return;
  }
  wrap.style.display = 'block';
  if (amIHost) {
    showToast(list.length + ' student' + (list.length === 1 ? '' : 's') + ' waiting to join');
    if (panelOpen !== 'participants') openPanel('participants');
  }
  el.innerHTML = list.map((entry) => `
    <div style="display:flex; align-items:center; gap:6px; padding:6px 0; border-bottom:1px solid var(--border);">
      <span style="flex:1; font-size:12px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">${escHtml(entry.name || 'Guest')}</span>
      <button data-approve="${escHtml(entry.socketId)}" style="background:rgba(16,185,129,0.15); border:1px solid rgba(16,185,129,0.35); color:#34d399; border-radius:5px; padding:3px 7px; font-size:11px; cursor:pointer;">Admit</button>
      <button data-deny="${escHtml(entry.socketId)}" style="background:rgba(244,63,94,0.12); border:1px solid rgba(244,63,94,0.3); color:#fb7185; border-radius:5px; padding:3px 7px; font-size:11px; cursor:pointer;">Deny</button>
    </div>
  `).join('');
  el.querySelectorAll('[data-approve]').forEach((btn) => {
    btn.addEventListener('click', () => socket.emit('waiting-approve', { socketId: btn.getAttribute('data-approve') }));
  });
  el.querySelectorAll('[data-deny]').forEach((btn) => {
    btn.addEventListener('click', () => socket.emit('waiting-deny', { socketId: btn.getAttribute('data-deny') }));
  });
}

const muteAllBtn = document.getElementById('mute-all-btn');
if (muteAllBtn) muteAllBtn.addEventListener('click', () => {
  socket.emit('host-action', { target: 'all', action: 'mute' });
  showToast('Muted all participants');
});
const lockRoomBtn = document.getElementById('lock-room-btn');
if (lockRoomBtn) lockRoomBtn.addEventListener('click', () => {
  socket.emit('set-lock', { locked: !roomLocked });
});
const admitAllBtn = document.getElementById('admit-all-btn');
if (admitAllBtn) admitAllBtn.addEventListener('click', () => {
  socket.emit('waiting-admit-all');
  showToast('Admitted all waiting students');
});
updateLockButton();
if (amIHost) socket.emit('waiting-list-request');

// ===== EXTRA CONTROLS =====
document.getElementById('fullscreen-btn').addEventListener('click', () => { if (!document.fullscreenElement) document.documentElement.requestFullscreen(); else document.exitFullscreen(); });

// ===== INFO PANEL =====
document.getElementById('info-btn').addEventListener('click', e => { e.stopPropagation(); infoPanel.style.display = infoPanel.style.display === 'none' ? 'block' : 'none'; });
document.getElementById('close-info').addEventListener('click', () => { infoPanel.style.display = 'none'; });
function copyLink() {
  const cleanUrl = new URL(window.location.href);
  cleanUrl.searchParams.delete('name');
  navigator.clipboard.writeText(cleanUrl.toString()).then(() => showToast('Link copied!'));
}
document.getElementById('copy-link-btn').addEventListener('click', copyLink);
document.getElementById('copy-link-full').addEventListener('click', copyLink);

// ===== LAYOUT TOGGLE =====
layoutBtn.addEventListener('click', () => { layout = layout === 'grid' ? 'spotlight' : 'grid'; layoutBtn.classList.toggle('active', layout === 'spotlight'); updateGridLayout(); showToast(layout === 'spotlight' ? 'Spotlight view' : 'Grid view'); });

const pipBtn = document.getElementById('pip-btn');
if (pipBtn) {
  if (!document.pictureInPictureEnabled) {
    pipBtn.style.display = 'none';
  } else {
    let pipCanvas, pipVideo, pipCtx, pipRaf;
    pipBtn.addEventListener('click', async () => {
      try {
        if (document.pictureInPictureElement) {
          await document.exitPictureInPicture();
          pipBtn.classList.remove('active');
        } else {
          if (!pipCanvas) {
            pipCanvas = document.createElement('canvas');
            pipCanvas.width = 1280;
            pipCanvas.height = 720;
            pipCtx = pipCanvas.getContext('2d');
            pipVideo = document.createElement('video');
            pipVideo.muted = true;
            pipVideo.style.opacity = '0';
            pipVideo.style.position = 'absolute';
            pipVideo.style.pointerEvents = 'none';
            document.body.appendChild(pipVideo);
            if (pipCanvas.captureStream) {
               pipVideo.srcObject = pipCanvas.captureStream(30);
            }
            await pipVideo.play();
          }

          function drawPip() {
             if (document.pictureInPictureElement !== pipVideo) return;
             
             pipCtx.fillStyle = '#12131f';
             pipCtx.fillRect(0, 0, pipCanvas.width, pipCanvas.height);
             
             const tiles = Array.from(document.querySelectorAll('.video-tile'));
             const pinned = document.querySelector('.video-tile.pinned');
             
             if (layout === 'spotlight' && pinned) {
                 const others = tiles.filter(t => t !== pinned);
                 const v = pinned.querySelector('video');
                 if (v && v.readyState >= 2 && v.style.display !== 'none') {
                     pipCtx.drawImage(v, 0, 0, pipCanvas.width * 0.75, pipCanvas.height);
                 }
                 const w = pipCanvas.width * 0.25;
                 const h = pipCanvas.height / (others.length || 1);
                 others.forEach((tile, i) => {
                     const ov = tile.querySelector('video');
                     if (ov && ov.readyState >= 2 && ov.style.display !== 'none') {
                         pipCtx.drawImage(ov, pipCanvas.width * 0.75, i * h, w, h);
                     }
                 });
             } else if (tiles.length > 0) {
                 const cols = Math.ceil(Math.sqrt(tiles.length));
                 const rows = Math.ceil(tiles.length / cols);
                 const w = pipCanvas.width / cols;
                 const h = pipCanvas.height / rows;
                 
                 tiles.forEach((tile, i) => {
                     const v = tile.querySelector('video');
                     const dx = (i % cols) * w;
                     const dy = Math.floor(i / cols) * h;
                     
                     if (v && v.readyState >= 2 && v.style.display !== 'none') {
                         pipCtx.drawImage(v, dx, dy, w, h);
                     }
                 });
             }
             
             pipRaf = requestAnimationFrame(drawPip);
          }

          await pipVideo.requestPictureInPicture();
          pipBtn.classList.add('active');
          drawPip();
          
          pipVideo.addEventListener('leavepictureinpicture', () => {
             pipBtn.classList.remove('active');
             cancelAnimationFrame(pipRaf);
          }, { once: true });
        }
      } catch (err) {
        console.error('PiP error:', err);
        showToast('Picture-in-Picture failed.');
      }
    });
  }
}

// ===== HELPERS =====
function showNotification(text) {
  const n = document.createElement('div'); n.className = 'notification'; n.textContent = text;
  document.getElementById('notifications').appendChild(n);
  setTimeout(() => n.remove(), 4500);
}
let toastTimeout;
function showToast(msg) { toast.textContent = msg; toast.style.display = 'block'; clearTimeout(toastTimeout); toastTimeout = setTimeout(() => { toast.style.display = 'none'; }, 3000); }
function sendPushNotification(title, body) {
  if ('Notification' in window && Notification.permission === 'granted') {
    const n = new Notification(title, {
      body,
      icon: contactGuardEnabled
        ? '/brand/younimatiq/favicon-32x32.png?v=20260918'
        : '/brand/vjmeet/favicon-32x32.png?v=20260918',
    });
    n.onclick = () => { window.focus(); n.close(); };
    setTimeout(() => n.close(), 5000);
  }
}
function escHtml(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

// ===== DEVICE SETTINGS =====
const settingsModal = document.getElementById('settings-modal');
const micSelect = document.getElementById('mic-select');
const speakerSelect = document.getElementById('speaker-select');
const camSelect = document.getElementById('cam-select');
let currentMicId = 'default';
let currentSpeakerId = 'default';
let currentCamId = 'default';

async function populateDeviceSettings() {
  if (!navigator.mediaDevices || !navigator.mediaDevices.enumerateDevices) return;
  try {
    const devices = await navigator.mediaDevices.enumerateDevices();
    micSelect.innerHTML = '';
    speakerSelect.innerHTML = '';
    camSelect.innerHTML = '';
    
    let hasMic = false;
    let hasSpeaker = false;
    let hasCam = false;

    devices.forEach(device => {
      const option = document.createElement('option');
      option.value = device.deviceId;
      if (device.kind === 'audioinput') {
        option.text = device.label || `Microphone ${micSelect.length + 1}`;
        if (device.deviceId === currentMicId) option.selected = true;
        micSelect.appendChild(option);
        hasMic = true;
      } else if (device.kind === 'audiooutput') {
        option.text = device.label || `Speaker ${speakerSelect.length + 1}`;
        if (device.deviceId === currentSpeakerId) option.selected = true;
        speakerSelect.appendChild(option);
        hasSpeaker = true;
      } else if (device.kind === 'videoinput') {
        option.text = device.label || `Camera ${camSelect.length + 1}`;
        if (device.deviceId === currentCamId) option.selected = true;
        camSelect.appendChild(option);
        hasCam = true;
      }
    });
    
    if (!hasSpeaker) {
      speakerSelect.innerHTML = '<option value="default">Default Speaker</option>';
      speakerSelect.disabled = true;
    } else {
      speakerSelect.disabled = false;
    }
  } catch (e) {
    console.error('Error enumerating devices', e);
  }
}

function openSettings() {
  populateDeviceSettings();
  if (settingsModal) settingsModal.style.display = 'flex';
}

if (document.getElementById('mic-chevron')) document.getElementById('mic-chevron').addEventListener('click', openSettings);
if (document.getElementById('cam-chevron')) document.getElementById('cam-chevron').addEventListener('click', openSettings);
if (document.getElementById('close-settings')) document.getElementById('close-settings').addEventListener('click', () => { settingsModal.style.display = 'none'; });

async function switchDevice(kind, deviceId) {
  if (kind === 'audiooutput') {
    currentSpeakerId = deviceId;
    document.querySelectorAll('.video-tile video').forEach(video => {
      if (typeof video.setSinkId === 'function' && !video.muted) {
        video.setSinkId(deviceId).catch(e => console.warn('setSinkId error', e));
      }
    });
    showToast('Speaker changed');
    return;
  }

  // Handle mic and camera switch
  const constraints = {
    video: isCamOn ? videoConstraints({ deviceId: currentCamId !== 'default' ? { exact: currentCamId } : undefined }) : false,
    audio: isMicOn ? { ...MEDIA_AUDIO, deviceId: currentMicId !== 'default' ? { exact: currentMicId } : undefined } : false
  };

  if (kind === 'audioinput') {
    currentMicId = deviceId;
    constraints.audio.deviceId = deviceId !== 'default' ? { exact: deviceId } : undefined;
    if (!isMicOn) { constraints.audio = { deviceId: deviceId !== 'default' ? { exact: deviceId } : undefined }; } 
  } else if (kind === 'videoinput') {
    currentCamId = deviceId;
    constraints.video.deviceId = deviceId !== 'default' ? { exact: deviceId } : undefined;
    if (!isCamOn) { constraints.video = { deviceId: deviceId !== 'default' ? { exact: deviceId } : undefined }; }
  }

  try {
    const newStream = await navigator.mediaDevices.getUserMedia(constraints);
    
    if (kind === 'audioinput') {
      const newAudioTrack = newStream.getAudioTracks()[0];
      if (newAudioTrack) {
        newAudioTrack.enabled = isMicOn;
        const oldAudioTrack = localStream.getAudioTracks()[0];
        if (oldAudioTrack) {
          localStream.removeTrack(oldAudioTrack);
          oldAudioTrack.stop();
        }
        localStream.addTrack(newAudioTrack);
        
        Object.values(peers).forEach(pc => {
          const sender = pc.getSenders().find(s => s.track && s.track.kind === 'audio');
          if (sender) sender.replaceTrack(newAudioTrack);
        });
      }
      showToast('Microphone changed');
    } else if (kind === 'videoinput') {
      const newVideoTrack = newStream.getVideoTracks()[0];
      if (newVideoTrack) {
        newVideoTrack.enabled = isCamOn;
        const oldVideoTrack = localStream.getVideoTracks()[0];
        if (oldVideoTrack) {
          localStream.removeTrack(oldVideoTrack);
          oldVideoTrack.stop();
        }
        localStream.addTrack(newVideoTrack);
        
        const tile = document.getElementById('tile-' + MY_ID);
        if (tile) {
          tile.querySelector('video').srcObject = localStream;
        }

        if (!isScreenSharing) {
          Object.values(peers).forEach(pc => {
            const sender = pc.__videoSender || pc.getSenders().find(s => s.track && s.track.kind === 'video');
            if (sender) sender.replaceTrack(newVideoTrack);
          });
        }
      }
      showToast('Camera changed');
    }
  } catch (err) {
    console.error('Failed to switch device', err);
    showToast('Failed to switch device');
  }
}

if (micSelect) micSelect.addEventListener('change', (e) => switchDevice('audioinput', e.target.value));
if (speakerSelect) speakerSelect.addEventListener('change', (e) => switchDevice('audiooutput', e.target.value));
if (camSelect) camSelect.addEventListener('change', (e) => switchDevice('videoinput', e.target.value));

// ===== CAPTIONS (CC) + SPEECH CONTACT GUARD =====
const ccBtn = document.getElementById('cc-btn');
const captionsOverlay = document.getElementById('captions-overlay');
const captionNameEl = document.getElementById('caption-name');
const captionTextEl = document.getElementById('caption-text');

function showCaptionLine(name, text) {
  if (!captionsOverlay || !captionTextEl || !captionsEnabled) return;
  captionsOverlay.style.display = 'block';
  if (captionNameEl) captionNameEl.textContent = name || '';
  captionTextEl.textContent = text || '';
  clearTimeout(lastCaptionHideTimer);
  lastCaptionHideTimer = setTimeout(() => {
    if (captionsOverlay) captionsOverlay.style.display = 'none';
  }, 4500);
}

function setCaptionsEnabled(on) {
  captionsEnabled = !!on;
  if (ccBtn) {
    ccBtn.classList.toggle('active', captionsEnabled);
    ccBtn.setAttribute('aria-pressed', captionsEnabled ? 'true' : 'false');
    ccBtn.title = captionsEnabled ? 'Turn off captions' : 'Turn on captions (CC)';
  }
  if (!captionsEnabled && captionsOverlay) captionsOverlay.style.display = 'none';
  socket.emit('caption-preference', { enabled: captionsEnabled });
  syncSpeechWatch();
  showToast(captionsEnabled ? 'Captions on — others’ speech will appear here' : 'Captions off');
}

if (ccBtn) {
  ccBtn.setAttribute('aria-pressed', 'false');
  ccBtn.addEventListener('click', () => setCaptionsEnabled(!captionsEnabled));
}

socket.on('captions-demand', (payload) => {
  captionsDemanded = !!(payload && payload.active);
  syncSpeechWatch();
});

socket.on('caption', (payload) => {
  if (!payload || !payload.text) return;
  if (!captionsEnabled) return;
  showCaptionLine(payload.senderName || 'Participant', payload.text);
});

socket.on('speech-blocked', () => {
  showToast('Contact info detected in speech — admin alerted');
});

socket.on('chat-blocked', () => {
  // Server already sent system chat message
});

function shouldShareCaptions() {
  return captionsEnabled || captionsDemanded;
}

function shouldRunSpeechWatch() {
  // Share speech text when: own CC on, someone else wants CC, or platform contact guard
  return isMicOn && mediaStarted && (contactGuardEnabled || shouldShareCaptions());
}

function stopSpeechWatch() {
  speechWatchActive = false;
  clearTimeout(speechRestartTimer);
  if (speechRecognition) {
    try { speechRecognition.onend = null; speechRecognition.onerror = null; speechRecognition.onresult = null; speechRecognition.stop(); } catch (_) {}
    speechRecognition = null;
  }
}

function syncSpeechWatch() {
  if (shouldRunSpeechWatch()) startSpeechWatch();
  else stopSpeechWatch();
}

let speechUnsupportedWarned = false;
function startSpeechWatch() {
  const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
  if (!SR) {
    if (!speechUnsupportedWarned && (captionsEnabled || contactGuardEnabled)) {
      speechUnsupportedWarned = true;
      showToast(captionsEnabled
        ? 'Captions need Chrome or Edge'
        : 'Speech contact monitoring needs Chrome or Edge (chat is still protected)');
    }
    return;
  }
  if (speechWatchActive && speechRecognition) return;
  stopSpeechWatch();
  speechWatchActive = true;
  const rec = new SR();
  speechRecognition = rec;
  rec.continuous = true;
  rec.interimResults = true;
  rec.lang = navigator.language || 'en-US';
  rec.maxAlternatives = 1;

  rec.onresult = (event) => {
    let interim = '';
    let finalText = '';
    for (let i = event.resultIndex; i < event.results.length; i++) {
      const r = event.results[i];
      if (r.isFinal) finalText += r[0].transcript;
      else interim += r[0].transcript;
    }
    const share = shouldShareCaptions();
    if (interim && share) {
      const display = (contactGuardEnabled && containsContactPii(interim)) ? maskContactPiiLocal(interim) : interim;
      if (captionsEnabled) showCaptionLine(MY_NAME || 'You', display);
      socket.emit('caption-interim', {
        userId: MY_ID,
        senderName: MY_NAME,
        text: interim,
        shareCaptions: share,
      });
    }
    if (finalText.trim()) {
      const text = finalText.trim();
      const hasPii = contactGuardEnabled && containsContactPii(text);
      if (captionsEnabled) {
        showCaptionLine(MY_NAME || 'You', hasPii ? maskContactPiiLocal(text) : text);
      }
      socket.emit('caption-final', {
        userId: MY_ID,
        senderName: MY_NAME,
        text,
        shareCaptions: share,
        source: share ? 'captions' : 'speech',
      });
      // Toast comes from server speech-blocked event to avoid duplicates
    }
  };

  rec.onerror = (e) => {
    if (e.error === 'not-allowed') {
      showToast('Microphone permission needed for captions');
      stopSpeechWatch();
    }
  };

  rec.onend = () => {
    if (!speechWatchActive) return;
    speechRestartTimer = setTimeout(() => {
      if (!shouldRunSpeechWatch()) return;
      try { rec.start(); } catch (_) {
        speechWatchActive = false;
        startSpeechWatch();
      }
    }, 250);
  };

  try { rec.start(); } catch (e) { console.warn('Speech recognition start failed', e); }
}
