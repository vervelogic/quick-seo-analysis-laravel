# Server Deploy Commands

Exact sequential commands for deploying this Laravel app to `new.quickseoanalysis.com` on a Hostinger VPS with WHM/cPanel.

Use these placeholders only:

```text
CPANEL_USER
SERVER_IP_OR_HOSTNAME
DB_NAME
DB_USER
DB_PASSWORD
GITHUB_REPO_URL
```

Important cPanel setting:

```text
Subdomain: new.quickseoanalysis.com
Document root: /home/CPANEL_USER/quick-seo-analysis/public
```

Do not point the subdomain to the Laravel project root. It must point to the `public` folder.

## 1. SSH Into the cPanel Account

```bash
ssh CPANEL_USER@SERVER_IP_OR_HOSTNAME
```

## 2. Go to the cPanel Home Folder

```bash
cd /home/CPANEL_USER
```

## 3. Backup/Rollback Existing Folder

If the project folder already exists, back it up first:

```bash
if [ -d /home/CPANEL_USER/quick-seo-analysis ]; then mv /home/CPANEL_USER/quick-seo-analysis /home/CPANEL_USER/quick-seo-analysis-backup-$(date +%Y%m%d-%H%M%S); fi
```

Rollback command if the new deployment fails:

```bash
cd /home/CPANEL_USER && rm -rf quick-seo-analysis && mv $(ls -td quick-seo-analysis-backup-* | head -1) quick-seo-analysis
```

## 4. Clone the Repository

```bash
cd /home/CPANEL_USER
git clone GITHUB_REPO_URL quick-seo-analysis
cd /home/CPANEL_USER/quick-seo-analysis
```

For future updates after the first deployment:

```bash
cd /home/CPANEL_USER/quick-seo-analysis
git pull
```

## 5. Create `.env`

```bash
cp .env.example .env
```

Replace `.env` with production values:

```bash
cat > .env <<'EOF'
APP_NAME="Quick SEO Analysis"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://new.quickseoanalysis.com

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=DB_NAME
DB_USERNAME=DB_USER
DB_PASSWORD=DB_PASSWORD

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database
CACHE_STORE=database

MAIL_MAILER=log
MAIL_FROM_ADDRESS="reports@new.quickseoanalysis.com"
MAIL_FROM_NAME="${APP_NAME}"

QSA_SCAN_TIMEOUT=12
QSA_SCAN_MAX_BYTES=2097152
QSA_DEFAULT_COMPANY_NAME="Quick SEO Analysis"

ADMIN_NAME="Admin"
ADMIN_EMAIL="admin@new.quickseoanalysis.com"
ADMIN_PASSWORD="CHANGE_THIS_STRONG_ADMIN_PASSWORD"
EOF
```

In cPanel, create `DB_NAME`, `DB_USER`, and `DB_PASSWORD` from **MySQL Database Wizard** before running migrations. Use the exact cPanel-prefixed database name and username.

`DB_PASSWORD` is only for MySQL. `ADMIN_PASSWORD` is separate and must be changed after first login.

## 6. Install PHP Dependencies

```bash
composer install --no-dev --optimize-autoloader
```

If Composer hits memory limits:

```bash
COMPOSER_MEMORY_LIMIT=-1 composer install --no-dev --optimize-autoloader
```

## 7. Install Node Dependencies and Build Assets

```bash
npm install
npm run build
```

## 8. Generate App Key

```bash
php artisan key:generate
```

## 9. Run Database Migrations and Seed Admin Data

```bash
php artisan migrate --seed --force
```

## 10. Create Storage Symlink

```bash
php artisan storage:link
```

## 11. Set Permissions

```bash
chmod -R 775 storage bootstrap/cache
find storage bootstrap/cache -type d -exec chmod 775 {} \;
find storage bootstrap/cache -type f -exec chmod 664 {} \;
```

Do not change ownership to `www-data` on cPanel unless the server administrator explicitly tells you to. Files should usually remain owned by `CPANEL_USER`.

## 12. Clear and Rebuild Laravel Caches

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 13. Final Verification Commands

```bash
php artisan about
php artisan route:list
php artisan migrate:status
test -f vendor/autoload.php && echo "Vendor installed"
test -d public/build && echo "Frontend assets built"
test -L public/storage && echo "Storage link exists"
curl -I https://new.quickseoanalysis.com
```

Open these URLs:

```text
https://new.quickseoanalysis.com
https://new.quickseoanalysis.com/admin
```

## 14. Full Update Command Sequence

Use this after the app has already been deployed once:

```bash
cd /home/CPANEL_USER/quick-seo-analysis
git pull
composer install --no-dev --optimize-autoloader
npm install
npm run build
php artisan migrate --force
php artisan storage:link
chmod -R 775 storage bootstrap/cache
find storage bootstrap/cache -type d -exec chmod 775 {} \;
find storage bootstrap/cache -type f -exec chmod 664 {} \;
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan about
curl -I https://new.quickseoanalysis.com
```

## 15. Common Troubleshooting Notes

### 500 Error

Check Laravel logs:

```bash
cd /home/CPANEL_USER/quick-seo-analysis
tail -n 100 storage/logs/laravel.log
```

Then clear cache and fix writable folders:

```bash
php artisan optimize:clear
chmod -R 775 storage bootstrap/cache
```

### Wrong Document Root

If you see source files, directory listing, or a cPanel default page, fix the subdomain document root.

Correct:

```text
/home/CPANEL_USER/quick-seo-analysis/public
```

Wrong:

```text
/home/CPANEL_USER/quick-seo-analysis
/home/CPANEL_USER/public_html
/home/CPANEL_USER/new.quickseoanalysis.com
```

### PHP Version Problem

Set PHP 8.2 or newer for `new.quickseoanalysis.com` in cPanel **MultiPHP Manager**.

Check CLI PHP:

```bash
php -v
which php
```

If needed, use a cPanel PHP binary:

```bash
/opt/cpanel/ea-php83/root/usr/bin/php artisan about
```

### Missing Vendor

If the error says `vendor/autoload.php` is missing:

```bash
cd /home/CPANEL_USER/quick-seo-analysis
composer install --no-dev --optimize-autoloader
```

### Missing Frontend Assets

If styling is broken:

```bash
cd /home/CPANEL_USER/quick-seo-analysis
npm install
npm run build
```

### Database Error

Confirm `.env` uses the exact cPanel database values:

```text
DB_DATABASE=DB_NAME
DB_USERNAME=DB_USER
DB_PASSWORD=DB_PASSWORD
```

Then run:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan migrate:status
```

### APP_KEY Missing

If Laravel reports a missing application key:

```bash
php artisan key:generate
php artisan config:cache
```
