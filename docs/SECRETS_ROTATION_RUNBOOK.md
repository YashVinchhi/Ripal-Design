# Secrets Cleanup and Rotation Runbook

Updated: 2026-04-16

## Scope

Use this runbook if credentials or tokens were ever committed to Git history.

## 1) Remove sensitive files from Git history

Prerequisites:

- Work from a clean clone.
- Coordinate downtime and force-push approval with all contributors.

Commands:

```bash
git fetch --all --prune
pip install git-filter-repo

git filter-repo --path .env --invert-paths

git for-each-ref --format='delete %(refname)' refs/original | git update-ref --stdin
git reflog expire --expire=now --all
git gc --prune=now --aggressive

git push --force --all
git push --force --tags
```

## 2) Rotate all exposed credentials

Rotate at provider side and immediately update your runtime secrets store:

- Database credentials
- PayPal client credentials
- SMTP username/password or API key
- Any custom API tokens

## 3) Invalidate user/session tokens

Run app-side invalidation for persistent auth tokens:

```sql
DELETE FROM auth_tokens;
```

## 4) Update deployment/runtime env

Set new values in platform secrets and `.env` (not committed), then redeploy.

## 5) Verify

- Login works with new credentials.
- Password reset mail works.
- Payment flow works.
- Database connection succeeds in production logs.

## 6) Team follow-up

- Ask all developers to re-clone or hard-reset to rewritten history.
- Close incident with timestamped evidence of rotation completion.
