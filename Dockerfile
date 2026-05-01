FROM composer:2 AS vendor

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader --no-scripts

FROM php:8.2-fpm-alpine

# Install runtime deps and required PHP extensions
RUN apk add --no-cache \
        nginx \
        freetype \
        libjpeg-turbo \
        libpng \
        libzip \
        oniguruma \
        libxml2 \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        freetype-dev \
        libjpeg-turbo-dev \
        libpng-dev \
        libzip-dev \
        oniguruma-dev \
        libxml2-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo pdo_mysql mysqli gd zip mbstring xml opcache \
    && apk del .build-deps \
    && mkdir -p /run/nginx

WORKDIR /var/www/html

# Copy application
COPY . .
COPY --from=vendor /app/vendor /var/www/html/vendor

# Configure nginx to serve the public document root and pass PHP to PHP-FPM.
RUN rm -f /etc/nginx/http.d/default.conf /etc/nginx/sites-enabled/default 2>/dev/null || true
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf

# Production PHP defaults
RUN printf '%s\n' \
    'opcache.enable=1' \
    'opcache.enable_cli=1' \
    'opcache.validate_timestamps=0' \
    'opcache.memory_consumption=128' \
    'opcache.max_accelerated_files=10000' \
    'memory_limit=1024M' \
    'upload_max_filesize=450M' \
    'post_max_size=460M' \
    'max_execution_time=600' \
    'max_input_time=600' \
    'display_errors=Off' \
    'display_startup_errors=Off' \
    'log_errors=On' \
    'expose_php=Off' \
    'session.cookie_httponly=1' \
    'session.cookie_samesite=Strict' \
    > /usr/local/etc/php/conf.d/zz-app.ini

# Create convenient symlinks inside the public document root so any references
# to top-level folders (Common, assets, etc.) resolve correctly when nginx
# serves /var/www/html/public as the site root.
RUN if [ -d /var/www/html/Common ] && [ ! -e /var/www/html/public/Common ]; then ln -s /var/www/html/Common /var/www/html/public/Common; fi \
    && if [ -d /var/www/html/assets ] && [ ! -e /var/www/html/public/assets ]; then ln -s /var/www/html/assets /var/www/html/public/assets; fi \
    && if [ -d /var/www/html/uploads ] && [ ! -e /var/www/html/public/uploads ]; then ln -s /var/www/html/uploads /var/www/html/public/uploads; fi

# Add an entrypoint helper that allows honoring $PORT if set by host
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/docker-entrypoint.sh \
    && chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["nginx-fpm"]
