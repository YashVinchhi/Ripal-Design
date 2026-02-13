# Client Folder

This folder contains pages and utilities used by clients to upload and review files.

Files
- `client_files.php`: Lists files uploaded by a client for a project. Accepts `project_id` via GET and shows file list.
- `client_revisions.php`: Interface for client revision requests and revision history per project.
- `upload_drawings.php`: Form allowing clients to upload drawing files or attachments tied to a project.

Notes
- These pages rely on session auth (`includes/auth.php`) for access control.
- Uploaded files should be validated and stored outside the web root or with access checks in place.
