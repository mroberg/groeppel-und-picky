#!/usr/bin/env bash
# Baut die Seite und kopiert sie in den Webordner.
# Wird von Plesk unter „Git > Zusätzliche Bereitstellungsaktionen“ aufgerufen:
#   bash scripts/plesk-deploy.sh /var/www/vhosts/groeppel-und-picky.de/neu.groeppel-und-picky.de/public
set -euo pipefail

TARGET="${1:?Bitte den Zielordner (Dokumentenstamm) angeben}"
NODE_BIN="${NODE_BIN:-/opt/plesk/node/22/bin}"
COMPOSER="${COMPOSER:-composer}"
export PATH="$NODE_BIN:$PATH"

npm ci --no-audit --no-fund
npm run build

(cd server && "$COMPOSER" install --no-dev --optimize-autoloader --no-interaction)
mkdir -p dist/api
cp -r server/vendor server/lib.php server/erinnerung.php server/versand.php server/.htaccess dist/api/

mkdir -p "$TARGET"
if command -v rsync >/dev/null; then
  rsync -a --delete --exclude '.well-known' dist/ "$TARGET/"
else
  find "$TARGET" -mindepth 1 -maxdepth 1 ! -name '.well-known' -exec rm -rf {} +
  cp -r dist/. "$TARGET/"
fi
echo "Fertig: $(date)"
