# Final Verification

Run these commands after cloning the repository on a machine with PHP, Composer, Node.js, and MySQL.

## 1. Confirm Required Tools

```bash
php -v
composer --version
node -v
npm -v
mysql --version
```

Expected:

- PHP 8.2 or newer
- Composer 2.x
- Node.js 20 or newer
- MySQL 8.x or compatible MariaDB

## 2. Install Dependencies

```bash
composer install
npm install
```

## 3. Configure Environment

```bash
cp .env.example .env
php artisan key:generate
```

Update `.env` with local database credentials:

```env
APP_URL=http://localhost:8000
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=quick_seo_analysis
DB_USERNAME=root
DB_PASSWORD=
```

Create the database if needed:

```bash
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS quick_seo_analysis CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

## 4. Verify Laravel Boots

```bash
php artisan about
php artisan config:clear
php artisan route:list
```

Expected named routes:

- `home`
- `scan.store`
- `report.show`
- `lead.capture`
- Filament admin routes under `/admin`

## 5. Run Migrations and Seed Data

```bash
php artisan migrate:fresh --seed
php artisan migrate:status
```

Expected tables include:

- `companies`
- `users`
- `scans`
- `scan_results`
- `leads`
- `report_templates`
- `widget_keys`
- `plans`
- `api_keys`
- `jobs`
- `sessions`
- `cache`

## 6. Build Assets

```bash
npm run build
```

Expected output:

- Compiled assets in `public/build`
- No Vite or Tailwind errors

## 7. Run Tests

```bash
composer test
```

Expected:

- Homepage test passes
- URL normalizer test passes
- SEO score calculator test passes

## 8. Run Locally

Start the app:

```bash
php artisan serve
```

In another terminal, start Vite for development:

```bash
npm run dev
```

Optional queue worker:

```bash
php artisan queue:work --tries=2
```

Open:

```text
http://localhost:8000
http://localhost:8000/admin
```

Seeded admin defaults:

```text
Email: admin@example.com
Password: password
```

## 9. Manual Product Check

1. Open the homepage.
2. Submit a URL such as `https://example.com`.
3. Confirm a scan record is created.
4. Confirm the report page loads at `/report/{uuid}`.
5. Submit the lead capture form.
6. Sign in to `/admin`.
7. Confirm scans, scan results, leads, users, companies, report templates, plans, and settings are visible.

## 10. Production Cache Check

Before deployment, verify production cache commands succeed:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize:clear
```

## 11. If Something Fails

Run:

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
tail -n 100 storage/logs/laravel.log
```

Common fixes:

- Confirm `.env` exists and has a valid `APP_KEY`.
- Confirm the database exists and credentials are correct.
- Confirm `storage` and `bootstrap/cache` are writable.
- Confirm Composer packages installed successfully.
- Confirm `public` is the web server document root.
