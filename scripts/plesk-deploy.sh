#!/usr/bin/env bash
# Baut die Seite und kopiert sie in den Webordner.
# Wird von Plesk unter „Git > Zusätzliche Bereitstellungsaktionen“ aufgerufen:
#   bash scripts/plesk-deploy.sh /var/www/vhosts/groeppel-und-picky.de/neu.groeppel-und-picky.de/public
set -euo pipefail

TARGET="${1:?Bitte den Zielordner (Dokumentenstamm) angeben}"
COMPOSER="${COMPOSER:-composer}"

# Node suchen: NODE_BIN gesetzt, sonst die neueste Plesk-Version unter /opt/plesk/node
if [ -z "${NODE_BIN:-}" ]; then
  NODE_BIN="$(ls -d /opt/plesk/node/*/bin 2>/dev/null | sort -V | tail -n 1 || true)"
fi
[ -n "${NODE_BIN:-}" ] && export PATH="$NODE_BIN:$PATH"
if ! command -v npm >/dev/null; then
  echo "npm nicht gefunden. Gefundene Node-Versionen:" >&2
  ls -d /opt/plesk/node/*/bin 2>&1 >&2 || true
  echo "Bitte Node.js in Plesk installieren oder NODE_BIN=/pfad/zu/node/bin setzen." >&2
  exit 1
fi
echo "Node $(node -v) aus ${NODE_BIN:-PATH}"

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
