#!/usr/bin/env bash
# probe.sh — Feed Scroll Reader on a real WordPress, in a local Playground: a shortcode, a widget, a
# template tag, a save, a migration from 1.x and an uninstall, which the unit suite and the workspace
# smoke (.claude/scripts/plugin-playground-smoke.sh: activate, fetch pages) cannot show. Fixture feeds
# are served by a pre_http_request filter (no network), with WP_DEBUG on.
#
# Usage: tests/live/probe.sh <plugin folder: source or deploy output> <WordPress folder>
#   <WordPress folder>  an unpacked release (e.g. 7.2-RC3). A folder and not --wp=, because Playground
#                       CLI 3.1.55 resolves --wp=beta and RC zip URLs to the latest stable; the folder
#                       is mounted with --mount-before-install and install-from-existing-files.
#   PORT=9432 (default) picks the port; KEEP=1 keeps the scratch folder.
set -uo pipefail
PLUGIN=${1:?usage: probe.sh <plugin folder> <WordPress folder>}
HERE=$(cd "$(dirname "$0")" && pwd)
WPV=${2:?WordPress folder}
SLUG=flash-feed-scroll-reader
MAIN=wp-flashfeedscrollreader.php
PORT=${PORT:-9432}
CLI=@wp-playground/cli@3.1.55
BASE=http://127.0.0.1:$PORT

lsof -ti tcp:$PORT >/dev/null && { echo "port $PORT busy"; exit 1; }
T=$(mktemp -d "${TMPDIR:-/tmp}/ffsr-probe.XXXXXX")
cleanup() { local p; p=$(lsof -ti tcp:$PORT); [ -n "$p" ] && kill $p 2>/dev/null; if [ -n "${KEEP:-}" ]; then echo "kept: $T"; else rm -rf "$T"; fi; }
trap cleanup EXIT

rsync -a --exclude node_modules --exclude .git "$PLUGIN/" "$T/$SLUG/"
cp -R "$WPV" "$T/wordpress"
mkdir -p "$T/logs"
# a language pack as translate.wordpress.org would install it: it must win over the bundled catalogue
mkdir -p "$T/wordpress/wp-content/languages/plugins"
printf 'msgid ""\nmsgstr "Content-Type: text/plain; charset=UTF-8\\nLanguage: es_ES\\n"\n\nmsgid "Latest items"\nmsgstr "PACK es"\n' > "$T/pack-es.po"
msgfmt -o "$T/wordpress/wp-content/languages/plugins/flash-feed-scroll-reader-es_ES.mo" "$T/pack-es.po"
cp -R "$HERE/fixtures" "$T/fixtures"; cp -R "$HERE/mu" "$T/mu"

cat > "$T/blueprint.json" <<JSON
{
  "steps": [
    { "step": "defineWpConfigConsts", "consts": { "WP_DEBUG": true, "WP_DEBUG_LOG": "/logs/debug.log", "WP_DEBUG_DISPLAY": false } },
    { "step": "runPHP", "code": "<?php require '/wordpress/wp-content/ffsr-fixtures/seed.php';" },
    { "step": "activatePlugin", "pluginPath": "$SLUG/$MAIN" }
  ]
}
JSON

( cd "$T" && exec npx -y "$CLI" server --blueprint="$T/blueprint.json" --port=$PORT \
  --mount-before-install="$T/wordpress:/wordpress" --wordpress-install-mode=install-from-existing-files \
  --mount="$T/$SLUG:/wordpress/wp-content/plugins/$SLUG" --mount="$T/logs:/logs" \
  --mount="$T/mu:/wordpress/wp-content/mu-plugins" --mount="$T/fixtures:/wordpress/wp-content/ffsr-fixtures" > "$T/playground.log" 2>&1 ) &
server=$!
for _ in $(seq 1 120); do grep -q 'Ready!' "$T/playground.log" 2>/dev/null && break; kill -0 $server 2>/dev/null || break; perl -e 'select(undef, undef, undef, 2)'; done
grep -q 'Ready!' "$T/playground.log" || { echo "Playground did not start"; tail -8 "$T/playground.log"; exit 1; }

fail=0; pass=0
ok()  { pass=$((pass+1)); echo "  ✓ $1"; }
bad() { fail=$((fail+1)); echo "  ✗ $1"; [ -n "${2:-}" ] && echo "      $2" | head -c 600; }
has() { # has <file> <fixed string> <description>
  if grep -qF -- "$2" "$1"; then ok "$3"; else bad "$3" "missing: $2"; fi; }
lacks() { if grep -qF -- "$2" "$1"; then bad "$3" "found: $2"; else ok "$3"; fi; }
hasre() { if grep -qE -- "$2" "$1"; then ok "$3"; else bad "$3" "no match: $2"; fi; }

admin="$T/admin.jar"; sub="$T/sub.jar"
login() { # login <jar> <user>
  curl -s -c "$1" -b "$1" -o /dev/null "$BASE/wp-login.php"
  curl -s -c "$1" -b "$1" -o /dev/null -w '%{http_code}' --data-urlencode "log=$2" --data-urlencode "pwd=password" --data-urlencode "wp-submit=Log In" --data-urlencode "testcookie=1" "$BASE/wp-login.php"
}
get() { # get <out> <jar|-> <path>
  if [ "$2" = "-" ]; then curl -s -o "$1" -w '%{http_code}' "$BASE/$3"; else curl -s -b "$2" -c "$2" -o "$1" -w '%{http_code}' "$BASE/$3"; fi; }

echo "== login"
c=$(login "$admin" admin); echo "  admin login → $c"
c=$(login "$sub" sub);     echo "  subscriber login → $c"

echo "== 1. activation migrated the planted 1.x settings"
get "$T/row1" - "?ffsr_probe=row" >/dev/null
has "$T/row1" 'feeds.test\/a.xml\nhttps:\/\/feeds.test\/b.xml' "feeds = the old aggregator list"
has "$T/row1" '"migrated":1' "migrated flag set"
has "$T/row1" '"speed":100' "10 ms per step = 100 px/s"
has "$T/row1" '"max_items":6' "3 per feed x 2 feeds = 6"
has "$T/row1" '"bgcolor":"#abcdef"' "background carried over"
has "$T/row1" '"separator":"#"' "separator trimmed"
has "$T/row1" '"suffix":"~"' "stringcutend -> suffix"
has "$T/row1" '"target":"_self"' "target carried over"
has "$T/row1" '"height":"60px"' "bare 60 became 60px"
has "$T/row1" 'OLD=present' "the old option is kept as a backup"
lacks "$T/row1" 'wmode' "no Flash-only key in the new row"
lacks "$T/row1" 'stylesheet' "no stylesheet key in the new row"

echo "== 2. shortcode, as a visitor (no cookie)"
c=$(get "$T/sc.html" - "?pagename=shortcode"); [ "$c" = 200 ] && ok "HTTP 200" || bad "HTTP $c"
has "$T/sc.html" 'class="ffsr"' "ticker is there"
has "$T/sc.html" 'role="region" aria-label="Latest items"' "labelled region"
[ "$(grep -o 'class="ffsr"' "$T/sc.html" | wc -l | tr -d ' ')" = 1 ] && ok "one ticker" || bad "not exactly one ticker"
[ "$(grep -o 'class="ffsr__item"' "$T/sc.html" | wc -l | tr -d ' ')" = 3 ] && ok "max_items=3 gives 3 items" || bad "item count is not 3"
has "$T/sc.html" '--ffsr-height:3em;' "height from the shortcode"
has "$T/sc.html" '--ffsr-bg:#112233;--ffsr-fg:#ffffff;' "colors from the shortcode"
has "$T/sc.html" 'data-speed="80"' "speed from the shortcode"
has "$T/sc.html" 'Alpha newest &amp; best' "title decoded then escaped once"
lacks "$T/sc.html" 'javascript:alert' "a javascript: link is dropped"
lacks "$T/sc.html" '<script>alert(2)' "feed markup in a title is not live"
lacks "$T/sc.html" 'aria-live' "no aria-live"
lacks "$T/sc.html" 'Feed Scroll Reader:' "no admin comment for a visitor"
python3 - "$T/sc.html" <<'PY' && ok "newest first across both feeds: Alpha, Bravo, Alpha oldest" || bad "order"
import sys,re
h=open(sys.argv[1]).read()
t=re.findall(r'class="ffsr__link"[^>]*>([^<]*)<',h)
assert t==['Alpha newest &amp; best','Bravo middle','Alpha oldest'],t
PY
python3 - "$T/sc.html" <<'PY' && ok "description cut at 20 characters, trailing space trimmed, suffix of the migrated defaults (~)" || bad "description cut"
import sys,re
h=open(sys.argv[1]).read()
d=re.findall(r'class="ffsr__desc">([^<]*)<',h)
assert d[0]=='The quick brown fox~',d
assert d[1].startswith('Atom summary with') and d[1].endswith('~'),d
PY
hasre "$T/sc.html" "href='[^']*public/css/feed-scroll-reader.css" "stylesheet enqueued"
hasre "$T/sc.html" 'src="[^"]*public/js/feed-scroll-reader.js' "script enqueued"
css=$(grep -o "href='[^']*public/css/feed-scroll-reader.css[^']*'" "$T/sc.html" | head -1 | sed "s/href='//;s/'\$//;s/&#038;/\\&/g"); jsu=$(grep -o 'src="[^"]*public/js/feed-scroll-reader.js[^"]*"' "$T/sc.html" | head -1 | sed 's/src="//;s/"$//;s/&#038;/\&/g')
[ "$(curl -s -o /dev/null -w '%{http_code}' "$css")" = 200 ] && ok "the stylesheet is served" || bad "css url $css"
[ "$(curl -s -o /dev/null -w '%{http_code}' "$jsu")" = 200 ] && ok "the script is served" || bad "js url $jsu"
cp "$T/sc.html" "$T/sc-visitor.html"

echo "== 3. template tags (1.x args)"
get "$T/tag.html" - "?pagename=tag" >/dev/null
has "$T/tag.html" 'id="ffsr-1"' "first ticker"
has "$T/tag.html" 'id="ffsr-2"' "second ticker, unique id"
has "$T/tag.html" '--ffsr-width:300px;--ffsr-height:40px;' "width=300&height=40"
has "$T/tag.html" 'data-speed="50"' "scrollspeed=20 -> 50 px/s"
lacks "$T/tag.html" 'x.css' "stylesheet ignored"
lacks "$T/tag.html" 'Opaque' "wmode ignored"
lacks "$T/tag.html" 'Evil link' "javascript: item absent"
python3 - "$T/tag.html" <<'PY' && ok "the echo form wrote the same kind of markup, separator trimmed" || bad "echo form"
import sys,re
h=open(sys.argv[1]).read()
a,b=h.split('<!--ECHO-->')
assert 'feeds.test/a/1' in a and 'feeds.test/b/1' not in a
assert 'feeds.test/b/1' in b and 'feeds.test/a/1' not in b
assert 'aria-hidden="true">|</span>' in b
PY

echo "== 4. a feed that fails: visitor vs administrator"
get "$T/down-v.html" - "?pagename=down" >/dev/null
lacks "$T/down-v.html" 'class="ffsr"' "visitor: no ticker"
lacks "$T/down-v.html" 'down.test' "visitor: nothing about the failure"
get "$T/down-a.html" "$admin" "?pagename=down" >/dev/null
has "$T/down-a.html" '<!-- Feed Scroll Reader: https://down.test/f.xml: WP HTTP Error: cURL error 6: Could not resolve host: down.test (not tried again for 15 minutes) -->' "admin: the WP_Error in an HTML comment (the visitor's request had already marked it)"
get "$T/empty-v.html" - "?pagename=empty" >/dev/null
lacks "$T/empty-v.html" 'class="ffsr"' "visitor: a 404 feed shows nothing"
get "$T/empty-a.html" "$admin" "?pagename=empty" >/dev/null
hasre "$T/empty-a.html" '<!-- Feed Scroll Reader: https://feeds.test/missing.xml: ' "admin: the 404 feed is explained"
get "$T/sc-a.html" "$admin" "?pagename=shortcode" >/dev/null
lacks "$T/sc-a.html" 'Feed Scroll Reader:' "admin: a working ticker has no comment"

echo "== 4b. dead feeds: a cap of 5 feeds and a negative cache"
get "$T/many.html" - "?pagename=many" >/dev/null
n=$(grep -c 'n=' "$T/logs/fetches.txt"); [ "$n" = 5 ] && ok "7 feeds in the shortcode: 5 were fetched" || bad "fetched $n feeds, not 5"
d1=$(grep -c 'down.test' "$T/logs/fetches.txt")
get "$T/down-a2.html" "$admin" "?pagename=down" >/dev/null
get "$T/down-a3.html" "$admin" "?pagename=down" >/dev/null
d2=$(grep -c 'down.test' "$T/logs/fetches.txt")
[ "$d1" = "$d2" ] && ok "a failed feed is not fetched again while the mark lasts ($d1 fetches in all)" || bad "down.test fetched again: $d1 -> $d2"
has "$T/down-a3.html" '(not tried again for 15 minutes)' "admin: the comment says it is remembered"
get "$T/tr.txt" - "?ffsr_probe=transients" >/dev/null
hasre "$T/tr.txt" '_transient_ffsr_down_[a-f0-9]{32}' "the failure mark is a transient named ffsr_down_<md5>"

echo "== 5. widget"
get "$T/w-v.html" - "?pagename=widget" >/dev/null
has "$T/w-v.html" '<div class="WBEFORE"><h2 class="WTITLE">News &lt;b&gt;box&lt;/b&gt;</h2>' "title escaped inside the theme's markup"
lacks "$T/w-v.html" '<b>box' "widget title is not live HTML"
has "$T/w-v.html" 'data-speed="70"' "widget speed"
lacks "$T/w-v.html" 'WBEFORE2' "visitor: a broken widget prints no box at all"
get "$T/w-a.html" "$admin" "?pagename=widget" >/dev/null
has "$T/w-a.html" 'WBEFORE2' "admin: a broken widget prints its box with the reason"
has "$T/w-a.html" 'down.test/f.xml' "admin: the reason is there"
get "$T/wf.txt" "$admin" "?ffsr_probe=widget_form" >/dev/null
has "$T/wf.txt" 'REGISTERED' "the widget is registered"
has "$T/wf.txt" 'name="widget-feed_scroll_reader[5][title]"' "form field names"
has "$T/wf.txt" 'value="T&quot;x"' "form values escaped"
has "$T/wf.txt" '"feeds":"https:\/\/ok.test\/f"' "update(): javascript: line dropped"
has "$T/wf.txt" '"max_items":"100"' "update(): max_items clamped"
has "$T/wf.txt" '"speed":""' "update(): junk speed -> use default"
has "$T/wf.txt" '"height":""' "update(): CSS injection in height -> use default"
has "$T/wf.txt" '"title":"Hi"' "update(): title text only"

echo "== 6. settings page"
c=$(get "$T/set.html" "$admin" "wp-admin/options-general.php?page=feed-scroll-reader"); [ "$c" = 200 ] && ok "admin: 200" || bad "admin: $c"
has "$T/set.html" 'value="#abcdef"' "form shows the migrated background"
has "$T/set.html" 'value="100"' "form shows the migrated speed"
has "$T/set.html" 'class="ffsr"' "preview ticker rendered"
has "$T/set.html" 'feed-scroll-reader-admin.js' "color picker script on this page"
get "$T/plugins.html" "$admin" "wp-admin/plugins.php" >/dev/null
has "$T/plugins.html" 'options-general.php?page=feed-scroll-reader' "Settings link in the Plugins list"
get "$T/dash.html" "$admin" "wp-admin/index.php" >/dev/null
lacks "$T/dash.html" 'feed-scroll-reader-admin.js' "admin script only on its own page"
c=$(get "$T/set-sub.html" "$sub" "wp-admin/options-general.php?page=feed-scroll-reader"); [ "$c" = 403 ] && ok "subscriber: 403" || bad "subscriber: $c"
c=$(get "$T/set-v.html" - "wp-admin/options-general.php?page=feed-scroll-reader"); [ "$c" = 302 ] && ok "visitor: sent to log in" || bad "visitor: $c"

nonce=$(grep -o 'name="_wpnonce" value="[a-f0-9]*"' "$T/set.html" | head -1 | sed 's/.*value="//;s/"//')
post() { # post <jar|-> <out> <extra curl args…>
  local jar=$1 out=$2; shift 2
  if [ "$jar" = "-" ]; then curl -s -o "$out" -w '%{http_code}' "$@" "$BASE/wp-admin/admin-post.php"; else curl -s -b "$jar" -c "$jar" -o "$out" -w '%{http_code}' "$@" "$BASE/wp-admin/admin-post.php"; fi; }
form=(--data-urlencode "action=ffsr_save_settings" --data-urlencode "ffsr[feeds]=https://feeds.test/b.xml
not a url" --data-urlencode "ffsr[speed]=33" --data-urlencode "ffsr[max_items]=4" --data-urlencode "ffsr[height]=auto" --data-urlencode "ffsr[bgcolor]=" --data-urlencode "ffsr[separator]=//" --data-urlencode "ffsr[target]=_blank" --data-urlencode "ffsr[cache]=5")

c=$(post "$admin" "$T/save-nonce.html" "${form[@]}"); [ "$c" = 403 ] && ok "save without a nonce: 403" || bad "save without a nonce: $c"
get "$T/row2" - "?ffsr_probe=row" >/dev/null; has "$T/row2" '"speed":100' "…and nothing was saved"
c=$(post "$sub" "$T/save-sub.html" "${form[@]}" --data-urlencode "_wpnonce=$nonce"); [ "$c" = 403 ] && ok "subscriber, even with a stolen nonce: 403" || bad "subscriber save: $c"
get "$T/row3" - "?ffsr_probe=row" >/dev/null; has "$T/row3" '"speed":100' "…and nothing was saved"
c=$(post - "$T/save-v.html" "${form[@]}" --data-urlencode "_wpnonce=$nonce"); ok "visitor posting to admin-post.php: $c"
get "$T/row4" - "?ffsr_probe=row" >/dev/null; has "$T/row4" '"speed":100' "…and nothing was saved"
c=$(post "$admin" "$T/save-ok.html" "${form[@]}" --data-urlencode "_wpnonce=$nonce" --data-urlencode "_wp_http_referer=/wp-admin/options-general.php?page=feed-scroll-reader" -D "$T/save-headers.txt"); [ "$c" = 302 ] && ok "admin save: 302 back to the page" || bad "admin save: $c"
grep -qi 'location: .*page=feed-scroll-reader.*updated=1' "$T/save-headers.txt" && ok "redirect carries updated=1" || bad "redirect" "$(cat "$T/save-headers.txt" | head -5)"
get "$T/set2.html" "$admin" "wp-admin/options-general.php?page=feed-scroll-reader&updated=1" >/dev/null
has "$T/set2.html" 'Settings saved.' "success notice"
has "$T/set2.html" 'were not saved' "the dropped lines are reported"
get "$T/row5" - "?ffsr_probe=row" >/dev/null
has "$T/row5" '"feeds":"https:\/\/feeds.test\/b.xml"' "feeds saved, junk lines dropped"
has "$T/row5" '"speed":33' "speed saved"
has "$T/row5" '"max_items":4' "items saved"
has "$T/row5" '"height":"auto"' "height auto saved"
has "$T/row5" '"bgcolor":""' "empty background = transparent"
has "$T/row5" '"separator":"\/\/"' "separator saved"
has "$T/row5" '"cache":5' "cache saved"
has "$T/row5" '"migrated":1' "migration state kept through a save"
has "$T/row5" '"description":0' "unchecked box saved as off"

if [ -n "${HOLD:-}" ]; then echo "HOLD: serving $BASE until $T/stop exists (pages: ?pagename=shortcode, tag, widget; wp-admin: admin/password)"; while [ ! -e "$T/stop" ]; do sleep 2; done; fi
echo "== 8. an update by copying files runs the migration on the next request"
get /dev/null - "?ffsr_probe=plant_old" >/dev/null
get /dev/null - "?ffsr_probe=reset_version" >/dev/null
get "$T/home.html" - "" >/dev/null
get "$T/row6" - "?ffsr_probe=row" >/dev/null
has "$T/row6" '"migrated":1' "migrated after the first request"
has "$T/row6" 'feeds.test\/a.xml\nhttps:\/\/feeds.test\/b.xml' "aggregator list again"
has "$T/row6" '"separator":"#"' "old separator again"

echo "== 8b. translations load (compiled .mo) in the five locales"
for pair in it_IT:Aspetto es_ES:Aspecto fr_FR:Apparence de_DE:Aussehen pt_BR:Aparência; do
  l=${pair%%:*}; w=${pair#*:}
  get "$T/i18n-$l.html" "$admin" "wp-admin/options-general.php?page=feed-scroll-reader&ffsr_lang=$l" >/dev/null
  has "$T/i18n-$l.html" ">$w<" "$l: the settings page says $w"
done
get "$T/i18n-front.html" - "?pagename=shortcode&ffsr_lang=it_IT" >/dev/null
has "$T/i18n-front.html" 'aria-label="Ultimi elementi"' "it_IT: the ticker's default label on the front end (WPLANG, bundled catalogue)"
has "$T/i18n-front.html" 'aria-label="Metti in pausa lo scorrimento"' "it_IT: the pause button label"
get "$T/i18n-pack.html" - "?pagename=shortcode&ffsr_lang=es_ES" >/dev/null
has "$T/i18n-pack.html" 'aria-label="PACK es"' "es_ES: a language pack in WP_LANG_DIR wins over the bundled catalogue"
has "$T/i18n-pack.html" 'aria-label="Pausar el desplazamiento"' "es_ES: a string the pack lacks falls back to the bundled one"

echo "== 8c. the block"
admin_nonce=$(curl -s -b "$admin" -c "$admin" "$BASE/wp-admin/admin-ajax.php?action=rest-nonce")
sub_nonce=$(curl -s -b "$sub" -c "$sub" "$BASE/wp-admin/admin-ajax.php?action=rest-nonce")
rest() { # rest <out> <jar> <nonce> <route> [curl args…]: a REST route as that user (?rest_route= works with any permalink structure)
  local out=$1 jar=$2 nonce=$3 route=$4; shift 4
  curl -s -g -G -b "$jar" -o "$out" -w '%{http_code}' -H "X-WP-Nonce: $nonce" "$BASE/" --data-urlencode "rest_route=$route" "$@"; }
BLOCK=flash-feed-scroll-reader/ticker

c=$(rest "$T/bt.json" "$admin" "$admin_nonce" "/wp/v2/block-types/$BLOCK"); [ "$c" = 200 ] && ok "block-types/$BLOCK: 200 as an administrator" || bad "block-types: $c"
python3 - "$T/bt.json" <<'PY' && ok "registered: api 3, widgets category, the editor script, the shared style and view script, 8 attributes" || bad "block type"
import sys,json
b=json.load(open(sys.argv[1]))
assert b['name']=='flash-feed-scroll-reader/ticker' and b['api_version']==3 and b['category']=='widgets',b
assert b['editor_script_handles']==['flash-feed-scroll-reader-ticker-editor-script'],b['editor_script_handles']
assert b['style_handles']==['feed-scroll-reader'] and b['view_script_handles']==['feed-scroll-reader'],(b['style_handles'],b['view_script_handles'])
for a in ['feeds','maxItems','speed','description','descriptionLength','height','target','label']: assert a in b['attributes'],a
s=b['supports']; assert s['align']==['wide','full'] and s['color']['background'] and s['color']['text'] and s['typography']['fontSize'] and s['spacing']['margin'],s
assert b['keywords']==['feed','rss','atom','ticker','news','marquee'],b['keywords']
PY
c=$(rest "$T/bt-sub.json" "$sub" "$sub_nonce" "/wp/v2/block-types/$BLOCK"); [ "$c" = 403 ] && ok "block-types as a subscriber: 403" || bad "block-types as a subscriber: $c"

# the call ServerSideRender makes
c=$(rest "$T/br.json" "$admin" "$admin_nonce" "/wp/v2/block-renderer/$BLOCK" --data-urlencode "context=edit" --data-urlencode "attributes[feeds]=https://feeds.test/a.xml" --data-urlencode "attributes[maxItems]=2" --data-urlencode "attributes[speed]=60" --data-urlencode "attributes[description]=true")
[ "$c" = 200 ] && ok "block-renderer (context=edit): 200" || bad "block-renderer: $c"
python3 - "$T/br.json" <<'PY' && ok "the editor's markup: the ticker itself, no block wrapper (the editor draws its own), 60 px/s, a description" || bad "block-renderer markup"
import sys,json
r=json.load(open(sys.argv[1]))['rendered']
assert r.startswith('<div id="ffsr-') and 'data-speed="60"' in r and 'class="ffsr__desc"' in r,r[:300]
assert 'wp-block-flash-feed-scroll-reader-ticker' not in r,r[:300]
PY
c=$(rest "$T/br-color.json" "$admin" "$admin_nonce" "/wp/v2/block-renderer/$BLOCK" --data-urlencode "context=edit" --data-urlencode "attributes[backgroundColor]=black" --data-urlencode "attributes[textColor]=white")
python3 - "$T/br-color.json" <<'PY' && ok "the editor's markup with block colors: the ticker's own colors are unset, so the wrapper's show" || bad "block colors in the editor"
import sys,json
r=json.load(open(sys.argv[1]))['rendered']
assert 'class="ffsr"' in r and '--ffsr-bg' not in r and '--ffsr-fg' not in r,r[:300]
PY
c=$(rest "$T/br-bad.json" "$admin" "$admin_nonce" "/wp/v2/block-renderer/$BLOCK" --data-urlencode "context=edit" --data-urlencode "attributes[maxItems]=abc"); [ "$c" = 400 ] && ok "block-renderer: an attribute of the wrong type is a 400" || bad "wrong type: $c"
c=$(rest "$T/br-down.json" "$admin" "$admin_nonce" "/wp/v2/block-renderer/$BLOCK" --data-urlencode "context=edit" --data-urlencode "attributes[feeds]=https://down.test/editor.xml")
has "$T/br-down.json" '<p>https:\/\/down.test\/editor.xml: WP HTTP Error' "administrator in the editor: a dead feed is explained in a paragraph (JSON-escaped)"
c=$(rest "$T/br-sub.json" "$sub" "$sub_nonce" "/wp/v2/block-renderer/$BLOCK" --data-urlencode "context=edit"); [ "$c" = 403 ] && ok "block-renderer as a subscriber: 403" || bad "block-renderer as a subscriber: $c"

# the front end
c=$(get "$T/b.html" - "?pagename=block"); [ "$c" = 200 ] && ok "a post with the block: 200" || bad "block page: $c"
python3 - "$T/b.html" <<'PY' && ok "the wrapper (block class, alignment, color classes, font size) holds the ticker" || bad "wrapper"
import sys,re
h=open(sys.argv[1]).read()
m=re.search(r'<div class="([^"]*wp-block-flash-feed-scroll-reader-ticker[^"]*)"[^>]*><div id="ffsr-1" class="ffsr"',h)
assert m,'no wrapper before the ticker'
for c in ['alignwide','has-black-background-color','has-white-color','has-background','has-text-color','has-large-font-size']: assert c in m.group(1).split(),(c,m.group(1))
assert h.count('wp-block-flash-feed-scroll-reader-ticker')>=1 and h.count('id="ffsr-')==1
PY
has "$T/b.html" 'data-speed="80"' "block speed"
has "$T/b.html" '--ffsr-height:3em;' "block height"
[ "$(grep -o 'class="ffsr__item"' "$T/b.html" | wc -l | tr -d ' ')" = 2 ] && ok "maxItems=2 gives 2 items" || bad "item count is not 2"
python3 - "$T/b.html" <<'PY' && ok "two feeds in the textarea's one-per-line form; description cut at 20" || bad "block feeds"
import sys,re
h=open(sys.argv[1]).read()
t=re.findall(r'class="ffsr__link"[^>]*>([^<]*)<',h); assert t==['Alpha newest &amp; best','Bravo middle'],t
assert re.findall(r'class="ffsr__desc">([^<]*)<',h)[0]=='The quick brown fox~'
PY
lacks "$T/b.html" '--ffsr-bg' "block colors: no ticker background over the wrapper's"
lacks "$T/b.html" '--ffsr-fg' "block colors: no ticker text color over the wrapper's"
[ "$(grep -o "href='[^']*public/css/feed-scroll-reader.css" "$T/b.html" | wc -l | tr -d ' ')" = 1 ] && ok "the stylesheet once" || bad "stylesheet count"
[ "$(grep -o 'src="[^"]*public/js/feed-scroll-reader.js' "$T/b.html" | wc -l | tr -d ' ')" = 1 ] && ok "the script once (the front-end script the shortcode uses)" || bad "script count"
c=$(get "$T/b-admin.html" "$admin" "?pagename=block"); [ "$c" = 200 ] && ok "administrator: 200" || bad "administrator: $c"
lacks "$T/b-admin.html" 'Feed Scroll Reader:' "administrator: a working block has no comment"
get "$T/bd.html" - "?pagename=blockdefaults" >/dev/null
has "$T/bd.html" 'data-speed="100"' "no attribute: the saved speed"
has "$T/bd.html" '--ffsr-bg:#abcdef;' "no block color: the saved background"
has "$T/bd.html" 'Alpha newest' "no attribute: the saved feeds"
get "$T/bb.html" - "?pagename=blockbad" >/dev/null
has "$T/bb.html" 'class="ffsr"' "a hand-edited post with values of the wrong type still shows the saved feeds"
lacks "$T/bb.html" 'to string conversion' "…without an Array to string notice in the page"
get "$T/bm.html" - "?pagename=blockmany" >/dev/null
n=$(grep -c 'b=' "$T/logs/fetches.txt"); [ "$n" = 5 ] && ok "8 feeds in the block: 5 were fetched" || bad "fetched $n feeds, not 5"
get "$T/bdown-v.html" - "?pagename=blockdown" >/dev/null
lacks "$T/bdown-v.html" 'wp-block-flash-feed-scroll-reader-ticker' "visitor: a dead feed leaves no empty wrapper"
lacks "$T/bdown-v.html" 'block.xml' "visitor: nothing about the failure"
get "$T/bdown-a.html" "$admin" "?pagename=blockdown" >/dev/null
has "$T/bdown-a.html" '<!-- Feed Scroll Reader: https://down.test/block.xml: ' "administrator: the reason in an HTML comment"
get "$T/bdown-a2.html" "$admin" "?pagename=blockdown" >/dev/null
n=$(grep -c 'down.test/block.xml' "$T/logs/fetches.txt"); [ "$n" = 1 ] && ok "a dead feed in a block is fetched once for three requests (the failure mark)" || bad "block.xml fetched $n times"

# the editor
get "$T/ed.html" "$admin" "wp-admin/post-new.php?ffsr_lang=it_IT" >/dev/null
hasre "$T/ed.html" 'public/blocks/ticker/index.js\?ver=2\.1\.0' "post-new.php loads the editor script"
has "$T/ed.html" 'window.ffsrDefaults = {' "the saved defaults for the placeholders"
has "$T/ed.html" 'feed-scroll-reader.css' "the front-end stylesheet reaches the editor's iframe"
has "$T/ed.html" 'Indirizzi dei feed' "it_IT: the editor's strings are translated (JSON from languages/)"
get "$T/ed-en.html" "$admin" "wp-admin/post-new.php" >/dev/null
lacks "$T/ed-en.html" 'Indirizzi dei feed' "en_US: no translation"
for pair in es_ES:'Direcciones de los feeds' fr_FR:'Adresses des flux' de_DE:'Feed-Adressen' pt_BR:'Endere\u00e7os dos feeds'; do
  l=${pair%%:*}; w=${pair#*:}
  get "$T/ed-$l.html" "$admin" "wp-admin/post-new.php?ffsr_lang=$l" >/dev/null
  has "$T/ed-$l.html" "$w" "$l: the editor says $w (JSON-escaped where it has an accent)"
done

# the widget, readable by the Legacy Widget block, which is what lets it become the block
c=$(curl -s -b "$admin" -o "$T/wr.json" -w '%{http_code}' -X POST -H "X-WP-Nonce: $admin_nonce" -H 'Content-Type: application/json' \
  --data '{"id_base":"feed_scroll_reader","sidebar":"ffsr-test","instance":{"raw":{"title":"T","feeds":"https://feeds.test/a.xml","max_items":"3","speed":"","height":"","description":"1"}}}' "$BASE/?rest_route=/wp/v2/widgets")
[ "$c" = 201 ] && ok "a widget created over REST: 201" || bad "widget REST create: $c" "$(head -c 300 "$T/wr.json")"
python3 - "$T/wr.json" <<'PY' && ok "its instance is readable (instance.raw): the Legacy Widget block can hand it to the block" || bad "widget instance not in REST"
import sys,json
w=json.load(open(sys.argv[1]))
raw=w['instance'].get('raw'); assert raw and raw['feeds']=='https://feeds.test/a.xml' and raw['max_items']=='3' and raw['title']=='T',w['instance']
PY

echo "== 9. uninstall removes every option"
get "$T/un.txt" - "?ffsr_probe=uninstall" >/dev/null
hasre "$T/un.txt" 'BEFORE=.*"feed_scroll_reader_slug"' "before: the settings row and ffsr_settings exist"
has "$T/un.txt" 'LEFT=[]' "after delete_plugins(): no option or transient of the plugin left (ffsr_down_*, ffsr_rejected_* too)"
hasre "$T/un.txt" 'BEFORE=.*_transient_ffsr_down_' "before: the failure marks existed"
b=$(grep -o 'COREFEED_BEFORE=[0-9]*' "$T/un.txt" | cut -d= -f2); a=$(grep -o 'COREFEED_AFTER=[0-9]*' "$T/un.txt" | cut -d= -f2)
[ "$b" -gt 0 ] && [ "$a" = "$b" ] && ok "core's shared feed_* transients are untouched ($b before, $a after)" || bad "core feed transients: $b before, $a after"

echo "== debug.log"
if [ -s "$T/logs/debug.log" ]; then bad "debug.log is not empty"; sed 's/^/      /' "$T/logs/debug.log" | head -30; else ok "debug.log is empty"; fi

[ -n "${KEEP:-}" ] && echo "kept pages in $T"
echo "passed $pass, failed $fail"
[ "$fail" = 0 ]
