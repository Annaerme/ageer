#!/usr/bin/env bash
# Snelle controle na het uploaden (rooktest). Gebruik:
#   SITE_URL=https://nieuw.aegir-gent.be ./scripts/rooktest.sh
# Controleert: homepage 200, /api/rest/v1/homepage_stats 200 met JSON,
# /api/config.php 403 (afgeschermd). Zonder SITE_URL wordt niets getest.
set -uo pipefail

URL="${SITE_URL:-}"
URL="${URL%/}"
if [ -z "$URL" ]; then
  echo "SITE_URL is niet ingesteld: rooktest overgeslagen."
  exit 0
fi
case "$URL" in http://*|https://*) ;; *) echo "FOUT: SITE_URL moet beginnen met https:// (nu: '$URL')." >&2; exit 1 ;; esac

TMP="$(mktemp)"; trap 'rm -f "$TMP"' EXIT
mislukt=0
status() { curl -sS -o "$TMP" -w '%{http_code}' --max-time 30 --retry 2 --retry-delay 5 "$1" || echo "000"; }

c="$(status "$URL/")"
if [ "$c" = 200 ]; then echo "OK   homepage geeft 200"; else echo "FOUT homepage geeft $c (verwacht 200)" >&2; mislukt=1; fi

c="$(status "$URL/api/rest/v1/homepage_stats")"
if [ "$c" = 200 ] && jq -e . "$TMP" >/dev/null 2>&1; then
  echo "OK   /api/rest/v1/homepage_stats geeft 200 met JSON"
else
  echo "FOUT /api/rest/v1/homepage_stats geeft $c of geen geldige JSON (verwacht 200 + JSON). Staat api/config.php goed op de server?" >&2
  mislukt=1
fi

c="$(status "$URL/api/config.php")"
if [ "$c" = 403 ]; then echo "OK   /api/config.php is afgeschermd (403)"; else echo "FOUT /api/config.php geeft $c (verwacht 403). Controleer api/.htaccess!" >&2; mislukt=1; fi

[ "$mislukt" = 0 ] && echo "Rooktest geslaagd." || { echo "Rooktest MISLUKT." >&2; exit 1; }
