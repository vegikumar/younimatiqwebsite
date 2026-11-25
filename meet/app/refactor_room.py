import re

with open('public/js/room.js', 'r') as f:
    content = f.read()

# 1. Add livekit room variable
content = content.replace("const socket = io();", "const socket = io();\nlet livekitRoom = null;\n")

# 2. Modify joinRoom to fetch token and connect LiveKit
join_room_new = """async function joinRoom() {
  socket.emit('join-room', ROOM_ID, MY_ID, MY_NAME, amIHost);
  hasJoinedRoom = true;
  
  try {
    const res = await fetch('/api/token', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ roomName: ROOM_ID, participantName: MY_NAME, isHost: amIHost })
    });
    const { token } = await res.json();
    
    livekitRoom = new LivekitClient.Room({
      adaptiveStream: true,
      dynacast: true,
      videoCaptureDefaults: {
        resolution: LivekitClient.VideoPresets.h720,
      }
    });

    setupLiveKitEvents(livekitRoom);

    await livekitRoom.connect('wss://' + window.location.host + '/livekit/', token);
    
    // Publish local tracks
    if (localStream) {
       for (const track of localStream.getTracks()) {
         await livekitRoom.localParticipant.publishTrack(track);
       }
    }
  } catch (err) {
    console.error("Failed to connect to LiveKit", err);
    showToast("LiveKit connection failed");
  }
}

function setupLiveKitEvents(room) {
  room.on(LivekitClient.RoomEvent.TrackSubscribed, (track, publication, participant) => {
    let tile = document.getElementById('tile-' + participant.identity);
    if (!tile) {
      tile = createTile(participant.identity, participant.name || participant.identity, false, false);
      videoGrid.appendChild(tile);
      updateGridLayout();
      addParticipantItem(participant.identity, participant.name, false);
    }
    const video = tile.querySelector('video');
    if (track.kind === 'video' || track.kind === 'audio') {
      track.attach(video);
    }
    if (track.kind === 'video') showVideo(tile);
  });

  room.on(LivekitClient.RoomEvent.TrackUnsubscribed, (track, publication, participant) => {
    const tile = document.getElementById('tile-' + participant.identity);
    if (tile) {
      track.detach(tile.querySelector('video'));
      if (track.kind === 'video') showNoVideo(tile, participant.name);
    }
  });

  room.on(LivekitClient.RoomEvent.ParticipantDisconnected, (participant) => {
    const tile = document.getElementById('tile-' + participant.identity);
    if (tile) tile.remove();
    updateGridLayout();
    removeParticipantItem(participant.identity);
  });
  
  room.on(LivekitClient.RoomEvent.ParticipantConnected, (participant) => {
    addParticipantItem(participant.identity, participant.name, false);
  });
}
"""
content = re.sub(r'function joinRoom\(\) \{.*?\n\}', join_room_new, content, flags=re.DOTALL)

# 3. Remove P2P WebRTC section entirely (dataChannels, setupDataChannel, createPeer)
content = re.sub(r'// ===== WEBRTC =====.*?// ===== SOCKET EVENTS =====', '// ===== SOCKET EVENTS =====', content, flags=re.DOTALL)

# 4. Remove socket.on('offer', 'answer', 'ice-candidate')
content = re.sub(r'socket\.on\(\'offer\'.*?\n\}\);', '', content, flags=re.DOTALL)
content = re.sub(r'socket\.on\(\'answer\'.*?\n\}\);', '', content, flags=re.DOTALL)
content = re.sub(r'socket\.on\(\'ice-candidate\'.*?\n\}\);', '', content, flags=re.DOTALL)

# 5. Fix chat sending (remove dataChannels fallback)
chat_send_new = """function sendChat() {
  const msg = chatInput.value.trim(); if (!msg) return;
  socket.emit('chat-message', msg, MY_NAME);
  appendChat(MY_NAME, msg, true);
  chatInput.value = '';
}"""
content = re.sub(r'function sendChat\(\) \{.*?\n\}', chat_send_new, content, flags=re.DOTALL)

# 6. Mic/Camera/Screen toggles to use LiveKit
mic_toggle = """micBtn.addEventListener('click', async () => {
  isMicOn = !isMicOn;
  if (livekitRoom && livekitRoom.localParticipant) {
    await livekitRoom.localParticipant.setMicrophoneEnabled(isMicOn);
  }
  micBtn.classList.toggle('active', isMicOn);
  micBtn.classList.toggle('off', !isMicOn);
  micBtn.querySelector('.icon-on').style.display = isMicOn ? '' : 'none';
  micBtn.querySelector('.icon-off').style.display = isMicOn ? 'none' : '';
  socket.emit('toggle-audio', MY_ID, isMicOn);
});"""
content = re.sub(r'micBtn\.addEventListener\(\'click\', \(\) => \{.*?socket\.emit\(\'toggle-audio\'.*?\n\}\);', mic_toggle, content, flags=re.DOTALL)

cam_toggle = """cameraBtn.addEventListener('click', async () => {
  isCamOn = !isCamOn;
  if (livekitRoom && livekitRoom.localParticipant) {
    await livekitRoom.localParticipant.setCameraEnabled(isCamOn);
  }
  cameraBtn.classList.toggle('active', isCamOn);
  cameraBtn.classList.toggle('off', !isCamOn);
  cameraBtn.querySelector('.icon-on').style.display = isCamOn ? '' : 'none';
  cameraBtn.querySelector('.icon-off').style.display = isCamOn ? 'none' : '';
  const tile = document.getElementById('tile-' + MY_ID);
  if (tile) { isCamOn ? showVideo(tile) : showNoVideo(tile, MY_NAME); }
  socket.emit('toggle-video', MY_ID, isCamOn);
});"""
content = re.sub(r'cameraBtn\.addEventListener\(\'click\', \(\) => \{.*?socket\.emit\(\'toggle-video\'.*?\n\}\);', cam_toggle, content, flags=re.DOTALL)

screen_toggle = """screenBtn.addEventListener('click', async () => {
  if (isScreenSharing) { stopScreenShare(); return; }
  try {
    if (livekitRoom && livekitRoom.localParticipant) {
      await livekitRoom.localParticipant.setScreenShareEnabled(true);
      isScreenSharing = true;
      screenBtn.classList.add('active');
      socket.emit('screen-share', MY_ID, true);
    }
  } catch(e) { console.error(e); }
});

async function stopScreenShare() {
  if (livekitRoom && livekitRoom.localParticipant) {
    await livekitRoom.localParticipant.setScreenShareEnabled(false);
  }
  isScreenSharing = false;
  screenBtn.classList.remove('active');
  socket.emit('screen-share', MY_ID, false);
}"""
content = re.sub(r'screenBtn\.addEventListener\(\'click\', async \(\) => \{.*?function stopScreenShare\(\) \{.*?\n\}', screen_toggle, content, flags=re.DOTALL)

with open('public/js/room.js', 'w') as f:
    f.write(content)
print("done")
