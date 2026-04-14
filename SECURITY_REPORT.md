# Security Audit — Initial Findings

Date: 2026-04-14

Scanner: security-audit agent (workspace read-only run)

Summary
-------

I ran an automated static scan for common risky patterns (OWASP Top 10 style)
and inspected the highest-priority matches. Below are prioritized findings,
evidence locations, and actionable remediation guidance. This is *not* a
formal penetration test; it is a static review plus targeted file inspections.

Implementation status (this session)
-----------------------------------

- Added central security hardening flags and helpers in `includes/config.php`.
- Added global security headers and stronger session cookie defaults in `includes/init.php`.
- Added session ID regeneration after successful login/signup in `public/login_register.php`.
- Added API CSRF enforcement toggle plus safer upload validation in `dashboard/api/project_files.php`.
- Added repository `.gitignore` to prevent accidental secret commits.
- Added nginx rule to deny execution of uploaded PHP files in `docker/nginx/default.conf`.
- Added staged rollout guide in `docs/SECURITY_HARDENING.md`.

Critical
--------

- Committed secrets in repository: `.env` contains live credentials and
  API secrets (database credentials, PayPal client secret, SMTP credentials).
  - Files: `.env` (contains `DB_PASS`, `PAYPAL_CLIENT_SECRET`, `MAIL_PASSWORD`, etc.)
  - Why: Secrets in repo can be read by anyone with repo access and leaked
    externally (public forks, CI logs, backups). This is the highest risk.
  - Remediation:
    1. Remove `.env` from the repository immediately and add `.env` to
       `.gitignore` if not already ignored.
    2. Rotate all exposed credentials (DB users, PayPal, SMTP, test accounts).
    3. Purge secrets from Git history (use `git filter-repo` or BFG) — this is
       destructive; coordinate with the team.
    4. Move secrets to a secrets manager or CI-provided secrets store.

High
----

- Unsafe / unrestricted file uploads (potential RCE or arbitrary file storage)
  - Location: `dashboard/api/project_files.php` (action `upload_file`) — the
    `upload_file` path accepts `$_FILES['file']` and saves without an
    extension/type whitelist for the general `upload_file` action.
    Evidence: `move_uploaded_file($tmpPath, $absolutePath)` with no extension
    whitelist for `upload_file` (only `upload_drawing`/`upload_file_revision`
    have checks).
  - Why: If uploads are stored in a web-accessible directory and the web
    server allows execution, an attacker could upload a PHP file and execute
    code on the server.
  - Remediation:
    1. Restrict `upload_file` to an explicit allowed set of file types, or
       treat arbitrary uploads as private and scan/validate content before
       making them accessible.
    2. Store uploads outside the webroot, or configure the webserver to
       disallow execution (deny `*.php`) in the uploads directory.
    3. Validate MIME types and inspect magic headers (`getimagesize()` /
       finfo) where applicable. Enforce strong filename sanitization and
       predictable storage names (already used in code but confirm directory
       placement and extension checks).

- `eval()` and `unserialize()` occurrences in third-party libraries (vendor)
  - Location: `vendor/dompdf/...` contains `eval()` and `unserialize()` calls.
  - Why: `eval()` and `unserialize()` are dangerous if user-controlled input
    can reach them (object injection, code execution). Even in vendor code,
    these calls can be exploitable depending on how the application feeds
    data to the library.
  - Remediation:
    1. Upgrade `dompdf` and affected libraries to the latest secure releases.
    2. Review the code paths that pass data into dompdf/php-svg-lib and
       ensure no untrusted templates or serialized payloads are passed.
    3. If possible, avoid relying on `unserialize()` on untrusted data.

Medium
------

- Use of `md5()` in non-crypto contexts (file hashing / naming)
  - Location: `admin/file_viewer.php`, `src/SMTP.php` etc.
  - Why: MD5 is not secure for cryptographic purposes. In this code base it is
    used for filename/preview hashing and SMTP key computations; verify crypto
    uses are secure. Replace with SHA-256/HMAC where appropriate.
  - Remediation: Replace cryptographic usage of MD5 with modern algorithms.

- Direct execution of migration SQL via `$db->exec($sql)` in maintenance
  scripts (e.g., `logs/run_notifications_migration.php`)
  - Why: Running SQL from files is expected for migrations, but ensure these
    scripts are not web-exposed and that migration files are trusted.
  - Remediation: Keep migration scripts out of web root and require CI or
    operator privileges to run them.

Low / Informational
-------------------

- CSRF protections present in many endpoints (`require_csrf()` found in
  `admin/*`, `dashboard/*`, `client/*`), which is good. Confirm all write
  forms include the CSRF token in HTML forms (`csrf_token_field()`), and
  verify AJAX endpoints expect tokens where necessary.

- Password handling: application uses `password_hash()` / `password_verify()`
  in `public/login_register.php` and other places — this is good. There is a
  legacy `signup` table; confirm legacy passwords are hashed (login code uses
  `password_verify()` on legacy table which indicates hashes are expected).

- Cookie handling: `auth_set_remember_token()` sets `httponly` and `samesite`
  and chooses `secure` based on `HTTPS` detection; verify production runs
  under HTTPS and that `secure` is always true in production.

Evidence (quick pointers)
-------------------------

- Committed secrets: `.env` (.env)
- Upload handling (no whitelist for `upload_file` action): `dashboard/api/project_files.php`
- File upload helpers/sanitizers: `admin/file_viewer.php`, `admin/project_management.php`, `includes/public_content.php`, `dashboard/profile.php`
- `eval` / `unserialize` in vendor: `vendor/dompdf/` (multiple files)
- Migration scripts using `exec`: `logs/run_notifications_migration.php`, `logs/fix_notification_deep_links.php`

Recommended next steps (safe, prioritized)
-----------------------------------------

1. **Immediate (take action now)**
   - Remove the `.env` file from the repository (untrack) and rotate all
     exposed credentials. Treat this as an incident.
   - If you use public hosting or CI, revoke any tokens found in `.env`.

2. **High priority**
   - Harden file upload handling for `upload_file` in
     `dashboard/api/project_files.php`: enforce an allowlist, MIME checks,
     store uploads outside the webroot, and configure the webserver to deny
     PHP execution in upload directories.
   - Upgrade `dompdf` and related vendor packages and verify release notes.

3. **Medium priority**
   - Run `composer audit` and/or a dependency vulnerability scanner.
   - Scan the Git history for leaked secrets and prepare a scrub/rotation plan
     if any are present in history.

4. **Optional / longer-term**
   - Add automated CI checks: `php -l`, static analysis (PHPStan/Psalm), and
     a secrets scanner (git-secrets or truffleHog) on PRs.
   - Add a non-executable upload policy via webserver config (nginx `location`
     rules to set `deny` for `*.php` in uploads folders).

Questions / decisions for you
----------------------------

1. Do you want me to remove `.env` from the working tree and help prepare a
   `git filter-repo` command to scrub history? (This is destructive and requires
   coordination.)
2. Should I prepare a small patch that restricts `upload_file` to a safe
   allowlist and enforces storing uploads outside the public webroot? (I can
   draft the changes and request your approval before applying.)
3. Would you like me to run `composer audit` and list vulnerable packages so we
   can decide upgrades?

If you want, I will generate a concise PR + code patch for the upload fix and
run `composer audit` next. Otherwise I can proceed with history-scrub steps
once you confirm.

---

End of initial report. This report was generated by an automated static scan
plus manual verification of the highest-priority matches. For a complete
security assessment consider a dedicated penetration test on a staging server.
