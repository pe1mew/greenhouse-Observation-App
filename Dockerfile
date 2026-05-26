# Greenhouse Observation App — PHP-FPM container
#
# Base: php:8.3-fpm (Debian-based, glibc — pdo_sqlite + gd build cleanly).
# Used by docker-compose.yml; nginx on host fastcgi_passes to the exposed port.
#
# Rebuild:
#   docker compose build
#
# Why PHP 8.3 even though TDS-STK-060 says PHP 7.4+ is the host minimum:
# the locked dependency endroid/qr-code ^4.0 requires PHP ^8.1, and Ubuntu
# 20.04 (focal) has no PHP 8.x in its apt repos. Running PHP 8.3 in a
# container keeps the host on PHP 7.4 for the other rfsee.net sites while
# the webapp gets the version it needs. Spec-update for TDS-STK-060 to be
# filed once Step 1 is verified.

FROM php:8.3-fpm

# Build dependencies for the PHP extensions we compile in.
# - libsqlite3-dev: pdo_sqlite headers + pkg-config (libsqlite3-0 in the base
#   is runtime-only; the build needs the -dev package)
# - libpng/jpeg/freetype-dev: gd
# - libzip-dev: zip
RUN apt-get update && apt-get install -y --no-install-recommends \
        libsqlite3-dev \
        libpng-dev libjpeg-dev libfreetype-dev libzip-dev \
        unzip git \
    && rm -rf /var/lib/apt/lists/*

# PHP extensions the app needs.
# mbstring, fileinfo, session, openssl are already in the base image.
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_sqlite gd zip opcache

# Composer 2 for the one-off `composer install` step.
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# App-level php.ini overrides.
#   - Photo upload limits per TDS-STO-060 (8 MB max upload).
#   - expose_php Off (no X-Powered-By header).
RUN { \
        echo 'upload_max_filesize = 8M'; \
        echo 'post_max_size       = 12M'; \
        echo 'memory_limit        = 128M'; \
        echo 'expose_php          = Off'; \
    } > /usr/local/etc/php/conf.d/zz-app.ini

WORKDIR /var/www/html

# FPM listens on TCP 9000 by default; docker-compose.yml binds it to host
# 127.0.0.1:9083 so nginx can fastcgi_pass to it without exposing publicly.
EXPOSE 9000
