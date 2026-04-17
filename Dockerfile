FROM composer:2 AS vendor

WORKDIR /app
COPY composer.json ./
RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader --no-scripts

FROM php:8.2-apache

# Install runtime deps and required PHP extensions
RUN apt-get update \
     && apt-get install -y --no-install-recommends \
         libzip-dev \
         libpng-dev \
         libjpeg62-turbo-dev \
         libfreetype6-dev \
         libonig-dev \
         libxml2-dev \
         zip \
     && docker-php-ext-configure gd --with-freetype --with-jpeg \
     && docker-php-ext-install -j"$(nproc)" pdo pdo_mysql mysqli gd zip mbstring xml opcache \
     && rm -rf /var/lib/apt/lists/*

# Enable useful Apache modules
RUN a2enmod rewrite headers expires

WORKDIR /var/www/html

# Copy application
COPY . .
COPY --from=vendor /app/vendor /var/www/html/vendor

# Ensure Apache serves the 'public' directory as document root
RUN sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/*.conf \
    && sed -ri 's!<Directory /var/www/html>!<Directory /var/www/html/public>!g' /etc/apache2/apache2.conf

# Production PHP defaults
RUN printf '%s\n' \
    'opcache.enable=1' \
    'opcache.enable_cli=1' \
    'opcache.validate_timestamps=0' \
    'opcache.memory_consumption=128' \
    'opcache.max_accelerated_files=10000' \
    'memory_limit=256M' \
    'upload_max_filesize=64M' \
    'post_max_size=64M' \
    > /usr/local/etc/php/conf.d/zz-app.ini

# Create convenient symlinks inside the public document root so any references
# to top-level folders (Common, assets, etc.) resolve correctly when Apache
# serves /var/www/html/public as the site root.
RUN if [ -d /var/www/html/Common ] && [ ! -e /var/www/html/public/Common ]; then ln -s /var/www/html/Common /var/www/html/public/Common; fi \
    && if [ -d /var/www/html/assets ] && [ ! -e /var/www/html/public/assets ]; then ln -s /var/www/html/assets /var/www/html/public/assets; fi \
    && if [ -d /var/www/html/uploads ] && [ ! -e /var/www/html/public/uploads ]; then ln -s /var/www/html/uploads /var/www/html/public/uploads; fi \
    && chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

# Add an entrypoint helper that allows honoring $PORT if set by host
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["apache2-foreground"]
