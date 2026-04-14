# Production Security Hardening (Non-Destructive)

This project now supports staged hardening via environment flags so you can
secure production without breaking current workflows.

## 1) Immediate Actions (No App Logic Changes)

1. Ensure `.env` is never committed.
2. Rotate any previously exposed credentials.
3. Set `APP_ENV=production`.

## 2) Staged Hardening Flags

Add these to your production environment:

```env
APP_STRICT_SECURITY=true
SECURITY_REQUIRE_CSRF_FOR_API=true
SECURITY_ENFORCE_UPLOAD_ALLOWLIST=true
SECURITY_ALLOWED_UPLOAD_EXTS=pdf,dwg,dxf,jpg,jpeg,png,webp,gif,txt,csv,doc,docx,xls,xlsx,ppt,pptx,zip,rar
SECURITY_ENABLE_HSTS=true
```

Behavior:

- `APP_STRICT_SECURITY` enables stricter defaults.
- `SECURITY_REQUIRE_CSRF_FOR_API` enforces CSRF on API POST requests.
- `SECURITY_ENFORCE_UPLOAD_ALLOWLIST` constrains general uploads to allowlist.
- `SECURITY_ALLOWED_UPLOAD_EXTS` controls allowed extensions.
- `SECURITY_ENABLE_HSTS` sends HSTS header when HTTPS is detected.

## 3) Safe Rollout Order

1. Deploy with `APP_STRICT_SECURITY=false` first (default behavior with safer headers/session settings).
2. Enable `SECURITY_ENFORCE_UPLOAD_ALLOWLIST=true` and monitor upload failures.
3. Enable `SECURITY_REQUIRE_CSRF_FOR_API=true` after confirming frontend sends CSRF token.
4. Enable `SECURITY_ENABLE_HSTS=true` only after HTTPS is fully enforced.

## 4) Infrastructure Rule

Nginx now denies executing uploaded PHP under `/uploads` in production config.
Keep this rule enabled and avoid storing executable files in upload paths.

## 5) Validation Checklist

1. Login and signup still work.
2. Remember-me login works over HTTPS.
3. Project file uploads still work for allowed types.
4. API requests include CSRF token once strict API CSRF mode is enabled.
5. Security headers appear in browser/network responses.
