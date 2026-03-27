# Issues Closure Report

Date: 2026-03-25
Source audit: issues.md (24 findings)
Scope: Remediation status after code updates and verification pass

## Executive Summary
- Fixed: 21
- Partially Fixed: 3
- Blocked: 0
- Total: 24

## Verification Performed
- PHP syntax validation (`php -l`) on key modified files passed.
- Pattern re-scan for high-risk items:
  - no jQuery 4.0.0 references in active target pages
  - no `?debug=1`/`var_dump($_SESSION)` in runtime profile flow
  - no delete-by-GET pattern in goods management
  - no plaintext password fallback pattern in login flow

## Issue-by-Issue Status

| ID | Severity | Status | Evidence | Residual Risk / Notes |
|---|---|---|---|---|
| 1 | Critical | Fixed | `includes/db.php`, `sql/config.php`, `check_db.php`, `check_db_simple.php`, `setup_db.php` now use env credentials | Rotate any previously exposed credentials in real DB/server immediately |
| 2 | Critical | Fixed | `public/login_register.php` removed plaintext equality fallback | Legacy plaintext rows (if any) will fail login until reset/migration |
| 3 | Critical | Fixed | `dashboard/assign_worker.php` now enforces login/admin role + CSRF, no origin reflection | Endpoint now assumes same-origin session/cookie flow |
| 4 | Critical | Fixed | `admin/provision_temp_user.php` now requires login/admin role + CSRF; demo bypass removed | None |
| 5 | Critical | Fixed | `dashboard/goods_manage.php` delete moved to POST + CSRF form | None |
| 6 | High | Fixed | CSRF added in listed mutating pages (`admin/add_user.php`, `admin/project_management.php`, `admin/leave_management.php`, `admin/payment_gateway.php`, `dashboard/goods_invoice.php`, `dashboard/goods_manage.php`, `dashboard/review_requests.php`, `dashboard/profile.php`) | Additional non-listed POST forms should be reviewed in a separate hardening pass |
| 7 | High | Fixed | Runtime DDL removed from request paths (`dashboard/project_details.php`, `dashboard/goods_invoice.php`, `dashboard/goods_manage.php`, `dashboard/assign_worker.php`); migration created at `sql/migrations/20260325_runtime_ddl_migration.sql` | Migration must be executed during deployment |
| 8 | High | Fixed | Case mismatch fixed in `public/services.php`; representative files already using `Common/` | None |
| 9 | High | Fixed | `admin/dashboard.php` and `worker/dashboard.php` are now clean redirect shims only | None |
| 10 | High | Fixed | Debug dump removed from `dashboard/profile.php` | None |
| 11 | High | Fixed | Raw exception text no longer shown in `admin/add_user.php` and `dashboard/profile.php`; errors logged server-side | None |
| 12 | High | Fixed | jQuery links updated to 3.7.1 in `public/login.php`, `public/contact_us.php`, `client/upload_drawings.php`, `public/forgot.php`, `public/signup.php` | Consider adding SRI hashes if strict CSP policy is required |
| 13 | High | Fixed | Explicit guards added for representative admin pages: `admin/user_management.php`, `admin/project_management.php`, `admin/payment_gateway.php` | Consider extending explicit guards to every admin entrypoint for consistency |
| 14 | Medium | Fixed | Broken `/styles.css` fallback removed from shared headers (`Common/header.php`, `Common/header_alt.php`); direct broken refs fixed in goods pages | None |
| 15 | Medium | Fixed | Duplicate `<html>` root removed in `public/contact_us.php` | None |
| 16 | Medium | Fixed | `includes/dbs.php` no longer executes undefined `$update`, now CLI-only and safer | None |
| 17 | Medium | Partially Fixed | Session normalization helpers added (`includes/auth.php`: `current_user_id()`, `current_username()`), profile/dashboard updated to use normalized access | Some pages still use direct `$_SESSION` assumptions; complete standardization still recommended |
| 18 | Medium | Fixed | `public/contact_us.php` now persists submissions to `logs/contact_messages.log` with validation and CSRF | For production, replace log sink with DB table or queue + alerting |
| 19 | Medium | Fixed | `worker/project_details.php` now stores `submitted_by` from authenticated user id | None |
| 20 | Medium | Fixed | `dashboard/goods_invoice.php` no longer builds URL from raw host header; uses `BASE_URL`; `includes/config.php` host sanitization and `APP_BASE_URL` override added | Set `APP_BASE_URL` in production for canonical link generation |
| 21 | Low | Fixed | `admin/add_user.php` now uses PRG (`Location`) and flash message instead of `Refresh` | None |
| 22 | Low | Partially Fixed | Representative duplicate `session_start()` removed in `admin/user_management.php`, `admin/leave_management.php`, `dashboard/goods_invoice.php`, `dashboard/goods_manage.php`, `client/upload_drawings.php` | Several other files still call `session_start()` directly; cleanup pass still needed |
| 23 | Low | Fixed | `includes/util.php` now documents strict integer invariant for LIMIT interpolation | None |
| 24 | Low | Partially Fixed | Root scripts hardened to CLI-only and env-based credentials (`check_db.php`, `check_db_simple.php`, `setup_db.php`) | Scripts are still in project root; moving to non-public tooling directory remains recommended |

## Files Added
- `.github/agents/issues-remediator.agent.md`
- `sql/migrations/20260325_runtime_ddl_migration.sql`
- `ISSUES_CLOSURE_REPORT_2026-03-25.md`

## Deployment/Runbook Notes
1. Execute migration: `sql/migrations/20260325_runtime_ddl_migration.sql` before serving traffic.
2. Set env vars in deployment:
   - `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`
   - `APP_ENV`, `APP_BASE_URL`
3. Rotate any credentials that were previously committed.

## Remaining Recommended Follow-ups
1. Complete session bootstrapping cleanup: remove direct `session_start()` from remaining entry files and rely on `includes/init.php`.
2. Move root utility scripts to a non-web tooling folder (for example `tools/`) and keep CLI-only guard.
3. Complete full session-contract normalization to remove array/string ambiguity in all pages.
