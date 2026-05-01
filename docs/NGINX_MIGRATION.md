# Nginx Migration Guide

This guide is for moving this project from Apache/LAMP to Nginx on Ubuntu, then optionally running it with Docker.

Recommended path:

1. Test first on the local Ubuntu machine with native Nginx + PHP-FPM.
2. After the app works, repeat the same server block on the DigitalOcean droplet.
3. Use Docker only if you want reproducible deploys more than the lowest possible memory use.

For a 1 GB production droplet, the lightest setup is usually:

- Ubuntu
- Nginx
- PHP-FPM
- MySQL/MariaDB installed on the host or a managed database

Docker is convenient, but running both the app and MySQL in containers on 1 GB RAM can be tight.

## Local Ubuntu Nginx Test

Install packages:

```bash
sudo apt update
sudo apt install nginx php-fpm php-mysql php-gd php-zip php-mbstring php-xml php-curl unzip
```

Check your PHP-FPM version:

```bash
ls /run/php/
```

You should see a socket like:

```text
php8.2-fpm.sock
```

Place the project somewhere like:

```bash
sudo mkdir -p /var/www/thefinal
sudo rsync -a --delete ./ /var/www/thefinal/
sudo chown -R www-data:www-data /var/www/thefinal/uploads /var/www/thefinal/storage /var/www/thefinal/logs
sudo find /var/www/thefinal -type d -exec chmod 755 {} \;
sudo find /var/www/thefinal -type f -exec chmod 644 {} \;
```

Install Composer dependencies on the test server:

```bash
cd /var/www/thefinal
composer install --no-dev --prefer-dist --optimize-autoloader
```

Create public symlinks used by the app:

```bash
cd /var/www/thefinal
for dir in Common assets uploads dashboard admin client worker api; do
  [ -e "public/$dir" ] || sudo -u www-data ln -s "../$dir" "public/$dir"
done
```

Copy the Nginx server block:

```bash
sudo cp docs/nginx/thefinal.conf /etc/nginx/sites-available/thefinal
sudo ln -s /etc/nginx/sites-available/thefinal /etc/nginx/sites-enabled/thefinal
sudo rm -f /etc/nginx/sites-enabled/default
```

Edit the config if your PHP socket is not `php8.2-fpm.sock`:

```bash
sudo nano /etc/nginx/sites-available/thefinal
```

Test and reload:

```bash
sudo nginx -t
sudo systemctl reload nginx
sudo systemctl restart php8.2-fpm
```

Visit:

```text
http://your-local-ubuntu-ip/
```

## Production Droplet

After local testing passes:

1. Take a droplet snapshot.
2. Keep Apache installed until Nginx is confirmed working.
3. Stop Apache and enable Nginx:

```bash
sudo systemctl stop apache2
sudo systemctl disable apache2
sudo systemctl enable nginx
sudo systemctl reload nginx
```

If using a domain, update `server_name` in `docs/nginx/thefinal.conf`, then add SSL:

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d example.com -d www.example.com
```

## Docker Option

The included Docker image runs Nginx and PHP-FPM in one Alpine-based container.

Local test:

```bash
docker compose up -d --build
```

Open:

```text
http://localhost:8080
```

For a 1 GB droplet, prefer one of these Docker patterns:

- App in Docker, database on the host or managed MySQL.
- App + database in Docker only if swap is enabled and MySQL memory is capped.

Avoid running Apache and Nginx at the same time on port 80.

