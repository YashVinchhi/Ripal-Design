# Includes Folder

Shared configuration and utilities required by most pages.

Files
- `auth.php`: Lightweight session-based auth checks. Use to gate admin/client/worker pages.
- `config.php`: Defines constants like `PROJECT_ROOT` and `BASE_PATH`. Adjust these for deployment.
- `db.php`: Creates a PDO connection (`$pdo`) to the MySQL database. Pages check for a valid `$pdo` and fall back to demo data when unavailable.
- `footer.php` / `header.php`: Minimal header/footer fragments used by pages under `public/`, `dashboard/`, `worker/`.

Important notes
- Use environment variables for DB credentials in production; `db.php` currently contains placeholders.
- Always validate/sanitize inputs before using them in SQL queries. Use prepared statements on `$pdo`.
