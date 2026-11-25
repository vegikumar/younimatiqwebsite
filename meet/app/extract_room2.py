import json

with open('/home/vjnan/.gemini/antigravity-ide/brain/29873639-7a0c-4428-b88d-47f63560532c/.system_generated/logs/transcript_full.jsonl', 'r') as f:
    for line in f:
        data = json.loads(line)
        if data.get('type') == 'TOOL_RESPONSE':
            content = data.get('content', '')
            if '===== WEBRTC =====' in content:
                print(f"Found WEBRTC section! Length: {len(content)}")
                with open('found_room2.txt', 'a') as outf:
                    outf.write(content + "\n\n---\n\n")

print("done")
