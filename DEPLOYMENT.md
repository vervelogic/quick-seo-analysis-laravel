# Hostinger VPS Deployment with WHM/cPanel

This guide deploys Quick SEO Analysis to `new.quickseoanalysis.com` on a Hostinger VPS that has WHM/cPanel installed. It is not for Hostinger shared hosting.

## 1. Create the Subdomain in WHM/cPanel

In WHM, make sure the main cPanel account for `quickseoanalysis.com` exists and has Shell Access enabled.

In cPanel:

1. Open **Domains** or **Subdomains**.
2. Create:

```text
Subdomain: new
Domain: quickseoanalysis.com
Full domain: new.quickseoanalysis.com
```

3. Set the document root to the Laravel public folder:

```text
/home/CPANEL_USER/quick-seo-analysis/public
```

Replace `CPANEL_USER` with the actual cPanel username.

If cPanel creates the subdomain with a temporary document root such as `/home/CPANEL_USER/new.quickseoanalysis.com`, update it in **Domains** so it points to:

```text
/home/CPANEL_USER/quick-seo-analysis/public
```

This is important. Laravel must serve from `/public`, not from the project root.

## 2. DNS

If DNS is managed in WHM/cPanel, confirm the subdomain has an `A` record:

```text
Name: new.quickseoanalysis.com
Type: A
Value: YOUR_VPS_PUBLIC_IP
```

If DNS is managed outside the VPS, create the same `A` record wherever the domain DNS is hosted.

Check propagation:

```bash
dig +short new.quickseoanalysis.com
```

## 3. Select PHP Version and Extensions

In cPanel, open **MultiPHP Manager** and set `new.quickseoanalysis.com` to PHP 8.2 or newer. PHP 8.3 is recommended.

In **Select PHP Version** or WHM EasyApache, make sure these extensions are enabled:

```text
bcmath
ctype
curl
dom
fileinfo
filter
hash
intl
json
mbstring
openssl
pdo
pdo_mysql
session
tokenizer
xml
zip
```

## 4. Create MySQL Database and User in cPanel

In cPanel, open **MySQL Database Wizard**.

Create:

```text
Database: quick_seo_analysis
User: qsa_user
Password: use a strong generated password
Privileges: All Privileges
```

cPanel usually prefixes database names and usernames with the cPanel username. Your final values may look like:

```text
Database: cpaneluser_quick_seo_analysis
Username: cpaneluser_qsa_user
```

Use the prefixed values in `.env`.

## 5. SSH Into the cPanel Account

SSH as the cPanel user, not root:

```bash
ssh CPANEL_USER@YOUR_VPS_PUBLIC_IP
```

Go to the home directory:

```bash
cd /home/CPANEL_USER
```

Confirm Composer, PHP, Node, and npm are available:

```bash
php -v
composer --version
node -v
npm -v
```

If Composer is missing, install it locally for the cPanel user:

```bash
curl -sS https://getcomposer.org/installer -o composer-setup.php
php composer-setup.php --install-dir=$HOME/bin --filename=composer
echo 'export PATH="$HOME/bin:$PATH"' >> ~/.bashrc
source ~/.bashrc
composer --version
```

If Node/npm are missing, install Node through WHM/cPanel tools or ask the VPS administrator to install Node.js for the account.

## 6. Clone or Upload the Project

From `/home/CPANEL_USER`:

```bash
git clone YOUR_REPOSITORY_URL quick-seo-analysis
cd /home/CPANEL_USER/quick-seo-analysis
```

If Git is unavailable, upload the project archive through cPanel File Manager and extract it to:

```text
/home/CPANEL_USER/quick-seo-analysis
```

Do not upload only the `public` folder. The full Laravel project must exist outside the public web root.

## 7. Install Dependencies

From the project root:

```bash
composer install --no-dev --optimize-autoloader
npm install
npm run build
```

If cPanel memory limits interrupt Composer, try:

```bash
COMPOSER_MEMORY_LIMIT=-1 composer install --no-dev --optimize-autoloader
```

## 8. Configure `.env`

Create the environment file:

```bash
cp .env.example .env
nano .env
```

Use values like:

```env
APP_NAME="Quick SEO Analysis"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://new.quickseoanalysis.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cpaneluser_quick_seo_analysis
DB_USERNAME=cpaneluser_qsa_user
DB_PASSWORD=PASTE_CPANEL_DATABASE_PASSWORD

QUEUE_CONNECTION=database
CACHE_STORE=database
SESSION_DRIVER=database

ADMIN_NAME="Admin"
ADMIN_EMAIL="admin@quickseoanalysis.com"
ADMIN_PASSWORD="CHANGE_THIS_ADMIN_PASSWORD"
```

Replace the database name and username with the exact prefixed values shown in cPanel.

## 9. Run Laravel Setup Commands

From the project root:

```bash
php artisan key:generate
php artisan migrate --seed --force
php artisan storage:link
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 10. Set Permissions

From the project root:

```bash
chmod -R 775 storage bootstrap/cache
find storage bootstrap/cache -type f -exec chmod 664 {} \;
find storage bootstrap/cache -type d -exec chmod 775 {} \;
```

On most WHM/cPanel VPS setups, files should remain owned by the cPanel user. Do not `chown` files to `www-data`; cPanel usually uses the account user with Apache/PHP handlers such as suPHP, PHP-FPM, or LSAPI.

If permissions still fail, ask the server admin to confirm the PHP handler and correct ownership for:

```text
/home/CPANEL_USER/quick-seo-analysis/storage
/home/CPANEL_USER/quick-seo-analysis/bootstrap/cache
```

## 11. Confirm Document Root

In cPanel **Domains**, verify again:

```text
new.quickseoanalysis.com -> /home/CPANEL_USER/quick-seo-analysis/public
```

Then open:

```text
https://new.quickseoanalysis.com
https://new.quickseoanalysis.com/admin
```

The admin login is created by the seeder using the `ADMIN_EMAIL` and `ADMIN_PASSWORD` values in `.env`.

## 12. SSL

In cPanel, open **SSL/TLS Status** or **Manage AutoSSL**.

Run AutoSSL for `new.quickseoanalysis.com`. After it completes, verify:

```bash
curl -I https://new.quickseoanalysis.com
```

## 13. Optional Queue Worker

QSA v1 runs public scans synchronously, but the project includes a queue-ready job. If WHM allows cron jobs, add this in cPanel **Cron Jobs**:

```bash
* * * * * cd /home/CPANEL_USER/quick-seo-analysis && php artisan queue:work database --stop-when-empty --tries=2 --timeout=60 >> /home/CPANEL_USER/quick-seo-analysis/storage/logs/queue.log 2>&1
```

For a long-running queue worker, use WHM/root-level systemd or Supervisor if available.

## 14. Troubleshooting

### 500 Error

Check Laravel logs:

```bash
tail -n 100 storage/logs/laravel.log
```

Also check cPanel **Errors** or Apache logs from WHM.

Common fixes:

```bash
php artisan optimize:clear
chmod -R 775 storage bootstrap/cache
```

Confirm `.env` exists and has:

```env
APP_KEY=base64:...
APP_DEBUG=false
APP_URL=https://new.quickseoanalysis.com
```

### Wrong Public Path

If the browser shows Laravel folders, raw files, a directory listing, or a blank cPanel page, the document root is wrong.

Correct value:

```text
/home/CPANEL_USER/quick-seo-analysis/public
```

Incorrect values:

```text
/home/CPANEL_USER/quick-seo-analysis
/home/CPANEL_USER/public_html
/home/CPANEL_USER/new.quickseoanalysis.com
```

### PHP Version Error

If Composer or the site reports PHP version problems, set PHP 8.2+ for the subdomain in **MultiPHP Manager**.

Check CLI PHP:

```bash
php -v
which php
```

On some cPanel servers, the CLI PHP path may be versioned. Examples:

```bash
/opt/cpanel/ea-php83/root/usr/bin/php artisan about
/opt/cpanel/ea-php82/root/usr/bin/php artisan about
```

Use the matching PHP binary for Composer and Artisan if needed.

### Missing Vendor

If you see an error like `Failed opening required vendor/autoload.php`, dependencies are missing.

Run:

```bash
cd /home/CPANEL_USER/quick-seo-analysis
composer install --no-dev --optimize-autoloader
```

Confirm:

```bash
test -f vendor/autoload.php && echo "vendor is installed"
```

### Assets Missing or Styling Broken

Run:

```bash
npm install
npm run build
```

Confirm:

```bash
test -d public/build && echo "assets are built"
```

### Database Connection Error

Check the exact cPanel-prefixed database and username in `.env`:

```env
DB_DATABASE=cpaneluser_quick_seo_analysis
DB_USERNAME=cpaneluser_qsa_user
```

Then clear cached config:

```bash
php artisan optimize:clear
php artisan config:cache
```

## 15. Update Deployment

For later updates:

```bash
cd /home/CPANEL_USER/quick-seo-analysis
git pull
composer install --no-dev --optimize-autoloader
npm install
npm run build
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```
