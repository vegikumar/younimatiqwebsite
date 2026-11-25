#!/usr/bin/env bash
# Build a small Material Symbols subset from every icon string used in the app.
# Scans JSX/JS/blade including ternary arms (expand_less / expand_more).
# Keeps ~40–80KB instead of the full ~4MB variable font.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"
mkdir -p public/fonts/material-symbols

python3 - <<'PY'
import re, pathlib, urllib.parse, urllib.request

roots = [
    pathlib.Path('resources/js'),
    pathlib.Path('resources/views'),
]
names = set()
patterns = [
    re.compile(r"""Icon\s+name=["']([a-z0-9_]+)["']"""),
    re.compile(r"""Icon\s+name=\{["']([a-z0-9_]+)["']\}"""),
    re.compile(r"""name=\{[^}]*\?["']([a-z0-9_]+)["']\s*:\s*["']([a-z0-9_]+)["']"""),
    re.compile(r"""\?["']([a-z0-9_]+)["']\s*:\s*["']([a-z0-9_]+)["']"""),
    re.compile(r"""icon:\s*["']([a-z0-9_]+)["']"""),
    re.compile(r"""icon\s*=\s*["']([a-z0-9_]+)["']"""),
    re.compile(r"""material-symbols-outlined[^>]*>\s*([a-z0-9_]+)\s*<"""),
    re.compile(r"""['"](expand_less|expand_more|analytics|monitoring|dashboard|notifications)['"]"""),
]

for root in roots:
    if not root.exists():
        continue
    for p in root.rglob('*'):
        if p.suffix not in {'.js', '.jsx', '.ts', '.tsx', '.blade.php', '.php', '.vue'}:
            continue
        try:
            text = p.read_text(errors='ignore')
        except Exception:
            continue
        for pat in patterns:
            for m in pat.finditer(text):
                for g in m.groups():
                    if g and re.fullmatch(r'[a-z0-9_]+', g):
                        names.add(g)

# Always include critical shell / KPI icons (ternaries + defaults)
names.update({
    'expand_less', 'expand_more', 'menu', 'close', 'search', 'logout',
    'dashboard', 'analytics', 'monitoring', 'notifications', 'notifications_active',
    'group', 'groups', 'person', 'school', 'event', 'payments', 'settings',
    'chat', 'campaign', 'trending_up', 'schedule', 'quiz', 'assignment',
    'videocam', 'check', 'error', 'warning', 'info', 'add', 'edit', 'delete',
    'arrow_back', 'arrow_forward', 'chevron_left', 'chevron_right',
    'admin_panel_settings', 'family_restroom', 'rocket_launch', 'workspace_premium',
    # Career Explorer + category icons often live only in the DB
    'palette', 'precision_manufacturing', 'code', 'science', 'smart_toy',
    'wb_sunny', 'psychology', 'sports_esports', 'videogame_asset', 'self_improvement',
    'emoji_events', 'favorite', 'share', 'video_library', 'rate_review', 'forum',
    'celebration', 'verified_user', 'folder', 'folder_open', 'person_check',
    'alt_route', 'calendar_month', 'fact_check', 'package_2', 'badge',
    'handshake', 'contact_page', 'how_to_reg', 'menu_book', 'smart_display',
    'shield_person', 'category', 'group_work', 'stars', 'reviews', 'article',
    'web', 'help_center', 'local_offer', 'dashboard_customize', 'sentiment_satisfied',
    'mail', 'history', 'hub', 'bar_chart',
})

# Pull icons stored in DB (career pathways, categories)
try:
    import subprocess, json
    raw = subprocess.check_output(
        ['php', 'artisan', 'tinker', '--execute',
         r'$out=[]; foreach (["career_pathways"=>"icon","categories"=>"icon"] as $t=>$c) { if (!Schema::hasTable($t)) continue; foreach (DB::table($t)->whereNotNull($c)->distinct()->pluck($c) as $v) { if (is_string($v) && preg_match("/^[a-z0-9_]+$/", $v)) $out[]=$v; } } echo json_encode(array_values(array_unique($out)));'],
        cwd=str(pathlib.Path('.').resolve()),
        timeout=60,
        stderr=subprocess.DEVNULL,
    ).decode()
    m = re.search(r'\[[^\]]*\]', raw)
    if m:
        for n in json.loads(m.group(0)):
            if isinstance(n, str) and re.fullmatch(r'[a-z0-9_]+', n):
                names.add(n)
        print(f'Added DB icons: {m.group(0)}')
except Exception as e:
    print(f'DB icon scan skipped: {e}')

# Drop obvious non-icons
deny = {
    'required', 'password', 'email', 'string', 'number', 'boolean', 'array',
    'default', 'primary', 'secondary', 'success', 'neutral', 'tertiary',
    'pending', 'active', 'cancelled', 'completed', 'student', 'tutor', 'admin',
    'create', 'update', 'index', 'show', 'store', 'destroy', 'true', 'false',
    'null', 'undefined', 'className', 'onClick', 'onChange', 'value', 'label',
}
names = sorted(n for n in names if n not in deny and len(n) >= 2)
pathlib.Path('public/fonts/material-symbols/icon-names.txt').write_text('\n'.join(names) + '\n')
print(f'Collected {len(names)} icon names')

qs = urllib.parse.urlencode({
    'family': 'Material Symbols Outlined:opsz,wght,FILL,GRAD@24,400,0,0',
    'icon_names': ','.join(names),
    'display': 'swap',
})
url = 'https://fonts.googleapis.com/css2?' + qs
req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0'})
css = urllib.request.urlopen(req, timeout=60).read().decode()
font_urls = re.findall(r'url\((https://[^)]+)\)', css)
if not font_urls:
    raise SystemExit('No font URL in Google CSS:\n' + css[:500])
data = urllib.request.urlopen(
    urllib.request.Request(font_urls[0], headers={'User-Agent': 'Mozilla/5.0'}),
    timeout=60,
).read()
out = pathlib.Path('public/fonts/material-symbols/material-symbols-outlined.min.woff2')
out.write_bytes(data)
print(f'Wrote {out} ({len(data)} bytes)')
if len(data) < 10_000:
    raise SystemExit('Subset font unexpectedly small')
if 'expand_less' not in names or 'expand_more' not in names:
    raise SystemExit('expand_less/expand_more missing from icon list')
print('expand_less/expand_more present in icon-names.txt')
PY

chown -R www-data:www-data public/fonts/material-symbols 2>/dev/null || true
ls -lh public/fonts/material-symbols/material-symbols-outlined.min.woff2
rg -n 'expand_less|expand_more|monitoring|analytics' public/fonts/material-symbols/icon-names.txt
echo "Done. Bump ?v= in app.css and app.blade.php"
