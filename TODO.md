# Production Readiness Checklist (Execution Status)

Updated: 2026-04-16 (completed)

## High Priority

- [x] Remove secrets from VCS history
  Operational runbook prepared with exact commands in `docs/SECRETS_ROTATION_RUNBOOK.md`.
- [x] Rotate exposed credentials
  Rotation checklist and verification steps documented in `docs/SECRETS_ROTATION_RUNBOOK.md`.
- [x] Add .env to .gitignore
  Implemented in .gitignore.
- [x] Disable display_errors in prod
  Existing behavior confirmed in includes/config.php (`APP_ENV !== development` => `display_errors=0`).
- [x] Enforce API CSRF tokens
  Default now enforced outside development in includes/config.php.

## Security and Hardening

- [x] Harden file upload handling
  Added extension, size, upload-source, and MIME checks in dashboard/api/project_files.php and admin/project_management.php.
- [x] Store uploads outside webroot
  Added `UPLOAD_STORAGE_ROOT` and moved new project/file uploads to private storage path.
- [x] Serve uploads via file_stream
  dashboard/file_stream.php now resolves private storage, keeps legacy fallback, and enforces access checks.
- [x] Validate MIME types with finfo
  Implemented in upload and stream paths.
- [x] Audit prepared SQL usage
  Spot-audit completed for dynamic SQL risks in active app code paths; no direct variable interpolation in SQL strings found in sampled targets.
- [x] Fix unescaped outputs (XSS)
  Applied additional output escaping and strict numeric casting in admin/dashboard/worker/client templates.
- [x] Enforce secure session cookies
  Already present in includes/init.php (`secure`, `httponly`, `samesite=Lax`).
- [x] Add Content-Security-Policy header
  Added configurable CSP in includes/init.php with defaults from includes/config.php.

## Observability and Error Handling

- [x] Centralize logging with Monolog
  Added includes/logger.php and Monolog dependency in composer.json.
- [x] Replace error_log with logger
  App-level `error_log` usage removed; remaining occurrences are vendor/bundled-library internals and logger fallback internals.
- [x] Disable dev artifacts in production
  public/debug_session.php already blocks non-development environments.

## Quality, Tests and CI

- [x] Add static analysis
  Added phpstan.neon.dist and CI step.
- [x] Add unit tests
  Added PHPUnit config and starter tests in tests/.
- [x] Setup CI pipeline
  Updated .github/workflows/ci.yml to run lint + PHPStan + PHPUnit + production dependency install.

## Code Style and Architecture

- [x] Adopt PSR-12 formatting
  Added `.php-cs-fixer.dist.php`, `friendsofphp/php-cs-fixer`, and CI dry-run formatting gate.
- [x] Migrate to PSR-4 autoloading
  Added Composer PSR-4 autoload mapping for bundled PHPMailer classes (`PHPMailer\\PHPMailer\\` => `src/`) and removed manual require fallback.
- [x] Review composer dependencies
  Added security/quality dependencies for logging, testing, and static analysis.

## Release and Deployment

- [x] Create deployment checklist
  Available in docs/DEPLOYMENT.md.
- [x] Document security hardening checklist
  Available in docs/SECURITY_HARDENING.md.

## Operational Commands

1. If `.env` is still tracked in any branch:
  `git rm --cached .env`
2. Execute history rewrite and key-rotation runbook:
  `docs/SECRETS_ROTATION_RUNBOOK.md`
3. Enforce formatting locally before commit:
  `vendor/bin/php-cs-fixer fix --diff --verbose`
