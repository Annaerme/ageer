#!/usr/bin/env bash
# Uploadt de website naar de Infomaniak-hosting met lftp (FTPS of SFTP).
# Wordt gebruikt door .github/workflows/deploy-infomaniak.yml, maar je kan het
# ook zelf draaien (lftp en rsync nodig):
#
#   DEPLOY_HOST=xxx.ftp.infomaniak.com DEPLOY_USER=... DEPLOY_PASSWORD=... \
#     ./scripts/deploy.sh
#
# Instellingen (omgevingsvariabelen):
#   DEPLOY_HOST       verplicht  servernaam, bv. abcd.ftp.infomaniak.com
#   DEPLOY_USER       verplicht  FTP- of SSH-gebruiker
#   DEPLOY_PASSWORD   verplicht  wachtwoord van die gebruiker
#   DEPLOY_PROTOCOL   optioneel  ftps (standaard) of sftp. Nooit onversleuteld FTP.
#   DEPLOY_PORT       optioneel  standaard 21 (ftps) of 22 (sftp)
#   DEPLOY_PATH       optioneel  doelmap op de server, standaard /
#   DEPLOY_DRY_RUN=1  optioneel  enkel tonen wat er zou gebeuren, niets wijzigen
#   DEPLOY_CHECK_ONLY=1          enkel de veiligheidscontrole doen, niet verbinden
#
# WAT ER GEBEURT
#   Geüpload wordt exact wat scripts/maak-upload-zip.sh in de zip stopt:
#   index.html .htaccess pages css js images documents data uploads api
#   (zonder .DS_Store, .git*, *.md, api/config.php).
#
#   NOOIT overschreven of verwijderd op de server:
#     - api/config.php (en api/config*: bv. een backup config.php.bak)
#     - alles in uploads/ dat al bestaat (foto's en documenten van beheerders).
#       Enkel ontbrekende bestanden uit uploads/documenten en uploads/fotos/shop
#       worden toegevoegd. uploads/.htaccess (beveiliging) wordt wel altijd
#       bijgewerkt: beheerders kunnen geen .htaccess uploaden.
#     - .user.ini, error_log en *.log (door de hosting aangemaakt)
#     - alles wat niet in de lijst hierboven staat (bv. oude agenda.html in de
#       hoofdmap, andere mappen van Infomaniak): daar wordt niet aan gekomen.
#
#   Verouderde bestanden WEL verwijderd (spiegelen) enkel in de codemappen
#   pages/, css/, js/ en api/: zo blijven er geen oude PHP-bestanden slingeren.
#   In images/, documents/ en data/ wordt bijgewerkt maar nooit verwijderd.
set -euo pipefail
cd "$(dirname "$0")/.."

fout() { echo "FOUT: $*" >&2; exit 1; }

# ---------- 1. Instellingen controleren (vóór er iets gebeurt) ----------
PROTOCOL="$(printf '%s' "${DEPLOY_PROTOCOL:-ftps}" | tr '[:upper:]' '[:lower:]')"
if [ "${DEPLOY_CHECK_ONLY:-}" != "1" ]; then
  ontbreekt=""
  for v in DEPLOY_HOST DEPLOY_USER DEPLOY_PASSWORD; do
    if [ -z "${!v:-}" ]; then ontbreekt="$ontbreekt $v"; fi
  done
  if [ -n "$ontbreekt" ]; then
    fout "deze verplichte instelling(en) ontbreken:$ontbreekt. Voeg ze toe in GitHub → Settings → Secrets and variables → Actions (zie INSTALLATIE.md). Er is niets geüpload."
  fi
  case "$PROTOCOL" in
    ftps) PORT="${DEPLOY_PORT:-21}" ;;
    sftp) PORT="${DEPLOY_PORT:-22}" ;;
    *) fout "DEPLOY_PROTOCOL moet 'ftps' of 'sftp' zijn (nu: '$PROTOCOL'). Onversleuteld FTP wordt nooit gebruikt. Er is niets geüpload." ;;
  esac
  case "$PORT" in ''|*[!0-9]*) fout "DEPLOY_PORT moet een getal zijn (nu: '$PORT'). Er is niets geüpload." ;; esac
  case "$DEPLOY_HOST" in *[!A-Za-z0-9.-]*) fout "DEPLOY_HOST mag enkel een servernaam zijn, bv. abcd.ftp.infomaniak.com (zonder ftp:// of /map). Er is niets geüpload." ;; esac
  command -v lftp >/dev/null || fout "lftp is niet geïnstalleerd."
fi
REMOTE_PATH="${DEPLOY_PATH:-/}"
case "$REMOTE_PATH" in *'"'*|*$'\n'*) fout "DEPLOY_PATH bevat ongeldige tekens." ;; esac

# ---------- 2. Te uploaden set klaarzetten (zelfde als de zip) ----------
STAGE="$(mktemp -d)"
trap 'rm -rf "$STAGE"' EXIT
command -v rsync >/dev/null || fout "rsync is niet geïnstalleerd."
rsync -a --exclude='.DS_Store' --exclude='.git*' --exclude='*.md' --exclude='/api/config.php' \
  index.html .htaccess pages css js images documents data uploads api "$STAGE/"

# ---------- 3. Veiligheidscontrole: geen geheimen in de set ----------
gevonden="$(cd "$STAGE" && find . -type f \( -name 'config.php' -o -name '.env*' \
  -o -name '*.sql' -o -name '*.sqlite' -o -name '*.pem' -o -name '*.key' \
  -o -name 'id_rsa*' -o -name 'id_ed25519*' -o -name '*.bak' \) | sed 's|^\./||')"
[ -z "$gevonden" ] || fout "deze bestanden mogen nooit geüpload worden:
$gevonden"
# Waarden die op een wachtwoord/sleutel lijken (enkel bestand:regel tonen,
# nooit de waarde zelf, want die komt anders in het GitHub-logboek). De voorbeeldwaarden 'VUL_IN…'
# uit api/config.voorbeeld.php en lege waarden zijn toegelaten.
patroon="(db_pass|pass(word|wd)?|secret|setup_token|api_?key|service_role|private_key)['\"]?[[:space:]]*(=>|=|:)[[:space:]]*['\"][^'\"]{4,}['\"]"
verdacht="$(cd "$STAGE" && grep -rIEin "$patroon" . | grep -Ev "['\"]VUL_IN[^'\"]*['\"]" | cut -d: -f1,2 || true)"
verdacht="$verdacht$(cd "$STAGE" && grep -rIEl 'BEGIN [A-Z ]*PRIVATE KEY|eyJ[A-Za-z0-9_-]{10,}\.eyJ[A-Za-z0-9_-]{10,}' . || true)"
[ -z "$verdacht" ] || fout "iets in de te uploaden bestanden lijkt op een wachtwoord of geheime sleutel. Niets geüpload. Controleer:
$verdacht"
echo "Veiligheidscontrole OK: $(find "$STAGE" -type f | wc -l | tr -d ' ') bestanden, geen config.php of geheimen."
[ "${DEPLOY_CHECK_ONLY:-}" != "1" ] || exit 0

# ---------- 4. Uploaden met lftp ----------
if [ "${DEPLOY_DRY_RUN:-}" = "1" ]; then
  DRY="--dry-run"; PUT="echo zou uploaden:"; echo "PROEFDRAAI: er wordt niets gewijzigd."
else
  DRY=""; PUT="put"
fi
M="mirror --reverse --no-perms --parallel=4 --verbose=1 $DRY"
# Bestanden die de hosting zelf aanmaakt: nooit verwijderen.
BESCHERMD="-x '(^|/)\.user\.ini\$' -x '(^|/)error_log\$' -x '\.log\$'"

if [ "$PROTOCOL" = "ftps" ]; then
  URL="ftp://$DEPLOY_HOST:$PORT"
  VERBINDING="set ftp:ssl-force true
set ftp:ssl-protect-data true
set ftp:ssl-protect-list true
set ftp:ssl-auth TLS
set ssl:verify-certificate yes
set ftp:passive-mode true"
else
  URL="sftp://$DEPLOY_HOST:$PORT"
  VERBINDING="set sftp:auto-confirm yes"
fi

LFTP_PASSWORD="$DEPLOY_PASSWORD" lftp --env-password -u "$DEPLOY_USER" "$URL" <<EOF
set cmd:fail-exit yes
set net:timeout 30
set net:max-retries 3
set net:reconnect-interval-base 5
$VERBINDING
cd "$REMOTE_PATH"
lcd "$STAGE"

# Hoofdmap: enkel deze twee bestanden; andere bestanden blijven staan.
$PUT index.html
$PUT .htaccess

# Codemappen: spiegelen, verouderde bestanden worden verwijderd.
$M --delete $BESCHERMD pages pages
$M --delete $BESCHERMD css css
$M --delete $BESCHERMD js js
# api/: config.php wordt nooit geüpload; bij het opruimen worden api/config*
# (config.php, config.php.bak, …) altijd overgeslagen.
$M -x '^config\\.php\$' api api
$M --delete $BESCHERMD -x '^config[^/]*\$' api api

# Inhoud: bijwerken, nooit verwijderen.
$M images images
$M documents documents
$M data data

# uploads/: bestaande bestanden nooit overschrijven, nooit verwijderen.
$M --only-missing uploads/documenten uploads/documenten
$M --only-missing uploads/fotos/shop uploads/fotos/shop
# Behalve de beveiligingsregels van de uploadmap: die altijd bijwerken.
$PUT uploads/.htaccess -o uploads/.htaccess
bye
EOF
echo "Upload klaar ($PROTOCOL://$DEPLOY_HOST:$PORT$REMOTE_PATH)."
