// Index page JS — room creation and joining logic

const nameModal = document.getElementById('name-modal');
const displayNameInput = document.getElementById('display-name-input');
const modalCancel = document.getElementById('modal-cancel');
const modalConfirm = document.getElementById('modal-confirm');
const roomIdInput = document.getElementById('room-id-input');

let pendingRoomId = null;
let pendingAction = null; // 'new' or 'join'
let pendingHostToken = null;

function showNameModal(roomId, action, hostToken = null) {
  pendingRoomId = roomId;
  pendingAction = action;
  pendingHostToken = hostToken;
  nameModal.style.display = 'flex';
  displayNameInput.value = localStorage.getItem('vjmeet-name') || '';
  displayNameInput.focus();
  displayNameInput.select();
}

function hideModal() {
  nameModal.style.display = 'none';
  pendingRoomId = null;
  pendingAction = null;
  pendingHostToken = null;
}

function proceedToRoom() {
  const name = displayNameInput.value.trim();
  if (!name) { displayNameInput.focus(); displayNameInput.style.borderColor = '#f43f5e'; return; }
  localStorage.setItem('vjmeet-name', name);

  const qs = new URLSearchParams({ name });

  if (pendingAction === 'new' && pendingHostToken) {
    localStorage.setItem('vjmeet-host-' + pendingRoomId, 'true');
    localStorage.setItem('vjmeet-host-token-' + pendingRoomId, pendingHostToken);
    qs.set('role', 'host');
    qs.set('host_token', pendingHostToken);
  }

  window.location.href = `/room/${pendingRoomId}?${qs.toString()}`;
}

// New meeting — creator gets host token so they are not stuck in "waiting for teacher"
async function createNewMeeting() {
  const res = await fetch('/api/room');
  const data = await res.json();
  showNameModal(data.roomId, 'new', data.host_token || null);
}

// Join existing
function joinMeeting() {
  const input = roomIdInput.value.trim();
  if (!input) { roomIdInput.focus(); return; }
  let roomId = input;
  try {
    const url = new URL(input);
    const parts = url.pathname.split('/');
    roomId = parts[parts.length - 1];
  } catch (_) {}
  showNameModal(roomId, 'join');
}

document.getElementById('new-meeting-btn').addEventListener('click', createNewMeeting);
document.getElementById('nav-new-meeting').addEventListener('click', createNewMeeting);
document.getElementById('cta-new-meeting').addEventListener('click', createNewMeeting);
document.getElementById('join-btn').addEventListener('click', joinMeeting);
roomIdInput.addEventListener('keydown', (e) => { if (e.key === 'Enter') joinMeeting(); });

modalCancel.addEventListener('click', hideModal);
modalConfirm.addEventListener('click', proceedToRoom);
displayNameInput.addEventListener('keydown', (e) => { if (e.key === 'Enter') proceedToRoom(); });
displayNameInput.addEventListener('input', () => { displayNameInput.style.borderColor = ''; });
nameModal.addEventListener('click', (e) => { if (e.target === nameModal) hideModal(); });

const urlParams = new URLSearchParams(window.location.search);
const joinParam = urlParams.get('join');
if (joinParam) {
  showNameModal(joinParam, 'join');
}
