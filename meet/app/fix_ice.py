import re

with open('public/js/room.js', 'r') as f:
    content = f.read()

old_offer_block = r"await peer\.setRemoteDescription\(payload\.sdp\);\s*const answer = await peer\.createAnswer\(\);"
new_offer_block = """await peer.setRemoteDescription(payload.sdp);
    if (peer.iceQueue) {
      for (let cand of peer.iceQueue) { await peer.addIceCandidate(cand).catch(console.error); }
      peer.iceQueue = [];
    }
    const answer = await peer.createAnswer();"""
content = re.sub(old_offer_block, new_offer_block, content)

old_answer_block = r"await peer\.setRemoteDescription\(payload\.sdp\)\.catch\(e=>console\.error\(e\)\);"
new_answer_block = """await peer.setRemoteDescription(payload.sdp).catch(e=>console.error(e));
    if (peer.iceQueue) {
      for (let cand of peer.iceQueue) { await peer.addIceCandidate(cand).catch(console.error); }
      peer.iceQueue = [];
    }"""
content = re.sub(old_answer_block, new_answer_block, content)

old_ice_block = r"socket\.on\('ice-candidate', async \(payload\) => \{.*?await peer\.addIceCandidate\(payload\.candidate\)\.catch\(e => console\.error\(e\)\);\n  \}\n\}\);"
new_ice_block = """socket.on('ice-candidate', async (payload) => {
  const peer = peers[payload.caller];
  if (peer) {
    if (peer.remoteDescription && peer.remoteDescription.type) {
      await peer.addIceCandidate(payload.candidate).catch(e => console.error(e));
    } else {
      peer.iceQueue = peer.iceQueue || [];
      peer.iceQueue.push(payload.candidate);
    }
  }
});"""
content = re.sub(old_ice_block, new_ice_block, content, flags=re.DOTALL)

with open('public/js/room.js', 'w') as f:
    f.write(content)
print("done")
