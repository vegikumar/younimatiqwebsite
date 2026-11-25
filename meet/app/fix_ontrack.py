import re

with open('public/js/room.js', 'r') as f:
    content = f.read()

new_ontrack = """  peer.ontrack = (e) => {
    let tile = document.getElementById('tile-' + userId);
    if (!tile) {
      tile = createTile(userId, "Participant", false, false);
      videoGrid.appendChild(tile);
      updateGridLayout();
    }
    const video = tile.querySelector('video');
    if (!video.srcObject) {
      video.srcObject = new MediaStream();
    }
    video.srcObject.addTrack(e.track);
    showVideo(tile);
  };"""

content = re.sub(r'  peer\.ontrack = \(e\) => \{.*?\n  \};\n', new_ontrack + '\n', content, flags=re.DOTALL)

with open('public/js/room.js', 'w') as f:
    f.write(content)
print("done")
