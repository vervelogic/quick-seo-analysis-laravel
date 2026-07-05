#!/usr/bin/env bash
set -euo pipefail

APP_DIR="/home/alphaver/public_html/quick-seo-analysis"
PHP="${PHP_BIN:-/opt/cpanel/ea-php83/root/usr/bin/php}"
COMPOSER="${COMPOSER_BIN:-/usr/local/bin/composer}"
NPM="${NPM_BIN:-/bin/npm}"

cd "$APP_DIR"

echo "Deploying QSA from GitHub main..."
git fetch origin main
git reset --hard origin/main

"$PHP" "$COMPOSER" install --no-dev --optimize-autoloader
"$PHP" artisan migrate --force

if [ -f package-lock.json ]; then
    "$NPM" ci
else
    "$NPM" install
fi

"$NPM" run build
"$PHP" artisan optimize:clear
"$PHP" artisan filament:assets
"$PHP" artisan route:clear
"$PHP" artisan view:clear
"$PHP" artisan config:clear

find storage bootstrap/cache -type d -exec chmod 775 {} \;
find storage bootstrap/cache -type f -exec chmod 664 {} \;

echo "QSA deploy complete at commit $(git rev-parse HEAD)"
