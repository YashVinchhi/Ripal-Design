# Deployment and CI/CD Guide

This repository includes GitHub Actions workflows and Docker configs to build, publish, and deploy a production-ready container for the site.

What was added

- `Dockerfile` — builds a single Nginx + PHP-FPM image with Composer dependencies.
- `.dockerignore` — excludes local files from the image.
- `docker-compose.prod.yml` — production compose file referencing an image (`IMAGE_NAME`).
- `docker/nginx/default.conf` — Nginx config copied into the application image.
- `.github/workflows/ci.yml` — CI: installs deps, runs `php -l`, PHPStan, PHPUnit, and builds/pushes Docker image to GHCR.
- `.github/workflows/deploy.yml` — CD: SSH deploy that pulls the published image and runs `docker compose` on your server.

Prerequisites (server)

1. A Linux server with Docker and Docker Compose (or Docker compose plugin) installed.
2. A working directory on the server where `docker-compose.prod.yml` will live (example: `/var/www/thefinal`).
3. Environment variables on the server (or an `.env` in the same folder) for DB and mail credentials.
4. A writable private upload path for `UPLOAD_STORAGE_ROOT` (default: `storage/private_uploads` under project root).

Required GitHub secrets

- `GITHUB_TOKEN` (provided by Actions) — used for GHCR login in CI; ensure `packages: write` allowed.
- `GHCR_PAT` — a Personal Access Token with `read:packages` (used by the deploy step when logging from the server). Create and store in repo secrets.
- `DEPLOY_HOST` — server IP or hostname
- `DEPLOY_USER` — ssh user (that can run docker)
- `DEPLOY_KEY` — private SSH key for `DEPLOY_USER`
- `DEPLOY_PORT` — optional (default `22`)
- `DEPLOY_PATH` — path on remote where `docker-compose.prod.yml` resides

How it works

1. Push to `Prod` (or `main` / `ripal-design`) — `ci.yml` runs: composer install, php lint, PHPStan, PHPUnit, then builds Docker image and pushes to GHCR with `:latest` and `:${{ github.sha }}` tags.
2. Deploy: either push to `Prod` (triggers `deploy.yml`) or trigger `workflow_dispatch` manually. The deploy action SSHes into your server, logs into GHCR using `GHCR_PAT`, pulls the `:latest` image, and runs `docker compose` to update services.

Local testing

- Build image locally:

```bash
docker build -t thefinal:local .
```

- Run locally with compose (set `IMAGE_NAME` to local tag if you want to test pulling local image):

```bash
IMAGE_NAME=thefinal:local docker compose -f docker-compose.prod.yml up -d
```

Next steps for you

1. Create the required repository secrets (`GHCR_PAT`, `DEPLOY_*`).
2. Place `docker-compose.prod.yml` on your server under `DEPLOY_PATH` (or clone this repo on the server in that path).
3. Ensure the server environment (`.env`) contains DB and mail variables used by the app.
4. Push to `Prod` to run CI and then trigger the deploy.

Questions? I can:

- Push the `Prod` branch to the remote for you (if you want).
- Add a minimal `docker-compose.override.yml` or a systemd unit for automatic start on the server.
- Replace GHCR with Docker Hub if you prefer.
