#!/usr/bin/env bash
set -euo pipefail

APP_DIR="/home/alphaver/public_html/quick-seo-analysis"
PHP="/opt/cpanel/ea-php83/root/usr/bin/php"
COMPOSER="/usr/local/bin/composer"
NPM="/bin/npm"

cd "$APP_DIR"

git pull origin main
"$PHP" "$COMPOSER" install --no-dev --optimize-autoloader
"$PHP" artisan migrate --force
"$NPM" ci || "$NPM" install
"$NPM" run build
"$PHP" artisan optimize:clear
"$PHP" artisan filament:assets
"$PHP" artisan route:clear
"$PHP" artisan view:clear
"$PHP" artisan config:clear

echo "QSA deploy complete"
