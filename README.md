# Ripal Design / Thefinal — PHP web platform for architecture & project workflows

Lightweight, deployable PHP platform used by an architecture & design practice. The repository contains:

- A public marketing site and content system
- A client portal (`client/`) for file sharing and revisions
- Admin and worker dashboards (`admin/`, `worker/`, `dashboard/`) with project, invoice, and user management
- File/drawing upload, project activity, and notification systems
- Docker + GitHub Actions configs for containerized CI/CD

This README gives a developer-focused quickstart, deployment pointers, and a short overview of the repository layout.

---

## Quick links

- `docs/DEPLOYMENT.md` — production deployment & CI/CD guide
- `docker-compose.prod.yml`, `Dockerfile`, `docker/nginx/` — container setup used in CI/CD
- `sql/database.sql` — full database schema (import for a fresh instance)
- `.env.example` — environment variables template (copy to `.env` locally)

## Features (high level)

- Role-based users (client, worker, employee/admin) with RBAC extension
- Project management: projects, assignments, milestones, drawings, files, activity logs
- Client-facing features: file revisions, dashboards, invoices/ PDF export
- Notifications system and SMTP mail helper (PHPMailer integration)
- CSRF protection, session helpers, and utilities for safe operation
- PDF generation via `dompdf` (installed via Composer)

## Tech stack

- PHP 8.x (Dockerfile targets `php:8.2-fpm`)
- MySQL 8.x (used in `docker-compose.prod.yml`)
- Composer-managed PHP dependencies (uses `dompdf/dompdf`)
- Nginx + PHP-FPM in the production Docker setup

## Quickstart — local development

Requirements:

- PHP 8.1/8.2+, Composer
- MySQL / MariaDB (or use Docker compose below)
- Optional: Laragon, XAMPP, or Docker for local LAMP-like setups

1. Clone the repository

```bash
git clone <your-repo-url> thefinal
cd thefinal
```

2. Copy environment template and update values

```bash
cp .env.example .env
# Edit .env and set DB_HOST, DB_USER, DB_PASS, DB_NAME, MAIL_* and APP_BASE_URL
```

3. Install PHP dependencies

```bash
composer install --no-dev --optimize-autoloader
```

4. Create the database and import schema

```bash
# Example using local MySQL
mysql -u root -p < sql/database.sql

# Optionally load dummy data
mysql -u root -p your_db_name < sql/dummy_data.sql
```

5. Serve locally (quick test)

```bash
php -S localhost:8000 -t public
# then open http://localhost:8000/
```

Notes:

- If your web server's document root points to the project root (not `/public`), `includes/config.php` detects this and sets `PUBLIC_PATH_PREFIX` accordingly so links work from either mode. For production, point your server to the `public/` directory.

## Docker & CI (build / run)

The repository includes a production-oriented `Dockerfile` and `docker-compose.prod.yml` used by CI and the deploy process.

Build locally:

```bash
docker build -t thefinal:local .
```

Run with compose (uses `IMAGE_NAME` when you want to override the remote image name):

```bash
IMAGE_NAME=thefinal:local docker compose -f docker-compose.prod.yml up -d
```

See `docs/DEPLOYMENT.md` for full CI/CD and deployed server setup details.

## Configuration

- Copy `.env.example` → `.env` and fill values; do not commit `.env`.
- Important env keys: `DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASS`, `DB_NAME`, `MAIL_HOST`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_PORT`, `APP_BASE_URL`, `APP_ENV`.
- The app reads environment variables in `includes/config.php` (it will also load a `.env` file if present).

## Database & seeds

- Full schema: `sql/database.sql`
- Example/dummy content: `sql/dummy_data.sql`

Importing the schema creates tables for users, projects, files, invoices, RBAC (roles/permissions), notifications, and more.

## Directory overview (important paths)

- `public/` — public-facing site files and docroot assets
- `includes/` — core bootstrap, config, DB, auth, utilities, mail helper
- `admin/`, `client/`, `worker/`, `dashboard/` — role-specific UI pages and APIs
- `sql/` — database schema and helper SQL
- `docker/` — nginx configuration used by production compose
- `src/` and `stubs/` — bundled libraries and PHPMailer shims
- `Common/` — shared header/footer and UI partials

## Security & maintenance notes

- Do NOT commit any credentials. Use the `.env` file and server environment variables.
- Rotate credentials if they were ever committed to Git history.
- Consider removing sensitive historical commits from Git history only after coordinating with your team.

## Contributing

If you'd like me to help with additional cleanup, automated tests, or CI steps, I can open PRs and run checks. Suggested starter tasks:

- Add PHPUnit tests and a `phpunit.xml` configuration
- Add linting or static analysis (PHPMD, PHPStan)
- Add a lightweight health-check endpoint for container orchestration

## Where to find more details

- Deployment and CI/CD: `docs/DEPLOYMENT.md`
- Environment template: `.env.example`
- DB schema: `sql/database.sql`

---

Updated: 2026-04-14

If you want, I can commit additional tidy-ups (remove legacy archives, add quick dev docker-compose, or a short CONTRIBUTING.md). Tell me which next step you prefer.

