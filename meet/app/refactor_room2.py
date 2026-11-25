import re

with open('public/js/room.js', 'r') as f:
    content = f.read()

# 1. Remove livekit room variable
content = content.replace("let livekitRoom = null;\n", "const peers = {};\nconst rtcConfig = { iceServers: [{ urls: 'stun:stun.l.google.com:19302' }, { urls: 'stun:global.stun.twilio.com:3478' }] };\n")

# 2. Modify joinRoom to use P2P
join_room_new = """async function joinRoom() {
  socket.emit('join-room', ROOM_ID, MY_ID, MY_NAME, amIHost);
  hasJoinedRoom = true;
  
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
      showVideo(myTile);
    }
  }
}

// ===== WEBRTC =====
socket.on('user-connected', async (userId, userName, isHost) => {
  addParticipantItem(userId, userName, isHost);
  const peer = createPeer(userId);
  peers[userId] = peer;
  try {
    const offer = await peer.createOffer();
    await peer.setLocalDescription(offer);
    socket.emit('offer', { target: userId, caller: MY_ID, sdp: offer });
  } catch(e) { console.error(e); }
});

socket.on('offer', async (payload) => {
  let peer = peers[payload.caller];
  if (!peer) {
    peer = createPeer(payload.caller);
    peers[payload.caller] = peer;
  }
  try {
    await peer.setRemoteDescription(payload.sdp);
    const answer = await peer.createAnswer();
    await peer.setLocalDescription(answer);
    socket.emit('answer', { target: payload.caller, caller: MY_ID, sdp: answer });
  } catch(e) { console.error(e); }
});

socket.on('answer', async (payload) => {
  const peer = peers[payload.caller];
  if (peer) {
    await peer.setRemoteDescription(payload.sdp).catch(e=>console.error(e));
  }
});

socket.on('ice-candidate', async (payload) => {
  const peer = peers[payload.caller];
  if (peer) {
    await peer.addIceCandidate(payload.candidate).catch(e => console.error(e));
  }
});

socket.on('user-disconnected', (userId) => {
  if (peers[userId]) {
    peers[userId].close();
    delete peers[userId];
  }
  const tile = document.getElementById('tile-' + userId);
  if (tile) tile.remove();
  updateGridLayout();
  removeParticipantItem(userId);
});

function createPeer(userId) {
  const peer = new RTCPeerConnection(rtcConfig);
  if (localStream) {
    localStream.getTracks().forEach(track => peer.addTrack(track, localStream));
  }
  peer.onicecandidate = (e) => {
    if (e.candidate) {
      socket.emit('ice-candidate', { target: userId, caller: MY_ID, candidate: e.candidate });
    }
  };
  peer.ontrack = (e) => {
    let tile = document.getElementById('tile-' + userId);
    if (!tile) {
      tile = createTile(userId, "Participant", false, false);
      videoGrid.appendChild(tile);
      updateGridLayout();
    }
    const video = tile.querySelector('video');
    if (video.srcObject !== e.streams[0]) {
      video.srcObject = e.streams[0];
      showVideo(tile);
    }
  };
  return peer;
}
"""
content = re.sub(r'async function joinRoom\(\) \{.*?\nfunction setupLiveKitEvents\(room\) \{.*?\n\}\n', join_room_new, content, flags=re.DOTALL)

# 3. Mic/Camera/Screen toggles
mic_toggle = """micBtn.addEventListener('click', async () => {
  isMicOn = !isMicOn;
  if (localStream) {
    localStream.getAudioTracks().forEach(t => t.enabled = isMicOn);
  }
  micBtn.classList.toggle('active', isMicOn);
  micBtn.classList.toggle('off', !isMicOn);
  micBtn.querySelector('.icon-on').style.display = isMicOn ? '' : 'none';
  micBtn.querySelector('.icon-off').style.display = isMicOn ? 'none' : '';
  socket.emit('toggle-audio', MY_ID, isMicOn);
});"""
content = re.sub(r'micBtn\.addEventListener\(\'click\', async \(\) => \{.*?socket\.emit\(\'toggle-audio\', MY_ID, isMicOn\);\n\}\);', mic_toggle, content, flags=re.DOTALL)

cam_toggle = """cameraBtn.addEventListener('click', async () => {
  isCamOn = !isCamOn;
  if (localStream) {
    localStream.getVideoTracks().forEach(t => t.enabled = isCamOn);
  }
  cameraBtn.classList.toggle('active', isCamOn);
  cameraBtn.classList.toggle('off', !isCamOn);
  cameraBtn.querySelector('.icon-on').style.display = isCamOn ? '' : 'none';
  cameraBtn.querySelector('.icon-off').style.display = isCamOn ? 'none' : '';
  const tile = document.getElementById('tile-' + MY_ID);
  if (tile) { isCamOn ? showVideo(tile) : showNoVideo(tile, MY_NAME); }
  socket.emit('toggle-video', MY_ID, isCamOn);
});"""
content = re.sub(r'cameraBtn\.addEventListener\(\'click\', async \(\) => \{.*?socket\.emit\(\'toggle-video\', MY_ID, isCamOn\);\n\}\);', cam_toggle, content, flags=re.DOTALL)

screen_toggle = """screenBtn.addEventListener('click', async () => {
  if (isScreenSharing) { stopScreenShare(); return; }
  try {
    const stream = await navigator.mediaDevices.getDisplayMedia({ video: true });
    const screenTrack = stream.getVideoTracks()[0];
    screenTrack.onended = () => stopScreenShare();
    
    for (const userId in peers) {
      const sender = peers[userId].getSenders().find(s => s.track && s.track.kind === 'video');
      if (sender) sender.replaceTrack(screenTrack);
    }
    const myTile = document.getElementById('tile-' + MY_ID);
    if (myTile) myTile.querySelector('video').srcObject = stream;
    
    isScreenSharing = true;
    screenBtn.classList.add('active');
    socket.emit('screen-share', MY_ID, true);
  } catch(e) { console.error(e); }
});

async function stopScreenShare() {
  if (!isScreenSharing) return;
  const myTile = document.getElementById('tile-' + MY_ID);
  const videoTrack = localStream ? localStream.getVideoTracks()[0] : null;
  if (myTile && localStream) myTile.querySelector('video').srcObject = localStream;
  
  for (const userId in peers) {
    const sender = peers[userId].getSenders().find(s => s.track && s.track.kind === 'video');
    if (sender && videoTrack) sender.replaceTrack(videoTrack);
  }
  isScreenSharing = false;
  screenBtn.classList.remove('active');
  socket.emit('screen-share', MY_ID, false);
}"""
content = re.sub(r'screenBtn\.addEventListener\(\'click\', async \(\) => \{.*?function stopScreenShare\(\) \{.*?\n\}', screen_toggle, content, flags=re.DOTALL)

with open('public/js/room.js', 'w') as f:
    f.write(content)
print("done")
