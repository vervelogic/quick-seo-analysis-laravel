# Quick SEO Analysis

Quick SEO Analysis is a Laravel 12 SEO scanner SaaS foundation. Version 1 keeps the product focused: a public scan form, basic SEO checks, a report page, lead capture, and a Filament admin panel.

## Stack

- Laravel 12
- MySQL
- Blade, Tailwind CSS, and Vite
- Filament Admin at `/admin`
- Database queue-ready scanner architecture
- Service classes under `app/Services`

## Features

- Public homepage with URL input and free report CTA
- URL validation and normalization
- SEO scan records with UUID public report links
- Checks for reachability, HTTP status, title, meta description, H1 count, canonical, robots meta, HTTPS, page size, response time, links, and images missing alt text
- SEO score out of 100 with recommendations
- Lead capture on report pages
- Admin management for scans, scan results, leads, users, companies, report templates, plans, and settings
- Placeholder architecture for white-label settings, API keys, widgets, AI visibility, GEO visibility, plans, and branded reports

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
npm install
```

Create a MySQL database and update `.env`:

```env
DB_DATABASE=quick_seo_analysis
DB_USERNAME=root
DB_PASSWORD=
```

Run migrations and seed the starter company, admin user, report template, and plan placeholders:

```bash
php artisan migrate --seed
```

Default seeded admin credentials are:

```text
Email: admin@example.com
Password: password
```

For production or shared environments, set these before seeding:

```env
ADMIN_NAME="Your Name"
ADMIN_EMAIL="you@example.com"
ADMIN_PASSWORD="use-a-strong-password"
```

## Local Development

```bash
composer run dev
```

That starts the Laravel server, queue listener, and Vite dev server. You can also run them separately:

```bash
php artisan serve
php artisan queue:listen
npm run dev
```

Build assets for deployment:

```bash
npm run build
```

## Scanning Architecture

The scanner is intentionally service-based:

- `app/Services/Scanner/UrlNormalizer.php`
- `app/Services/Scanner/PageFetcher.php`
- `app/Services/Scanner/HtmlSeoParser.php`
- `app/Services/Scanner/SeoScoreCalculator.php`
- `app/Services/Scanner/SeoScanner.php`

The public v1 flow runs scans immediately so the visitor sees a report without waiting for background polling. `app/Jobs/RunSeoScan.php` is included so the flow can move to queued scans later.

## Admin

Visit `/admin` and sign in with the seeded admin account. Admin access is controlled by `users.is_admin`.

Filament resources included:

- Companies
- Users
- Scans
- Scan Results
- Leads
- Report Templates
- Plans
- Settings page

## Configuration

No domain is hardcoded. Use environment variables:

```env
APP_NAME="Quick SEO Analysis"
APP_URL=https://your-domain.com
QSA_SCAN_TIMEOUT=12
QSA_SCAN_MAX_BYTES=2097152
QSA_DEFAULT_COMPANY_NAME="Quick SEO Analysis"
```

## Deployment Notes

1. Point the web server document root to `public`.
2. Set production `.env` values and `APP_DEBUG=false`.
3. Run `composer install --no-dev --optimize-autoloader`.
4. Run `npm ci && npm run build`.
5. Run `php artisan migrate --force`.
6. Run `php artisan config:cache && php artisan route:cache && php artisan view:cache`.
7. Run a queue worker if scans or report emails are moved to queued jobs:

```bash
php artisan queue:work --tries=2
```

## Tests

```bash
composer test
```

Included tests cover URL normalization, SEO scoring, and homepage rendering. Add HTTP fake based scanner tests when expanding the fetcher behavior.
