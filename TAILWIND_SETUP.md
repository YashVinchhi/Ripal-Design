Tailwind setup (production build)

This project now includes a local Tailwind build pipeline. Follow these steps on your Ubuntu server to produce a production-ready compiled CSS file at `./assets/css/tailwind.css`.

1) Install Node.js (18+) and npm (example):

```bash
sudo apt update
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt-get install -y nodejs
```

2) From project root, install dev dependencies:

```bash
cd /path/to/Thefinal
npm install
```

3) Build production CSS:

```bash
npm run build:css
```

This will write a minified `assets/css/tailwind.css`. The project's header prefers a local compiled file in production; make sure `ENABLE_TAILWIND_CDN` (if used) is set to `0` in production environment.

4) Optional: watch during development:

```bash
npm run watch:css
```

5) Deployment notes:
- If you don't have a CI pipeline, you can build locally and commit `assets/css/tailwind.css` to make deployments simple.
- Ensure your webserver serves `assets/css/tailwind.css` and clear caches after updating.

6) Troubleshooting:
- If classes are missing, confirm `tailwind.config.js` `content` globs match where CSS classes are used (PHP templates and JS files are included by default).
- If npm install fails, confirm Node/npm versions and internet access on the server.

7) Optional Post-deploy: update server env and restart services:

```bash
export ENABLE_TAILWIND_CDN=0
# restart php/nginx/apache as appropriate, e.g.:
sudo systemctl restart php8.1-fpm
sudo systemctl restart nginx
```

That's it — the site will now use the compiled Tailwind CSS in production when present at `assets/css/tailwind.css`.
