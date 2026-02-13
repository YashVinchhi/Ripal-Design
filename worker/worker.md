# Worker Folder

Worker-facing pages for assigned projects and dashboards.

Files
- `assigned_projects.php`: Lists projects assigned to the currently authenticated worker.
- `dashboard.php`: Worker dashboard showing assigned tasks, progress and contact info.
- `project_details.php`: Detailed view of a specific project (tasks, location, due date, progress). Uses `includes/db.php` when available.
- `worker_dashboard.css`: Stylesheet for the worker UI.
- `worker_rating.php`: Rating form allowing clients or admins to rate workers (prototype).

Notes
- Worker pages include `../includes/header.php` and rely on session auth; ensure `includes/auth.php` is used to protect routes.
