FROM php:8.3-fpm-alpine

WORKDIR /app

RUN apk add --no-cache \
    nginx \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    postgresql-dev \
    oniguruma-dev \
    curl \
    git \
    unzip \
    gettext \
    bash \
    fcgi \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo_pgsql gd mbstring zip bcmath intl opcache \
    && pecl install redis \
    && docker-php-ext-enable redis

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

COPY . /app

RUN composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-progress \
    && mkdir -p storage/framework/{cache,sessions,views} bootstrap/cache /run/nginx /etc/nginx/templates \
    && cp docker/nginx/default.conf.template /etc/nginx/templates/default.conf.template \
    && chmod +x docker/start-container.sh \
    && chown -R www-data:www-data /app/storage /app/bootstrap/cache

ENV PHP_FPM_PM=dynamic \
    PHP_FPM_PM_MAX_CHILDREN=16 \
    PHP_FPM_PM_START_SERVERS=4 \
    PHP_FPM_PM_MIN_SPARE_SERVERS=2 \
    PHP_FPM_PM_MAX_SPARE_SERVERS=6

RUN { \
    echo '[www]'; \
    echo 'user = www-data'; \
    echo 'group = www-data'; \
    echo 'listen = 127.0.0.1:9000'; \
    echo 'pm = '${PHP_FPM_PM}; \
    echo 'pm.max_children = '${PHP_FPM_PM_MAX_CHILDREN}; \
    echo 'pm.start_servers = '${PHP_FPM_PM_START_SERVERS}; \
    echo 'pm.min_spare_servers = '${PHP_FPM_PM_MIN_SPARE_SERVERS}; \
    echo 'pm.max_spare_servers = '${PHP_FPM_PM_MAX_SPARE_SERVERS}; \
    echo 'catch_workers_output = yes'; \
    echo 'clear_env = no'; \
  } > /usr/local/etc/php-fpm.d/zz-jawla.conf

EXPOSE 8080

CMD ["/app/docker/start-container.sh"]
