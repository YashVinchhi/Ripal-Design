# Ripal Design — Public Site

This folder contains the reorganized Ripal Design website and supporting files.

Purpose
- Public-facing site files, assets, and includes for the project.

Primary entry
- `ripal_design/public/index.html` — main public homepage.

Structure
- `public/` — HTML/PHP pages intended to be served (index, about, services, contact, login, etc.).
- `dashboard/` — authenticated user pages (dashboard, profile, project details).
- `admin/` — admin tools (user/project management, payments, file viewer).
- `client/` — client-specific pages (uploads, revisions, files).
- `worker/` — worker-specific pages (assigned projects, ratings).
- `includes/` — shared server includes: `db.php`, `auth.php`, `header.php`, `footer.php`.
- `assets/` — CSS, JS, and images used by the site.
- `sql/` — example schema and SQL helpers.

Quick setup (local development)
1. Requirements: PHP 7.4+ (or PHP 8), a web server (Apache/Nginx) or the PHP built-in server.
2. From the repo root run the built-in server (for quick testing):

```bash
php -S localhost:8000 -t ripal_design/public
```

3. Open `http://localhost:8000` in your browser.

Database
- `ripal_design/includes/db.php` is a PDO stub. Replace the placeholder credentials with environment variables or a secure configuration before using in production.

Assets and images
- The project was updated to reference local images stored in `Content/` (root) and `ripal_design/assets/images/`.
- Recommended: rename long `Content/` filenames to simpler names (e.g., `slide1.jpg`) and update references for reliability.
