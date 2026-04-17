# Thefinal — Architecture & Project Management Platform for Design Firms

A lightweight, deployable PHP platform tailored for architecture and design practices. "Thefinal" provides project and client workflows, file/drawing revisions, invoices, and admin dashboards — ready for local development and containerized deployment.

<!-- TOC -->
- [Overview](#overview)
- [Features](#features)
- [Tech stack](#tech-stack)
- [Quick start](#quick-start)
	- [Requirements](#requirements)
	- [Local development](#local-development)
	- [Docker (recommended for full stack)](#docker-recommended-for-full-stack)
- [Configuration](#configuration)
- [Database](#database)
- [Scripts & useful commands](#scripts--useful-commands)
- [Development & testing](#development--testing)
- [Deployment](#deployment)
- [Project structure](#project-structure)
- [Security](#security)
- [Contributing](#contributing)
- [License](#license)
- [Contact](#contact)

## Overview

This repository contains the codebase and deployment artifacts for Thefinal — an integrated web platform used by an architecture/design practice for managing projects, clients, file revisions, invoices, and internal workflows.

The project mixes composer-managed PHP backend components, Tailwind-based frontend styles, and Docker artifacts for reproducible deployments.

## Features

- Role-based access: client, worker, admin/employee views and APIs
- Project and task management with file/drawing uploads and revision history
- Client portal for file sharing, revisions, and invoices
- PDF generation (dompdf) and email notifications (PHPMailer-compatible helper)
- Notification center and activity logs
- Container-ready: Dockerfiles and docker-compose for local and production setups

## Tech stack

- PHP (recommended 8.1 / 8.2)
- MySQL / MariaDB
- Composer for PHP dependencies ([composer.json](composer.json))
- Tailwind CSS for styling ([package.json](package.json))
- Nginx / PHP-FPM or Apache (containerized images)

## Quick start

### Requirements

- PHP 8.1+ and Composer
- Node.js (for Tailwind builds) and npm
- MySQL 8+ (or Docker)
- Docker & Docker Compose (optional, recommended for full-stack local runs)

### Local development (quick)

1. Clone the repo:

```bash
git clone <repo-url> thefinal
cd thefinal
```

2. Copy env template and configure:

```bash
cp .env.example .env
# Edit .env to set DB_*, MAIL_*, APP_BASE_URL, and other values.
```

3. Install PHP dependencies:

```bash
composer install
```

4. Install Node deps and build CSS (Tailwind):

```bash
npm install
npm run build:css
```

5. Import database schema:

```bash
mysql -u root -p < sql/database.sql
# Optionally import sample data:
mysql -u root -p your_db_name < sql/dummy_data.sql
```

6. Serve the site (for quick testing using built-in PHP server):

```bash
php -S localhost:8000 -t public
# Visit http://localhost:8000
```

Notes:

- For development it's fine to use the PHP built-in server. For production, point your webserver to the `public/` directory.
- The app will also detect a `.env` file and load environment variables via `includes/config.php`.

### Docker (recommended for full stack)

Run the full application and a seeded MySQL instance locally using Docker Compose:

```bash
cp .env.example .env
docker compose up -d --build
```

After startup:

- App: http://localhost:8080 (or port defined in `.env`)
- Database: localhost:3306

To clean volumes and re-seed sample data:

```bash
docker compose down -v
docker compose up -d --build
```

For production compose, see [docker-compose.prod.yml](docker-compose.prod.yml) and the deployment notes in [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md).

## Configuration

- Copy `.env.example` → `.env` and fill values. Do not commit `.env`.
- Typical keys: `DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASS`, `DB_NAME`, `MAIL_HOST`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_PORT`, `APP_BASE_URL`, `APP_ENV`.

## Database

- Schema: [sql/database.sql](sql/database.sql)
- Sample/dummy data: [sql/dummy_data.sql](sql/dummy_data.sql)

Importing the schema creates tables for users, projects, files, invoices, roles/permissions, notifications, and other domain entities.

## Scripts & useful commands

- Build Tailwind CSS: `npm run build:css` (see [package.json](package.json))
- Watch CSS during development: `npm run watch:css`
- Run PHPUnit tests: `vendor/bin/phpunit` (after `composer install`)
- Static analysis: `vendor/bin/phpstan analyse` (if installed)
- Code style: `vendor/bin/php-cs-fixer fix` (if installed)

## Development & testing

- Install dev dependencies via Composer to enable test and analysis tools:

```bash
composer install --dev
```

- Run tests:

```bash
vendor/bin/phpunit --testdox
```

## Deployment

See the production deployment guide: [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md).

Main production artifacts include `Dockerfile`, [docker-compose.prod.yml](docker-compose.prod.yml), and container configuration in the `docker/` folder.

Automated builds and image publishing are performed via CI (GitHub Actions) in this repository (if configured).

## Project structure (high level)

- `public/` — Document root for the web server
- `includes/` — Bootstrapping, configuration, DB helpers, auth, mail helper
- `admin/`, `client/`, `worker/`, `dashboard/` — Role-specific pages and endpoints
- `sql/` — Database schema & seed scripts
- `docker/` — Docker artifacts for images and seeded DB
- `app/`, `src/`, `stubs/` — Application code, libraries, and small shims
- `assets/` — Compiled CSS/JS and content

## Security

- Never commit secrets. Use `.env` and environment variables in CI/CD.
- See [SECURITY_REPORT.md](SECURITY_REPORT.md) for known security guidance and reporting.

If sensitive credentials were committed historically, rotate them and remove them from Git history only after coordinating with maintainers.

## Contributing

Contributions are welcome. Suggested first steps:

1. Open an issue describing the bug or feature.
2. Create a topic branch off `main`.
3. Add tests where applicable and update documentation.
4. Open a pull request referencing the issue.

If you want help setting up tests, CI, or a CONTRIBUTING.md, I can assist.

## License

No `LICENSE` file was detected in the repository. Add a `LICENSE` to make the project license explicit.

## Contact

For support or questions, open an issue in this repository or contact the project owner/maintainers.

---

This README was reorganized to provide a concise developer-focused quickstart and clear sections for configuration, development, and deployment. Update roadmap and contributor guidance as you prefer.


