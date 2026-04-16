#!/bin/sh
set -e

# If $PORT is provided (Railway, Fly, etc.), adjust Apache to listen on it
if [ -n "${PORT}" ] && [ "${PORT}" != "80" ]; then
  sed -ri "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf
  sed -ri "s/:80/:${PORT}/g" /etc/apache2/sites-available/*.conf || true
fi

# Create runtime symlinks under the document root so files outside the
# public folder (Common, assets, uploads) are reachable when the project
# is bind-mounted into the container.
if [ -d /var/www/html/public ]; then
  if [ -d /var/www/html/Common ] && [ ! -e /var/www/html/public/Common ]; then
    ln -s /var/www/html/Common /var/www/html/public/Common || true
  fi
  if [ -d /var/www/html/assets ] && [ ! -e /var/www/html/public/assets ]; then
    ln -s /var/www/html/assets /var/www/html/public/assets || true
  fi
  if [ -d /var/www/html/uploads ] && [ ! -e /var/www/html/public/uploads ]; then
    ln -s /var/www/html/uploads /var/www/html/public/uploads || true
  fi
  # Expose app folders commonly referenced by absolute paths
  if [ -d /var/www/html/dashboard ] && [ ! -e /var/www/html/public/dashboard ]; then
    ln -s /var/www/html/dashboard /var/www/html/public/dashboard || true
  fi
  if [ -d /var/www/html/admin ] && [ ! -e /var/www/html/public/admin ]; then
    ln -s /var/www/html/admin /var/www/html/public/admin || true
  fi
  if [ -d /var/www/html/client ] && [ ! -e /var/www/html/public/client ]; then
    ln -s /var/www/html/client /var/www/html/public/client || true
  fi
  if [ -d /var/www/html/worker ] && [ ! -e /var/www/html/public/worker ]; then
    ln -s /var/www/html/worker /var/www/html/public/worker || true
  fi
  if [ -d /var/www/html/api ] && [ ! -e /var/www/html/public/api ]; then
    ln -s /var/www/html/api /var/www/html/public/api || true
  fi
fi
# Execute the container CMD (apache2-foreground)
exec "$@"
