FROM php:8.2-apache

# Install system deps and PHP extensions
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
       git \
       unzip \
       libzip-dev \
       libpng-dev \
       libjpeg62-turbo-dev \
       libfreetype6-dev \
       libonig-dev \
       libxml2-dev \
       zip \
       default-mysql-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql mysqli gd zip mbstring xml \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Enable useful Apache modules
RUN a2enmod rewrite headers expires

# Install Composer (from official composer image)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Install PHP dependencies (cache-friendly layer)
COPY composer.json composer.lock* ./
RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader || true

# Copy application
COPY . .

# Ensure Apache serves the 'public' directory as document root
RUN sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/*.conf \
    && sed -ri 's!<Directory /var/www/html>!<Directory /var/www/html/public>!g' /etc/apache2/apache2.conf

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
