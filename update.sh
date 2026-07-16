#!/usr/bin/env bash
# Updates this Panel install from the jackh54/panel GitHub release tarball.
# Usage (on the server, as root or with sudo for chown):
#   cd /var/www/pterodactyl && ./update.sh
# Optional:
#   PANEL_RELEASE=v1.2.3 ./update.sh   # pin a tag instead of latest
#   PANEL_WEB_USER=nginx ./update.sh   # web server user:group owner

set -euo pipefail

PANEL_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$PANEL_DIR"

REPO="jackh54/panel"
RELEASE="${PANEL_RELEASE:-latest}"
WEB_USER="${PANEL_WEB_USER:-www-data}"

if [[ "$RELEASE" == "latest" ]]; then
  DOWNLOAD_URL="https://github.com/${REPO}/releases/latest/download/panel.tar.gz"
else
  DOWNLOAD_URL="https://github.com/${REPO}/releases/download/${RELEASE}/panel.tar.gz"
fi

if [[ ! -f artisan || ! -f composer.json ]]; then
  echo "error: run this from a Pterodactyl panel install (artisan/composer.json missing)" >&2
  exit 1
fi

if [[ ! -f .env ]]; then
  echo "error: .env not found — refusing to update an incomplete install" >&2
  exit 1
fi

MAINTENANCE_ON=0
cleanup() {
  local code=$?
  if [[ $MAINTENANCE_ON -eq 1 ]]; then
    php artisan up || true
  fi
  exit "$code"
}
trap cleanup EXIT

echo "==> Updating panel from ${DOWNLOAD_URL}"
echo "==> Entering maintenance mode"
php artisan down
MAINTENANCE_ON=1

echo "==> Downloading and extracting release"
curl -fsSL "$DOWNLOAD_URL" | tar -xz

echo "==> Fixing storage/cache permissions"
chmod -R 755 storage/* bootstrap/cache

echo "==> Installing PHP dependencies"
composer install --no-dev --optimize-autoloader

echo "==> Clearing caches"
php artisan view:clear
php artisan config:clear

echo "==> Running migrations"
php artisan migrate --seed --force

echo "==> Setting ownership to ${WEB_USER}:${WEB_USER}"
chown -R "${WEB_USER}:${WEB_USER}" "${PANEL_DIR}"/*

echo "==> Restarting queue workers"
php artisan queue:restart

echo "==> Exiting maintenance mode"
php artisan up
MAINTENANCE_ON=0

echo "==> Panel update complete"
trap - EXIT
