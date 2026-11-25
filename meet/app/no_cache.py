import re

with open('server.js', 'r') as f:
    content = f.read()

old_route = r"app\.get\('/room/:room', \(req, res\) => \{\n    res\.sendFile\(path\.join\(__dirname, 'public', 'room\.html'\)\);\n\}\);"
new_route = """app.get('/room/:room', (req, res) => {
    res.setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, proxy-revalidate');
    res.setHeader('Pragma', 'no-cache');
    res.setHeader('Expires', '0');
    res.sendFile(path.join(__dirname, 'public', 'room.html'));
});"""

content = re.sub(old_route, new_route, content)

with open('server.js', 'w') as f:
    f.write(content)
print("done")
