#!/usr/bin/env bash
set -euo pipefail

APP_DIR="/home/alphaver/public_html/quick-seo-analysis"

cd "$APP_DIR"

git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
npm ci || npm install
npm run build
php artisan optimize:clear
php artisan filament:assets
php artisan route:clear
php artisan view:clear
php artisan config:clear

if chown -R alphaver:alphaver "$APP_DIR" 2>/dev/null; then
    echo "Ownership refreshed for $APP_DIR"
else
    echo "Skipping ownership refresh; run it once as root if permissions need repair."
fi

find storage bootstrap/cache -type d -exec chmod 775 {} \;
find storage bootstrap/cache -type f -exec chmod 664 {} \;
