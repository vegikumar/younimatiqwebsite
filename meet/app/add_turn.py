import re

with open('public/js/room.js', 'r') as f:
    content = f.read()

old_rtc = r"const rtcConfig = \{ iceServers: \[\{ urls: 'stun:stun\.l\.google\.com:19302' \}, \{ urls: 'stun:global\.stun\.twilio\.com:3478' \}\] \};"
new_rtc = """const rtcConfig = { 
  iceServers: [
    { urls: 'stun:stun.l.google.com:19302' },
    { urls: 'stun:global.stun.twilio.com:3478' },
    { 
      urls: 'turn:openrelay.metered.ca:80', 
      username: 'openrelayproject', 
      credential: 'openrelayproject' 
    },
    { 
      urls: 'turn:openrelay.metered.ca:443', 
      username: 'openrelayproject', 
      credential: 'openrelayproject' 
    }
  ] 
};"""

content = re.sub(old_rtc, new_rtc, content)

with open('public/js/room.js', 'w') as f:
    f.write(content)
print("done")
