# ---- Stage 1: Build frontend assets with Node ----
FROM node:22-alpine AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --ignore-scripts

COPY . .
RUN npm run build

# ---- Stage 2: PHP 8.3 + Apache ----
FROM php:8.3-apache

# System deps + PHP extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
        libpq-dev libpng-dev libzip-dev libicu-dev unzip git \
    && docker-php-ext-install pdo pdo_pgsql gd mbstring zip bcmath intl opcache \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# DocumentRoot -> Laravel public/
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" \
        /etc/apache2/sites-available/*.conf \
    && sed -ri -e "s!/var/www/!${APACHE_DOCUMENT_ROOT}!g" \
        /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Allow .htaccess overrides in public/
RUN sed -i '/<Directory ${APACHE_DOCUMENT_ROOT}>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' \
        /etc/apache2/apache2.conf

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy app (excluding what .dockerignore trims)
COPY . .

# Copy built Vite assets from frontend stage
COPY --from=frontend /app/public/build ./public/build

# Install PHP deps (no dev, optimized)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Storage permissions
RUN mkdir -p storage/app/private storage/app/public storage/framework/{sessions,views,cache/data} bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Entrypoint
COPY scripts/render-start.sh /usr/local/bin/render-start.sh
RUN chmod +x /usr/local/bin/render-start.sh

EXPOSE 10000

ENTRYPOINT ["render-start.sh"]
