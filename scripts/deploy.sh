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
#   CONTROLE VOORAF: de doelmap moet de nieuwe site zijn (api/index.php staat
#   er al) of een lege map van een nieuwe site (eerste installatie: enkel
#   standaardbestanden zoals index.html/index.php van Infomaniak). Ze mag nooit
#   sporen van de oude site of van de hoofdmap van de hosting bevatten
#   (news.php, leden/, rag/, web/, sites/, …). Anders stopt het script zonder
#   iets te wijzigen.
#
#   Er wordt NOOIT iets verwijderd op de server. Bestanden van de nieuwe site
#   worden enkel toegevoegd of bijgewerkt; verouderde bestanden blijven staan.
#
#   NOOIT overschreven op de server:
#     - api/config.php (en api/config*: bv. een backup config.php.bak)
#     - alles in uploads/ dat al bestaat (foto's en documenten van beheerders).
#       Enkel ontbrekende bestanden uit uploads/documenten en uploads/fotos/shop
#       worden toegevoegd. uploads/.htaccess (beveiliging) wordt wel altijd
#       bijgewerkt: beheerders kunnen geen .htaccess uploaden.
#     - alles wat niet in de lijst hierboven staat: daar wordt niet aan gekomen.
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

# ---------- 5. Controle van de doelmap (vóór er iets geschreven wordt) ----------
# Er wordt enkel geüpload naar de map van de NIEUWE site. Staan er sporen van
# de oude site of van de hoofdmap van de hosting, of ontbreekt api/index.php
# (de site is daar nog niet geïnstalleerd), dan stopt alles zonder iets te wijzigen.
lftp_cmd() {
  LFTP_PASSWORD="$DEPLOY_PASSWORD" lftp --env-password -u "$DEPLOY_USER" "$URL" \
    -e "set cmd:fail-exit yes; set net:timeout 30; set net:max-retries 2; $(printf '%s' "$VERBINDING" | tr '\n' ';'); $1; bye"
}
lftp_fout="$(mktemp)"
inhoud="$(lftp_cmd "cd \"$REMOTE_PATH\"; cls -1a" 2>"$lftp_fout")" || {
  echo "Melding van de server: $(grep -v -i 'pass' "$lftp_fout" | tail -3)" >&2
  fout "kan de doelmap '$REMOTE_PATH' niet openen (zie melding hierboven: meestal een verkeerd wachtwoord of gebruikersnaam). Niets geüpload."
}
rm -f "$lftp_fout"
inhoud="$(printf '%s\n' "$inhoud" | sed 's#/$##')"
for spoor in content.php news.php events.php contact.php logon.php leden rag wp LiveResults doccenter \
             nextcloud.data web sites backups application_backups vendor; do
  if printf '%s\n' "$inhoud" | grep -qx "$spoor"; then
    fout "de doelmap '$REMOTE_PATH' bevat '$spoor': dat lijkt de OUDE site of de hoofdmap van de hosting. Niets geüpload en niets verwijderd. Beperk het FTP-account tot de map van nieuw.aegir-gent.be of zet DEPLOY_PATH juist."
  fi
done
if lftp_cmd "cd \"$REMOTE_PATH\"; cls api/index.php" >/dev/null 2>&1; then
  echo "Doelmap gecontroleerd: dit is de nieuwe site."
else
  # Eerste installatie: enkel toegelaten als de map leeg is of alleen de
  # standaardbestanden van een nieuwe Infomaniak-site bevat.
  vreemd="$(printf '%s\n' "$inhoud" | grep -vxE '\.|\.\.|index\.html|index\.php|\.htaccess|\.user\.ini|error_log|favicon\.ico|robots\.txt|cgi-bin|' || true)"
  [ -z "$vreemd" ] || fout "in '$REMOTE_PATH' staat geen api/index.php, maar de map is ook niet leeg (bv. '$(printf '%s' "$vreemd" | head -1)'). Uit voorzorg niets geüpload. Controleer DEPLOY_PATH en de map van het FTP-account."
  echo "Doelmap gecontroleerd: lege map van een nieuwe site, eerste installatie."
fi

# ---------- 6. Uploaden ----------
# Er wordt NOOIT iets verwijderd op de server: enkel bestanden van de nieuwe
# site toegevoegd of bijgewerkt. Verouderde bestanden blijven gewoon staan.
LFTP_PASSWORD="$DEPLOY_PASSWORD" lftp --env-password -u "$DEPLOY_USER" "$URL" <<EOF
set cmd:fail-exit yes
set net:timeout 30
set net:max-retries 3
set net:reconnect-interval-base 5
$VERBINDING
cd "$REMOTE_PATH"
lcd "$STAGE"

# Hoofdmap: enkel deze twee bestanden.
$PUT index.html
$PUT .htaccess

# Code en inhoud: bijwerken, nooit verwijderen. api/config.php wordt nooit geüpload.
$M pages pages
$M css css
$M js js
$M -x '^config[^/]*\$' api api
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
