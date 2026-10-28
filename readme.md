<p align="center"><img src="logo.png" alt="Haridwar Rishikesh Tourism" width="220"></p>

# Haridwar Rishikesh Tourism

The website and content management system for [haridwarrishikeshtourism.com](https://www.haridwarrishikeshtourism.com), rebuilt on **Laravel 13** and **Filament 5**.

The rebuild keeps every legacy URL exactly as Google indexed it (including the `.html` suffix). It also adds an admin panel, a booking and enquiry flow, and a technical SEO stack.

## Features

- **Public website**: tour packages, destinations, blog and static pages, all served from the database at their original `*.html` URLs.
- **Admin panel** (`/hrtsiteadmin` by default, set with `ADMIN_PATH`; Filament): manage pages, menu items, authors, testimonials, redirects, enquiries and site settings. The dashboard has widgets for site statistics, SEO health and the latest enquiries.
- **Bookings & enquiries**: `/book-now.php` and `/enquiry` forms with rate limiting, email notifications and a thank-you page.
- **Search & chatbot**: site search with autosuggest, and a package-recommendation chatbot endpoint.
- **SEO tooling**:
  - Canonical tags, JSON-LD schema, Open Graph and Twitter cards
  - `sitemap.xml`, `sitemap.html`, `urllist.txt`, `robots.txt` and an RSS feed (`feed.xml`)
  - `llms.txt` and `llms-full.txt`
  - IndexNow pings
  - Managed 301 redirects
  - A canonical-host middleware and legacy-URL redirects
- **Performance**:
  - Full-page caching for public routes (no sessions or cookies), cleared automatically when content is edited
  - Security headers
  - Optimised images (WebP)
- **Legacy migration tools**: commands to archive the old site, import its content and media, and check SEO parity.

## Tech Stack

| Layer     | Technology                                     |
|-----------|------------------------------------------------|
| Backend   | PHP 8.3+, Laravel 13                           |
| Admin     | Filament 5                                     |
| Frontend  | Blade, Tailwind CSS 4, Vite 8                  |
| Database  | MySQL / MariaDB                                |
| Tooling   | Composer, Node.js 20+ / npm, Laravel Boost     |

## Requirements

- **PHP 8.3 or newer** with these extensions: `intl`, `pdo_mysql`, `mbstring`, `openssl`, `curl`, `fileinfo`, `zip`, `gd`, `bcmath`
- **Composer** 2.x
- **Node.js** 20+ and npm
- **MySQL** 8 or MariaDB 10.4+ (XAMPP works fine)

> **Windows / XAMPP note:** XAMPP ships PHP 8.2, which is too old for this project. Use XAMPP only for MySQL. Install PHP 8.3+ separately, for example with `winget install PHP.PHP.8.4`, and enable the extensions above in its `php.ini`.

## Local Setup

```bash
# 1. Clone
git clone https://github.com/kabu631/haridwar-rishikesh-tourism.git
cd haridwar-rishikesh-tourism

# 2. Install dependencies
composer install
npm install

# 3. Environment
cp .env.example .env          # Windows: copy .env.example .env
php artisan key:generate

# 4. Database: create an empty `hrt_tourism` database first, e.g.
#    mysql -u root -e "CREATE DATABASE hrt_tourism CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan migrate --seed    # loads all site content via ContentSeeder
php artisan storage:link

# 5. Create an admin user (a password is generated if --password is omitted)
php artisan app:create-admin you@example.com --name="Your Name"
```

### Running the app

```bash
# Option A: development (hot reload)
npm run dev          # terminal 1
php artisan serve    # terminal 2

# Option B: production-like assets
npm run build
php artisan serve
```

- Website: http://localhost:8000
- Admin panel: http://localhost:8000/hrtsiteadmin

## Artisan Commands

| Command | Description |
|---------|-------------|
| `php artisan app:create-admin {email} [--name=] [--password=]` | Create or update an admin panel user |
| `php artisan legacy:fetch [--fresh] [--concurrency=4]` | Download a snapshot of the legacy site |
| `php artisan legacy:import [--skip-images] [--seed]` | Build the content snapshot and media from the archived legacy site |
| `php artisan seo:parity [--base=] [--min-coverage=97] [--report=]` | Compare the new site with the legacy snapshot page by page |
| `php artisan tripadvisor:import-reviews` | Import 4 and 5 star Tripadvisor reviews as homepage testimonials (needs `TRIPADVISOR_API_KEY`) |
| `php artisan demo:export [--path=static-demo] [--base=] [--live=]` | Export a static, noindex preview (e.g. for GitHub Pages) |

## Configuration

Key `.env` settings (see `.env.example` for the full list):

| Variable | Purpose |
|----------|---------|
| `DB_*` | Database connection (default: `hrt_tourism`, user `root`, no password) |
| `ADMIN_PATH` | URL segment of the admin panel (default `hrtsiteadmin`) |
| `SEO_SITE_URL` | Canonical live domain |
| `SEO_ALLOW_INDEXING` | Override indexing. By default it is on only when `APP_ENV=production` |
| `SEO_FORCE_CANONICAL_HOST` | One-hop http→https and bare-domain→www redirects |
| `PAGE_CACHE_TTL` | Seconds to cache rendered pages (cleared on admin edits) |
| `SITE_ENQUIRY_EMAIL` | Where enquiry notifications are sent |
| `MAIL_*` | Mail transport for enquiry emails (`log` by default) |
| `GA4_MEASUREMENT_ID`, `GOOGLE_ADS_*` | Analytics and ads tracking (also editable in Admin → Settings) |
| `SEO_GOOGLE_VERIFICATION`, `SEO_BING_VERIFICATION` | Search console verification |
| `INDEXNOW_KEY` | 32-character key for IndexNow re-crawl requests |
| `TRIPADVISOR_API_KEY` | Tripadvisor Content API key |

### Production checklist

**Server:** PHP 8.3+ with the extensions listed above (`intl` is required by the admin panel) and OPcache enabled. Point the web root at `public/`. The web server user needs write access to `storage/`, `bootstrap/cache/` and `public/` (images uploaded in the admin are saved under `public/`).

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://www.haridwarrishikeshtourism.com
ADMIN_PATH=<a private segment of your own>
LOG_STACK=daily
LOG_LEVEL=warning
SESSION_SECURE_COOKIE=true
MAIL_MAILER=smtp              # plus MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD
MAIL_FROM_ADDRESS="mail@haridwarrishikeshtourism.com"
SITE_OPENING_HOURS="Mo-Su 09:00-20:00"   # your real hours; used in LocalBusiness structured data
```

Then run:

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build       # or build locally and upload public/build/
php artisan migrate --force   # first deploy: php artisan migrate --seed --force
php artisan optimize
php artisan filament:optimize
php artisan app:create-admin you@example.com --name="Your Name"
```

Add the scheduler to cron (weekly Tripadvisor import):

```cron
* * * * * cd /path/to/site && php artisan schedule:run >> /dev/null 2>&1
```

After every deploy, run `php artisan optimize` and `php artisan filament:optimize` again. Check speed with [PageSpeed Insights](https://pagespeed.web.dev/) once the site is live.

## Project Structure

```
app/
├── Console/Commands/     # Admin, legacy import, SEO parity, Tripadvisor, static export
├── Filament/             # Admin panel resources, pages and dashboard widgets
├── Http/Controllers/     # Home, Page, Search, Sitemap, Robots, Feed, LlmsTxt, Enquiry, Booking, Chatbot
├── Http/Middleware/      # Page cache, canonical host, legacy redirects, security headers
├── Models/               # Page, MenuItem, Author, Enquiry, Testimonial, Redirect, Setting, Media, User
└── Support/              # Content rendering, SEO/schema builders, media, search, legacy parsing
routes/
├── public.php            # Cacheable, session-less public routes
└── web.php               # Session routes (booking & enquiry forms)
database/seeders/         # ContentSeeder loads the site content
```

## Testing

```bash
composer test
```

## SEO Implementation Checklist

The rebuild is designed to meet the following checklist.

### Technical Foundation
- robots.txt configuration
- XML sitemap submission
- Canonical tags (`rel="canonical"`)
- Flat site architecture (3 clicks or less)
- HTTPS encryption (SSL certificate)
- Custom 404 error page setup
- Proper redirect execution (301 vs. 302 redirects)
- Eradication of redirect chains and loops
- Clean source code with minimal inline scripts
- Elimination of server errors (5xx)
- Proper handling of URL parameters
- Native RSS feed implementation
- Noindex/Nofollow tag management
- Localization setups via hreflang tags
- JavaScript rendering optimization

### User Experience & Performance
- Mobile-first responsive design
- Largest Contentful Paint (LCP) under 2.5 seconds
- Interaction to Next Paint (INP) under 200 milliseconds
- Cumulative Layout Shift (CLS) under 0.1
- Server Response Time optimization (TTFB)
- Image compression and conversion (WebP/AVIF formats)
- Browser caching configuration
- Code minification (HTML, CSS, JavaScript)
- Content Delivery Network (CDN) deployment
- Complete removal of intrusive interstitial popups
- Dynamic page layout scaling
- Touch target sizing and spacing

### On-Page & Semantic SEO
- Single H1 tag usage per page
- Logical heading hierarchy (H2, H3, H4)
- Title tag optimization (50–60 characters)
- Meta description optimization (150–160 characters)
- Keyword placement in the first 100 words
- Primary keyword in the URL string
- Use of short, lowercase URLs with hyphens
- Image alt text insertion
- Image file name optimization
- Schema markup injection (JSON-LD format)
- Canonical self-referencing
- Internal linking with descriptive anchor text
- External linking to authoritative domains
- Open Graph and Twitter Card tag configurations

### Content & E-E-A-T Strategy
- Match search intent (Informational, Transactional, Navigational, Commercial)
- High original data density
- Topic cluster creation
- Elimination of keyword stuffing
- Integration of Natural Language Processing (NLP) terms
- Inclusion of verifiable author bios
- Clearly stated editorial review dates
- Accurate citation of external data sources
- Elimination of thin or superficial text
- Prevention of duplicate content blocks
- High readability scores
- Regular content decay updates

### Off-Page & Authority
- Editorial backlink acquisition
- Anchor text diversification
- High-authority brand mention accumulation
- Local citation building (Google Business Profile)
- Social sharing trigger integration
- Toxic backlink monitoring
- Nofollow/UGC/Sponsored link attribute usage
- Industry directory submissions
