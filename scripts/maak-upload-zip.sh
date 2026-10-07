#!/bin/sh
# Maakt het pakket om de website naar Infomaniak te uploaden:
#   ~/Desktop/aegir-website-upload.zip  → uitpakken in de map van de site
#   ~/Desktop/aegir-database/           → schema.sql en data.sql voor phpMyAdmin
# api/config.php (met wachtwoorden) gaat nooit mee.
set -e
cd "$(dirname "$0")/.."
OUT="$HOME/Desktop/aegir-website-upload.zip"
rm -f "$OUT"
zip -qr "$OUT" index.html .htaccess pages css js images documents data uploads api \
  -x '*.DS_Store' 'api/config.php' '*/.git*'
mkdir -p "$HOME/Desktop/aegir-database"
cp database/schema.sql database/data.sql "$HOME/Desktop/aegir-database/"
if unzip -l "$OUT" | grep -q 'api/config.php$'; then echo "FOUT: config.php zit in de zip" >&2; exit 1; fi
echo "Klaar: $OUT ($(du -h "$OUT" | cut -f1)) en ~/Desktop/aegir-database/"
