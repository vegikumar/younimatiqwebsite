import re

with open('public/js/room.js', 'r') as f:
    content = f.read()

old_ontrack_block_regex = r'    const video = tile\.querySelector\(\'video\'\);.*?showVideo\(tile\);\n  \};'
new_ontrack_block = """    const video = tile.querySelector('video');
    
    let stream = video.srcObject;
    if (!stream) {
      stream = new MediaStream();
      video.srcObject = stream;
    }
    
    // Add the new track to our custom MediaStream
    if (e.track && !stream.getTracks().includes(e.track)) {
      stream.addTrack(e.track);
    }
    
    // Force the video element to re-evaluate the tracks (fixes black screen bug)
    video.srcObject = stream;
    
    video.play().catch(console.error);
    video.onloadedmetadata = () => {
      video.play().catch(console.error);
    };
    showVideo(tile);
  };"""

content = re.sub(old_ontrack_block_regex, new_ontrack_block, content, flags=re.DOTALL)

with open('public/js/room.js', 'w') as f:
    f.write(content)
print("done")
