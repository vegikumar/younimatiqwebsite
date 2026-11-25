import re

with open('public/js/room.js', 'r') as f:
    content = f.read()

# 1. Update user-connected to include callerName in offer
old_user_connected = """    socket.emit('offer', { target: userId, caller: MY_ID, sdp: offer });"""
new_user_connected = """    socket.emit('offer', { target: userId, caller: MY_ID, callerName: MY_NAME, sdp: offer });"""
content = content.replace(old_user_connected, new_user_connected)

# 2. Update offer handler to use callerName
old_offer = """socket.on('offer', async (payload) => {
  let peer = peers[payload.caller];
  if (!peer) {
    peer = createPeer(payload.caller);
    peers[payload.caller] = peer;
  }"""
new_offer = """socket.on('offer', async (payload) => {
  if (payload.callerName && !participants[payload.caller]) {
    addParticipantItem(payload.caller, payload.callerName, false);
  }
  let peer = peers[payload.caller];
  if (!peer) {
    peer = createPeer(payload.caller);
    peers[payload.caller] = peer;
  }"""
content = content.replace(old_offer, new_offer)

# 3. Update createPeer ontrack
old_ontrack_block_regex = r'peer\.ontrack = \(e\) => \{.*?\n  \};'
new_ontrack_block = """peer.ontrack = (e) => {
    let tile = document.getElementById('tile-' + userId);
    let peerName = participants[userId] ? participants[userId].name : "Participant";
    let isPeerHost = participants[userId] ? participants[userId].isHost : false;
    
    if (!tile) {
      tile = createTile(userId, peerName, false, isPeerHost);
      videoGrid.appendChild(tile);
      updateGridLayout();
    } else {
      // update name if it was "Participant"
      const nameDiv = tile.querySelector('.tile-no-video-name');
      if (nameDiv && peerName !== "Participant") nameDiv.textContent = peerName;
    }
    
    const video = tile.querySelector('video');
    if (e.streams && e.streams[0]) {
      if (video.srcObject !== e.streams[0]) {
        video.srcObject = e.streams[0];
      }
    } else {
      if (!video.srcObject) video.srcObject = new MediaStream();
      video.srcObject.addTrack(e.track);
    }
    
    video.onloadedmetadata = () => {
      video.play().catch(console.error);
    };
    showVideo(tile);
  };"""
content = re.sub(old_ontrack_block_regex, new_ontrack_block, content, flags=re.DOTALL)

with open('public/js/room.js', 'w') as f:
    f.write(content)
print("done")
